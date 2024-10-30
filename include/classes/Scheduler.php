<?php

namespace Curbside_Pickup;

class Scheduler extends Base_Class
{
	function __construct()
	{

	}

	/*
	 * Returns an <select> compatible list of options for the available locations.
	 *
	 * @return array Array of Pickup Locations. Array keys contain the
	 * 				 Location IDs, and the values contain the Location names.
	 */
	function get_location_options()
	{
		$opts = [];
		$args = array(
			'numposts' => -1,
			'post_type' => 'pickup-location',
		);
		$locations = get_posts($args);
		if ( !empty($locations) ) {
			foreach($locations as $location) {
				$opts[$location->ID] = get_the_title($location->ID);
			}
		}
		return $opts;
	}

	/*
	 * Returns the value of the pickup_delay setting in minutes,
	 * taking into account the 'units' value (minutes, hours, or days)
	 *
	 * @return int Number of minutes to delay.
	 */
	function get_pickup_delay()
	{
		$pickup_delay_val = $this->get_option_value('pickup_delay', 60);
		$pickup_delay_period = $this->get_option_value_ne('pickup_delay_period', 'minute');

		// handle invalid values by using default
		if ( intval($pickup_delay_val) < 0 ) {
			$pickup_delay_val = 60;
		}

		switch($pickup_delay_period) {
			case 'day':
				$multiplier = 1440;
			break;

			case 'hour':
				$multiplier = 60;
			break;

			default:
			case 'minute':
				$multiplier = 1;
			break;
		}
		$pickup_delay = intval($pickup_delay_val * $multiplier);
		return apply_filters('curbside_pickup_lead_time', $pickup_delay, $pickup_delay_val, $pickup_delay_period);
	}

	/*
	 * Determines whether the given local time (e.g., "10:30") has passed
	 * based on the time according to WordPress. "Ties" are considered passed.
	 *
	 * @param string $hours_txt A local time string (no date) in 24 hour format,
	 * 							e.g., "10:30" or "14:30"
	 * @param int $minutes_to_buffer The number of minutes out from the current time
	 * 								 which should still be considered "in the past."
	 *								 Intended for use with the pickup_delay option.
	 *
	 * @return boolean true if the time has already passed, false if not.
	 */
	function local_time_has_passed(string $hours_txt, int $minutes_to_buffer = 0)
	{
		$hours_parts 		= explode(':', $hours_txt);
		$compare_hours 		= $hours_parts[0];
		$compare_minutes 	= $hours_parts[1];
		$current_hours 		= wp_date('G');
		$current_minutes 	= wp_date('i');

		// adjust local time forward before comparing if needed
		if ( ! empty( $minutes_to_buffer ) ) {
			$current_hours 		= wp_date( 'G', strtotime('+' . $minutes_to_buffer . ' minutes') );
			$current_minutes 	= wp_date( 'i', strtotime('+' . $minutes_to_buffer . ' minutes') );
		} else {
			$current_hours 		= wp_date('G');
			$current_minutes 	= wp_date('i');
		}

		// if the hour has passed, stop here - the time has already passed
		if (  $compare_hours < $current_hours ) {
			return true;
		}

		// if the hour has not even started, stop here - the time has not passed
		if (  $compare_hours > $current_hours ) {
			return false;
		}

		// if we are in the current hour, check the minutes
		// NOTE: in this function we say that if the minutes are equal the time has passed.
		// We don't bother to compare seconds. This is because in our business logic
		// we don't want someone scheduling for *right now* anyway
		if ( $compare_hours == $current_hours
			 && $compare_minutes <= $current_minutes ) {
			// the time has passed
			return true;
		} else {
			// the time is still in the future
			return false;
		}
	}

	/*
	 * Helper function to load the available times from the database
	 * for a given date and optionally location.
	 *
	 * @param string $my_date A strtotime compatible string representing the
	 * 						  date to consider
	 * @param int $location_id Optional, the Location which should be
	 * 						   considered (may have a different schedule).
	 *
	 * @return array Array of available intervals on the given day/location.
	 */
	private function load_hours_from_meta(string $my_date, int $location_id = 0)
	{
		$schedule_id = $this->get_schedule_id_from_location($location_id);

		// now we just need to load the options, generate the ranges, and combine them!
		$day_name = strtolower(date('l', strtotime($my_date)));
		$key = sprintf('hours_%s', $day_name);
		$key_open = sprintf('open_%s', $day_name);
		$key_close = sprintf('close_%s', $day_name);
		$hours = get_post_meta($schedule_id, $key, true);
		if ( empty($hours) ) {
			return [];
		}
		return $hours;
	}

	/*
	 * Returns an array of hours for a given date that can be used as
	 * <select> options. Takes throttling and local (server) time into account.
	 *
	 * @param string $my_date Optional. A strtotime compatible string representing the
	 * 						  date to consider. Defaults to current date.
	 * @param int $location_id Optional, the Location which should be
	 * 						   considered (may have a different schedule).
	 *
	 * @return array Array of <select> compatible options of available times.
	 */
	function get_hours_for_date($my_date = '', $location_id = 0)
	{
		if ( empty($my_date) ) {
			$my_date = wp_date('Y-m-d');
		}

		$day_name 	= strtolower(date('l', strtotime($my_date)));
		$key 		= sprintf('hours_%s', $day_name);
		$key_open 	= sprintf('open_%s', $day_name);
		$key_close 	= sprintf('close_%s', $day_name);
		$hours 		= $this->load_hours_from_meta($my_date, $location_id);

		// generate the options for all of the intervals for this day
		// and merged them into one big array
		$merged = [];
		foreach($hours as $index => $hours) {
			$open_time = strtotime($hours[$key_open]);
			$close_time = strtotime($hours[$key_close]);
			$opts = $this->get_time_options_from_range($open_time, $close_time);
			$merged = array_merge($merged, $opts);
		}

		// remove duplicate entries and re-sort
		// this allows us to ignore whether user-entered ranges are in order or overlap
		$merged = array_unique($merged);
		ksort($merged);

		// if the date is today, or within the lead time period,
		// remove any hours that have already passed
		$pickup_delay = $this->get_pickup_delay();
		if ( $this->is_today($my_date) || $this->date_within_lead_time($my_date) ) {
			foreach($merged as $time_24hr => $time_display) {
				if ( $this->local_time_has_passed( $time_24hr, $pickup_delay ) ) {
					unset($merged[$time_24hr]);
				}
			}
		}

		// if throttling is enabled, remove any time slots that are full
		$merged = $this->maybe_remove_throttled_times($merged, $my_date, $location_id);

		// return the reduced set of hours, or an empty array if no hours remain
		if ( !empty($merged) ) {
			return $merged;
		} else {
			return [];
		}
	}

	/*
	 * Determines if the provided date falls within the period
	 * affected by the lead time.
	 *
	 * @param string $my_date the date to check; a strtotime compatible string.
	 *
	 * @return bool true if the provided date is affected by lead time, false if not.
	 */
	function date_within_lead_time($my_date)
	{
		// calculate the max date (local time) affected by the lead time
		$pickup_delay = $this->get_pickup_delay();
		$cur_timestamp = current_time('timestamp');
		$max_timestamp = strtotime( '+' . intval($pickup_delay) . ' minutes', $cur_timestamp );

		// if the provided date is less than or equal to that date, return true
		$my_date_timestamp = strtotime($my_date);
		if ( $this->same_date( $my_date_timestamp, $max_timestamp ) ||
			 $this->date_is_before( $my_date_timestamp, $max_timestamp )
			) {
			return true;
		}
		// else return false
		return false;
	}

	function get_time_options($my_date = '', $location_id = 0)
	{
		if ( empty($my_date) ) {
			$my_date = wp_date('Y-m-d');
		}
		$hours = $this->get_hours_for_date($my_date, $location_id);

		if ( !empty($hours) ) {
			return $hours;
		} else {
			return [
				'none' => __('Finding available times...', 'curbside-pickup')
			];
		}
	}

	/*
	 * Given a time and optionally a location, checks whether there are any
	 * remaining slots available at that time.
	 *
	 * @param string $timestamp A UNIX timestamp OR a strtotime compatible string
	 * 					   		representing the date + time to consider.
	 * @param int $location_id Optional, the Location which should be
	 * 						   considered (may have a different schedule).
	 *
	 * @return boolean true if the timeslot is full, false if there are still
	 * 				   available times remaining.
	 */
	function timeslot_is_full($timestamp, $location_id = 0)
	{
		// convert string to timestamp if needed
		if ( ! $this->is_timestamp($timestamp) ) {
			$timestamp = strtotime($timestamp);
		}

		$threshold = $this->get_max_orders_per_timeslot($location_id);

		// find count of pending orders scheduled at the given time
		$orders = $this->get_orders_by_time($timestamp, $location_id);
		
		// look up the relevant options, and compare them to the count
		return (count($orders) >= $threshold);
	}

	function get_max_orders_per_timeslot($location_id = 0)
	{
		$cache_key = 'max_orders_per_timeslot_' . $location_id;
		$cached_val = $this->cache_get($cache_key);
		if ( ! empty($cached_val) ) {
			return $cached_val;			
		}
		
		if ( !empty($location_id) ) {
			// use location's value
			$threshold = get_post_meta($location_id, 'max_orders_per_hour', true);
		}
		else {
			// use global setting
			$threshold = rwmb_meta( 'orders_per_period', ['object_type' => 'setting'], 'curbside_pickup' );			
		}
		
		// default to one order per period if not set
		if ( empty($threshold) ) {
			$threshold = 1;
		}
		
		// cache value for the rest of the request
		$this->cache_set($cache_key, $threshold);
		
		return $threshold;
	}

	/*
	 * Returns all pickups in the database for a given time, optionally
	 * filtered by location.
	 *
	 * NOTE: Checking for orders at one time is fast, but checking every 
	 * 		 timeslot on a 30 day range takes a long time (3-5s), but is 
	 * 		 required for throttling support). 
	 *
	 *		 So instead of querying for pickups at the specified time, this 
	 * 		 function grabs all the pickups for that date, caches them, and then
	 * 		 plucks out the pickups which correspond to the specified timeslot.
	 *
	 * 		 This makes subsequent lookups on the same day much faster.
	 *
	 *		 Right now, we're only maintaining this cache through the current
	 *		 request. If we implement cache busting every time any order is 
	 * 		 placed/updated, we could cache indefinitely.
	 *
	 * @param string $timestamp A UNIX timestamp representing the
	 * 					  		date + time to consider.
	 * @param int $location_id Optional, the Location which should be
	 * 						   considered (may have a different schedule).
	 *
	 * @return array Array of WP Post objects representing the pickups, or an
	 * 				 empty array if none are found.
	 */
	function get_orders_by_time($timestamp, $location_id = 0)
	{		
		// setup some initial values for caching		
		$date_format = 'Y-m-d H:i'; // MySQL format (but WITHOUT seconds)
		$start_day = date( $date_format, strtotime('midnight', $timestamp) );
		$end_day = date( $date_format, strtotime('23:59:59', $timestamp) );
		$cache_key = 'order_cache_' . strtotime($start_day) . '_' . strtotime($end_day) . '_' . $location_id;
		
		// check for a cache of orders for today
		$order_cache = $this->cache_get($cache_key);
		
		if ( false === $order_cache ) {
			$order_cache = [];
			$query = array(
				'post_type'   => 'scheduled-pickup',
				'tax_query' => array(
					array (
						'taxonomy' => 'delivery-status',
						'field' => 'slug',
						'terms' => 'pending',
					)
				),
				'posts_per_page' => -1,
				'nopaging' => true,
			);

			// add time filter
			$query['meta_query'] = array(
				array(
					'key'   => 'scheduled_delivery_time',
					'value' => [ $start_day, $end_day ],
					'compare' => 'BETWEEN',
					'type' => 'DATE'
				)
			);

			// add a location filter if specified
			if ( !empty($location_id)  ) {
				$query['meta_query']['relation'] = 'AND';
				$query['meta_query'][] = array(
					'key'   => 'delivery_location',
					'value' => $location_id,
				);
			}

			$result = new \WP_Query($query);
			$to_cache = [];
			if ( $result->have_posts() ) {
				// add times
				foreach($result->posts as $index => $post) {
					$sdt = get_post_meta($post->ID, 'scheduled_delivery_time', true);
					$key = strtotime($sdt);
					if ( !isset( $order_cache[$key] ) ) {
						$order_cache[$key] = [];
					}				
					$order_cache[$key][] = $post;
				}
			}

			// cache (even if empty)
			$this->cache_set($cache_key, $order_cache);

		} // end if empty($order_cache)
		
		// pluck
		if ( !empty( $order_cache[$timestamp] ) ) {
			return $order_cache[$timestamp];
		}
		else {
			return [];
		}		
	}

	/*
	 * Returns the Pickup Schedule ID used by a given Location (or the schedule
	 * used for all Locations if no Location ID is specified.
	 *
	 * @param int $location_id Optional, the Location which should be
	 * 						   considered (may have a different schedule).
	 *
	 * @return int ID of the Pickup Schedule to use.
	 */
	function get_schedule_id_from_location($location_id = 0)
	{
		$schedule_id = !empty($location_id)
					   ? get_post_meta($location_id, 'pickup_schedule_id', true)
					   : 0;

		if ( empty($schedule_id) ) {
			// use first entry if there was no location or the location doesn't have a schedule chosen
			$schedule_id = $this->get_default_schedule_id();
		}
		return $schedule_id;
	}

	/*
	 * Returns the default Pickup Schedule ID (can be set by the user)
	 *
	 * @return int ID of the default Pickup Schedule.
	 */
	function get_default_schedule_id()
	{
		// remove the meta flag from the current default schedule
		$args = array(
			'post_type'     => 'pickup-schedule',
			'post_status'   => 'publish',
			'meta_query' => array(
				array(
					'key' => 'default_schedule',
					'value' => '1'
				)
			)
		);

		$matches = get_posts($args);
		if ( !empty($matches) ) {
			foreach($matches as $match) {
				return $match->ID;
			}
		}

		// no matches, so use oldest schedule as the default
		return $this->get_oldest_post_id('pickup-schedule');
	}

	/*
	 * Returns the ID of the oldesr Pickup Schedule in the database
	 *
	 * @return int ID of the oldest Pickup Schedule in the database.
	 */
	function get_oldest_schedule_id()
	{
		return $this->get_oldest_post_id('pickup-schedule');
	}

	/*
	 * Returns a list of <select> compatible options for a given time range
	 * (inclusive), spaced out by the pickup_time_increment setting.
	 *
	 * @param int $open_time A UNIX timestamp representing the start of the range
	 * @param int $open_time A UNIX timestamp representing the end of the range
	 *
	 * @return array Array of available times which can be used for a <select>
	 */
	function get_time_options_from_range($open_time, $close_time)
	{
		if ( $open_time == $close_time ) {
			return [];
		}

		$opts = [];

		// start at open time, rounding up to the first 15 minute interval (15 * 60 seconds)
		$delivery_time = ceil($open_time / (15*60)) * (15*60);

		$time_incr = rwmb_meta( 'pickup_time_increment', ['object_type' => 'setting'], 'curbside_pickup' );
		if ( empty($time_incr) ) {
			$time_incr = 15;
		}
		$str_time_incr = sprintf('+%d minutes', $time_incr);

		// add options for every 15 minute interval until close
		while($delivery_time <= $close_time && $delivery_time >= $open_time) {
			$key = date('H:i', $delivery_time);
			$opts[$key] = $this->format_time_label($delivery_time);
			$delivery_time = strtotime($str_time_incr, $delivery_time);
		}
		return $opts;
	}

	/*
	 * Returns the label for an <option> in a time selector
	 *
	 * @param int $delivery_time A UNIX timestamp representing the pick-up time
	 * @param string $time_format Optional. date() compatible string to use to
	 * 							  format timestamps. Default: 'g:i a'.
	 *
	 * @return string Label for the time selector's <option>
	 *
	 */
	function format_time_label($delivery_time, $time_format = 'g:i a')
	{
		$time_incr = rwmb_meta( 'pickup_time_increment', ['object_type' => 'setting'], 'curbside_pickup' );
		if ( empty($time_incr) ) {
			$time_incr = 15;
		}
		$str_time_incr = sprintf('+%d minutes', $time_incr);
		$start_range = date('H:i', $delivery_time);

		$display_choice = $this->get_option_value('display_pickup_times_as_ranges', 'start_only');
		if ( 'start_only' == $display_choice ) {
			return date($time_format, $delivery_time);
		}

		$end_range = strtotime($str_time_incr, $delivery_time);
		if ( 'range_exclusive' == $display_choice ) {
			$end_range = strtotime('-1 minute', $end_range);
		}
		return date($time_format, $delivery_time) . ' - ' . date($time_format, $end_range);
	}

	function get_date_options($location_id = 0, $num_days_from_today = 0)
	{
		if ( empty($num_days_from_today) ) {
			$num_days_from_today = $this->get_option_value('allowed_days_in_future', 30);
		}
		$date_options = [];
		$start_date = current_time('timestamp');

		// add the lead time (a.k.a. pickup delay) to start date
		// NOTE: lead time is returned in minutes, so convert it to seconds
		// 		 before adding it to the $start_date timestamp
		$lead_time_min = $this->get_pickup_delay();
		$start_date += ($lead_time_min * 60);

		// add first option, today (if there are any more times available today)
		$range_offset = 0;
		if ( $this->is_today($start_date) && $this->has_times_on_date( $start_date, $location_id) ) {
			$key = wp_date('Y-m-d'); // use wp_date to use local timezone
			$date_options[$key] = __('Today', 'curbside-pickup');
			$range_offset = 1;
		}

		// add options for the next N days after today (default 30)
		if ( $num_days_from_today > 0 ) {
			foreach( range($range_offset, $num_days_from_today + $range_offset) as $days_offset) {

				$calc_date = strtotime('+ ' . $days_offset . ' days', $start_date);
				if ( !$this->has_times_on_date($calc_date, $location_id) ) {
					continue;
				}

				$key = date('Y-m-d', $calc_date);
				$val = date('F j, Y (l)', $calc_date);
				$date_options[$key] = $val;
			}
		}
		return $date_options;
	}

	/*
	 * Checks whether any available times remain on the specified date/location.
	 *
	 * @param string $check_date_ts A UNIX timestamp representing the date to consider
	 * @param int $location_id Optional, the Location which should be
	 * 						   considered (may have a different schedule).
	 *
	 * @return boolean true if there are available times remaining, false if not
	 */
	function has_times_on_date($check_date_ts, $location_id = 0)
	{
		if ( empty($check_date_ts) ) {
			// the current time, as a UNIX timestamp in local time
			$check_date_ts = current_time('timestamp');
		}
		$check_date = date('Y-m-d H:i:s', $check_date_ts);


		// check if its a holiday
		if  ( $this->is_a_holiday($check_date_ts, $location_id) ) {
			return false;
		}

		// load the hours for this day of the week, and see if it has any open hours
		$day_name 	= strtolower(date('l', strtotime($check_date)));
		$key 		= sprintf('hours_%s', $day_name);
		$key_open 	= sprintf('open_%s', $day_name);
		$key_close 	= sprintf('close_%s', $day_name);
		$hours 		= $this->load_hours_from_meta($check_date, $location_id);

		// generate the options for all of the intervals for this day
		// and merged them into one big array
		$merged = [];
		foreach($hours as $index => $hours) {
			$open_time = strtotime($hours[$key_open]);
			$close_time = strtotime($hours[$key_close]);
			$opts = $this->get_time_options_from_range($open_time, $close_time);
			$merged = array_merge($merged, $opts);
		}

		// if throttling is enabled, remove any time slots that are full
		$merged = $this->maybe_remove_throttled_times($merged, $check_date, $location_id);

		// if the check date is today, remove times that have already passed
		if ( $this->is_today($check_date_ts) || $this->date_within_lead_time($check_date) ) {
			$pickup_delay = $this->get_pickup_delay();
			foreach($merged as $time_24hr => $time_display) {
				if ( $this->local_time_has_passed( $time_24hr, $pickup_delay ) ) {
					unset($merged[$time_24hr]);
				}
			}
		}

		return !empty($merged);
	}

	/*
	 * Checks whether the given date is a holiday,
	 * as specified in the Pickup Schedule.
	 *
	 * @param string $check_date_ts A UNIX timestamp representing
	 * 							 	the date to check
	 * @param int $location_id Optional, the Location which should be
	 * 						   considered (may have a different schedule).
	 *
	 * @return boolean true if the given date is a holiday, false if not
	 */
	function is_a_holiday($check_date_ts, $location_id = 0)
	{
		$schedule_id = !empty($location_id)
					   ? $this->get_schedule_id_from_location($location_id)
					   : $this->get_default_schedule_id();

		// no schedule, so can't be a holiday
		if ( empty($schedule_id) ) {
			return false;
		}

		// load the list of holidays and look for a match
		$holidays = $this->load_holidays_from_schedule($schedule_id);
		if ( empty($holidays) ) {
			return false;
		}

		foreach($holidays as $holiday) {
			if ( date('Y-m-d', $check_date_ts) == $holiday ) {
				// found match, so today is a holiday
				return true;
			}
		}

		// no matches, not a holiday
		return false;
	}

	/*
	 * Retrieves the list of holidays for the given Pickup Schedule
	 *
	 * @param int $schedule_id The Pickup Schedule ID
	 *
	 * @return array Array of holiday dates, or an empty array
	 */
	function load_holidays_from_schedule($schedule_id)
	{
		$holidays = get_post_meta($schedule_id, 'holidays', true);
		return ( empty($holidays) || empty($holidays['holiday_date']) )
			   ? []
			   : $holidays['holiday_date'];
	}

	/*
	 * If throttling is enabled, remove any slots that are full
	 * from the given list of times.
	 *
	 * @param array $times The list of times to check in 24-HR local time
	 * 					  format (i.e., no dates included).
	 * @param string $check_date A UNIX compatible date string to combine
	 * 							 with the list of times.
	 * @param int $location_id Optional, the Location which should be
	 * 						   considered (may have a different schedule).
	 *
	 * @return array Original array with times that are full removed.
	 */
	function maybe_remove_throttled_times($times, $check_date, $location_id = 0)
	{
		// if the list to filter is empty, abort
		if ( empty($times) ) {
			return [];
		}

		$throttling_enabled = $this->get_option_value('throttling_enabled');

		// throttling is not enabled, so return times unchanged
		if ( empty($throttling_enabled) ) {
			return $times;
		}

		// throttling is enabled, so remove any time slots that are full
		foreach ($times as $time_of_day => $label) {
			// use $check_date to contextualize the time of day
			// e.g., $time_of_day == '16:30' and $check_date == '2020-12-25'
			$timestamp = strtotime($time_of_day, strtotime($check_date));
			if ( $this->timeslot_is_full($timestamp, $location_id) ) {
				unset($times[$time_of_day]);
			}
		}

		return $times;
	}


	/*
	 * Finds the next open pickup time
	 *
	 * @param string $min_time A strtotime compatible string representing the
	 * 						   time from which to start searching
	 * @param int $location_id Optional, the Location which should be considered
	 *
	 * @return string A MySQL datetime representing the next available time, or
	 * 				  an empty string if no times are available within
	 * 				  allowed_days_in_future.
	 */
	function get_next_available_time($min_time = '', $location_id = 0)
	{
		$max_days = $this->get_option_value('allowed_days_in_future', 30);
		$date_options = [];
		$day_counter = 0;

		// if min_time was not provided, use the current time + the pickup delay
		if ( empty($min_time) ) {
			$pickup_delay = $this->get_pickup_delay();
			$min_time = strtotime('+' . intval($pickup_delay) . ' minutes', current_time('timestamp'));
		} else if ( !$this->is_timestamp($min_time) ) {
			$min_time = strtotime($min_time);
		}
		
		do {
			// calculate the date for this iteration of the loop
			$calc_date_ts = $day_counter > 0
							? strtotime('+ ' . $day_counter . ' days', $min_time)
							: $min_time;
			
			// check for available times on this date
			$calc_date = date('Y-m-d H:i:s', $calc_date_ts);		
			$times = $this->get_hours_for_date($calc_date, $location_id);
			
			// if no times are available on this date, move on to the next one
			if  ( empty($times) ) {
				$day_counter++;
				continue;
			}

			// find the first time that hasn't yet passed and return it
			foreach($times as $time => $label) {
				$cmp_date = date('Y-m-d', $calc_date_ts);
				$cmp_time = $cmp_date . ' ' . $time;
				$cmp_time_ts = strtotime($cmp_time);

				// check if this time has already passed
				if ( $cmp_time_ts < $min_time ) {
					continue;
				}

				// found an available time that hasn't passed yet! return it
				return $cmp_date . ' ' . $time;
			}
			
			// no times were available on this date that have not already passed,
			// so move on to the next date to check
			$day_counter++;
		} while($day_counter < $max_days);

		// checked all the way through $max_days, and nothing is available
		// so return an empty string
		return '';
	}

	/*
	 * Determines whether the given string is a UNIX timestamp
	 *
	 * @param string $timestamp The string to check
	 *
	 * @return boolean true if the string is a valid timestamp,
	 *				   false if not.
	 */
	function is_timestamp($timestamp)
	{
		return ctype_digit($timestamp)
			&& ($timestamp <= PHP_INT_MAX)
			&& ($timestamp >= ~PHP_INT_MAX);
	}

	/*
	 * Determines whether the given timestamp or time string falls on today's
	 * date.
	 *
	 * @param string $min_time A UNIX timestamp or strtotime compatible string
	 * 						   representing the time to check.
	 *
	 * @return boolean true if the given time falls on today's date,
	 *				   false if not.
	 */
	function is_today($timestamp)
	{
		if ( !$this->is_timestamp($timestamp) ) {
			$timestamp = strtotime($timestamp);
		}
		// note: use WP's wp_date() to account for the website's timezone setting,
		// but use date() with the timestamp. feeding a timestamp to wp_date will cause
		// it to apply the timezone twice, causing a wrong result
		return ( wp_date('Ymd') == date('Ymd', $timestamp) );
	}

	/*
	 * Determines whether the given timestamp or time string falls on today's
	 * date.
	 *
	 * @param string $ts1 The first UNIX timestamp to compare
	 * @param string $ts2 The second UNIX timestamp to compare
	 *
	 * @return boolean true if the two timestamps fall on the same date,
	 *				   false if not.
	 */
	function same_date($ts1, $ts2)
	{
		return ( date('Ymd', $ts1)  == date('Ymd', $ts2) );
	}

	/*
	 * Determines whether the first timestamp falls on a day *before*
	 * the second timestamp.
	 *
	 * @param string $ts1 The first UNIX timestamp to compare
	 * @param string $ts2 The second UNIX timestamp to compare
	 *
	 * @return boolean true if the two timestamps fall on the same date,
	 *				   false if not.
	 */
	function date_is_before($ts1, $ts2)
	{
		$year1 = date('Y', $ts1);
		$year2 = date('Y', $ts2);

		if ($year1 < $year2) {
			return true;
		}

		$month1 = date('m', $ts1);
		$month2 = date('m', $ts2);

		if ($month1 < $month2) {
			return true;
		}


		$day1 = date('d', $ts1);
		$day2 = date('d', $ts2);

		if ($day1 < $day2) {
			return true;
		}

		return false;
	}
}

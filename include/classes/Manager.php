<?php

namespace Curbside_Pickup;

class Manager extends Base_Class
{
	function __construct()
	{
	}

	function get_pickup_status($pickup_id)
	{
		$terms = get_the_terms($pickup_id, 'delivery-status');
		return !empty($terms)
			   ? $terms[0]->slug
			   : '';
	}

	function set_pickup_status($pickup_id, $new_status)
	{
		// set the new status and delete any other statuses
		wp_set_post_terms($pickup_id, $new_status, 'delivery-status', false);
	}

	/*
	 * Retreives all pickups with a given status, and optionally within a time window.
	 *
	 * @depricated @TODO use get_pickups_by_status instead
	 *
	 * @param string $status The status of orders to be returned.
	 * @param string $start_time Optional, MySQL timestamp of the start time filter
	 * @param string $end_time Optional, MySQL timestamp of the end time filter
	 * @param int $location_id Optional, restrict results to a specific location
	 * @param int $max_rows Optional, limit the number of pickups returned.
	 *
	 * @return array Array of WP Post objects (from WP Query results) if any
	 * 				 matching orders are found, empty array if not.
	 */
	function get_orders_by_status($status, $start_time = '', $end_time = '', $location_id = false, $max_rows = -1)
	{
		return $this->get_pickups_by_status($status, $start_time, $end_time, $location_id, $max_rows);
	}
	
	/*
	 * Retreives all pickups with a given status, and optionally within a time window.
	 *
	 * @param string $status The status of orders to be returned.
	 * @param string $start_time Optional, MySQL timestamp of the start time filter
	 * @param string $end_time Optional, MySQL timestamp of the end time filter
	 * @param int $location_id Optional, restrict results to a specific location
	 * @param int $max_rows Optional, limit the number of pickups returned.
	 *
	 * @return array Array of WP Post objects (from WP Query results) if any
	 * 				 matching orders are found, empty array if not.
	 */
	function get_pickups_by_status($status, $start_time = '', $end_time = '', $location_id = false, $max_rows = -1)
	{
		$query = array(
			'post_type'   => 'scheduled-pickup',
			'tax_query' => array(
				array (
					'taxonomy' => 'delivery-status',
					'field' => 'slug',
					'terms' => $status,
				)
			),
			'posts_per_page' => -1,
			'nopaging' => true,
		);
		
		if ( $max_rows > 0 ) {
			$query['posts_per_page'] = $max_rows;
			$query['nopaging'] = false;
		}

		// add time filter if specified
		if ( !empty($start_time) && !empty($end_time) ) {
			$date_format = 'Y-m-d H:i:s'; // MySQL format
			$query['meta_query'] = array(
				array(
					'key'   => 'scheduled_delivery_time',
					'value' => array(
						$start_time,
						$end_time,
						//get_date_from_gmt($start_time),
						//get_date_from_gmt($end_time),
						//date($date_format, $start_time),
						//date($date_format, $end_time),
					),
					//'type' => 'numeric',
					'compare' => 'BETWEEN'
				)
			);
		}

		// add a location filter if specified
		if ( !empty($location_id)  ) {
			// if we already have a meta query (time filter),
			// specify that BOTH keys must match
			if ( !empty($query['meta_query']) ) {
				$query['meta_query']['relation'] = 'AND';
			} else {
				$query['meta_query'] = array();
			}

			// add the fitler for delivery location
			$query['meta_query'][] = array(
				'key'   => 'delivery_location',
				'value' => $location_id,
			);
		}

		// order by scheduled time (first scheduled time is first in line)
		$query['orderby'] = 'meta_value_num';
		$query['order'] = 'ASC';
		$query = apply_filters('curbside_pickup_get_orders_by_status', $query, $status, $start_time, $end_time, $location_id);

		$result = new \WP_Query($query);
		if ( $result->have_posts() ) {
			usort( $result->posts, array($this, 'sort_by_delivery_time') );
			return $result->posts;
		} else {
			return [];
		}
	}

	/*
	 * Retreives the count of pickups with a given status, and optionally within a time window.
	 *
	 * @param string $status The status of orders to be returned.
	 * @param string $start_time Optional, MySQL timestamp of the start time filter
	 * @param string $end_time Optional, MySQL timestamp of the end time filter
	 * @param int $location_id Optional, restrict results to a specific location
	 *
	 * @return int number matching orders are found
	 */
	function get_pickup_count_by_status($status, $start_time = '', $end_time = '', $location_id = false)
	{
		$rows = $this->get_orders_by_status($status, $start_time, $end_time, $location_id);
		return count($rows);
	}

	/*
	 * Retreives all orders with no status specified
	 *
	 * @return array Array of WP Post objects (from WP Query results) if any
	 * 				 matching orders are found, empty array if not.
	 */
	function get_orders_without_status()
	{
		$all_tax_terms = get_terms( 'delivery-status', array(
            'fields' => 'ids'
        ) );
		
		$tax_query = array(
			array(
				'taxonomy'         => 'delivery-status',
				'terms'            => $all_tax_terms,
				'field'            => 'term_id',
				'operator'         => 'NOT IN',
			),
		);
		
		$query = array(
			'post_type'   => 'scheduled-pickup',
			'tax_query' => $tax_query,
			'posts_per_page' => -1,
			'nopaging' => true,
		);

		$result = new \WP_Query($query);
		if ( $result->have_posts() ) {
			return $result->posts;
		} else {
			return [];
		}
	}

	/*
	 * Retreives all orders with a given status, and optionally within a time window.
	 *
	 * @param string $target_date Optional. Any strtotime compatible string. Restricts orders to this date. Defaults to current day.
	 * @param string $status Optional, The status of orders to be returned.
	 * @param int $location_id Optional, restrict results to a specific location
	 *
	 * @return array Array of WP Post objects (from WP Query results) if any
	 * 				 matching orders are found, empty array if not.
	 */
	function get_orders_by_date($target_date = '', $status = '', $location_id = 0)
	{
		if ( empty($target_date) ) {
			$target_date = wp_date('Y-m-d'); // TODO: load from $_GET/$_POST
		}

		$query = array(
			'post_type'   => 'scheduled-pickup',
			'posts_per_page' => -1,
			'nopaging' => true,
		);

		if ( !empty($status) ) {
			$query['tax_query'] = array(
				array (
					'taxonomy' => 'delivery-status',
					'field' => 'slug',
					'terms' => $status,
				)
			);			
		}
		
		$date_format = 'Y-m-d H:i';
		$start_time = date( $date_format, strtotime( 'midnight ', strtotime($target_date) ) );
		$end_time 	= date( $date_format, strtotime( '11:59:59 PM ', strtotime($target_date) ) );

		// add time filter
		$date_format = 'Y-m-d H:i:s'; // MySQL format
		$query['meta_query'] = array(
			array(
				'key'   => 'scheduled_delivery_time',
				'value' => array(
					$start_time,
					$end_time,
				),
				'compare' => 'BETWEEN'
			)
		);

		// add a location filter if specified
		if ( !empty($location_id)  ) {
			
			// both meta fields should match (time and location)
			$query['meta_query']['relation'] = 'AND';

			// add the fitler for delivery location
			$query['meta_query'][] = array(
				'key'   => 'delivery_location',
				'value' => $location_id,
			);
		}

		// order by scheduled time (first scheduled time is first in line)
		$query['orderby'] = 'meta_value_num';
		$query['order'] = 'ASC';
		
		$result = new \WP_Query($query);
		if ( $result->have_posts() ) {
			usort( $result->posts, array($this, 'sort_by_delivery_time') );
			return $result->posts;
		} else {
			return [];
		}
	}
	
	function sort_by_delivery_time($a, $b)
	{
		$a_time = get_post_meta($a->ID, 'scheduled_delivery_time', true);
		$a_time = !empty($a_time) ? strtotime($a_time) : 0;

		$b_time = get_post_meta($b->ID, 'scheduled_delivery_time', true);
		$b_time = !empty($b_time) ? strtotime($b_time) : 0;

		if ($a_time == $b_time) {
			// sort by ID if times are the same
			return ($a->ID < $b->ID) ? -1 : 1;
		}

		return ($a_time < $b_time) ? -1 : 1;
	}

	function check_in_customer($pickup, $customer_notes = '', $vehicle_description = '', $space_number = '')
	{
		// change the order status to 'waiting'
		$old_status = $this->get_pickup_status($pickup->ID);
		$this->set_pickup_status($pickup->ID, 'waiting');
		update_post_meta($pickup->ID, 'arrived', 1);
		update_post_meta($pickup->ID, 'arrival_time', current_time('mysql', false) );
		$customer_name = get_post_meta($pickup->ID, 'customer_name', true);
		$location_id = get_post_meta($pickup->ID, 'delivery_location', true);

		// update customer_notes to new value (only if new value is not empty)
		if ( !empty($customer_notes) ) {
			update_post_meta($pickup->ID, 'customer_notes', $customer_notes);
		}

		// update customer_notes to new value (only if new value is not empty)
		if ( !empty($vehicle_description) ) {
			update_post_meta($pickup->ID, 'vehicle_description', $vehicle_description);
		}
		
		// update customer_notes to new value (only if new value is not empty)
		if ( !empty($space_number) ) {
			update_post_meta($pickup->ID, 'space_number', $space_number);
		}
		
		if ( 'pending' == $old_status ) {
			do_action('curbside_pickup_customer_arrived', $pickup, $customer_notes, $vehicle_description, $space_number);
		}
		
		$this->add_ajax_event([
			'event_id' => date('U') . rand(1000,9999),
			'pickup_id' => $pickup->ID,
			'location_id' => $location_id,
			'time' => date('U'),
			'type' => 'new_customer',
			'data' => [
				'customer_name' => $customer_name,
				'customer_notes' => esc_html($customer_notes, 'curbside-pickup'),
			],
		]);
	}

	function reschedule_pickup($pickup_id, $new_date, $new_time)
	{
		// calculate required data
		$date_format = 'Y-m-d H:i'; // MySQL datetime format, without seconds
		$mysql_date = date( $date_format, strtotime($new_date . ' ' . $new_time) );
		$scheduled_time = get_post_meta($pickup_id, 'scheduled_delivery_time', true);

		// update post meta
		$this->set_pickup_status($pickup_id, 'pending');
		update_post_meta($pickup_id, 'scheduled_delivery_time', $mysql_date);
		update_post_meta($pickup_id, 'arrived', '0');
		update_post_meta($pickup_id, 'arrival_time', '');

		// save internal note
		$note = sprintf( __('Order rescheduled. Old Time: %s, New time: %s', 'curbside-pickup'), $scheduled_time, $mysql_date );
		$this->append_log_entry($pickup_id, $note);

		// fire action
		do_action('curbside_pickup_pickup_rescheduled', $pickup_id, $mysql_date);
	}

	function complete_pickup($pickup_id)
	{
		// calculate required data
		$mysql_date = current_time( 'mysql' );
		$scheduled_time = get_post_meta($pickup_id, 'scheduled_delivery_time', true);

		// update post meta
		$this->set_pickup_status($pickup_id, 'delivered');
		update_post_meta($pickup_id, 'delivery_time', $mysql_date);
		update_post_meta($pickup_id, 'delivered', '1');

		// save internal note
		$note = sprintf( __('Order completed. Scheduled time: %s, Actual time: %s', 'curbside-pickup'), $scheduled_time, $mysql_date );
		$this->append_log_entry($pickup_id, $note);

		// fire action
		do_action('curbside_pickup_pickup_completed', $pickup_id);
	}

	function cancel_pickup($pickup_id)
	{
		// calculate required data
		$date_format = 'Y-m-d H:i'; // MySQL datetime format, without seconds
		$mysql_date = date( $date_format, strtotime($new_date . ' ' . $new_time) );
		$scheduled_time = get_post_meta($pickup_id, 'scheduled_delivery_time', true);

		// update post meta
		$this->set_pickup_status($pickup_id, 'canceled');
		update_post_meta($pickup_id, 'delivery_time', $mysql_date);
		update_post_meta($pickup_id, 'delivered', '1');

		// save internal note
		$note = sprintf( __('Order canceled. Scheduled time: %s, Cancelation time: %s', 'curbside-pickup'), $scheduled_time, $mysql_date );
		$this->append_log_entry($pickup_id, $note);

		// fire action
		do_action('curbside_pickup_pickup_complete', $pickup_id);
	}

	function update_notes($pickup_id, $notes)
	{
		// calculate required data
		$old_notes = get_post_meta($pickup_id, 'notes', true);

		// update post meta
		update_post_meta($pickup_id, 'notes', $notes);

		// save internal note
		$note = sprintf( __('Updated customer notes. New notes: %s', 'curbside-pickup'), $notes);
		$this->append_log_entry($pickup_id, $note);

		// fire action
		do_action('curbside_pickup_notes_updated', $pickup_id, $notes, $old_notes);
	}

	function append_log_entry($pickup_id, $note)
	{
		if ( empty( trim($note) ) ) {
			return;
		}

		$old_meta = get_post_meta($pickup_id, 'log', true);
		if ( empty($old_meta) ) {
			$old_meta = '';
		} else {
			$old_meta .= "\n";
		}

		if ( WP_DEBUG && WP_DEBUG_LOG ) {
			error_log( sprintf('[%s] [Pickup ID %s] %s', current_time('mysql'), $pickup_id, $note) );
		}

		$new_meta = $old_meta . sprintf('[%s] %s', current_time('mysql'), $note);
		return update_post_meta($pickup_id, 'log', $new_meta);
	}

	function get_ajax_events($location_id = 0, $clear_event_queue = true)
	{
		$events = get_option('curbside_pickup_ajax_events');
		if ( empty($events) ) {
			$events = [];
		}

		// filter by location ID if one was specified, else return all events
		if ( !empty($location_id) ) {
			// only return events for the specified location
			$return_events = array_filter($events, function($event) use ($location_id) {
				return ( $event['location_id'] == $location_id );
			});
		} else {
			// no location speicied, return all events
			$return_events = $events;
		}

		// clear the event queue. if a location was specified,
		// only remove events for that location (leave the rest)
		if ($clear_event_queue) {
			if ( !empty($location_id) ) {
				// location specified, so only remove events for that location
				$new_queue = array_filter($events, function($event) use ($location_id) {
					return ( $event['location_id'] != $location_id );
				});
			} else {
				// no location specified, so clear the entire queue
				$new_queue = [];
			}

			// save the updated event queue
			update_option('curbside_pickup_ajax_events', $new_queue);
		}
		return $events;
	}

	function add_ajax_event(array $new_event)
	{
		$events = get_option('curbside_pickup_ajax_events');
		if ( empty($events) ) {
			$events = [];
		}
		array_unshift($events, $new_event);
		update_option('curbside_pickup_ajax_events', $events);
		return $events;
	}
}

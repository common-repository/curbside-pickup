<?php

namespace Curbside_Pickup;

class Base_Class
{
	/*
	 * Looks for a given key in the options, and returns the value (if found),
	 * or the provided default if the key is not found. Allows empty values.
	 *
	 * @param string $key The option key to fetch
	 * @param mixed $default_value The value to return if the option is not set or contains an empty value
	 * @param string $option_name The option name in which to search.
	 *
	 * @return mixed The value found in the option, or $default_value if the option was not found or was empty.
	 */
	function get_option_value($key, $default_value = '', $option_name = 'curbside_pickup')
	{
		$opt = get_option($option_name);
		$opt = maybe_unserialize($opt);
		if ( isset($opt[$key]) ) {
			return maybe_unserialize($opt[$key]);
		} else {
			return $default_value;
		}
	}

	/*
	 * get_option_value_ne (non-empty)
	 *
	 * Looks for a given key in the options, and returns the value (if found),
	 * or the provided default if the key is not found. Does not allow empty values.
	 *
	 * Note: this function is just like get_option_value, but returns the default value
	 *       if the option has an empty value (whereas get_option_value allows empty values)
	 *
	 * @param string $key The option key to fetch
	 * @param mixed $default_value The value to return if the option is not set or contains an empty value
	 * @param string $option_name The option name in which to search.
	 *
	 * @return mixed The value found in the option, or $default_value if the option was not found or was empty.
	 */
	function get_option_value_ne($key, $default_value = '', $option_name = 'curbside_pickup')
	{
		$opt = get_option($option_name);
		$opt = maybe_unserialize($opt);
		if ( !empty($opt[$key]) ) {
			return maybe_unserialize($opt[$key]);
		} else {
			return $default_value;
		}
	}

	/*
	 * Finds the oldest post ID of the given custom post type.
	 *
	 * @param string $post_type The post type to search
	 *
	 * @return int The post ID of the oldest post of the given type, or 0 if no posts were found
	 */
	function get_oldest_post_id($post_type)
	{
		$args = [
			'numberposts'     => 1,
			'offset'          => 0,
			'orderby'         => 'post_date',
			'order'           => 'ASC',
			'post_type'       => $post_type,
			'post_status'     => 'publish'
		];

		$posts = get_posts( $args );
		if ( !empty($posts) ) {
			return $posts[0]->ID;
		}

		return 0;
	}
	

	/*
	 * Returns the number of published posts for the given custom post type
	 *
	 * @param string $post_type The post type to search
	 *
	 * @return int The nunmber of posts found (can be 0).
	 */
	function get_post_type_count($post_type)
	{
		$count_posts = wp_count_posts( $post_type );
		return !empty($count_posts->publish)
			   ? $count_posts->publish
			   : 0;
	}
	
	/*
	 * Returns whether the current installation is a Pro instance
	 *
	 * @return bool true for Pro users, false for not
	 */
	function is_pro()
	{
		$cached_val = wp_cache_get( 'curbside_pickup_is_pro' );
		if ( !empty($cached_val) ) {
			return ('pro' == $cached_val);
		}
		
		if ( is_plugin_active('curbside-pickup-pro/curbside-pickup-pro.php') ) {
			// Pro plugin is active
			wp_cache_set( 'curbside_pickup_is_pro', 'pro' );
			return true;
		}
		else {
			// Pro plugin is not active
			wp_cache_set( 'curbside_pickup_is_pro', 'no' );
			return false;
		}

	}
	
	/*
	 * Returns the MySQL friendly string for the start of the day, based on the
	 * current date in the local timezone (as specified in WP settings).
	 *
	 * Useful because calculating e.g., "tomorrow midnight" with strtotime is 
	 * affected by the server time, which often does not reflect the local time.
	 *
	 * @param string $base_day_str Optional, a strtitime compatible string to 
	 * 				 base the current day of off. Useful to calculate the start 
	 * 				 of e.g., "tomorrow"). Default: 'now' (current day)
	 */	 
	function local_day_start($base_day_str = 'now')
	{
		$local_date = get_date_from_gmt( $base_day_str, 'Y-m-d' );
		return $local_date . ' 00:00:00';
	}

	/*
	 * Returns the MySQL friendly string for the end of the day, based on the
	 * current date in the local timezone (as specified in WP settings).
	 *
	 * Useful because calculating e.g., "tomorrow midnight" with strtotime is 
	 * affected by the server time, which often does not reflect the local time.
	 *
	 * @param string $base_day_str Optional, a strtitime compatible string to 
	 * 				 base the current day of off. Useful to calculate the start 
	 * 				 of e.g., "tomorrow"). Default: 'now' (current day)
	 */	 
	function local_day_end($base_day_str = 'now')
	{
		$local_date = get_date_from_gmt( $base_day_str, 'Y-m-d' );
		return $local_date . ' 23:59:59';
	}

	/*
	 * Formats the provided strtotime compatible string into a human-friendly time
	 *
	 * @param string $str_time strtotime compatible string
	 *
	 * @return string Human-friendly date, e.g., April 20, 2020, 4:20 pm
	 */
	function friendly_date($str_time)
	{
		$format = "F j, Y, g:i a"; // e.g., April 20, 2020, 4:20 pm
		return date( $format, strtotime($str_time) );
	}

	/*
	 * Returns a relative time string (e.g, "four days ago") from the given timestamp
	 *
	 * @param int $ts Unix timestamp
	 *
	 * @return string Relative time string, e.g., "four days ago" or "three hours ago"
	 */
	function friendly_time($ts)
	{
		$relativeTime = new \RelativeTime\RelativeTime([ 'truncate' => 2, 'suffix' => false ]);
		$date_format = 'Y-m-d H:i:s'; // MySQL format
		$ft = $relativeTime->convert( $ts, current_time($date_format, false) );
		$now = current_time('timestamp');
		$suffix = '';
		if ( strtotime($ts) < $now ) {
			$suffix = ' ago';
		} else if ( strtotime($ts) > $now ) {
			$suffix = ' from now';
		}
		 // . ' ' . __('ago', 'curbside-pickup')
		$ft = str_replace('left', '', $ft);
		return $ft . $suffix;
	}	
	
	/*
	 * Reverse of PHP's nl2br. Converts HTML line breaks to newline characters.
	 *
	 * @param string $str_input Input string
	 *
	 * @return string String with line breaks converted
	 */
	function br2nl($str_input)
	{
		return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $str_input);
	}

	/*
	 * Checks for whether the Pickup Locations module is installed/activated
	 *
	 * @return bool true if the Pickup Locations module is present, false if not
	 */
	function locations_module_enabled()
	{
		// TODO: make this look for the actual plugin
		return post_type_exists('pickup-location');
	}
	
	/*
	 * Writes the given message to the debug log only if WP is in debug mode
	 * (else it is silently ignored). WP is considered in debug mode if
	 * WP_DEBUG & WP_DEBUG_LOG are both defined and truthy.
	 *
	 */
	function debug_log($msg)
	{
		if ( empty( trim($msg) ) ) {
			return;
		}

		if ( WP_DEBUG && WP_DEBUG_LOG ) {
			error_log( sprintf('[Curbside Pickup] [%s] %s', current_time('mysql'), $msg) );
		}
	}
	
	
	/*
	 * Writes the message to the error log with a timestamp.
	 *
	 * @param string $msg The string to echo to the error log
	 */
	function time_check($msg = '')
	{
		error_log ( sprintf('[%s] %s', microtime(), $msg) );		
	}

	/*
	 * Stores data into the WordPress cache, that only lasts for the current 
	 * request
	 *
	 * @param string $key cache key
	 * @param mixed $data data to cache key
	 * @param int $expires number of seconds to cache. default -1 (this request only)
	 */
	function cache_set($key, $data, $expires = -1)
	{
		wp_cache_set($key, $data, 'curbside_pickup', $expires);
	}

	/*
	 * Retrieves data from the WordPress cache
	 *
	 * @param string $key cache key
	 * @param mixed $default_value Value to return on cache miss
	 *
	 * @return mixed Cached value, or $default_value if not in cache.
	 */
	function cache_get($key, $default_value = false)
	{
		$cached_val = wp_cache_get($key, 'curbside_pickup');
		if ( false !== $cached_val ) {
			return $cached_val;
		}
		else {
			return $default_value;
		}
	}

	/*
	 * Deletes data from the WordPress cache
	 *
	 * @param string $key cache key to delete
	 */
	function cache_delete($key)
	{
		wp_cache_delete($key, 'curbside_pickup');		
	}
}
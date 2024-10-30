<?php

namespace Curbside_Pickup;

class Scheduled_Pickup extends Base_Class
{
	/*
	 * Creates a new Scheduled_Pickup object
	 *
	 * @param int $pickup_id The ID of the scheduled pickup
	 */
	function __construct($pickup_id)
	{
		$this->pickup_id = $pickup_id;
	}
	
	/*
	 * Returns the pickup's WooCommerce order ID (if one exists)
	 *
	 * @return mixed int of the WooCommerce Order ID if one is found, empty string if not
	 */
	function get_order_id()
	{
		$order_id = $this->get_meta('order_id');
		return ! empty( intval($order_id) )
			   ? intval($order_id)
			   : '';
	}

	/*
	 * Returns the pickup's delivery status
	 *
	 * @return bool true if the pickup is complete (delivered or canceled),
	 * 				false if not
	 */
	function get_status()
	{
		$terms = get_the_terms($this->pickup_id, 'delivery-status');
		return ! empty($terms)
			   ? $terms[0]->slug
			   : '';
	}

	/*
	 * Returns whether the pickup is complete (delivered or canceled)
	 *
	 * @return bool true if the pickup is complete (delivered or canceled),
	 * 				false if not
	 */
	function is_complete()
	{
		$status = $this->get_status();
		return in_array($status, ['delivered', 'canceled']);
	}

	/*
	 * Returns whether the pickup has been delivered
	 *
	 * @return bool true if the pickup has been delivered, false if not
	 */
	function is_delivered()
	{
		$status = $this->get_status();
		return ('delivered' == $status);
	}


	/*
	 * Returns whether the pickup has been canceled
	 *
	 * @return bool true if the pickup has been canceled, false if not
	 */
	function is_canceled()
	{
		$status = $this->get_status();
		return ('canceled' == $status);
	}


	/*
	 * Returns whether the pickup has been marked late
	 *
	 * @return bool true if the pickup has been marked late, false if not
	 */
	function is_late()
	{
		$status = $this->get_status();
		return ('late' == $status);
	}

	/*
	 * Returns whether the pickup is in waiting status
	 *
	 * @return bool true if the pickup is in waiting status, false if not
	 */
	function is_waiting()
	{
		$status = $this->get_status();
		return ('waiting' == $status);
	}
	
	/*
	 * Returns whether the pickup is pending
	 *
	 * @return bool true if the pickup is pending, false if not
	 */
	function is_pending()
	{
		$status = $this->get_status();
		return ('pending' == $status);
	}
	
	function get_scheduled_time($date_format = 'F j, Y \a\t g:i a')
	{
		$scheduled_delivery_time = $this->get_meta('scheduled_delivery_time');
		return !empty($scheduled_delivery_time)
			   ? date( $date_format, strtotime( $scheduled_delivery_time ) )
			   : '';		
	}

	function get_arrival_time($date_format = 'F j, Y \a\t g:i a')
	{
		$arrival_time = $this->get_meta('arrival_time');
		return !empty($arrival_time)
			   ? date( $date_format, strtotime( $arrival_time ) )
			   : '';		
	}

	function get_delivery_time($date_format = 'F j, Y \a\t g:i a')
	{
		$delivery_time = $this->get_meta('delivery_time');
		return !empty($delivery_time)
			   ? date( $date_format, strtotime( $delivery_time ) )
			   : '';		
	}

	function get_location_name($date_format = 'F j, Y \a\t g:i a')
	{
		$delivery_location = $this->get_meta('delivery_location');
		return !empty($delivery_location)
			   ? get_the_title($delivery_location)
			   : '';
	}

	/*
	 * Returns all the meta fields for the given pickup. Adds some special
	 * calculated fields. By default, "private keys" (those starting with '_')
	 * are not returned.
	 *
	 * @param bool $remove_private Whether to remove private keys. 
	 * 							   Default true.
	 *
	 * @return array Array of meta keys. Empty array if no pickup_id was set.
	 */
	function get_all_meta($remove_private = true)
	{
		$all_meta = !empty($this->pickup_id)
					? get_post_meta($this->pickup_id) // returns [] anyway if $this->pickup_id not found
					: [];

		// if desired, filter out keys starting with an underscore
		if ( $remove_private ) {
			$all_meta = array_filter($all_meta, function ($key) {
				return (strpos($key, '_') !== 0);
			}, ARRAY_FILTER_USE_KEY);
		}

		// add order ID and other special fields
		$date_format = 'F j, Y \a\t g:i a';
		$all_meta['order_id'] = $this->pickup_id;
		$all_meta['arrival_time_friendly'] = !empty($all_meta['arrival_time'][0])
											 ? $this->friendly_time( $all_meta['arrival_time'][0] )
											 : '';
		$all_meta['arrival_time_formatted'] = !empty($all_meta['arrival_time'][0])
											  ? date( $date_format, strtotime( $all_meta['arrival_time'][0] ) )
											  : '';
		$all_meta['scheduled_time_formatted'] = date( $date_format, strtotime( $all_meta['scheduled_delivery_time'][0] ) );
		$all_meta['scheduled_time_friendly'] = $this->friendly_time( $all_meta['scheduled_delivery_time'][0] );

		if ( !empty($all_meta['delivery_location']) && !empty($all_meta['delivery_location'][0]) ) {
			$all_meta['delivery_location_name'] = get_the_title( $all_meta['delivery_location'][0] );
		}

		// add required fields if not present
		$required_keys = [
			'notes',
			'customer_notes',
			'delivery_location',
			'delivery_location_name',
		];

		foreach ($required_keys as $required_key) {
			if ( !isset($all_meta[$required_key]) ) {
				$all_meta[$required_key] = '';
			}
		}
		
		// unpack single keys from 1-item arrays to flat values
		foreach ( $all_meta as $key => $val ) {
			if ( is_array($val) && 1 == count($val) ) {
				$all_meta[$key] = $val[0];
			}
		}

		return $all_meta;
	}
	
	/*
	 * Returns a single meta field's value
	 *
	 * @param bool $is_single Whether the key is a single value. Default true.
	 *
	 * @return mixed Value of the meta key. Maybe an array depending
	 * 				 on $is_single
	 */
	function get_meta($meta_key, $is_single = true)
	{
		return get_post_meta($this->pickup_id, $meta_key, $is_single);
	}
	
	function update_pickup_button( $button_label = '' )
	{
		$button_label = ! empty($button_label)
						? $button_label
						: __('Update Pickup', 'curbside-pickup');
		$tmpl = '<button class="button button-secondary curbside_pickup_update_order_button" type="button" data-order-id="%d">%s</button>';
		$btn = sprintf( $tmpl, $this->pickup_id, htmlspecialchars($button_label) );
		return $btn;
	}

} // end class

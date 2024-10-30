<?php

function curbside_pickup_get_pickup_url($pickup_id)
{
		$opt = get_option('curbside_pickup');
		$opt = maybe_unserialize($opt);

		$pickup_page_id = isset($opt['pickup_page_id'])
						? maybe_unserialize($opt['pickup_page_id'])
						: '';
		$pickup_page_url = !empty($pickup_page_id)
						   ? get_the_permalink($pickup_page_id)
						   : get_home_url();

		// set random hash if needed
		$pickup_hash = get_post_meta($pickup_id, 'pickup_hash', true);
		if ( empty($pickup_hash) ) {
			$pickup_hash = md5( $pickup_id . '_curbside_pickup' . rand() );
			update_post_meta($pickup_id, 'pickup_hash', $pickup_hash);
		}

		$pickup_url = add_query_arg([
			'curbside_pickup_pickup_id' => $pickup_hash
		], $pickup_page_url);
		return apply_filters('curbside_pickup_pickup_url', $pickup_url, $pickup_id, $pickup_page_id);

}

function curbside_pickup_get_store_address_from_wc()
{
	if ( ! class_exists('woocommerce') ) {
		return '';
	}
	
	$addr = [
		'address_1' => WC()->countries->get_base_address(),
		'address_2' => WC()->countries->get_base_address_2(),
		'city' => WC()->countries->get_base_city(),
		'postcode' => WC()->countries->get_base_postcode(),
		'state' => WC()->countries->get_base_state(),
		'country' => WC()->countries->get_base_country(),
	];
	$formatted_addr = WC()->countries->get_formatted_address( $addr );
	return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $formatted_addr); // br2nl
}


function curbside_pickup_get_store_address($pickup_id = '')
{
	$store_address = '' ;

	// first, if we have a pickup ID, see if there is a Location associated 
	// with the order. if so, use the address of the Location
	if ( !empty($pickup_id) ) {
		$location_id = get_post_meta($pickup_id, 'delivery_location', true);
		if ( !empty($location_id) ) {
			$store_address = get_post_meta($location_id, 'address', true);
		}
	}

	// second, look for our setting value
	if ( empty($store_address) ) {
		$store_address = curbside_pickup_get_option_value('store_address' );
	}

	// third, try to load it from WooCommerce settings
	if ( empty($store_address) && class_exists('woocommerce') ) {
		$store_address = curbside_pickup_get_store_address_from_wc();
	}

	// finally, filter and return whatever we have (maybe empty string)
	return apply_filters('curbside_pickup_get_store_address', $store_address, $pickup_id);
}

function curbside_pickup_get_option_value($key, $default_value = '')
{
	$opt = get_option('curbside_pickup');
	$opt = maybe_unserialize($opt);
	if ( isset($opt[$key]) ) {
		return maybe_unserialize($opt[$key]);
	} else {
		return $default_value;
	}
}

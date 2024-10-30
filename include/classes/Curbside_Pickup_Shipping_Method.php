<?php
function init_curbside_pickup_shipping_method()
{
	if ( !class_exists('Curbside_Pickup_Shipping_Method') )
	{
		class Curbside_Pickup_Shipping_Method  extends WC_Shipping_Method
		{
			/**
			 * Constructor. The instance ID is passed to this.
			 */
			public function __construct( $instance_id = 0 )
			{
				$this->id                    = 'curbside_pickup';
				$this->Scheduler             = new \Curbside_Pickup\Scheduler();
				$this->shown_extra_fields 	 = false;
				$this->instance_id           = absint( $instance_id );
				$this->method_title          = __( 'Curbside Pickup' );
				$this->method_description    = __( 'Allow your customers to pickup their order at your store and check in upon arrival.' );
				$this->supports              = array(
					'shipping-zones',
					'instance-settings',
				);
				$more_settings = sprintf( '<a href="%s">%s %s &raquo;</a>',
										  admin_url('admin.php?page=curbside-pickup-settings'),
										  'Curbside Pickup',
										  __( 'Settings', 'curbside-pickup' ) );
				$this->instance_form_fields = array(
					'enabled' => array(
						'title' 		=> __( 'Enable/Disable' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Enable this shipping method' ),
						'default' 		=> 'yes',
					),
					'title' => array(
						'title' 		=> __( 'Shipping Label' ),
						'type' 			=> 'text',
						'description' 	=> __( 'This is the label for this method when shown to the customer.' ),
						'default'		=> __( 'Curbside Pickup' ),
						'desc_tip'		=> true
					),
					'more_settings' => array(
						'title' 		=> __( 'More Settings' ),
						'type' 			=> 'title',
						'description' 	=> $more_settings,
						'default'		=> '',
						'desc_tip'		=> false
					)
				);
				$this->enabled              = $this->get_option( 'enabled' );
				$this->title                = $this->get_option( 'title' );

				$this->init();
			}

			/**
			 * Init your settings
			 *
			 * @access public
			 * @return void
			 */
			function init()
			{
				// Load the settings API
				$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
				$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

				// Save settings in admin if you have any defined
				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			}

			/**
			 * calculate_shipping function.
			 *
			 * @access public
			 * @param mixed $package
			 * @return void
			 */
			public function calculate_shipping( $package = array() ) {
				$rate = array(
					'id' => $this->id,
					'label' => $this->title,
					'cost' => '0',
					'calc_tax' => 'per_item'
				);

				// Register the rate
				$this->add_rate( $rate );
			}

			function get_option_value($key, $default_value = '')
			{
				$opt = get_option('curbside_pickup');
				$opt = maybe_unserialize($opt);
				if ( isset($opt[$key]) ) {
					return maybe_unserialize($opt[$key]);
				} else {
					return $default_value;
				}
			}

		} // end class
	}
}

add_action( 'woocommerce_shipping_init', 'init_curbside_pickup_shipping_method');

function add_curbside_pickup_shipping_method( $methods )
{
	$methods['curbside_pickup'] = 'Curbside_Pickup_Shipping_Method';
	return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_curbside_pickup_shipping_method' );

function curbside_pickup_save_checkout_fields( $order_id )
{
	$pickup_location = !empty($_POST['curbside_pickup_pickup_location'])
					   ? intval($_POST['curbside_pickup_pickup_location'])
					   : 0;

	$pickup_date = !empty($_POST['curbside_pickup_pickup_date'])
				   ? sanitize_text_field($_POST['curbside_pickup_pickup_date'])
				   : '';
				   
	$pickup_time = !empty($_POST['curbside_pickup_pickup_time'])
				   ? sanitize_text_field($_POST['curbside_pickup_pickup_time'])
				   : '';

	if ( !empty($pickup_location) ) {
		update_post_meta($order_id, 'curbside_pickup_pickup_location', $pickup_location);
	}

	if ( !empty($pickup_date) && !empty($pickup_time) ) {
		update_post_meta($order_id, 'curbside_pickup_pickup_date', $pickup_date);
		update_post_meta($order_id, 'curbside_pickup_pickup_time', $pickup_time);
	}
}
add_action( 'woocommerce_checkout_order_processed', 'curbside_pickup_save_checkout_fields' );

function curbside_pickup_create_pickup_from_wc_order( $order_id )
{
    $order = wc_get_order( $order_id );
    if( $order ) {
		
		// abort if this is not a curbside pickup order
		if( ! $order->has_shipping_method('curbside_pickup') ) {
			return;
		}

		// create initial log entry
		$note = 'Created pickup from WooCommerce order #' . $order_id;
		$log = sprintf('[%s] %s', current_time('mysql'), $note);
		$customer_name = $order->get_formatted_shipping_full_name();
		$pickup_date = get_post_meta($order_id, 'curbside_pickup_pickup_date', true);
		$pickup_time = get_post_meta($order_id, 'curbside_pickup_pickup_time', true);
		
		// if the user supplied a pickup time, reformat and use it
		// if not, get the next available time from the Scheduler and use that instead
		$scheduler = new \Curbside_Pickup\Scheduler();
		$last_adjusted = '';
		
		// apply a filter to allow the pickup time to be set (i.e., via integration with other plugins)
		$scheduled_time = apply_filters('curbside_pickup_new_order_prefill_scheduled_time', '', $order_id);
		
		/*
		 * time provided by filter
		 */
		if ( !empty($scheduled_time) ) {
			// $scheduled_time was provided by a filter, so reformat and use it
			if ( is_numeric($scheduled_time) && (int)$scheduled_time == $scheduled_time ) {
				// $scheduled_time is a timestamp, so use it directly
				$scheduled_time = date( 'Y-m-d H:i', $scheduled_time );				
			}
			else {
				// $scheduled_time is a string, so reformat it into a timestamp first
				$scheduled_time = date( 'Y-m-d H:i', strtotime($scheduled_time) );
			}
		}
		/*
		 * time chosen by the user at checkout
		 */
		else if ( !empty($pickup_date) && !empty($pickup_time) ) {
			
			// reformat provided time into our format
			$scheduled_time = date('Y-m-d H:i', strtotime($pickup_date . ' ' . $pickup_time) );			
			
			// make sure the timeslot has not become full in the mean-time
			// if it has, adjust the time to the next available slot,
			// and set the time_adjusted flag to the current time
			if ( $scheduler->timeslot_is_full($scheduled_time) ) {
				$nat = $scheduler->get_next_available_time($scheduled_time);
				$scheduled_time = date( 'Y-m-d H:i', strtotime($nat) );
				$last_adjusted = date('Y-m-d H:i');
			}
			
		}
		/*
		 * no time provided so assign the next available time
		 */
		else {
			// empty pickup date and time, so assign the next available timeslot
			$nat = $scheduler->get_next_available_time();
			$scheduled_time = date( 'Y-m-d H:i', strtotime($nat) );
			$last_adjusted = date('Y-m-d H:i');
		}

        // create a new scheduled pickup for this order
		$new_pickup_meta = [
			'order_id' 					=> $order_id,
			'customer_name' 			=> $customer_name,
			'customer_email' 			=> $order->get_billing_email(),
			'customer_phone' 			=> $order->get_billing_phone(),
			'order_total' 				=> $order->get_total(),
			'item_count' 				=> $order ->get_item_count(),
			'delivery_location' 		=> get_post_meta($order_id, 'curbside_pickup_pickup_location', true),
			'scheduled_delivery_time' 	=> $scheduled_time,
			'log' 						=> $log,
		];
		
		// save last_adjusted timestamp if needed
		if ( !empty($last_adjusted) ) {
			$new_pickup_meta['last_adjusted'] = $last_adjusted;
		}

		// all new orders begin with 'Pending' status
		$new_pickup_terms = [
			'delivery-status' => [
				'pending'
			]
		];

		// allow meta fields to be modified before inserting
		$new_pickup_meta = apply_filters('curbside_pickup_new_scheduled_pickup_meta', $new_pickup_meta);

		// attempt to create the new scheduled pickup
		$new_pickup_id = wp_insert_post([
			'post_title' 	=> $customer_name,
			'post_type' 	=> 'scheduled-pickup',
			'post_status'	=> 'publish',
			'meta_input' 	=> $new_pickup_meta,
			'tax_input' 	=> $new_pickup_terms,
		]);

		if ( !empty($new_pickup_id) ) {
			// save pickup ID as post meta on WooCommerce order
			update_post_meta($order_id, 'curbside_pickup_scheduled_pickup_id', $new_pickup_id);

			// fire WP's save_post hook manually
			$new_pickup = get_post($new_pickup_id);
			do_action('save_post_scheduled-pickup', $new_pickup_id, $new_pickup, false);

			// fire our own hook
			do_action('curbside_pickup_pickup_created', $new_pickup_id, $order_id);
		}
	}
}

//add_action( 'woocommerce_payment_complete', 'curbside_pickup_create_pickup_from_wc_order' );
add_action( 'woocommerce_checkout_order_processed', 'curbside_pickup_create_pickup_from_wc_order' );

function curbside_pickup_add_pickup_details_to_wc_thankyou_page($order_id)
{
	// abort if not our shipping method
	$order = wc_get_order($order_id);
	$obj_shipping = $order->get_items( 'shipping' );
	$first_shipping = reset( $obj_shipping );
	
	// there may be no shipping methods at all, 
	// i.e., when only virtual products are in the cart
	if ( empty($first_shipping) ) {
		return;
	}

	$shipping_method = $first_shipping->get_method_id();
	if ( 'curbside_pickup' != $shipping_method ) {
		return;
	}

	$tmpl = '<h2 class="woocommerce-column__title">%s</h2>' . "\n\n";
	printf($tmpl, __('Curbside Pickup details', 'wp-curbsde'));

	$pickup_id = get_post_meta($order->get_id(), 'curbside_pickup_scheduled_pickup_id', true);
	if ( empty($pickup_id) ) {
		return;
	}

	
	$pickup_location_id = get_post_meta($pickup_id, 'delivery_location', true);
	$pickup_location_name = !empty($pickup_location_id)
							? get_the_title($pickup_location_id)
							: get_bloginfo('name');
	$pickup_location_address = curbside_pickup_get_store_address($pickup_id); 
 
	$pickup_time_meta = get_post_meta($pickup_id, 'scheduled_delivery_time', true);
	$pickup_time = date( 'F j, Y, g:i a', strtotime($pickup_time_meta) );
	$check_in_link = curbside_pickup_get_pickup_url($pickup_id);
	$google_maps_url = sprintf('https://www.google.com/maps/dir/current+location/%s/', urlencode($pickup_location_address));
	$google_maps_link = sprintf( '<a href="%s">%s</a>', $google_maps_url, __('Get Directions', 'curbside-pickup') );
	$fields = [
		__('Pickup Location', 'curbside-pickup') => esc_html($pickup_location_name) . "<br>" . nl2br(esc_html($pickup_location_address), false) . '<br>' .'<br>' . $google_maps_link,
		__('Pickup Time', 'curbside-pickup') => esc_html($pickup_time),
		__('Check-In Link', 'curbside-pickup') => '{check_in_link}',
	];
	
	$fields = apply_filters('curbside_pickup_wc_confirmation_page_fields', $fields, $pickup_id, $order_id);

	foreach($fields as $key => $val) {
		$line = sprintf("<p><strong>%s</strong><br>\n%s</p>\n", esc_html($key), $val);
		if ( !empty($plain_text) ) {
			// this is the plain text version, so replace <br>'s,
			// strip the rest of the tags, and add a plain URL for check-in
			$line = str_replace('<br>', "\n", $line);
			$line = strip_tags($line);
			$line = str_replace('{check_in_link}', $check_in_link, $line);
		}
		else {
			// this is the HTML version, so add an HTML link for check-in
			$link_tag = sprintf( '<a href="%s">%s</a>', $check_in_link, __('Click Here To Check-In When You Arrive', 'wp-curbsde') );
			$line = str_replace('{check_in_link}', $link_tag, $line);
		}
		echo $line;
	}

	if ( !empty($plain_text) ) {
		echo "\n";
	} else {
		echo "<br>\n";
	}

}

function curbside_pickup_add_pickup_details_to_wc_email($order, $sent_to_admin, $plain_text, $email)
{
	// abort if not our shipping method
	$obj_shipping = $order->get_items( 'shipping' );
	$first_shipping = reset( $obj_shipping );
	
	// there may be no shipping methods at all, 
	// i.e., when only virtual products are in the cart
	if ( empty($first_shipping) ) {
		return;
	}

	$shipping_method = $first_shipping->get_method_id();
	if ( 'curbside_pickup' != $shipping_method ) {
		return;
	}

	$tmpl = '<h2 style="color: #96588a; display: block; font-family: &quot;Helvetica Neue&quot;, Helvetica, Roboto, Arial, sans-serif; font-size: 18px; font-weight: bold; line-height: 130%%; margin: 0 0 18px; text-align: left;">%s</h2>' . "\n\n";
	printf($tmpl, __('Curbside Pickup Details', 'wp-curbsde'));

	$pickup_id = get_post_meta($order->get_id(), 'curbside_pickup_scheduled_pickup_id', true);
	if ( empty($pickup_id) ) {
		return;
	}

	$pickup_location_id = get_post_meta($pickup_id, 'delivery_location', true);
	$pickup_location_name = !empty($pickup_location_id)
							? get_the_title($pickup_location_id)
							: get_bloginfo('name');
	$pickup_location_address = curbside_pickup_get_store_address($pickup_id); 

	$pickup_time_meta = get_post_meta($pickup_id, 'scheduled_delivery_time', true);
	$pickup_time = date( 'F j, Y, g:i a', strtotime($pickup_time_meta) );
	$check_in_link = curbside_pickup_get_pickup_url($pickup_id);
	$google_maps_url = sprintf('https://www.google.com/maps/dir/current+location/%s/', urlencode($pickup_location_address));
	$google_maps_link = sprintf( '<a href="%s">%s</a>', $google_maps_url, __('Get Directions', 'curbside-pickup') );
	$fields = [
		__('Pickup Location', 'curbside-pickup') => esc_html($pickup_location_name) . "<br>" . nl2br(esc_html($pickup_location_address), false) . '<br>' .'<br>' . $google_maps_link,
		__('Pickup Time', 'curbside-pickup') => esc_html($pickup_time),
		__('Check-In Link', 'curbside-pickup') => '{check_in_link}',
	];
	
	$fields = apply_filters('curbside_pickup_wc_confirmation_email_fields', $fields, $pickup_id, $order_id, $sent_to_admin);

	foreach($fields as $key => $val) {
		$line = sprintf("<p><strong>%s</strong><br>\n%s</p>\n", esc_html($key), $val);
		if ( !empty($plain_text) ) {
			// this is the plain text version, so replace <br>'s,
			// strip the rest of the tags, and add a plain URL for check-in
			$line = str_replace('<br>', "\n", $line);
			$line = strip_tags($line);
			$line = str_replace('{check_in_link}', $check_in_link, $line);
		}
		else {
			// this is the HTML version, so add an HTML link for check-in
			$link_tag = sprintf( '<a href="%s">%s</a>', $check_in_link, __('Click Here To Check-In When You Arrive', 'wp-curbsde') );
			$line = str_replace('{check_in_link}', $link_tag, $line);
		}
		echo $line;
	}

	if ( !empty($plain_text) ) {
		echo "\n";
	} else {
		echo "<br>\n";
	}

}

add_action( 'woocommerce_email_after_order_table', 'curbside_pickup_add_pickup_details_to_wc_email', 10, 4 );
add_action( 'woocommerce_thankyou', 'curbside_pickup_add_pickup_details_to_wc_thankyou_page', 4 );

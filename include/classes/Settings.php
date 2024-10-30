<?php

namespace Curbside_Pickup;

class Settings extends Base_Class
{
	function __construct( )
	{
		$this->add_hooks();
	}

	function add_hooks()
	{
		// settings
		add_filter( 'mb_settings_pages', array($this, 'create_settings_page') );
		add_filter( 'rwmb_meta_boxes', array($this, 'setup_settings_meta_boxes') );
	}

	function create_settings_page( $settings_pages )
	{
		$tabs = array(
				'store' 		=> 'Store Settings',
				'pickup' 		=> 'Pickup Settings',
				'email'  		=> 'Email Settings',
				'notifications'	=> 'Notifications',
				'pickup-page'   => 'Pickup Page Settings',
				'dashboard'     => 'Dashboard Settings',
				'help'     		=> 'Help & Troubleshooting',
		);
		$tabs = apply_filters('curbside_pickup_admin_settings_tabs', $tabs);
		
		$settings_pages[] = array(
			'id'          => 'curbside-pickup-settings',
			'option_name' => 'curbside_pickup',
			'menu_title'  => 'Settings',
			'icon_url'    => 'dashicons-edit',
			'style'       => 'no-boxes',
			'tabs'        => $tabs,
			'parent' => 'curbside-pickup/curbside-pickup.php'
		);
		return $settings_pages;
	}

	function setup_settings_meta_boxes($meta_boxes)
	{
		$prefix = '';
		$meta_boxes[] = array(
			'id'             => 'settings_store',
			'title'          => 'Store Settings',
			'settings_pages' => 'curbside-pickup-settings',
			'tab'            => 'store',
			'fields' => array(
				array(
					'id' => $prefix . 'store_name',
					'type' => 'text',
					'name' => esc_html__( 'Store Name', 'curbside-pickup' ),
					'std' => esc_html( get_bloginfo('name') ),
					'desc' => esc_html__( 'Used in emails and other messaging.', 'curbside-pickup' ),
					'size' => 60,
				),
				array(
					'id' => $prefix . 'store_address',
					'type' => 'textarea',
					'name' => esc_html__( 'Store Address:', 'curbside-pickup' ),
					'std' => curbside_pickup_get_store_address(),
					'desc' => esc_html__( 'The address of your store. Can be overridden on a per location basis (requires Curbside Pickup  Pro).', 'curbside-pickup' ),
					'rows' => 4,
				),
			),
		);

		$meta_boxes[] = array(
			'id'             => 'settings_pickup',
			'title'          => 'Pickup Settings',
			'settings_pages' => 'curbside-pickup-settings',
			'tab'            => 'pickup',
			'fields' => array(
				array(
					'id' => 'heading_pick_up_times',
					'type' => 'heading',
					'name' => esc_html__( 'Pickup Times', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'pickup_time_assignment',
					'name' => esc_html__( 'Allow Customer To Select Their Pickup Time', 'curbside-pickup' ),
					'type' => 'radio',
					'options' => array(
						'customer_selects' => __('Allow customer to select their preferred pickup time.', 'curbside-pickup'),
						'automatic' => __('Automatically choose the next available time', 'curbside-pickup')
					),
					'std' => 'customer_selects',
					'inline' => false,
				),
				array(
					'id' => $prefix . 'allowed_days_in_future',
					'type' => 'number',
					'name' => esc_html__( 'Days Into Future Allowed', 'curbside-pickup' ),
					'desc' => esc_html__( 'How many days in advance can an order be placed? Enter 0 to only allow same-day orders.', 'curbside-pickup' ),
					'std' => '30',
					'min' => '0',
					'max' => '365',
				),
				array(
					'id' => $prefix . 'pickup_delay',
					'type' => 'number',
					'name' => esc_html__( 'Pickup Delay', 'curbside-pickup' ),
					'desc' => esc_html__( 'Minimum time between ordering and pickup.', 'curbside-pickup' ),
					'std' => '60',
					'max' => '9999',
				),
				array(
					'id' => $prefix . 'pickup_delay_period',
					'name' => esc_html__( 'Pickup Delay Period', 'curbside-pickup' ),
					'type' => 'select',
					//'placeholder' => esc_html__( 'Choose a Period', 'curbside-pickup' ),
					'options' => array(
						'minute' => esc_html__( 'Minutes', 'curbside-pickup' ),
						'hour' => esc_html__( 'Hours', 'curbside-pickup' ),
						'day' => esc_html__( 'Days', 'curbside-pickup' ),
					),
					'std' => 'minute',
					'desc' => esc_html__( 'Please select a unit for the pickup delay setting.', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'pickup_time_increment',
					'name' => esc_html__( 'Pickup Time Increment', 'curbside-pickup' ),
					'type' => 'select',
					'placeholder' => esc_html__( 'Choose a Time Period', 'curbside-pickup' ),
					'options' => array(
						'5' => esc_html__( '5 minutes', 'curbside-pickup' ),
						'10' => esc_html__( '10 minutes', 'curbside-pickup' ),
						'15' => esc_html__( '15 minutes', 'curbside-pickup' ),
						'30' => esc_html__( '30 minutes', 'curbside-pickup' ),
						'60' => esc_html__( '1 hour', 'curbside-pickup' ),
						'120' => esc_html__( '2 hours', 'curbside-pickup' ),
					),
					'std' => '15',
					'desc' => esc_html__( 'When times are offered to the customer, this setting determines how specific of a time the customer can select.', 'curbside-pickup' ),
				),
				/* array(
					'id' => $prefix . 'display_pickup_times_as_ranges',
					'name' => esc_html__( 'Display Pickup Times As Ranges', 'curbside-pickup' ),
					'type' => 'select',
					//'placeholder' => esc_html__( 'Choose a display option', 'curbside-pickup' ),
					'options' => array(
						'start_only' => esc_html__( 'Display normally (e.g., 2:00pm)', 'curbside-pickup' ),
						'range_exclusive' => esc_html__( 'Display as a range, excluding the end time (e.g., 2:00pm - 2:59pm)', 'curbside-pickup' ),
						'range_inclusive' => esc_html__( 'Display as a range, including the end time (e.g., 2:00pm - 3:00pm)', 'curbside-pickup' ),
					),
					'std' => 'start_only',
					'desc' => esc_html__( 'When customers are selecting their time, should it be one specific time, or a range?', 'curbside-pickup' ),
				),
				*/
				array(
					'id' => 'heading_early_pickups',
					'type' => 'heading',
					'name' => esc_html__( 'Early Pickups', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'allow_early_pickups',
					'name' => esc_html__( 'Allow Early Pickups', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Allow customers to check in early, before their pick-up time.', 'curbside-pickup' ),
					'std' => 1,
				),
				array(
					'id' => $prefix . 'checkin_grace_period',
					'type' => 'number',
					'name' => esc_html__( 'Early Check-In Grace Period', 'curbside-pickup' ),
					'desc' => esc_html__( 'The number of minutes before their scheduled time that a customer can check-in.', 'curbside-pickup' ),
					'std' => '10',
					'min' => '0',
					'max' => '9999',
				),
				array(
					'id' => 'heading_late_pickups',
					'type' => 'heading',
					'name' => esc_html__( 'Late Pickups', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'allow_late_pickups',
					'name' => esc_html__( 'Allow Late Pickups', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Allow customers to check in, even after their pickup has been marked Late.', 'curbside-pickup' ),
					'std' => 1,
				),
				array(
 					'id' => $prefix . 'minutes_until_late',
					'type' => 'number',
					'name' => esc_html__( 'Mark Orders Late After', 'curbside-pickup' ),
					'desc' => esc_html__( "The number of minutes that should pass before the system marks a pickup as 'Late'.", 'curbside-pickup' ),
					'std' => '30',
					'max' => '9999',
				),
				array(
					'id' => $prefix . 'late_checkin_grace_period',
					'type' => 'number',
					'name' => esc_html__( 'Late Check-In Grace Period', 'curbside-pickup' ),
					'desc' => esc_html__( 'The number of minutes after a customer\'s pickup has been maked Late that they can still check-in.', 'curbside-pickup' ),
					'std' => '180',
					'min' => '0',
					'max' => '9999',
				),
				array(
					'id' => 'heading_order_throttling',
					'type' => 'heading',
					'name' => esc_html__( 'Order Throttling', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'throttling_enabled',
					'name' => esc_html__( 'Enable Order Throttling', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Limit the number of pickups allowed per time slot.', 'curbside-pickup' ),
					'std' => true,
				),
				array(
					'id' => $prefix . 'orders_per_period',
					'type' => 'number',
					'name' => esc_html__( 'Orders Allowed Per Time Slot', 'curbside-pickup' ),
					'std' => '2',
					'min' => '1',
					'max' => '9999',
					'step' => '1',
				),
			),
		);

		$meta_boxes[] = array(
			'id'             => 'settings_emails_page',
			'title'          => 'Email Settings',
			'settings_pages' => 'curbside-pickup-settings',
			'tab'            => 'email',
			'fields' => array(
				array(
					'id' => 'heading_1',
					'type' => 'heading',
					'name' => esc_html__( 'Global Settings', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'use_woocommerce_mailer',
					'name' => esc_html__( 'Use WooCommerce Mailer', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Send emails using the WooCommerce mailer and templates.', 'curbside-pickup' ),
					'std' => class_exists( 'woocommerce' ) ? 1 : 0,
				),
				array(
					'id' => $prefix . 'include_email_signature',
					'name' => esc_html__( 'Include Signature', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Include a signature on all outbound emails', 'curbside-pickup' ),
					'std' => 1,
				),
				array(
					'id' => $prefix . 'global_email_signature',
					'type' => 'textarea',
					'name' => esc_html__( 'Email Signature:', 'curbside-pickup' ),
					'desc' => esc_html__( 'This text will be added to the bottom of all outbound emails.', 'curbside-pickup' ),
					'std' => __('Thank you!','curbside-pickup'),
					'rows' => 4,
				),
				array(
					'id' => 'heading_8',
					'type' => 'heading',
					'name' => esc_html__( 'Order Confirmation Emails', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'send_order_confirmation_email',
					'name' => esc_html__( 'Send Order Confirmation Emails', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Send an email to your customer when their order is received, containing the special pickup link.', 'curbside-pickup' ),
					'std' => 'true',
				),
				array(
					'id' => $prefix . 'emails_confirmation_subject',
					'type' => 'text',
					'name' => esc_html__( 'Subject', 'curbside-pickup' ),
					'std' => 'Your Pickup Has Been Scheduled',
					'desc' => esc_html__( 'Merge variables: {pickup_link}, {customer_name}, {customer_first_name}, {customer_last_name}, {order_number} {order_total}, {item_count}, {pickup_time}', 'curbside-pickup' ),
					'size' => 60,
				),
				array(
					'id' => $prefix . 'emails_confirmation_body',
					'type' => 'textarea',
					'name' => esc_html__( 'Email Body:', 'curbside-pickup' ),
					'desc' => esc_html__( 'Merge variables: {pickup_link}, {customer_name}, {customer_first_name}, {customer_last_name}, {order_number} {order_total}, {item_count}, {pickup_time}', 'curbside-pickup' ),
					'std' => 'Dear {customer_name},

Thank you for your order!

Your pickup is scheduled for {pickup_time}. When you arrive, please click the following link to let us know you are here. We\'ll come out to your car with your order as soon as possible.

{pickup_link}

See you soon!',
					'rows' => 12,
				),
				array(
					'id' => 'heading_9',
					'type' => 'heading',
					'name' => esc_html__( 'Pickup Rescheduled Emails', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'send_rescheduled_pickup_email',
					'name' => esc_html__( 'Send Rescheduled Pickup Emails', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Send an email to your customer when a pickup is rescheduled.', 'curbside-pickup' ),
					'std' => 'true',
				),
				array(
					'id' => $prefix . 'emails_rescheduled_pickup_subject',
					'type' => 'text',
					'name' => esc_html__( 'Subject', 'curbside-pickup' ),
					'std' => 'Your Pickup Has Been Rescheduled',
					'desc' => esc_html__( 'Merge variables: {pickup_link}, {customer_name}, {customer_first_name}, {customer_last_name}, {order_number} {order_total}, {item_count}, {pickup_time}', 'curbside-pickup' ),
					'size' => 60,
				),
				array(
					'id' => $prefix . 'emails_rescheduled_pickup_body',
					'type' => 'textarea',
					'name' => esc_html__( 'Email Body:', 'curbside-pickup' ),
					'desc' => esc_html__( 'Merge variables: {pickup_link}, {customer_name}, {customer_first_name}, {customer_last_name}, {order_number} {order_total}, {item_count}, {pickup_time}', 'curbside-pickup' ),
					'std' => 'Dear {customer_name},

Your pickup has been rescheduled for {pickup_time}. If this is not a good time for you, please give us a call.

When you arrive, please click the following link to let us know you are here. We\'ll come out to your car with your order as soon as possible.

{pickup_link}

See you soon!',
					'rows' => 12,
				),
				array(
					'id' => 'heading_9',
					'type' => 'heading',
					'name' => esc_html__( 'Completed Pickup Emails', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'send_completed_pickup_email',
					'name' => esc_html__( 'Send Completed Pickup Emails', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Send an email to your customers after their order has been successfully picked-up.', 'curbside-pickup' ),
					'std' => 'true',
				),
				array(
					'id' => $prefix . 'emails_completed_pickup_subject',
					'type' => 'text',
					'name' => esc_html__( 'Subject', 'curbside-pickup' ),
					'std' => 'Your Order Has Been Picked-Up. Thank you!',
					'desc' => esc_html__( 'Merge variables: {pickup_link}, {customer_name}, {customer_first_name}, {customer_last_name}, {order_number} {order_total}, {item_count}, {pickup_time}', 'curbside-pickup' ),
					'size' => 60,
				),
				array(
					'id' => $prefix . 'emails_completed_pickup_body',
					'type' => 'textarea',
					'name' => esc_html__( 'Email Body:', 'curbside-pickup' ),
					'desc' => esc_html__( 'Merge variables: {pickup_link}, {customer_name}, {customer_first_name}, {customer_last_name}, {order_number} {order_total}, {item_count}, {pickup_time}', 'curbside-pickup' ),
					'std' => 'Dear {customer_name},

Your order was just picked-up. If this is in error, please give us a call.

Thank you again for your business! We hope to see you again soon.',
					'rows' => 12,
				),
			),
		);

		$meta_boxes[] = array(
			'id'             => 'notifications',
			'title'          => 'Notifications',
			'settings_pages' => 'curbside-pickup-settings',
			'tab'            => 'notifications',
			'fields' => array(
				array(
					'id' => $prefix . 'emails_to_notify_new_orders',
					'type' => 'textarea',
					'name' => esc_html__( 'Emails To Notify on New Orders', 'curbside-pickup' ),
					'std' => get_bloginfo('admin_email'),
					'desc' => esc_html__( 'List of emails to notify when a new pickup is scheduled. Enter one email per line, or separate emails by commas or semicolons.', 'curbside-pickup' ),
					'rows' => 4,
				),
				array(
					'id' => $prefix . 'emails_to_notify_arrivals',
					'type' => 'textarea',
					'name' => esc_html__( 'Emails To Notify on Customer Arrival', 'curbside-pickup' ),
					'std' => get_bloginfo('admin_email'),
					'desc' => esc_html__( 'List of emails to notify when a customer checks in using their personal link. Enter one email per line, or separate emails by commas or semicolons.', 'curbside-pickup' ),
					'rows' => 4,
				),
			),
		);

		$meta_boxes[] = array(
			'id'             => 'settings_pickup_page',
			'title'          => 'Pickup Page Settings',
			'settings_pages' => 'curbside-pickup-settings',
			'tab'            => 'pickup-page',
			'fields' => array(
				array(
					'id' => $prefix . 'pickup_page_id',
					'type' => 'post',
					'name' => esc_html__( 'Pickup Page', 'curbside-pickup' ),
					'desc' => esc_html__( 'Please select a Page to use to display the Pick-Up form. Please be sure this page is not password protected or otherwise inaccessible.', 'curbside-pickup' ),
					'post_type' => 'page',
					'field_type' => 'select',
				),
				array(
					'id' => $prefix . 'pickup_page_heading',
					'type' => 'text',
					'name' => esc_html__( 'Pickup Page Heading', 'curbside-pickup' ),
					'std' => __( 'Curbside Pickup', 'curbside-pickup'),
					'desc' => esc_html__( 'The heading which should be displayed on your pickup page, above the pickup form.', 'curbside-pickup' ),
					'size' => 60,
				),
				array(
					'id' => $prefix . 'instructions_for_pickup',
					'type' => 'textarea',
					'name' => esc_html__( 'Instructions To Your Customer', 'curbside-pickup' ),
					'desc' => esc_html__( 'Please enter the message you would like to show customers on the page where they check-in for pickup.', 'curbside-pickup' ),
					'std' => 'Please complete the form below and then click the "Check In" button to let us know you are here.',
				),
				array(
					'id' => $prefix . 'check_in_confirmation_message',
					'type' => 'textarea',
					'name' => esc_html__( 'Check-In Confirmation Message', 'curbside-pickup' ),
					'desc' => esc_html__( 'Please enter the message you would like to show your customers after they are checked-in.', 'curbside-pickup' ),
					'std' => 'A member of our staff will come out to you shortly. Please remain in your vehicle.',
				),
				array(
					'id' => $prefix . 'too_early_message',
					'type' => 'textarea',
					'name' => esc_html__( 'Too Early To Check-In Message', 'curbside-pickup' ),
					'desc' => esc_html__( 'The message to show customers if they visit the Pickup Page before their check-in time.', 'curbside-pickup' ),
					'std' => __('Its still a bit early to check-in. Please refresh to this page when its closer to your pickup time.', 'curbside-pickup')
				),
				array(
					'id' => $prefix . 'too_late_message',
					'type' => 'textarea',
					'name' => esc_html__( 'Too Late To Check-In Message', 'curbside-pickup' ),
					'desc' => esc_html__( 'The message to show customers if they visit the Pickup Page after their pickup has been marked late.', 'curbside-pickup' ),
					'std' => __('Please contact us to reschedule your pickup.', 'curbside-pickup')
				),
				array(
					'id' => $prefix . 'pickup_error_message',
					'type' => 'textarea',
					'name' => esc_html__( 'Order Not Found Error Message', 'curbside-pickup' ),
					'desc' => esc_html__( 'The message shown to customers if their pickup link is not working. Usually your contact information should be included here.', 'curbside-pickup' ),
					'std' => __('We\'re sorry, but this order could not be found. Please contact us and we will be glad to help.', 'curbside-pickup')
				),
				array(
					'id' => $prefix . 'ask_for_vehicle_description',
					'name' => esc_html__( 'Ask For Vehicle Description', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Ask the customer for a description of their vehicle when they arrive', 'curbside-pickup' ),
					'std' => 'true',
					'std' => 1,
				),
				array(
					'id' => $prefix . 'ask_for_space_number',
					'name' => esc_html__( 'Ask For Space #', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Ask the customer for their space number when they arrive', 'curbside-pickup' ),
					'std' => 0,
				),
				array(
					'id' => $prefix . 'ask_for_customer_notes',
					'name' => esc_html__( 'Ask For Special Instructions', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Ask the customer for special instructions when they arrive', 'curbside-pickup' ),
					'std' => 1,
				),
			),
		);

		$meta_boxes[] = array(
			'id'             => 'dashboard',
			'title'          => 'Dashboard Settings',
			'settings_pages' => 'curbside-pickup-settings',
			'tab'            => 'dashboard',
			'fields'         => array(
				array(
					'id' => 'dashboard_notifications_heading',
					'type' => 'heading',
					'desc' => esc_html__( 'Control what notifications you received when the Dashboard page is open.', 'curbside-pickup' ),
					'name' => esc_html__( 'Dashboard Notifications', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'dashboard_play_sound_on_arrival',
					'name' => esc_html__( 'Audio Notifications', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Play a sound when a new customer arrives', 'curbside-pickup' ),
					'std' => 'true',
				),
				array(
					'id' => $prefix . 'dashboard_show_notification_on_arrival',
					'name' => esc_html__( 'Browser Notifications', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Display a browser notification when a new customer arrives', 'curbside-pickup' ),
					'std' => 'true',
				),
				array(
					'id' => $prefix . 'button_6',
					'type' => 'button',
					'std' => 'Enable Notifications',
					'name' => esc_html__( 'Enable Notifications', 'curbside-pickup' ),
					//'desc' => esc_html__( 'Click to be prompted to enable notifications from this website.', 'curbside-pickup' ),
					'attributes' => array(
						'class'        => 'curbside_pickup_btn_enable_permissions',
					),
				),
				array(
					'id' => 'dashboard_display_heading',
					'type' => 'heading',
					'desc' => esc_html__( 'Control which panels are visible on the Dashboard.', 'curbside-pickup' ),
					'name' => esc_html__( 'Panels To Display', 'curbside-pickup' ),
				),
				array(
					'id' => $prefix . 'dashboard_show_late_panel',
					'name' => esc_html__( 'Missed Pickup Time Panel', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Show the Missed Pickup Time Panel', 'curbside-pickup' ),
					'std' => 1,
				),
				array(
					'id' => $prefix . 'dashboard_show_in_window_panel',
					'name' => esc_html__( 'Expected Soon Panel', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Show the Expected Soon Panel', 'curbside-pickup' ),
					'std' => 1,
				),
				array(
					'id' => $prefix . 'dashboard_show_upcoming_panel',
					'name' => esc_html__( 'Scheduled Later Today Panel', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Show the Scheduled Later Today Panel', 'curbside-pickup' ),
					'std' => 1,
				),
				array(
					'id' => $prefix . 'dashboard_show_tomorrow_panel',
					'name' => esc_html__( 'Scheduled For Tomorrow Panel', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Show the Scheduled For Tomorrow Panel', 'curbside-pickup' ),
					'std' => 0,
				),
				/*
				array(
					'id' => $prefix . 'dashboard_show_current_stats',
					'name' => esc_html__( 'Current Stats Panel', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Show the Current Stats Panel', 'curbside-pickup' ),
					'std' => 'true',
				),
				array(
					'id' => $prefix . 'dashboard_show_daily_stats',
					'name' => esc_html__( 'Daily Stats Panel', 'curbside-pickup' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Show the Daily Stats Panel', 'curbside-pickup' ),
					'std' => 'true',
				),
				 */
			),
		);

		$meta_boxes[] = array(
			'id'             => 'info',
			'title'          => 'Get Help',
			'settings_pages' => 'curbside-pickup-settings',
			'tab'            => 'help',
			'fields'         => array(
				array(
					'type' => 'custom_html',
					'std'  => $this->get_help_tab_text(),
				),
			),
		);

		return $meta_boxes;

	}
	
	function get_help_tab_text()
	{
		$demo_content = new Demo_Content();
		$msg = '';
		$help_url = 'https://goldplugins.com/documentation/curbside-pickup-pro-documentation/?utm_source=plugin_help_tab&utm_campaign=get_help';
		$help_message = __('Find answers to common questions in our online documentation.', 'curbside-pickup');
		$upgrade_url = $demo_content->get_upgrade_url($campaign = 'docs', $medium = 'help_tab');
		$upgrade_message = __('Upgrade to Curbside Pickup Pro for technical support and powerful new features.', 'curbside-pickup');
		$support_url = 'https://goldplugins.com/contact/?utm_source=plugin_help_tab&utm_campaign=get_help&plugin=curbside-pickup-pro';
		$support_message = __('Contact Support', 'curbside-pickup');

		$msg .= sprintf('<p><a href="%s" target="_blank">%s</a></p>', $help_url, $help_message);
		if ( ! $this->is_pro() ) {		
			$msg .= sprintf('<p><a href="%s" target="_blank">%s</a></p>', $upgrade_url, $upgrade_message);
		}
		else {
			$msg .= sprintf('<p><strong>%s:</strong> <a href="%s" target="_blank">%s</a></p>', __('Pro Users', 'curbside-pickup'), $support_url, $support_message);
		}
		
		return $msg;
	}
	
	function get_store_address_from_wc()
	{
		if ( !class_exists( 'woocommerce' ) ) {
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
		return $this->br2nl($formatted_addr);
	}
}

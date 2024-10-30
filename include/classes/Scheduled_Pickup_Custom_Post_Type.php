<?php

namespace Curbside_Pickup;

class Scheduled_Pickup_Custom_Post_Type extends Base_Class
{
	function __construct()
	{
		$this->add_hooks();
	}

	function add_hooks()
	{
		add_action( 'init', array($this, 'create_cpt') );
		add_filter( 'rwmb_meta_boxes', array($this, 'create_custom_fields') );
		add_action( 'save_post_scheduled-pickup', array($this, 'maybe_set_post_title'), 10, 3 );
 	}

	function create_cpt()
	{
		$args = array (
			'label' => esc_html__( 'Scheduled Pickups', 'text-domain' ),
			'labels' => array(
				'menu_name' => esc_html__( 'Scheduled Pickups', 'text-domain' ),
				'name_admin_bar' => esc_html__( 'Scheduled Pickup', 'text-domain' ),
				'add_new' => esc_html__( 'Add new', 'text-domain' ),
				'add_new_item' => esc_html__( 'Add new Scheduled Pickup', 'text-domain' ),
				'new_item' => esc_html__( 'New Scheduled Pickup', 'text-domain' ),
				'edit_item' => esc_html__( 'Edit Scheduled Pickup', 'text-domain' ),
				'view_item' => esc_html__( 'View Scheduled Pickup', 'text-domain' ),
				'update_item' => esc_html__( 'Update Scheduled Pickup', 'text-domain' ),
				'all_items' => esc_html__( 'Scheduled Pickups', 'text-domain' ),
				'search_items' => esc_html__( 'Search Scheduled Pickups', 'text-domain' ),
				'parent_item_colon' => esc_html__( 'Parent Scheduled Pickup', 'text-domain' ),
				'not_found' => esc_html__( 'No Scheduled Pickups found', 'text-domain' ),
				'not_found_in_trash' => esc_html__( 'No Scheduled Pickups found in Trash', 'text-domain' ),
				'name' => esc_html__( 'Scheduled Pickups', 'text-domain' ),
				'singular_name' => esc_html__( 'Scheduled Pickup', 'text-domain' ),
			),
			'public' => false,
			'description' => 'One scheduled pickup order.',
			'exclude_from_search' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_nav_menus' => true,
			'show_in_menu' => 'curbside-pickup/curbside-pickup.php',
			'show_in_admin_bar' => true,
			'show_in_rest' => true,
			'menu_position' => 5,
			'menu_icon' => 'dashicons-calendar',
			'capability_type' => 'page',
			'hierarchical' => false,
			'has_archive' => false,
			'query_var' => true,
			'can_export' => true,
			'rewrite_no_front' => false,
			'supports' => array(
				'title',
				'revisions',
			),
			'rewrite' => true,
		);

		register_post_type( 'scheduled-pickup', $args );
 	}

	function create_custom_fields($meta_boxes)
	{
		$prefix = '';
		$fields = array(
			array (
				'id' => $prefix . 'pickup_information_fields_header',
				'type' => 'custom_html',
				'std' => sprintf(
							'<h3 style="margin:0 0 6px">%s</h3>%s<br><hr><br>',
							__('Scheduled Pickup', 'curbside-pickup'),
							__('Details about the customer and their order. This information will be automatically imported from WooCommerce when possible.', 'curbside-pickup')
						 ),
				'tab' => 'pickup_details',
			),
			array (
				'id' => $prefix . 'customer_name',
				'type' => 'text',
				'name' => esc_html__( 'Customer Name', 'text-domain' ),
				'required' => 1,
				'tab' => 'pickup_details',
				'desc' => __('Customer\'s full name.', 'text-domain'),
			),
			array (
				'id' => $prefix . 'customer_email',
				'name' => esc_html__( 'Customer Email', 'text-domain' ),
				'type' => 'email',
				'required' => 1,
				'tab' => 'pickup_details',
				'desc' => __('Customer\'s email address.', 'text-domain'),
			),
			array (
				'id' => $prefix . 'customer_phone',
				'name' => esc_html__( 'Customer Phone', 'text-domain' ),
				'type' => 'phone',
				'required' => 1,
				'tab' => 'pickup_details',
				'desc' => __('Customer\'s phone number.', 'text-domain'),
			),
			array (
				'id' => $prefix . 'order_id',
				'type' => 'text',
				'name' => esc_html__( 'Order ID', 'text-domain' ),
				'tab' => 'pickup_details',
				'desc' => __('WooCommerce Order ID', 'text-domain'),
			),
			array (
				'id' => $prefix . 'scheduled_delivery_time',
				'type' => 'datetime',
				'name' => esc_html__( 'Pickup Date & Time', 'text-domain' ),
				'js_options' => array(
					'pickerTimeFormat' => 'hh:mm tt',
				),
				'required' => 1,
				'tab' => 'pickup_details',
				'desc' => __('The customer\'s scheduled pickup time.', 'text-domain'),
			),
			array (
				'id' => $prefix . 'metadata_fields_header',
				'type' => 'custom_html',
				'std' => sprintf(
							'<h3 style="margin:0 0 6px">%s</h3>%s<br><hr><br>',
							__('Metadata', 'curbside-pickup'),
							__('This information is updated by the system. In most cases you should not modify it.', 'curbside-pickup')
						 ),
				'tab' => 'metadata',
			),
			array (
				'id' => $prefix . 'delivery_time',
				'type' => 'datetime',
				'name' => esc_html__( 'Delivery Time', 'text-domain' ),
				'readonly' => 1,
				'tab' => 'metadata',
				'desc' => __('Actual time of delivery.', 'text-domain'),
			),
			array (
				'id' => $prefix . 'status',
				'type' => 'taxonomy',
				'name' => esc_html__( 'Delivery Status', 'text-domain' ),
				'std' => 11,
				'taxonomy' => 'delivery-status',
				'field_type' => 'select',
				'tab' => 'metadata',
				'desc' => __('Status of the pickups. Undelivered orders are marked Pending.', 'text-domain'),
			),
			array (
				'id' => $prefix . 'arrived',
				'name' => esc_html__( 'Arrived', 'text-domain' ),
				'type' => 'checkbox',
				'desc' => esc_html__( 'Has the customer arrived for pickup.', 'text-domain' ),
				'tab' => 'metadata',
			),
			array (
				'id' => $prefix . 'arrival_time',
				'type' => 'datetime',
				'name' => esc_html__( 'Arrival Time', 'text-domain' ),
				'desc' => __('Time when the customer checked in.', 'text-domain'),
				'tab' => 'metadata',
			),
			array (
				'id' => $prefix . 'item_count',
				'type' => 'number',
				'name' => esc_html__( 'Item Count', 'text-domain' ),
				'desc' => __('The number of items in the order.', 'text-domain'),
				'tab' => 'pickup_details',
			),
			array (
				'id' => $prefix . 'order_total',
				'type' => 'text',
				'name' => esc_html__( 'Order Total', 'text-domain' ),
				'desc' => __('Total order value.', 'text-domain'),
				'tab' => 'pickup_details',
			),
			array (
				'id' => $prefix . 'notes',
				'type' => 'textarea',
				'name' => esc_html__( 'Notes', 'text-domain' ),
				'desc' => esc_html__( 'Internal notes. Not seen by the customer.', 'text-domain' ),
				'tab' => 'pickup_details',
			),
			array (
				'id' => $prefix . 'check_in_fields_header',
				'type' => 'custom_html',
				'std' => sprintf(
							'<h3 style="margin:0 0 6px">%s</h3>%s<br><hr><br>',
							__('Check-In Details', 'curbside-pickup'),
							__('This information will be provided by the customer when they check-in.', 'curbside-pickup')
						 ),
				'tab' => 'check_in',
			),
			array (
				'id' => $prefix . 'customer_notes',
				'type' => 'textarea',
				'name' => esc_html__( 'Customer Notes', 'text-domain' ),
				'tab' => 'check_in',
				'desc' => __('Notes/instructions left by the customer on the check-in form.', 'text-domain'),
			),
			array (
				'id' => $prefix . 'vehicle_description',
				'type' => 'textarea',
				'name' => esc_html__( 'Vehicle Description', 'text-domain' ),
				'desc' => __('Customer\'s description of their vehicle.', 'text-domain'),
				'tab' => 'check_in',
			),
			array (
				'id' => $prefix . 'space_number',
				'type' => 'text',
				'name' => esc_html__( 'Space Number', 'text-domain' ),
				'desc' => __('Space number (entered by customer on check-in form).', 'text-domain'),
				'tab' => 'check_in',
			),
			array (
				'id' => $prefix . 'log',
				'type' => 'textarea',
				'name' => esc_html__( 'Log', 'text-domain' ),
				'readonly' => 1,
				'desc' => __('Changes to this order, as logged by the system.', 'text-domain'),
				'tab' => 'metadata',
			),
			array (
				'id' => $prefix . 'order_information_fields_header',
				'type' => 'custom_html',
				'std' => sprintf(
							'<h3 style="margin:0 0 6px">%s</h3>%s<br><hr><br>',
							__('Order Details', 'curbside-pickup'),
							__('Information about the WooCommerce order (if present).', 'curbside-pickup')
						 ),
				'tab' => 'order_details',
			),
			array (
				'id' => $prefix . 'order_details_html',
				'type' => 'custom_html',
				'callback' => array($this, 'output_order_details_tab'),
				'tab' => 'order_details',
			),
		);
		
		// add delivery_location field if supported
		if ( $this->is_pro() ) {
			$insert = array ( 
				array (
					'id' => $prefix . 'delivery_location',
					'type' => 'post',
					'name' => esc_html__( 'Delivery Location', 'text-domain' ),
					'post_type' => array(
						0 => 'pickup-location',
					),
					'field_type' => 'select',
					'tab' => 'pickup_details',
					'desc' => __('For which location is the pickup scheduled.', 'text-domain'),
				) 
			);
			array_splice( $fields, 8, 0, $insert );						
		}
		
		$fields = apply_filters( 'curbside_pickup_schedule_pickup_custom_fields', $fields, $this->is_pro() );

		$meta_boxes[] = array (
			'title' => esc_html__( 'Pickup Settings', 'text-domain' ),
			'id' => 'pickup-settings',
			'post_types' => array(
				0 => 'scheduled-pickup',
			),
			'context' => 'normal',
			'priority' => 'high',
			'tabs'      => array(
				'pickup_details' => array(
					'label' => __('Scheduled Pickup', 'curbside-pickup'),
					'icon'  => 'dashicons-store',
				),
				'order_details'    => array(
					'label' => __('Order Details', 'curbside-pickup'),
					'icon'  => 'dashicons-cart',
				),
				'check_in'    => array(
					'label' => __('Check-In Details', 'curbside-pickup'),
					'icon'  => 'dashicons-yes-alt',
				),
				'metadata'  => array(
					'label' => __('Metadata', 'curbside-pickup'),
					'icon'  => 'dashicons-admin-generic',
				),
			),

			// Tab style: 'default', 'box' or 'left'. Optional
			'tab_style' => 'default',

			// Show meta box wrapper around tabs? true (default) or false. Optional
			'tab_wrapper' => false,
			'fields' => $fields,
		);

		return $meta_boxes;
	}

	function maybe_set_post_title($post_id, $post, $update)
	{
		$customer_name = get_post_meta($post_id, 'customer_name', true);
		$title = get_the_title($post_id);

		if ( empty($title) && !empty($customer_name) ) {
			$my_post = array(
				'ID'           => $post_id,
				'post_title'   => $customer_name,
			);

			// Update the post into the database
			wp_update_post( $my_post );
		}
	}
	
	function output_order_details_tab()
	{
		global $post;
		$pickup_id = $post->ID;
		
		// look for attched WooCommerce order
		$order_id = get_post_meta($pickup_id, 'order_id', true);
		if ( empty($order_id) ) {
			return sprintf( '<p><em>%s</em></p>', __('No WooCommerce order is associated with this pickup.') );
		}
		
		// load pickup/order metadata
		$customer_name = get_post_meta($pickup_id, 'customer_name', true);
		$customer_email = get_post_meta($pickup_id, 'customer_email', true);
		$pickup_time = get_post_meta($pickup_id, 'scheduled_delivery_time', true);
		$friendly_time = !empty($pickup_time)
						 ? date('F j, Y, g:i a', strtotime($pickup_time))
						 : '';
		$item_count = get_post_meta($pickup_id, 'item_count', true);
		$order_total = get_post_meta($pickup_id, 'order_total', true);
		$order_items = $this->list_order_line_items($pickup_id, $total_quantity);
		$order_details_rows = [
			__('Customer Name', 'curbside-pickup') => $customer_name,
			__('Customer Email', 'curbside-pickup') => $customer_email,
			__('Pickup Time', 'curbside-pickup') => $friendly_time,
			__('Order Total', 'curbside-pickup') => $order_total,
			__('Item Count', 'curbside-pickup') => $item_count,
		];

		// create the order details table
		$order_details = '<table class="curbside_pickup_order_table" style="width: 100%; max-width: 480px;" cellpadding="0" cellspacing="0">';
		$order_details .= '<tbody>';
		foreach($order_details_rows as $order_details_key => $order_details_value) {
			$order_details .= sprintf( '<tr><td width="200px"><strong>%s:</strong></td><td>%s</td></tr>',
									   $order_details_key,
									   esc_html($order_details_value) );
		}
		$order_details .= '</tbody>';
		$order_details .= '</table>';
		$order_details .= '<br>' .
						  '<h3>' . __('Order Items', 'curbside-pickup') . '</h3>' .
						  $order_items .
						  '<strong>' . __('Total', 'curbside-pickup') . ': </strong>' .
						  $total_quantity;


		// put the collected information inside a template
		$tmpl =
		'<div class="curbside_pickup_email_pickup_details">
			%s			
		</div>';

		$pickup_details = sprintf( $tmpl, $order_details );

		// allow filtering and return HTML
		return $pickup_details;
	}
	
	/*
	 * Lists all items from the WooCommerce order associated with this pickup.
	 *
	 * @param int $pickup_id ID (post ID) of the pickup.
	 * @param int $total_quantity (By ref) Optional variable to receive the total item quantity.
	 *
	 * @return string HTML list (ul) of items, on new lines. Empty string if none found.
	 */
	private function list_order_line_items(int $pickup_id, &$total_quantity = 0)
    {
		$items = [];
		$order_id = get_post_meta($pickup_id, 'order_id', true);
		if ( empty($order_id) ) {
			return '';
		}

		$order = wc_get_order( $order_id );
		if ( false == $order ) {
			return '';
		}
		$out = '<ul>';
		foreach ( $order->get_items() as  $item_key => $item_values ) {
			$item_data = $item_values->get_data();
			$out .= sprintf( '<li>%s  × %d</li>',
					esc_html($item_data['name']),
					$item_data['quantity'] );
			$total_quantity += $item_data['quantity'];
		}
		$out .= '</ul>';
		return $out;
    }
	
}

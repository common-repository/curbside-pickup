<?php

namespace Curbside_Pickup;

class Admin_UI extends Base_Class
{
	function __construct(\Curbside_Pickup\Dashboard $dashboard, $demo_content = false )
	{
		$this->Dashboard = $dashboard;
		$this->Demo_Content = $demo_content;
		$this->add_hooks();
	}

	function add_hooks()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'), 10);
		add_action('admin_menu', array($this, 'add_admin_submenus'), 20);
		add_action('admin_menu', array($this, 'reorder_admin_submenus'), 99);
		add_action('admin_menu', array($this, 'fix_admin_menu_permalinks'), 99);
		add_action('pre_get_posts', array($this, 'maybe_add_post_views') );
		add_action('init', array($this, 'add_taxonomy_terms') );
		add_filter( 'manage_scheduled-pickup_posts_columns', array($this, 'set_custom_columns') );
		add_action( 'manage_scheduled-pickup_posts_custom_column' , array($this, 'custom_column_content'), 10, 2 );
		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
		add_action( 'admin_footer', array($this, 'output_bootstrap_wrapper') );

		// admin CSS & JS
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_css') );
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_js') );

		add_action( 'curbside_pickup_new_pickup_created', array($this, 'save_pickup_link'), 10, 2 );

		if ( $this->is_pro() ) {
			add_filter( 'submenu_file', array($this, 'remove_demo_menu'), 10, 2 );
		}

	}

	function add_taxonomy_terms()
	{
		$this->add_delivery_status('Waiting', 'Customers who have arrived, but have not been served.');
		$this->add_delivery_status('Pending', 'Orders which have not been delivered.');
		$this->add_delivery_status('Late', 'Orders where the customer has not arrived and their window has passed.');
		$this->add_delivery_status('Delivered', 'Orders which been delivered to the customer.');
		$this->add_delivery_status('Canceled', 'Orders that were canceled.');
	}

	function add_admin_menu()
	{
		add_menu_page(
			'Curbside Pickup',
			'Curbside Pickup',
			'manage_options',
			'curbside-pickup/curbside-pickup.php',
			array($this, 'no_page'),
			'dashicons-store',
			6
		);

	}

	function add_admin_submenus()
	{
		add_submenu_page(
			'curbside-pickup/curbside-pickup.php', //'edit.php?post_type=scheduled-pickup',
			__( 'Dashboard', 'curbside-pickup' ),
			__( 'Dashboard', 'curbside-pickup' ),
			'manage_options',
			'curbside-pickup/dashboard',
			array($this->Dashboard, 'dashboard_page')
		);

		//$waiting_count = rand(1, 10);
		$waiting_count = $this->get_waiting_pickup_count();
		$counter_bubble = !empty($waiting_count)
						  ? sprintf(' <span class="awaiting-mod">%d</span>', $waiting_count)
						  : '';
		add_submenu_page(
			'curbside-pickup/curbside-pickup.php', //'edit.php?post_type=scheduled-pickup',
			__( 'Waiting Now', 'curbside-pickup' ),
			__( '&mdash; Waiting Now', 'curbside-pickup' ) . $counter_bubble,
			'manage_options',
			'curbside-pickup/pickups/waiting',
			array($this, 'no_page')
		);

		add_submenu_page(
			'curbside-pickup/curbside-pickup.php', //'edit.php?post_type=scheduled-pickup',
			__( 'Pending Pickups', 'curbside-pickup' ),
			__( '&mdash; Pending Pickups', 'curbside-pickup' ),
			'manage_options',
			'curbside-pickup/pickups/pending',
			array($this, 'no_page')
		);

		add_submenu_page(
			'curbside-pickup/curbside-pickup.php', //'edit.php?post_type=scheduled-pickup',
			__( 'Late Pickups', 'curbside-pickup' ),
			__( '&mdash; Late Pickups', 'curbside-pickup' ),
			'manage_options',
			'curbside-pickup/pickups/late',
			array($this, 'no_page')
		);

		add_submenu_page(
			'curbside-pickup/curbside-pickup.php', //'edit.php?post_type=scheduled-pickup',
			__( 'Delivered Pickups', 'curbside-pickup' ),
			__( '&mdash; Delivered Pickups', 'curbside-pickup' ),
			'manage_options',
			'curbside-pickup/pickups/delivered',
			array($this, 'no_page')
		);

		add_submenu_page(
			'curbside-pickup/curbside-pickup.php', //'edit.php?post_type=scheduled-pickup',
			__( 'Canceled Pickups', 'curbside-pickup' ),
			__( '&mdash; Canceled Pickups', 'curbside-pickup' ),
			'manage_options',
			'curbside-pickup/pickups/canceled',
			array($this, 'no_page')
		);

		add_submenu_page(
			'curbside-pickup/curbside-pickup.php', //'edit.php?post_type=scheduled-pickup',
			__( 'Pickup Locations', 'curbside-pickup' ),
			__( 'Pickup Locations', 'curbside-pickup' ),
			'manage_options',
			'curbside-pickup-pickup-locations',
			array($this->Demo_Content, 'pickup_locations_page')
		);
		
		if ( !$this->is_pro() ) {
			add_submenu_page(
				'curbside-pickup/curbside-pickup.php', //'edit.php?post_type=scheduled-pickup',
				__( 'Order List', 'curbside-pickup' ),
				__( 'Order List', 'curbside-pickup' ),
				'manage_options',
				'curbside-pickup-order-list',
				array($this->Demo_Content, 'order_list_page')
			);
			
			add_submenu_page(
				'curbside-pickup/curbside-pickup.php', //'edit.php?post_type=scheduled-pickup',
				__( 'Pick List', 'curbside-pickup' ),
				__( 'Pick List', 'curbside-pickup' ),
				'manage_options',
				'curbside-pickup-packing-list',
				array($this->Demo_Content, 'pick_list_page')
			);
		}		
		do_action('curbside_pickup_admin_menus', 'curbside-pickup/curbside-pickup.php');
	}
	
	function get_waiting_pickup_count()
	{
		$query = array(
			'post_type'   => 'scheduled-pickup',
			'tax_query' => array(
				array (
					'taxonomy' => 'delivery-status',
					'field' => 'slug',
					'terms' => 'waiting',
				)
			),
			'query_vars' => array(
				'leave_alone' => true
			)

		);
		$result = new \WP_Query($query);
		return $result->found_posts;
	}

	function remove_demo_menu($submenu_file, $parent_file)
	{		
		global $plugin_page;

		$hidden_submenus = array(
			'curbside-pickup-pickup-locations' => true,
		);

		// Select another submenu item to highlight (optional).
		if ( $plugin_page && isset( $hidden_submenus[ $plugin_page ] ) ) {
			$submenu_file = 'curbside-pickup/curbside-pickup.php';
		}

		// Hide the submenu.
		foreach ( $hidden_submenus as $submenu => $unused ) {
			remove_submenu_page( 'curbside-pickup/curbside-pickup.php', $submenu );
		}

		return $submenu_file;		
	}

	function fix_admin_menu_permalinks()
	{
		global $submenu;
		foreach($submenu['curbside-pickup/curbside-pickup.php'] as $index => $sm) {
			if ( false !== strpos($sm[2], 'curbside-pickup/pickups/') ) {
				$new_permalink = str_replace('curbside-pickup/pickups/', admin_url('edit.php?post_type=scheduled-pickup&delivery-status='), $sm[2]);
				$submenu['curbside-pickup/curbside-pickup.php'][$index][2] = $new_permalink;
			}
		}

	}

	function reorder_admin_submenus()
	{
		global $submenu;

		$this->move_admin_menu_to_position('Dashboard', 0);
		$this->move_admin_menu_to_position('Waiting Now', 2);
		$this->move_admin_menu_to_position('Pending Pickups', 3);
		$this->move_admin_menu_to_position('Late Pickups', 4);
		$this->move_admin_menu_to_position('Delivered Pickups', 5);
		$this->move_admin_menu_to_position('Canceled Pickups', 6);
		$this->move_admin_menu_to_position('Order List', 9);
		$this->move_admin_menu_to_position('Pick List', 10);
		$this->move_admin_menu_to_position('Lead Times', 11);
	}

	function move_admin_menu_to_position($label, $new_position)
	{
		global $submenu;

		// find dashboard page
		$pos = $this->find_submenu_pos_by_label($label);
		if ( empty($pos) ) {
			return;
		}

		// remove it
		$save_menu = $submenu['curbside-pickup/curbside-pickup.php'][$pos];
		unset($submenu['curbside-pickup/curbside-pickup.php'][$pos]);

		// reinsert it in desired position
		$this->array_put_to_position_numeric($submenu['curbside-pickup/curbside-pickup.php'], $save_menu, $new_position);
	}

	function maybe_add_post_views()
	{
		if( is_admin()) {
			add_filter( 'views_edit-scheduled-pickup', array($this, 'add_post_views') );
		}
	}

	function add_post_views($views)
	{
		$views = $this->add_post_status_filter('waiting', __('Waiting Now', 'curbside-pickup'), $views);
		$views = $this->add_post_status_filter('pending', __('Pending', 'curbside-pickup'), $views);
		$views = $this->add_post_status_filter('delivered', __('Delivered', 'curbside-pickup'), $views);
		$views = $this->add_post_status_filter('late', __('Late', 'curbside-pickup'), $views);
		$views = $this->add_post_status_filter('canceled', __('Canceled', 'curbside-pickup'), $views);
		unset($views['publish']);
		return $views;
	}

	function add_post_status_filter($status, $label, $views)
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
			'query_vars' => array(
				'leave_alone' => true
			)

		);
		$result = new \WP_Query($query);
		$current_status = filter_input(INPUT_GET, 'delivery-status', FILTER_SANITIZE_URL);
		$class = ($current_status == $status)
				 ? ' class="current"'
				 : '';

		$edit_url = admin_url('edit.php?post_type=scheduled-pickup&delivery-status=' . $status);
		$views[$status] = sprintf(__('<a href="%s"'. $class .'>%s <span class="count">(%d)</span></a>', 'curbside-pickup'),
			$edit_url,
			$label,
			$result->found_posts);
		return $views;
	}

	function add_delivery_status($status, $description = '')
	{
		$term = term_exists( $status, 'delivery-status' );
		if ( empty($term) ) {
			$slug = sanitize_title($status);
			wp_insert_term(
				$status,
				'delivery-status',
				array(
					'description' => $description,
					'slug'        => $slug
				)
			);
		}
	}

	function enqueue_admin_js()
	{
		wp_enqueue_script( 'curbside_pickup_bootstrap', plugins_url('../../assets/js/bootstrap.bundle.min.js', __FILE__), array( 'jquery' ), '', true );
		wp_enqueue_script( 'curbside_pickup_admin', plugins_url('../../assets/js/admin.js', __FILE__), array( 'jquery' ), '', true );
		$view_vars = [
			'ajaxurl' => admin_url('admin-ajax.php'),
			'msg_customer_arrived' => __('A customer has arrived!', 'curbside-pickup'),
			'msg_new_pickup' => __('A new order has been placed!', 'curbside-pickup'),
			'notification_sound_src' => plugins_url('../../assets/audio/music_box.mp3', __FILE__),
		];
		wp_localize_script( 'curbside_pickup_admin', 'curbside_pickup', $view_vars);
	}

	function enqueue_admin_css()
	{
		$bootstrap_url = plugins_url('../../assets/css/bootstrap_iso.css', __FILE__);
		wp_enqueue_style( 'curbside-pickup-bootstrap', $bootstrap_url );

		$fa_url = plugins_url('../../assets/font-awesome/css/all.min.css', __FILE__);
		wp_enqueue_style( 'curbside-pickup-fontawesome', $fa_url );

		$admin_css_url = plugins_url('../../assets/css/admin_style.css', __FILE__);
		wp_enqueue_style( 'curbside-pickup-admin', $admin_css_url );

		$admin_css_url = plugins_url('../../assets/css/demo_content.css', __FILE__);
		wp_register_style( 'curbside-pickup-demo_content', $admin_css_url );
	}

	function set_custom_columns($columns)
	{
		unset($columns['date']);
		$columns['delivery_time'] = __( 'Scheduled Pickup Time', 'curbside-pickup' );
		$columns['delivery_location'] = __( 'Pickup Location', 'curbside-pickup' );
		$columns['delivery_status'] = __( 'Status', 'curbside-pickup' );
		if ( $this->get_option_value('ask_for_vehicle_description', false) ) {
			$columns['vehicle_description'] = __( 'Vehicle Description', 'curbside-pickup' );
		}
		if ( $this->get_option_value('ask_for_space_number', false) ) {
			$columns['space_number'] = __( 'Space Number', 'curbside-pickup' );
		}		
		$columns['customer_notes'] = __( 'Customer Notes', 'curbside-pickup' );
		$columns['notes'] = __( 'Internal Notes', 'curbside-pickup' );
		$columns['update_pickup'] = __( 'Update Pickup', 'curbside-pickup' );
		return $columns;
	}

	// Add the data to the custom columns for the book post type:
	function custom_column_content( $column, $post_id )
	{
		$pickup = new Scheduled_Pickup($post_id);
		switch ( $column ) {

			case 'delivery_time' :
				$delivery_time = rwmb_meta('scheduled_delivery_time', array(), $post_id);
				if ( is_string( $delivery_time ) ) {
					echo $this->friendly_date($delivery_time);
				}
				break;

			case 'delivery_location' :
				$location_id = rwmb_meta('delivery_location', array(), $post_id);
				$location_name = !empty($location_id)
								 ? get_the_title($location_id)
								 : '';
				if ( is_string( $location_name ) ) {
					echo $location_name;
				}
				break;

			case 'delivery_status' :
				$this->print_linked_taxonomy_terms($post_id, 'delivery-status');
				break;

			case 'vehicle_description' :
				$vehicle_description = get_post_meta($post_id, 'vehicle_description', true);
				if ( is_string( $vehicle_description ) ) {
					echo $vehicle_description;
				}
				break;

			
			case 'space_number' :
				$space_number = get_post_meta($post_id, 'space_number', true);
				if ( is_string( $space_number ) ) {
					echo $space_number;
				}
				break;

			case 'customer_notes' :
				$customer_notes = get_post_meta($post_id, 'customer_notes', true);
				if ( is_string( $customer_notes ) ) {
					echo $customer_notes;
				}
				break;

			case 'notes' :
				$notes = get_post_meta($post_id, 'notes', true);
				if ( is_string( $notes ) ) {
					echo $notes;
				}
				break;

			case 'update_pickup' :
				echo $pickup->update_pickup_button();
				break;

			default:
			break;
		}
	}

	function no_page()
	{
	}

	function find_submenu_pos_by_label($label)
	{
		global $submenu;
		$top_level_slug = 'curbside-pickup/curbside-pickup.php';
		$found_index = -1;
		foreach( $submenu[$top_level_slug] as $index => $sm ) {
			$comp = str_replace('&mdash; ', '', $sm[0]);
			$comp = preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $comp); // rm tags and contents
			$comp = trim( $comp );
			if ( $label == $comp ) {
				return $index;
			}
		}
		return -1;
	}

	function print_linked_taxonomy_terms($post_id, $taxonomy)
	{
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( is_array( $terms ) ) {
			$term_count = 0;
			foreach($terms as $term) {
				if ($term_count > 0) {
					echo ', ';
				}
				$url = admin_url('edit.php?post_type=scheduled-pickup&' . $term->taxonomy . '=' . $term->slug);
				printf( '<a href="%s">%s</a>',
						$url,
						htmlentities($term->name) );

				$term_count++;
			}
		}
	}

	function array_put_to_position_numeric(&$array, $object, $position)
	{
			$count = 0;
			$return = array();
			foreach ($array as $k => $v)
			{
					// insert new object
					if ($count == $position)
					{
						$return[] = $object;
						$inserted = true;
						$count++;
					}
					// insert old object
					$return[] = $v;
					$count++;
			}
			$array = $return;
			return $array;
	}

	function add_meta_boxes()
	{
		add_meta_box(
            'curbside_pickup_quick_links',
            __('Quick Links', 'curbside-pickup'),
            array($this, 'output_quick_links_meta_box'),
            'scheduled-pickup',
			'side'
        );
	}

	function output_quick_links_meta_box()
	{
		global $post;
		printf( '<p><a href="%s">%s</a></p>',
			    $this->create_pickup_link($post->ID),
			    'Visit Customer\'s Pickup Page' );
				
		$order_id = get_post_meta($post->ID, 'order_id', true);
		if ( !empty($order_id) ) {
			printf( '<p><a href="%s">%s</a></p>',
					get_edit_post_link($order_id),
					'View WooCommerce Order' );
		}
	}
	
	function output_bootstrap_wrapper()
	{
		$screen = get_current_screen();
		if  ( false !== strpos($screen->id, 'curbside-pickup/dashboard') ) {
			return;
		}
		echo '<div class="curbside_pickup_bootstrap"></div>';
	}

	function save_pickup_link($pickup_id, $order_id)
	{
		$pickup_link = $this->create_pickup_link($pickup_id);
		update_post_meta($pickup_id, 'pickup_link', $pickup_link);
	}

	function create_pickup_link($pickup_id)
	{
		$pickup_page_id = $this->get_option_value('pickup_page_id');
		$pickup_page_url = get_the_permalink($pickup_page_id);
		$pickup_hash = get_post_meta($pickup_id, 'pickup_hash', true);
		$pickup_url = add_query_arg([
			'curbside_pickup_pickup_id' => $pickup_hash,
		], $pickup_page_url);
		return apply_filters('curbside_pickup_pickup_url', $pickup_url, $pickup_id, $pickup_page_id);
	}

	function friendly_date($str_time)
	{
		$format = "F j, Y, g:i a"; // e.g., April 20, 2020, 4:20 pm
		return date( $format, strtotime($str_time) );
	}

	function maybe_s($input, $to_add = 's', $alt = '')
	{
		if ( !empty($input) ) {
			return $to_add;
		} else {
			return $alt;
		}
	}

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

}

<?php

namespace Curbside_Pickup;

class Pickup_Schedule_Custom_Post_Type extends Base_Class
{
	function __construct()
	{
		$this->add_hooks();
	}

	function add_hooks()
	{
		add_action( 'init', array($this, 'create_cpt') );
		add_action( 'init', array($this, 'maybe_create_default_schedule') );
		add_filter( 'rwmb_meta_boxes', array($this, 'create_custom_fields') );
		add_filter( 'display_post_states', array($this, 'add_custom_post_state'), 10, 2 );
		add_filter( 'post_row_actions', array($this, 'add_post_row_action'), 10, 2 );
		add_action( 'admin_post_cspu_set_default_pickup_schedule', array($this, 'handle_set_default_pickup_schedule_action') );
		if ( isset($_REQUEST['cspu_schedule_update_success']) ) {
			add_action( 'admin_notices', array($this, 'show_default_schedule_update_admin_notices') );
		}
		
 	}

	function create_cpt()
	{
		$args = array (
			'label' => esc_html__( 'Pickup Schedules', 'text-domain' ),
			'labels' => array(
				'menu_name' => esc_html__( 'Pickup Schedules', 'text-domain' ),
				'name_admin_bar' => esc_html__( 'Pickup Schedule', 'text-domain' ),
				'add_new' => esc_html__( 'Add new', 'text-domain' ),
				'add_new_item' => esc_html__( 'Add new Pickup Schedule', 'text-domain' ),
				'new_item' => esc_html__( 'New Pickup Schedule', 'text-domain' ),
				'edit_item' => esc_html__( 'Edit Pickup Schedule', 'text-domain' ),
				'view_item' => esc_html__( 'View Pickup Schedule', 'text-domain' ),
				'update_item' => esc_html__( 'Update Pickup Schedule', 'text-domain' ),
				'all_items' => esc_html__( 'Pickup Schedules', 'text-domain' ),
				'search_items' => esc_html__( 'Search Pickup Schedules', 'text-domain' ),
				'parent_item_colon' => esc_html__( 'Parent Pickup Schedule', 'text-domain' ),
				'not_found' => esc_html__( 'No Pickup Schedules found', 'text-domain' ),
				'not_found_in_trash' => esc_html__( 'No Pickup Schedules found in Trash', 'text-domain' ),
				'name' => esc_html__( 'Pickup Schedules', 'text-domain' ),
				'singular_name' => esc_html__( 'Pickup Schedule', 'text-domain' ),
			),
			'public' => false,
			'description' => 'A schedule of hours available for pickup.',
			'exclude_from_search' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_nav_menus' => true,
			'show_in_menu' => 'curbside-pickup/curbside-pickup.php',
			'show_in_admin_bar' => true,
			'show_in_rest' => true,
			'menu_icon' => 'dashicons-backup',
			'capability_type' => 'post',
			'hierarchical' => false,
			'has_archive' => false,
			'query_var' => true,
			'can_export' => true,
			'rewrite_no_front' => false,
			'supports' => array(
				'title',
				'thumbnail',
			),
			'rewrite' => true,
		);

		register_post_type( 'pickup-schedule', $args );
 	}

	function create_custom_fields($meta_boxes)
	{
		$prefix = '';

		$meta_boxes[] = array (
				'title' => esc_html__( 'Pickup Schedules', 'text-domain' ),
				'id' => 'pickup-schedules',
				'post_types' => array(
					0 => 'pickup-schedule',
				),
				'context' => 'normal',
				'priority' => 'high',

				'tabs'      => array(
					'daily_hours' => array(
						'label' => __('Hours', 'curbside-pickup'),
						'icon'  => 'dashicons-clock',
					),
					'holidays'  => array(
						'label' => __('Holidays', 'curbside-pickup'),
						'icon'  => 'dashicons-buddicons-community',
					),
				),

				// Tab style: 'default', 'box' or 'left'. Optional
				'tab_style' => 'default',

				// Show meta box wrapper around tabs? true (default) or false. Optional
				'tab_wrapper' => false,

				'fields' => array(
					array (
						'id' => $prefix . 'hours_monday',
						'type' => 'group',
						'name' => esc_html__( 'Monday Hours', 'text-domain' ),
						'fields' => array(
							array (
								'id' => $prefix . 'open_monday',
								'name' => esc_html__( 'Open', 'text-domain' ),
								'type' => 'time',
								'std' => '09:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
							array (
								'id' => $prefix . 'close_monday',
								'name' => esc_html__( 'Close', 'text-domain' ),
								'type' => 'time',
								'std' => '17:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
						),
						'clone' => 1,
						'default_state' => 'expanded',
						'clone_default' => 1,
						'tab' => 'daily_hours',
					),
					array (
						'id' => $prefix . 'hours_tuesday',
						'type' => 'group',
						'name' => esc_html__( 'Tuesday Hours', 'text-domain' ),
						'fields' => array(
							array (
								'id' => $prefix . 'open_tuesday',
								'name' => esc_html__( 'Open', 'text-domain' ),
								'type' => 'time',
								'std' => '09:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
							array (
								'id' => $prefix . 'close_tuesday',
								'name' => esc_html__( 'Close', 'text-domain' ),
								'type' => 'time',
								'std' => '17:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
						),
						'clone' => 1,
						'default_state' => 'expanded',
						'clone_default' => 1,
						'tab' => 'daily_hours',
					),
					array (
						'id' => $prefix . 'hours_wednesday',
						'type' => 'group',
						'name' => esc_html__( 'Wednesday Hours', 'text-domain' ),
						'fields' => array(
							array (
								'id' => $prefix . 'open_wednesday',
								'name' => esc_html__( 'Open', 'text-domain' ),
								'type' => 'time',
								'std' => '09:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
							array (
								'id' => $prefix . 'close_wednesday',
								'name' => esc_html__( 'Close', 'text-domain' ),
								'type' => 'time',
								'std' => '17:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
						),
						'clone' => 1,
						'default_state' => 'expanded',
						'clone_default' => 1,
						'tab' => 'daily_hours',
					),
					array (
						'id' => $prefix . 'hours_thursday',
						'type' => 'group',
						'name' => esc_html__( 'Thursday Hours', 'text-domain' ),
						'fields' => array(
							array (
								'id' => $prefix . 'open_thursday',
								'name' => esc_html__( 'Open', 'text-domain' ),
								'type' => 'time',
								'std' => '09:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
							array (
								'id' => $prefix . 'close_thursday',
								'name' => esc_html__( 'Close', 'text-domain' ),
								'type' => 'time',
								'std' => '17:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
						),
						'clone' => 1,
						'default_state' => 'expanded',
						'clone_default' => 1,
						'tab' => 'daily_hours',
					),
					array (
						'id' => $prefix . 'hours_friday',
						'type' => 'group',
						'name' => esc_html__( 'Friday Hours', 'text-domain' ),
						'fields' => array(
							array (
								'id' => $prefix . 'open_friday',
								'name' => esc_html__( 'Open', 'text-domain' ),
								'type' => 'time',
								'std' => '09:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
							array (
								'id' => $prefix . 'close_friday',
								'name' => esc_html__( 'Close', 'text-domain' ),
								'type' => 'time',
								'std' => '17:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
						),
						'clone' => 1,
						'default_state' => 'expanded',
						'clone_default' => 1,
						'tab' => 'daily_hours',
					),
					array (
						'id' => $prefix . 'hours_saturday',
						'type' => 'group',
						'name' => esc_html__( 'Saturday Hours', 'text-domain' ),
						'fields' => array(
							array (
								'id' => $prefix . 'open_saturday',
								'name' => esc_html__( 'Open', 'text-domain' ),
								'type' => 'time',
								'std' => '09:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
							array (
								'id' => $prefix . 'close_saturday',
								'name' => esc_html__( 'Close', 'text-domain' ),
								'type' => 'time',
								'std' => '17:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
						),
						'clone' => 1,
						'default_state' => 'expanded',
						'clone_default' => 1,
						'tab' => 'daily_hours',
					),
					array (
						'id' => $prefix . 'hours_sunday',
						'type' => 'group',
						'name' => esc_html__( 'Sunday Hours', 'text-domain' ),
						'fields' => array(
							array (
								'id' => $prefix . 'open_sunday',
								'name' => esc_html__( 'Open', 'text-domain' ),
								'type' => 'time',
								'std' => '09:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
							array (
								'id' => $prefix . 'close_sunday',
								'name' => esc_html__( 'Close', 'text-domain' ),
								'type' => 'time',
								'std' => '17:00',
								'js_options' => array(
									'pickerTimeFormat' => 'hh:mm tt',
								),
								'required' => 1,
								'add_button' => esc_html__( 'Add another interval', 'text-domain' ),
							),
						),
						'clone' => 1,
						'default_state' => 'expanded',
						'clone_default' => 1,
						'tab' => 'daily_hours',
					),
					array (
						'id' => $prefix . 'holidays',
						'type' => 'group',
						'name' => esc_html__( 'Holidays', 'text-domain' ),
						'fields' => array(
							array (
								'id' => $prefix . 'holiday_date',
								'type' => 'date',
								'name' => esc_html__( 'Select A Date', 'text-domain' ),
								'clone' => 1,
								'add_button' => esc_html__( 'Add another date', 'text-domain' ),
							),
						),
						'default_state' => 'expanded',
						'desc' => esc_html__( 'Days when this location is closed entirely.', 'text-domain' ),
						'tab' => 'holidays',
					),
				),
			);

			return $meta_boxes;
	}

	function maybe_create_default_schedule()
	{
		$schedule_count = wp_count_posts( 'pickup-schedule' );
		if ( empty($schedule_count->publish) ) {
			// no schedules, so create a default schedule
			$meta = [];
			$days_of_the_week = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
			foreach($days_of_the_week as $day_name) {
				$day_key = sprintf('hours_%s', $day_name);
				$open_key = sprintf('open_%s', $day_name);
				$close_key = sprintf('close_%s', $day_name);
				$meta[$day_key][0][$open_key] = '09:00';
				$meta[$day_key][0][$close_key] = '17:00';
				$meta['default_schedule'] = '1';
			}
			wp_insert_post([
				'post_type' => 'pickup-schedule',
				'post_title' => __('Normal Hours', 'curbside-pickup'),
				'post_content' => '',
				'post_status' => 'publish',
				'meta_input' => $meta
			]);
		}
	}

	function handle_set_default_pickup_schedule_action()
	{
		// set the detault schedule
		$new_default_id = !empty($_REQUEST['cspu_default_schedule_id'])
						  ? intval($_REQUEST['cspu_default_schedule_id'])
						  : 0;
		$update_success = 0;
		
		if ( !empty($new_default_id) ) {
			$update_success = $this->set_default_schedule($new_default_id)
							  ? 1
							  : 0;
		}

		// redirect back to the schedules page
		wp_safe_redirect( admin_url('edit.php?post_type=pickup-schedule&cspu_schedule_update_success=' . $update_success) );
		exit();
	}
	
	function show_default_schedule_update_admin_notices()
	{
		$status = !empty($_REQUEST['cspu_schedule_update_success'])
				  ? intval($_REQUEST['cspu_schedule_update_success'])
				  : 0;
		if ( !empty($status) ) {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php _e( 'The default schedule has been updated.', 'curbside-pickup' ); ?></p>
		</div>
		<?php		
		}
		else {
			$class = 'notice notice-error';
			$message = __( 'Error: The default schedule could not be updated.', 'curbside-pickup' );
			printf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}
	
	function add_post_row_action($actions, $post)
	{

		//check for your post type
		if ('pickup-schedule' == $post->post_type ) {

			// don't show the Make Default Schedule action for unpublished posts
			if ( 'publish' != get_post_status($post->ID) ) {
				return $actions;
			}

			$meta = get_post_meta($post->ID, 'default_schedule', true);
			if ( empty($meta) ) {
				$admin_url = admin_url('admin-post.php?action=cspu_set_default_pickup_schedule&cspu_default_schedule_id=' . $post->ID);
				$action_link = sprintf( '<a href="%s">%s</a>', $admin_url, __('Make Default Schedule', 'curbside-pickup') );
				$actions['set_default_schedule'] = $action_link;
			}
		}
		return $actions;
	}
	
	function set_default_schedule($post_id)
	{
		// make sure the new schedule id is a published post
		if ( 'publish' != get_post_status($post_id) ) {
			// cant set the default schedule to an unpublished schedule
			return false;
		}
		
		// remove the meta flag from the current default schedule
		$this->clear_default_schedule_flags();

		// assign it to the new default schedule
		return update_post_meta($post_id, 'default_schedule', '1');
	}
	
	function clear_default_schedule_flags()
	{
		// remove the meta flag from the current default schedule
		$args = array(
			'post_type'     => 'pickup-schedule',
			'post_status'   => 'any',
			'meta_query' => array(
				array(
					'key' => 'default_schedule',
					//'value' => '1'
				)
			)
		);

		$matches = get_posts($args);
		if ( !empty($matches) ) {
			foreach($matches as $match) {
				delete_post_meta($match->ID, 'default_schedule');
			}
		}
	}

	function add_custom_post_state($states, $post)
	{
		if ( ( 'pickup-schedule' == get_post_type( $post->ID ) ) ) {
			$meta = get_post_meta($post->ID, 'default_schedule', true);
			if ( !empty($meta) ) {
				$states[] = __('Default Schedule');
			}
		}
		return $states;
	}
}

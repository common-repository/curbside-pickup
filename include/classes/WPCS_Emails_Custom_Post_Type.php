<?php

namespace Curbside_Pickup;

class WPCS_Emails_Custom_Post_Type extends Base_Class
{
	function __construct()
	{
		$this->add_hooks();
	}

	function add_hooks()
	{
		add_action( 'init', array($this, 'create_cpt') );
		add_filter( 'rwmb_meta_boxes', array($this, 'create_custom_fields') );
 	}

	function create_cpt()
	{
		$args = array (
			'label' => esc_html__( 'WPCS Emails', 'text-domain' ),
			'labels' => array(
				'menu_name' => esc_html__( 'WPCS Emails', 'text-domain' ),
				'name_admin_bar' => esc_html__( 'WPCS Email', 'text-domain' ),
				'add_new' => esc_html__( 'Add new', 'text-domain' ),
				'add_new_item' => esc_html__( 'Add new WPCS Email', 'text-domain' ),
				'new_item' => esc_html__( 'New WPCS Email', 'text-domain' ),
				'edit_item' => esc_html__( 'Edit WPCS Email', 'text-domain' ),
				'view_item' => esc_html__( 'View WPCS Email', 'text-domain' ),
				'update_item' => esc_html__( 'Update WPCS Email', 'text-domain' ),
				'all_items' => esc_html__( 'All WPCS Emails', 'text-domain' ),
				'search_items' => esc_html__( 'Search WPCS Emails', 'text-domain' ),
				'parent_item_colon' => esc_html__( 'Parent WPCS Email', 'text-domain' ),
				'not_found' => esc_html__( 'No WPCS Emails found', 'text-domain' ),
				'not_found_in_trash' => esc_html__( 'No WPCS Emails found in Trash', 'text-domain' ),
				'name' => esc_html__( 'WPCS Emails', 'text-domain' ),
				'singular_name' => esc_html__( 'WPCS Email', 'text-domain' ),
			),
			'public' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'show_ui' => false,
			'show_in_nav_menus' => false,
			'show_in_menu' => 'curbside-pickup/curbside-pickup.php',
			'show_in_admin_bar' => false,
			'show_in_rest' => false,
			'menu_icon' => 'dashicons-email-alt',
			'capability_type' => 'post',
			'hierarchical' => false,
			'has_archive' => false,
			'query_var' => false,
			'can_export' => false,
			'rewrite_no_front' => false,
			'supports' => array(
				'title',
			),
			'rewrite' => true,
		);

		register_post_type( 'cspu-email', $args );
 	}
	
	function create_custom_fields($meta_boxes)
	{
		$prefix = '';

		$meta_boxes[] = array (
			'title' => esc_html__( 'WPCS Email Fields', 'text-domain' ),
			'id' => 'cspu-email-fields',
			'post_types' => array(
				0 => 'cspu-email',
			),
			'context' => 'normal',
			'priority' => 'high',
			'fields' => array(
				array (
					'id' => $prefix . 'scheduled_pickup_id',
					'type' => 'number',
					'name' => esc_html__( 'Scheduled Pickup ID', 'text-domain' ),
				),
				array (
					'id' => $prefix . 'email_to',
					'type' => 'text',
					'name' => esc_html__( 'To', 'text-domain' ),
				),
				array (
					'id' => $prefix . 'email_subject',
					'type' => 'text',
					'name' => esc_html__( 'Email Subject', 'text-domain' ),
					'required' => 1,
					'readonly' => 1,
				),
				array (
					'id' => $prefix . 'email_body',
					'type' => 'textarea',
					'name' => esc_html__( 'Email Body', 'text-domain' ),
				),
				array (
					'id' => $prefix . 'email_id',
					'type' => 'text',
					'name' => esc_html__( 'Email ID', 'text-domain' ),
					'readonly' => 1,
				),
				array (
					'id' => $prefix . 'status',
					'type' => 'text',
					'name' => esc_html__( 'Status', 'text-domain' ),
					'readonly' => 1,
				),
				array (
					'id' => $prefix . 'time_sent',
					'type' => 'datetime',
					'name' => esc_html__( 'Time Sent', 'text-domain' ),
					'readonly' => 1,
				),
				array (
					'id' => $prefix . 'error_log',
					'type' => 'textarea',
					'name' => esc_html__( 'Error Log', 'text-domain' ),
					'readonly' => 1,
				),
			),
		);

		return $meta_boxes;		
	}
}

<?php

namespace Curbside_Pickup;

class Delivery_Status_Taxonomy extends Base_Class
{

	function __construct()
	{
		$this->add_hooks();
	}

	function add_hooks()
	{
		add_action( 'init', array($this, 'register_taxonomy'), 0 );
	}
	
	function register_taxonomy()
	{
		$args = array (
			'label' => esc_html__( 'Delivery Status', 'text-domain' ),
			'labels' => array(
				'menu_name' => esc_html__( 'Delivery Status', 'text-domain' ),
				'all_items' => esc_html__( 'All Delivery Status', 'text-domain' ),
				'edit_item' => esc_html__( 'Edit Delivery Status', 'text-domain' ),
				'view_item' => esc_html__( 'View Delivery Status', 'text-domain' ),
				'update_item' => esc_html__( 'Update Delivery Status', 'text-domain' ),
				'add_new_item' => esc_html__( 'Add new Delivery Status', 'text-domain' ),
				'new_item_name' => esc_html__( 'New Delivery Status', 'text-domain' ),
				'parent_item' => esc_html__( 'Parent Delivery Status', 'text-domain' ),
				'parent_item_colon' => esc_html__( 'Parent Delivery Status:', 'text-domain' ),
				'search_items' => esc_html__( 'Search Delivery Status', 'text-domain' ),
				'popular_items' => esc_html__( 'Popular Delivery Status', 'text-domain' ),
				'separate_items_with_commas' => esc_html__( 'Separate Delivery Status with commas', 'text-domain' ),
				'add_or_remove_items' => esc_html__( 'Add or remove Delivery Status', 'text-domain' ),
				'choose_from_most_used' => esc_html__( 'Choose most used Delivery Status', 'text-domain' ),
				'not_found' => esc_html__( 'No Delivery Status found', 'text-domain' ),
				'name' => esc_html__( 'Delivery Status', 'text-domain' ),
				'singular_name' => esc_html__( 'Delivery Status', 'text-domain' ),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud' => false,
			'show_in_quick_edit' => true,
			'show_admin_column' => false,
			'show_in_rest' => true,
			'hierarchical' => false,
			'query_var' => true,
			'sort' => false,
			'rewrite_no_front' => false,
			'rewrite_hierarchical' => false,
			'rewrite' => true,
		);

		register_taxonomy( 'delivery-status', array( 'scheduled-pickup' ), $args );	
	}
}
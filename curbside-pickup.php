<?php
/*
 * Plugin Name: Curbside Pickup
 * Plugin Script: curbside-pickup.php
 * Plugin URI: https://goldplugins.com/our-plugins/curbside-pickup/
 * Description: Add Curbside Pickup (Takeout) to your WooCommerce website.
 * Version: 2.1
 * WC requires at least: 4.2.0
 * WC tested up to: 5.4.1
 * Author: Gold Plugins
 * Author URI: https://goldplugins.com/
*/

namespace Curbside_Pickup;
require_once('vendor/autoload.php');
require_once('include/requires.php');
/* require_once('include/debug.php'); */

class Curbside_Pickup_Plugin
{
	var $plugin_title = 'Curbside Pickup';
	var $prefix = 'curbside_pickup';
	var $proUser = false;

	function __construct()
	{
		$this->Scheduled_Pickup_Custom_Post_Type = new Scheduled_Pickup_Custom_Post_Type();
		$this->Pickup_Schedule_Custom_Post_Type = new Pickup_Schedule_Custom_Post_Type();
		$this->Delivery_Status_Taxonomy = new Delivery_Status_Taxonomy();
		$this->WPCS_Emails_Custom_Post_Type = new WPCS_Emails_Custom_Post_Type();
		$this->Front_End = new Front_End( new Scheduler() );
		$this->Manager = new Manager();
		$this->Emails = new Emails();
		$this->Scheduled_Emails = new Scheduled_Emails($this->Emails);
		$this->Dashboard = new Dashboard( $this->Manager, new Scheduler() );
		$this->Pickup_Form = new Pickup_Form( $this->Manager );
		$this->Demo_Content = new Demo_Content();
		$this->Admin_Email_Notifications = new Admin_Email_Notifications();
		$this->Admin_UI = new Admin_UI( $this->Dashboard, $this->Demo_Content );
		$this->Settings = new Settings();
		$this->Cron = new Cron( $this->Manager );
		$this->add_hooks();
		$this->setup_welcome_screen();
	}
	
	function init_widgets()
	{
		$all_locations = apply_filters('curbside_pickup_get_wp_dashboard_locations', []);
		if ( ! empty($all_locations) ) {
			foreach($all_locations as $location) {
				$this->WP_Dashboard_Status_Widget = new WP_Dashboard_Status_Widget( $this->Manager, ['location_id' => $location->ID] );
			}
		}
		else {
			$this->WP_Dashboard_Status_Widget = new WP_Dashboard_Status_Widget( $this->Manager );
		}
		$this->WP_Dashboard_Customers_Waiting_Widget = new WP_Dashboard_Customers_Waiting_Widget( $this->Manager );
		$this->WP_Dashboard_Late_Customers_Widget = new WP_Dashboard_Late_Customers_Widget( $this->Manager );
		$this->WP_Dashboard_Upcoming_Customers_Widget = new WP_Dashboard_Upcoming_Customers_Widget( $this->Manager );
		$this->WP_Dashboard_Recent_Deliveries_Widget = new WP_Dashboard_Recent_Deliveries_Widget( $this->Manager );
	}

	function add_hooks()
	{
		add_action( 'init', array($this, 'init_widgets') );
		
		// TODO: move code for creating CPTs from metabox.io plugin to here
		// add_filter( 'init', array($this, 'create_custom_post_types') );

		// save pickup hash
		add_action( 'save_post', array($this, 'maybe_set_pickup_hash') );

		// run some actions on plugin activation
		register_activation_hook( __FILE__, array($this, 'activation_hook' ));

		// clean up when plugin is deactivated
		register_deactivation_hook( __FILE__, array($this->Cron, 'deactivate_hook') );
 	}

	function activation_hook()
	{
		// make sure the welcome screen gets seen again
		if ( !empty($this->Aloha) ) {
			$this->Aloha->reset_welcome_screen();
		}
	}

	function setup_welcome_screen()
	{
		if ( is_admin() ) {
			// load Aloha
			$config = array(
				'menu_label' => __('About Plugin'),
				'page_title' => __('Welcome To Curbside Pickup'),
				'tagline' => __('Curbside Pickup lets you provide a complete curbside pickup experience for your customers.'),
				'top_level_menu' => 'curbside-pickup/curbside-pickup.php',
			);
			$this->Aloha = new GP_Aloha($config);
			add_filter( 'gp_aloha_welcome_page_content_curbside-pickup/curbside-pickup.php', array($this, 'get_welcome_template') );
		}
	}

	function create_custom_post_types()
	{
		// TODO: Move all the init code from metabox.io plugin to here

		// hook for add-ons
		do_action('curbside_pickup_create_custom_post_types');
	}

	function maybe_set_pickup_hash($pickup_id)
	{
		// only modify our CPT
		$post_type = get_post_type($pickup_id);
		if ( 'scheduled-pickup' != $post_type ) {
			return;
		}

		// set random hash if needed
		$pickup_hash = get_post_meta($pickup_id, 'pickup_hash', true);
		if ( empty($pickup_hash) ) {
			$pickup_hash = md5( $pickup_id . '_curbside_pickup' . rand() );
			update_post_meta($pickup_id, 'pickup_hash', $pickup_hash);
		}
	}

	function get_welcome_template()
	{
		$base_path = plugin_dir_path( __FILE__ );
		$template_path = $base_path . '/include/content/welcome.php';
		$is_pro = $this->Settings->is_pro();
		$content = file_exists($template_path)
				   ? include($template_path)
				   : '';
		return $content;
	}
}

$curbside_pickup_plugin_obj = new Curbside_Pickup_Plugin();

// Initialize any addons now
do_action('curbside_pickup_bootstrap');

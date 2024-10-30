<?php
/*
 * Plugin Name: Curbside Pickup
 * Plugin Script: curbside-pickup.php
 * Description: Add Curbside Pickup (Takeout) to your WooCommerce website.
 * Version: 1.20
 * WC requires at least: 4.2.0
 * WC tested up to: 4.6.1
 * Author: Gold Plugins
 * Author URI: https://goldplugins.com/
*/

namespace Curbside_Pickup;
require_once('polyfills.php');
require_once('global_funcs.php');
require_once('wc_hooks.php');
require_once('controls/Location_Selector.php');
require_once('classes/Base_Class.php');
require_once('classes/Colors.php');
require_once('classes/Scheduled_Pickup.php');
require_once('classes/Scheduled_Pickup_Custom_Post_Type.php');
require_once('classes/Pickup_Schedule_Custom_Post_Type.php');
require_once('classes/WPCS_Emails_Custom_Post_Type.php');
require_once('classes/Delivery_Status_Taxonomy.php');
require_once('classes/Front_End.php');
require_once('classes/Admin_Email_Notifications.php');
require_once('classes/Admin_UI.php');
require_once('classes/Manager.php');
require_once('classes/Dashboard.php');
require_once('classes/Dashboard_Card.php');
require_once('classes/Update_Pickup_Modal.php');
require_once('classes/Emails.php');
require_once('classes/Scheduled_Emails.php');
require_once('classes/Pickup_Form.php');
require_once('classes/Settings.php');
require_once('classes/Scheduler.php');
require_once('classes/Cron.php');
require_once('classes/Curbside_Pickup_Shipping_Method.php');
require_once('classes/Demo_Content.php');
require_once('classes/wp-dashboard-widgets/WP_Dashboard_Widget.php');
require_once('classes/wp-dashboard-widgets/WP_Dashboard_Status_Widget.php');
require_once('classes/wp-dashboard-widgets/WP_Dashboard_Customers_Widget.php');
require_once('classes/wp-dashboard-widgets/WP_Dashboard_Customers_Waiting_Widget.php');
require_once('classes/wp-dashboard-widgets/WP_Dashboard_Late_Customers_Widget.php');
require_once('classes/wp-dashboard-widgets/WP_Dashboard_Upcoming_Customers_Widget.php');
require_once('classes/wp-dashboard-widgets/WP_Dashboard_Recent_Deliveries_Widget.php');
require_once('lib/GP_Aloha/gp_aloha.class.php');

<?php

namespace Curbside_Pickup;

class WP_Dashboard_Late_Customers_Widget extends WP_Dashboard_Customers_Widget
{
	var $widget_slug = 'curbside_pickup_wp_dashboard_late_customers_widget';
	
	function get_widget_title()
	{
		return 	'Curbside Pickup - ' . __('Late Customers', 'curbside-pickup');
	}	

	function load_customers()
	{
		return $this->Manager->get_orders_by_status('late');
	}
	
	function show_arrival_column()
	{
		return false;
	}
	 
	function get_no_customers_message()
	{
		return __('No customers missed their pickup times today.', 'curbside-pickup');
	}	 
}

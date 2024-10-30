<?php

namespace Curbside_Pickup;

class WP_Dashboard_Customers_Waiting_Widget extends WP_Dashboard_Customers_Widget
{
	var $widget_slug = 'curbside_pickup_wp_dashboard_customers_waiting_widget';
	
	function get_widget_title()
	{
		return 	'Curbside Pickup - ' . __('Customers Waiting', 'curbside-pickup');
	}	

	function load_customers()
	{
		return $this->Manager->get_orders_by_status('waiting');
	}
	
	function get_no_customers_message()
	{
		return __('No customers waiting.', 'curbside-pickup');
	}	 
	
}

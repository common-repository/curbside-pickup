<?php

namespace Curbside_Pickup;

class WP_Dashboard_Upcoming_Customers_Widget extends WP_Dashboard_Customers_Widget
{
	var $widget_slug = 'curbside_pickup_wp_dashboard_upcoming_customers_widget';
	
	function get_widget_title()
	{
		return 	'Curbside Pickup - ' . __('Later Today', 'curbside-pickup');
	}	

	function load_customers()
	{
		$upcoming_start_time = get_date_from_gmt( 'now' );
		$upcoming_end_time = $this->local_day_end();
		return $this->Manager->get_orders_by_status('pending', $upcoming_start_time, $upcoming_end_time);
	}
	
	function show_arrival_column()
	{
		return false;
	}
	
	function get_no_customers_message()
	{
		return __('No customers yet to arrive today.', 'curbside-pickup');
	}	 
}

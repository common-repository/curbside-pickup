<?php

namespace Curbside_Pickup;

class WP_Dashboard_Recent_Deliveries_Widget extends WP_Dashboard_Customers_Widget
{
	var $widget_slug = 'curbside_pickup_wp_dashboard_recent_deliveries_widget';
	
	function get_widget_title()
	{
		return 	'Curbside Pickup - ' . __('Recent Deliveries', 'curbside-pickup');
	}	

	function load_customers()
	{
		$upcoming_start_time = get_date_from_gmt( '-7 days' );
		$upcoming_end_time = $this->local_day_end();
		return $this->Manager->get_orders_by_status('delivered', '', '', '', 5);
	}
	
	function show_arrival_column()
	{
		return false;
	}
	
	function show_scheduled_column()
	{
		return false;
	}
	
	function show_delivered_column()
	{
		return false;
	}
	
	function get_no_customers_message()
	{
		return __('No recent deliveries.', 'curbside-pickup');
	}

	function output_footer()
	{
	?>
		<p><a class="button button-secondary" href="<?php echo admin_url('edit.php?post_type=scheduled-pickup&delivery-status=delivered'); ?>"><?php esc_html_e('View All Deliveries', 'curbside-pickup');?> &raquo;</a></p>
	<?php
	}		
}

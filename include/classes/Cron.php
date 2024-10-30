<?php

namespace Curbside_Pickup;

class Cron extends Base_Class
{
	function __construct(\Curbside_Pickup\Manager $manager)
	{
		$this->Manager = $manager;
		$this->add_hooks();
		$this->schedule_cron_event();
	}

	function add_hooks()
	{
		// setup cron hooks to update order status
		add_filter( 'cron_schedules', array($this, 'add_cron_interval') );
		add_action( 'curbside_pickup_cron_update_order_status', array($this, 'update_order_status') );
	}


	function deactivate_hook()
	{
		$timestamp = wp_next_scheduled( 'curbside_pickup_cron_update_order_status' );
		if ( !empty($timestamp) ) {
			wp_unschedule_event( $timestamp, 'curbside_pickup_cron_update_order_status' );
		}
	}

	function add_cron_interval( $schedules ) {
		$schedules['thirty_seconds'] = array(
			'interval' => 30,
			'display'  => esc_html__( 'Every Thirty Seconds', 'curbside-pickup' ), );
		return $schedules;
	}

	function schedule_cron_event()
	{
		if ( ! wp_next_scheduled( 'curbside_pickup_cron_update_order_status' ) ) {
			wp_schedule_event( time(), 'thirty_seconds', 'curbside_pickup_cron_update_order_status' );
		}
	}

	function update_order_status()
	{
		$this->fix_no_status_orders();
		$this->mark_orders_late();
	}

	function fix_no_status_orders()
	{
		$orders = $this->Manager->get_orders_without_status('');
		
		if ( empty($orders) ) {
			return;
		}

		foreach($orders as $order) {
			$this->Manager->set_pickup_status($order->ID, 'pending');
			$note = __('Fixed order - no status, so set status to pending', 'curbside-pickup');
			$this->Manager->append_log_entry($order->ID, $note);
		}
	}
		
	function mark_orders_late()
	{
		$minutes_until_late = $this->get_option_value('minutes_until_late', 30);
		$start_time = get_date_from_gmt( '-2 years' );
		$end_time = get_date_from_gmt( sprintf('-%d minutes', $minutes_until_late) );
		$orders = $this->Manager->get_orders_by_status('pending', $start_time, $end_time);

		if ( empty($orders) ) {
			return;
		}

		foreach($orders as $order) {
			$this->Manager->set_pickup_status($order->ID, 'late');
			$note = sprintf( __('Order marked late - missed pickup window by %d minutes.', 'curbside-pickup'), $minutes_until_late);
			$this->Manager->append_log_entry($order->ID, $note);
		}
	}
}

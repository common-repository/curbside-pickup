<?php

namespace Curbside_Pickup;

class Dashboard_Widget extends Base_Class
{
	function __construct( \Curbside_Pickup\Manager $manager )
	{
		$this->Manager = $manager;
		$this->add_hooks();
	}

	function add_hooks()
	{
		add_action( 'wp_dashboard_setup', array($this, 'add_dashboard_widget') );
	}

	/**
	 * Add a widget to the dashboard.
	 *
	 * This function is hooked into the 'wp_dashboard_setup' action below.
	 */
	function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'curbside_pickup_dashboard_widget', // Widget slug.
			esc_html__( 'Curbside Pickup', 'curbside-pickup' ),
			array($this, 'render_dashboard_widget')
		); 
	}
	 
	/**
	 * Create the function to output the content of our Dashboard Widget.
	 */
	function render_dashboard_widget()
	{
		$upcoming_start_time = get_date_from_gmt( 'now' );
		$upcoming_end_time = $this->local_day_end();
		
		$counts = [
			'waiting' => $this->Manager->get_pickup_count_by_status('waiting'),
			'pending' => $this->Manager->get_pickup_count_by_status('pending'),
			'upcoming' => $this->Manager->get_pickup_count_by_status('pending', $upcoming_start_time, $upcoming_end_time),
			'late' => $this->Manager->get_pickup_count_by_status('late'),
			'delivered' => $this->Manager->get_pickup_count_by_status('delivered'),
		];
?>
		<div class="curbside_pickup_dashboard_widget curbside_pickup_dashboard_status_widget">
			<div class="curbside_pickup_dashboard_widget_pad">
				<h3>Current Status</h3>
			</div>
			<div>
				<table cellpadding="0" cellspacing="0" class="curbside_pickup_dashboard_widget_table">
					<tbody>
						<tr>
							<td width="10px"><?php echo $counts['waiting']; ?></td>
							<td><a href="<?php $this->pickups_url('waiting'); ?>"><?php esc_html_e( "Customers Waiting", 'curbside-pickup' ); ?></a></td>
						</tr>
						<tr>
							<td><?php echo $counts['pending']; ?></td>
							<td><a href="<?php $this->pickups_url('pending'); ?>"><?php esc_html_e( "Pending Pickups", 'curbside-pickup' ); ?></a></td>
						</tr>
						<tr>
							<td><?php echo $counts['upcoming']; ?></td>
							<td><a href="<?php $this->pickups_url('pending'); ?>"><?php esc_html_e( "Arriving Soon", 'curbside-pickup' ); ?></a></td>
						</tr>
						<tr>
							<td><?php echo $counts['late']; ?></td>
							<td><a href="<?php $this->pickups_url('late'); ?>"><?php esc_html_e( "Missed Pickup Time", 'curbside-pickup' ); ?></a></td>
						</tr>
						<tr>
							<td><?php echo $counts['delivered']; ?></td>
							<td><a href="<?php $this->pickups_url('delivered'); ?>"><?php esc_html_e( "Delivered", 'curbside-pickup' ); ?></a></td>
						</tr>
					<tbody>
				</table>
			</div>
			<div class="curbside_pickup_dashboard_widget_pad">
				<p><a class="button button-secondary" href="<?php echo admin_url('admin.php?page=curbside-pickup/dashboard'); ?>"><?php esc_html_e('View Dashboard', 'curbside-pickup');?> &raquo;</a></p>
			</div>
		</div>
		
<?php
	}
	
	function pickups_url($status)
	{
		echo admin_url('edit.php?post_type=scheduled-pickup&delivery-status=' . $status);
	}
}

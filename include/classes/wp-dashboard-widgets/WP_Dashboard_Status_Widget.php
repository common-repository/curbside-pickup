<?php

namespace Curbside_Pickup;

class WP_Dashboard_Status_Widget extends WP_Dashboard_Widget
{
	var $widget_slug = 'curbside_pickup_status_dashboard_widget';
	var $location_id = false;

	function get_widget_slug()
	{
		if ( !empty($this->location_id) ) {
			return $this->widget_slug . '_' . $this->location_id;
		}
		else {
			return $this->widget_slug;
		}		
	}
	
	function get_widget_title()
	{
		if ( !empty($this->location_id) ) {
			return 	'Curbside Pickup - ' . __('Status', 'curbside-pickup') . sprintf( ' (%s)', htmlentities( get_the_title($this->location_id) ) );
		}
		else {
			return 	'Curbside Pickup - ' . __('Status', 'curbside-pickup');
		}		
	}
	
	function set_args($args)
	{
		if ( ! empty($args['location_id']) ) {
			$this->location_id = intval($args['location_id']);
		}
		parent::set_args($args);
	}
	
	function output_footer()
	{
	?>
		<p><a class="button button-secondary" href="<?php echo $this->get_dashboard_url(); ?>"><?php esc_html_e('View Dashboard', 'curbside-pickup');?> &raquo;</a></p>
	<?php
	}	
	
	function get_dashboard_url()
	{
		$url = admin_url('admin.php?page=curbside-pickup/dashboard');
		if ( !empty($this->location_id) ) {
			$url = add_query_arg('location_id', $this->location_id, $url);
		}
		return $url;
	}
	
	/**
	 * Create the function to output the content of our Dashboard Widget.
	 */
	function render_dashboard_widget()
	{
		$upcoming_start_time = get_date_from_gmt( 'now' );
		$upcoming_end_time = $this->local_day_end();

		$counts = [
			'waiting' => $this->Manager->get_pickup_count_by_status('waiting', '', '', $this->location_id),
			'pending' => $this->Manager->get_pickup_count_by_status('pending', '', '', $this->location_id),
			'upcoming' => $this->Manager->get_pickup_count_by_status('pending', $upcoming_start_time, $upcoming_end_time, $this->location_id),
			'late' => $this->Manager->get_pickup_count_by_status('late', '', '', $this->location_id),
			'delivered' => $this->Manager->get_pickup_count_by_status('delivered', '', '', $this->location_id),
		];
?>
		<div class="curbside_pickup_wp_dashboard_widget curbside_pickup_dashboard_status_widget" data-widget-slug="<?php echo htmlspecialchars( $this->get_widget_slug() ); ?>">
			<div>
				<table cellpadding="0" cellspacing="0" class="curbside_pickup_wp_dashboard_widget_table">
					<tbody>
						<tr>
							<td class="curbside_pickup_wp_dashboard_widget_count" width="10px"><?php echo $counts['waiting']; ?></td>
							<td><a href="<?php $this->pickups_url('waiting'); ?>"><?php esc_html_e( "Customers Waiting", 'curbside-pickup' ); ?></a></td>
						</tr>
						<tr>
							<td class="curbside_pickup_wp_dashboard_widget_count" ><?php echo $counts['pending']; ?></td>
							<td><a href="<?php $this->pickups_url('pending'); ?>"><?php esc_html_e( "Pending Pickups", 'curbside-pickup' ); ?></a></td>
						</tr>
						<tr>
							<td class="curbside_pickup_wp_dashboard_widget_count" ><?php echo $counts['upcoming']; ?></td>
							<td><a href="<?php $this->pickups_url('pending'); ?>"><?php esc_html_e( "Arriving Soon", 'curbside-pickup' ); ?></a></td>
						</tr>
						<tr>
							<td class="curbside_pickup_wp_dashboard_widget_count" ><?php echo $counts['late']; ?></td>
							<td><a href="<?php $this->pickups_url('late'); ?>"><?php esc_html_e( "Missed Pickup Time", 'curbside-pickup' ); ?></a></td>
						</tr>
						<tr>
							<td class="curbside_pickup_wp_dashboard_widget_count" ><?php echo $counts['delivered']; ?></td>
							<td><a href="<?php $this->pickups_url('delivered'); ?>"><?php esc_html_e( "Delivered", 'curbside-pickup' ); ?></a></td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="curbside_pickup_wp_dashboard_widget_pad">
				<div class="curbside_pickup_wp_dashboard_widget_pad">
				<?php $this->output_footer(); ?>
			</div>			
			</div>
		</div>
		
<?php
	}
}

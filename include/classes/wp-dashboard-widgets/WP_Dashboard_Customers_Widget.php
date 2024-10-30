<?php

namespace Curbside_Pickup;

class WP_Dashboard_Customers_Widget extends WP_Dashboard_Widget
{
	var $widget_slug = 'curbside_pickup_wp_dashboard_customers_widget';
	
	function load_customers()
	{
		return [];
	}
	
	function show_arrival_column()
	{
		return true;
	}
	
	function show_scheduled_column()
	{
		return true;
	}
	
	function show_delivered_column()
	{
		return false;
	}
	
	function show_customer_name_column()
	{
		return true;
	}
	
	function show_location_column()
	{
		$count_posts = wp_count_posts( 'pickup-location' )->publish;
		return $count_posts > 0;
	}
	
	function get_no_customers_message()
	{
		return __('No customers.', 'curbside-pickup');
	}
	 
	/**
	 * Create the function to output the content of our Dashboard Widget.
	 */
	function render_dashboard_widget()
	{
		$customers = $this->load_customers();
?>
		<div class="curbside_pickup_wp_dashboard_widget <?php echo $this->get_widget_slug(); ?> no_padding" data-widget-slug="<?php echo htmlspecialchars( $this->get_widget_slug() ); ?>">
			<div>
				<?php if ( !empty ($customers) ): ?>			
				<table cellpadding="0" cellspacing="0" class="curbside_pickup_wp_dashboard_widget_table">
					<tbody>
						<?php foreach($customers as $pickup_post): 
							$pickup = new Scheduled_Pickup($pickup_post->ID);
						?>
						<tr class="curbside_pickup_wp_dashboard_widget_customer_row">
							<?php if ( $this->show_arrival_column() ): ?>
							<td class="curbside_pickup_wp_dashboard_widget_time"  width="60px"><span class="curbside_pickup_wp_dashboard_widget_inline_label"><?php esc_html_e('Arrived', 'curbside-pickup'); ?></span> <?php echo htmlspecialchars( $pickup->get_arrival_time('g:i a') ); ?></td>
							<?php endif; ?>
							<?php if ( $this->show_scheduled_column() ): ?>
							<td class="curbside_pickup_wp_dashboard_widget_time" width="60px"><span class="curbside_pickup_wp_dashboard_widget_inline_label"><?php esc_html_e('Scheduled', 'curbside-pickup'); ?></span> <?php echo htmlspecialchars( $pickup->get_scheduled_time('g:i a') ); ?></td>
							<?php endif; ?>
							<?php if ( $this->show_customer_name_column() ): ?>
							<td class="curbside_pickup_wp_dashboard_widget_customer_name"><span class="curbside_pickup_wp_dashboard_widget_inline_label"><?php esc_html_e('Customer Name', 'curbside-pickup'); ?></span> <?php echo htmlspecialchars( $pickup->get_meta('customer_name') ); ?></td>
							<?php endif; ?>
							<?php if ( $this->show_delivered_column() ): ?>
							<td class="curbside_pickup_wp_dashboard_widget_time" width="70px"><span class="curbside_pickup_wp_dashboard_widget_inline_label"><?php esc_html_e('Scheduled', 'curbside-pickup'); ?></span> <?php echo htmlspecialchars( $pickup->get_delivery_time('g:i a') ); ?></td>
							<?php endif; ?>
							<?php if ( $this->show_location_column() ): ?>
							<td class="curbside_pickup_wp_dashboard_widget_location" width="70px"><span class="curbside_pickup_wp_dashboard_widget_inline_label"><?php esc_html_e('Location', 'curbside-pickup'); ?></span> <?php echo htmlspecialchars( $pickup->get_location_name() ); ?></td>
							<?php endif; ?>
							<td width="40px"><?php echo $pickup->update_pickup_button( __('Update', 'curbside-pickup') ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else: ?>
				<div class="curbside_pickup_wp_dashboard_widget_pad">
					<p><em><?php echo htmlspecialchars( $this->get_no_customers_message() ); ?></em></p>
				</div>
				<?php endif; ?>
			</div>
			<div class="curbside_pickup_wp_dashboard_widget_pad">
				<?php $this->output_footer(); ?>
			</div>
		</div>
		
<?php
	}
}

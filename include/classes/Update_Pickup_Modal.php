<?php

namespace Curbside_Pickup;

class Update_Pickup_Modal extends Base_Class
{
	var $pickup = false;
	var $pickup_id = false;

	function __construct($pickup_id = false)
	{
		if ( ! empty($pickup_id) ) {
			$this->pickup = new Scheduled_Pickup($pickup_id);
			$this->pickup_id = $pickup_id;
		}
	}

	function render($modal_title = '', $div_id = 'curbside_pickup_order_modal')
	{
		if ( empty($modal_title) ) {
			$modal_title = __('Update Pickup', 'curbside-pickup');
		}
		$body = $this->modal_body();

		ob_start();
?>
		<div id="<?php echo $div_id ?>" class="modal" tabindex="-1" role="dialog">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header bg-info text-white">
						<h5 class="modal-title"><?php echo $modal_title; ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="<?php _e('Close', 'curbside-pickup'); ?>">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body pl-4">
						<p><?php echo $body; ?></p>
					</div>
					<?php if ( ! $this->pickup->is_complete() ): ?>
					<div class="modal-footer justify-content-start">
						<button type="button" class="curbside_pickup_modal_button_complete_pickup curbside_pickup_modal_button btn btn-success float-left" data-action="complete" data-pickup-id="<?php echo htmlspecialchars($this->pickup_id); ?>"><i class="fas fa-check"></i>&nbsp;&nbsp;<?php _e('Pickup Completed', 'curbside-pickup'); ?></button>
						<button class="curbside_pickup_modal_button_cancel_pickup btn btn-warning float-left text-left mr-auto " data-toggle="collapse" data-target="#curbside_pickup_cancel_pickup_form" aria-expanded="false" aria-controls="curbside_pickup_cancel_pickup_form"><i class="fas fa-trash-alt"></i>&nbsp;&nbsp;<?php _e('Cancel Pickup', 'curbside-pickup'); ?></button>
						<button type="button" class="btn btn-secondary float-right" data-dismiss="modal"><?php _e('Close', 'curbside-pickup'); ?></button>
					</div>
					<?php else: ?>
					<div class="modal-footer justify-content-end">
						<button type="button" class="btn btn-secondary float-right" data-dismiss="modal"><?php _e('Close', 'curbside-pickup'); ?></button>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	function modal_body()
	{
		$edit_notes_form = $this->get_edit_notes_form();
		$reschedule_form = $this->get_reschedule_form();
		$cancel_form = $this->get_cancel_pickup_form();
		$all_meta = ! empty( $this->pickup )
					? $this->pickup->get_all_meta()
					: [];
		if ( empty($all_meta) ) {
			return '';
		}
		ob_start();
?>
		<h3 class="curbside_pickup_customer_name text-primary mb-2"><?php echo htmlspecialchars($all_meta['customer_name']); ?></h3>
		<input type="hidden" name="curbside_pickup_delivery_location_id" value="<?php echo htmlspecialchars($all_meta['delivery_location']); ?>" />

		<?php do_action('curbside_pickup_update_order_modal_before_table', $this->pickup_id); ?>

		<table class="table table-striped table-bordered">
			<?php if ( ! empty($all_meta['delivery_location']) ): ?>
			<tr class="hide_if_no_delivery_location">
				<td>
					<span class="font-weight-bold"><?php _e('Pickup Location', 'curbside-pickup'); ?>:</span>
				</td>
				<td>
					<span class="curbside_pickup_delivery_location_name"><?php echo htmlspecialchars($all_meta['delivery_location_name']); ?></span>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ( ! empty($all_meta['item_count']) ): ?>
			<tr>
				<td>
					<span class="font-weight-bold"><?php _e('Item Count', 'curbside-pickup'); ?>:</span>
				</td>
				<td>
					<span class="curbside_pickup_item_count"><?php echo htmlspecialchars($all_meta['item_count']); ?></span> <?php _e('items', 'curbside-pickup'); ?>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ( ! empty($all_meta['order_total']) ): ?>
			<tr>
				<td>
					<span class="font-weight-bold"><?php _e('Order Total', 'curbside-pickup'); ?>:</span>
				</td>
				<td>
					$ <span class="curbside_pickup_order_total"><?php echo htmlspecialchars($all_meta['order_total']); ?></span>
				</td>
			</tr>
			<?php endif; ?>
			<tr>
				<td>
					<span class="font-weight-bold"><?php _e('Scheduled Time', 'curbside-pickup'); ?>:</span>
				</td>
				<td>
					<span class="curbside_pickup_scheduled_time_formatted"><?php echo htmlspecialchars($all_meta['scheduled_time_formatted']); ?></span> (<span class="curbside_pickup_scheduled_time_friendly"><?php echo htmlspecialchars($all_meta['scheduled_time_friendly']); ?></span>)
				</td>
			</tr>
			<?php if ( ! empty($all_meta['arrived']) ): ?>
			<tr class="hide_if_not_arrived">
				<td>
					<span class="font-weight-bold"><?php _e('Arrival Time', 'curbside-pickup'); ?>:</span>
				</td>
				<td>
					<span class="curbside_pickup_arrival_time_formatted"><?php echo htmlspecialchars($all_meta['arrival_time_formatted']); ?></span> (<span class="curbside_pickup_arrival_time_friendly"><?php echo htmlspecialchars($all_meta['arrival_time_friendly']); ?></span>)
				</td>
			</tr>
			<?php endif; ?>
			<?php if ( ! empty($all_meta['space_number']) ): ?>
			<tr class="hide_if_no_space_number">
				<td>
					<span class="font-weight-bold"><?php _e('Space #', 'curbside-pickup'); ?>:</span>
				</td>
				<td>
					<span class="curbside_pickup_space_number"><?php echo htmlspecialchars($all_meta['space_number']); ?></span>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ( ! empty($all_meta['vehicle_description']) ): ?>
			<tr class="hide_if_no_vehicle_description">
				<td>
					<span class="font-weight-bold"><?php _e('Vehicle Description', 'curbside-pickup'); ?>:</span>
				</td>
				<td>
					<div class="curbside_pickup_vehicle_description"><?php echo htmlspecialchars($all_meta['vehicle_description']); ?></div>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ( ! empty($all_meta['customer_notes']) ): ?>
			<tr class="hide_if_no_customer_notes">
				<td>
					<span class="font-weight-bold"><?php _e('Notes from Customer', 'curbside-pickup'); ?>:</span>
				</td>
				<td>
					<div class="curbside_pickup_customer_notes"><?php echo htmlspecialchars($all_meta['customer_notes']); ?></div>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ( ! empty($all_meta['notes']) ): ?>
			<tr class="hide_if_no_notes">
				<td>
					<span class="font-weight-bold"><?php _e('Internal Notes', 'curbside-pickup'); ?>:</span>
				</td>
				<td>
					<div class="curbside_pickup_notes"><?php echo nl2br( htmlspecialchars($all_meta['notes']) ); ?></div>
				</td>
			</tr>
			<?php endif; ?>
		</table>
		<div class="mt-4 pt-2 curbside_pickup_update_modal_buttons">
			<!--<button class="btn btn-success mx-1 mb-2 text-left" data-action="complete"><i class="fas fa-check"></i>&nbsp;&nbsp;Complete</button>-->
			<button class="btn btn-secondary mx-1 mb-2 text-left" data-toggle="collapse" data-target="#curbside_pickup_reschedule_form" aria-expanded="false" aria-controls="curbside_pickup_reschedule_form"><i class="fas fa-calendar"></i>&nbsp;&nbsp;Reschedule</button>
			<button class="btn btn-secondary mx-1 mb-2 text-left" data-toggle="collapse" data-target="#curbside_pickup_edit_notes_form" aria-expanded="false" aria-controls="curbside_pickup_edit_notes_form"><i class="fas fa-edit"></i>&nbsp;&nbsp;Notes</button>
			<?php do_action('curbside_pickup_update_order_modal_buttons', $this->pickup_id); ?>
		</div>
		<div class="mt-4"><?php echo $edit_notes_form; ?></div>
		<div class="mt-4"><?php echo $reschedule_form ?></div>
		<div class="mt-4"><?php echo $cancel_form ?></div>
		<?php do_action('curbside_pickup_update_order_modal_secondary_forms', $this->pickup_id); ?>
<?php
		$modal_body = ob_get_contents();
		ob_end_clean();
		return $modal_body;
	}

	function get_edit_notes_form()
	{
		$notes = $this->pickup->get_meta('notes');
		ob_start();
?>
		<div id="curbside_pickup_edit_notes_form" class="curbside_pickup_modal_edit_notes_fields collapse pb-4">
			<div class="form-group">
				<label for="curbside_pickup_edit_notes"><?php _e('Notes', 'curbside-pickup'); ?>:</label>
				<textarea name="notes" id="curbside_pickup_edit_notes" class="form-control"><?php echo htmlspecialchars($notes); ?></textarea>
			</div>
			<div class="mt-4">
				<button class="curbside_pickup_modal_button btn btn-info" data-action="save_notes" data-pickup-id="<?php echo htmlspecialchars($this->pickup_id); ?>"><?php _e('Save Notes', 'curbside-pickup'); ?></button>
				<button class="curbside_pickup_modal_button_cancel_form btn btn-warning" data-toggle="collapse" data-target="#curbside_pickup_edit_notes_form" aria-expanded="false" aria-controls="curbside_pickup_edit_notes_form"><?php _e('Cancel', 'curbside-pickup'); ?></button>
			</div>
		</div>
<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	function get_reschedule_form()
	{
		$location_id = 0; // @TODO grab location ID from AJAX POST data
		$date_options = $this->get_date_options(30, $location_id, $first_available_date);
		$time_options = $this->get_time_options($first_available_date, $location_id);
		ob_start();
?>
		<div id="curbside_pickup_reschedule_form" class="curbside_pickup_modal_reschedule_fields collapse pb-4">
			<div class="form-group">
				<label for="curbside_pickup_reschedule_date"><?php _e('Date', 'curbside-pickup'); ?>:</label>
				<select name="curbside_pickup_reschedule_date" class="form-control curbside_pickup_modal_datepicker">
				<?php echo $date_options; ?>
				</select>
			</div>
			<div class="form-group">
				<label for="curbside_pickup_reschedule_date"><?php _e('Time', 'curbside-pickup'); ?>:</label>
				<select name="curbside_pickup_reschedule_time" class="form-control curbside_pickup_modal_timepicker">
				<?php echo $time_options; ?>
				</select>
			</div>
			<div class="mt-4">
				<button class="curbside_pickup_modal_button btn btn-info" data-action="reschedule" data-pickup-id="<?php echo htmlspecialchars($this->pickup_id); ?>"><?php _e('Reschedule Pickup', 'curbside-pickup'); ?></button>
				<button class="curbside_pickup_modal_button_cancel_form btn btn-warning" data-toggle="collapse" data-target="#curbside_pickup_reschedule_form" aria-expanded="false" aria-controls="curbside_pickup_reschedule_form"><?php _e('Cancel', 'curbside-pickup'); ?></button>
			</div>
		</div>
<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	function get_date_options($num_days_from_today = 30, $location_id = 0, &$first_available_date = '')
	{
		$scheduler = new Scheduler();
		$opts =  $scheduler->get_date_options($location_id, $num_days_from_today);
		$keys = array_keys($opts);
		$first_available_date = !empty($keys) && !empty($keys[0])
								? $keys[0]
								: '';
		return $this->array_to_html_options($opts);
	}

	function get_time_options($my_date = '', $location_id = 0)
	{
		$scheduler = new Scheduler();
		$opts =  $scheduler->get_time_options($my_date, $location_id);
		return $this->array_to_html_options($opts);
	}

	function get_cancel_pickup_form()
	{
		ob_start();
?>
		<div id="curbside_pickup_cancel_pickup_form" class="curbside_pickup_modal_cancel_fields collapse pb-4">
			<h5><?php _e('Cancel Pickup', 'curbside-pickup'); ?></h5>
			<p class="font-weight-bold"><?php _e('Are you sure you want to cancel this pickup?', 'curbside-pickup'); ?></p>
			<button class="curbside_pickup_modal_button btn btn-danger" data-action="cancel" data-pickup-id="<?php echo htmlspecialchars($this->pickup_id); ?>"><?php _e('Cancel Pickup', 'curbside-pickup'); ?></button>
			<button class="curbside_pickup_modal_button_cancel_form btn btn-warning" data-toggle="collapse" data-target="#curbside_pickup_cancel_pickup_form" aria-expanded="false" aria-controls="curbside_pickup_cancel_pickup_form"><?php _e('Go Back', 'curbside-pickup'); ?></button>
		</div>
<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	function array_to_html_options($arr)
	{
		$options = '';
		if ( empty($arr) ) {
			return '';
		}

		foreach ($arr as $key => $val) {
			$options .= sprintf( '<option value="%s">%s</option>',
									  htmlspecialchars($key),
									  htmlspecialchars($val) );
		}
		return $options;
	}

}


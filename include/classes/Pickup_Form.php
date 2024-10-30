<?php

namespace Curbside_Pickup;

class Pickup_Form extends Base_Class
{
	function __construct( \Curbside_Pickup\Manager $manager )
	{
		$this->Manager = $manager;
		$this->add_hooks();
	}

	function add_hooks()
	{
		// catch pickup links
		add_action( 'wp', array($this, 'catch_pickup_links') );

		// frontend css
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_frontend_css') );
	}

	function enqueue_frontend_css()
	{
		$url = plugins_url('../../assets/css/style.css', __FILE__);
		wp_enqueue_style( 'curbside-pickup', $url );
	}

	function catch_pickup_links()
	{
		global $wp_query;
		$cur_page_id = intval( $wp_query->query_vars['page_id'] );
		$pp_id = $this->get_option_value('pickup_page_id');
		
		// if its an admin without a valid hash, show them the preview page
		if ( ! empty($pp_id)
			 && ! empty($cur_page_id)
			 && $pp_id === $cur_page_id
			 && ! isset($_GET['curbside_pickup_pickup_id'])
			 && ! is_home()
			 && ! is_front_page()
			 && current_user_can( 'manage_options' ) 
		) {			 
			add_filter('the_content', array($this, 'show_pickup_page_preview') );
			return;
		}

		// for normal people, either show the pickup page or an error
		if ( isset($_GET['curbside_pickup_pickup_id']) ) {
			add_filter('the_content', array($this, 'show_pickup_page') );
			return;
		}
	}

	function show_pickup_page_preview($the_content)
	{
		$start_div = '<div class="curbside_pickup_pickup_page">';
		$end_div = '</div><!-- End .curbside_pickup_pickup_page -->';

		$admin_msg = '<div class="curbside_pickup_admin_message_fe">' . 
					 __("Admin: This is a preview of the pickup page. Please note, not all elements may be present.", 'curbside-pickup') .
					 '</div>';
		$header_preview = $this->get_pickup_header_preview();
		$form_preview = $this->get_pickup_form();
		return $start_div . $admin_msg . $header_preview . $form_preview . $end_div . $the_content;
	}
	
	
	function show_pickup_page($the_content)
	{
		$pickup = $this->get_pickup_from_url();
		echo '<div class="curbside_pickup_pickup_page">';

		if ( !empty($pickup)  ) {

			// valid URL, so show the check-in form (or confirmation message)
			$check_in_now = !empty($_POST['check_in_now']);
			$already_checked_in = get_post_meta($pickup->ID, 'arrived', true);
			$too_early = $this->is_customer_too_early($pickup);
			$too_late = $this->is_customer_too_late($pickup);

			// run an action now, so that other modules can add content to the page
			do_action('curbside_pickup_before_pickup_form', $pickup, $already_checked_in);

			if ( $already_checked_in ) {
				// already checked in, so just show the confirmation message
				echo $this->get_check_in_confirmation_message($pickup);
			}
			else if ( $too_early ) {
				// already checked in, so just show the confirmation message
				echo $this->get_pickup_header($pickup);
				echo $this->get_too_early_message($pickup);
			}
			else if ( $too_late ) {
				// already checked in, so just show the confirmation message
				echo $this->get_pickup_header($pickup);
				echo $this->get_too_late_message($pickup);
			}
			else if ( $check_in_now ) {
				// form posted, so capture its data and show thank you message
				$customer_notes 		= !empty($_POST['customer_notes'])
										? sanitize_text_field($_POST['customer_notes'])
										: '';
				$vehicle_description 	= !empty($_POST['vehicle_description'])
										? sanitize_text_field($_POST['vehicle_description'])
										: '';
				$space_number 			= !empty($_POST['space_number'])
										? sanitize_text_field($_POST['space_number'])
										: '';
				echo $this->Manager->check_in_customer($pickup, $customer_notes, $vehicle_description, $space_number);
				echo $this->get_check_in_confirmation_message($pickup);
			}
			else {
				// no form data posted, so show the form
				echo $this->get_pickup_header($pickup);
				echo $this->get_pickup_form($pickup);
			}
		}
		else {
			// invalid/missing hash, so show error message
			echo $this->get_pickup_error_message();
		}

		// run an action now, so that other modules can add content to the page
		do_action('curbside_pickup_after_pickup_form', $pickup, $already_checked_in);

		echo '</div><!-- End .curbside_pickup_pickup_page -->';

		// show the original content that was on this page
		echo "<br>";
		echo $the_content;
	}

	function get_pickup_form($pickup = false)
	{
		ob_start();
?>
		<div class="curbside_pickup_instructions_for_pickup">
		<?php echo wpautop( $this->get_option_value('instructions_for_pickup') ); ?>
		</div>
		<div class="curbside_pickup_pickup_form">
			<form action="" method="POST">
				<?php if ( $this->get_option_value('ask_for_space_number', false) ): ?>
				<div class="pickup_page_input_group">
					<label><?php _e('Space number', 'curbside-pickup'); ?>:</label>
					<input type="text" name="space_number" />
				</div>
				<?php endif; ?>

				<?php if ( $this->get_option_value('ask_for_vehicle_description', false) ): ?>
				<div class="pickup_page_input_group">
					<label><?php _e('Description of your vehicle', 'curbside-pickup'); ?>:</label>
					<textarea name="vehicle_description"></textarea>
				</div>
				<?php endif; ?>

				<?php if ( $this->get_option_value('ask_for_customer_notes', true) ): ?>
				<div class="pickup_page_input_group">
					<label><?php _e('Special instructions for our staff', 'curbside-pickup'); ?>:</label>
					<textarea name="customer_notes"></textarea>
				</div>
				<?php endif; ?>

				<button type="submit" class="curbside_pickup_pickup_page_button"><?php _e('Check-In', 'curbside-pickup'); ?></button>
				<input type="hidden" name="check_in_now" value="1" />
			</form>
		</div>
		<!-- End .curbside_pickup_pickup_form -->
<?php
		$form = ob_get_contents();
		ob_end_clean();
		return apply_filters('curbside_pickup_pickup_form', $form, $pickup);
	}

	function get_pickup_header($pickup)
	{
		$customer_name = get_post_meta($pickup->ID, 'customer_name', true);
		$page_heading = $this->get_option_value_ne( 'pickup_page_heading', __('Curbside Pickup', 'curbside-pickup') );
		ob_start();
?>
		<h3><?php echo esc_html($page_heading); ?></h3>
		<p class="curbside_pickup_pickup_page_welcome"><?php _e('Welcome', 'curbside-pickup'); ?>, <?php echo esc_html($customer_name); ?>!</p>
<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	function get_pickup_header_preview()
	{
		$customer_name = __('Test Customer', 'curbside-pickup');
		$page_heading = $this->get_option_value_ne( 'pickup_page_heading', __('Curbside Pickup', 'curbside-pickup') );
		ob_start();
?>
		<h3><?php echo esc_html($page_heading); ?></h3>
		<p class="curbside_pickup_pickup_page_welcome"><?php _e('Welcome', 'curbside-pickup'); ?>, <?php echo esc_html($customer_name); ?>!</p>
<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	function get_pickup_from_url()
	{
		$hash = !empty($_GET['curbside_pickup_pickup_id'])
				? sanitize_text_field($_GET['curbside_pickup_pickup_id'])
				: '';
		return !empty($hash)
			   ? $this->find_pickup_by_hash($hash)
			   : [];
	}

	function find_pickup_by_hash($pickup_hash)
	{
		$query = array(
			'post_type'   => 'scheduled-pickup',
			'meta_query' => array(
				array (
					'key' => 'pickup_hash',
					'value' => $pickup_hash,

				)
			),
		);
		$result = new \WP_Query($query);
		if ( $result->have_posts() ) {
			return array_shift($result->posts);
		} else {
			return false;
		}

	}

	function get_check_in_confirmation_message($pickup)
	{
		ob_start();
?>
		<div class="curbside_pickup_pickup_page">
			<?php echo $this->get_pickup_header($pickup); ?>
			<p>✅ &nbsp;<span class="curbside_pickup_checked_in">You have been checked-in!</strong></p>
			<div class="curbside_pickup_instructions_for_pickup">
			<?php
				$confirmation_message = $this->get_option_value('check_in_confirmation_message');
				echo wpautop( $confirmation_message );
			?>
			</div>
			<!-- End .curbside_pickup_pickup_form -->
		</div>
		<!-- End .curbside_pickup_pickup_page -->
<?php
		$page_content = ob_get_contents();
		ob_end_clean();
		return apply_filters('curbside_pickup_pickup_confirmation', $page_content, $pickup);
	}

	function get_pickup_error_message()
	{
		ob_start();
?>
		<div class="curbside_pickup_pickup_page">
			<h3><?php _e('Order not found', 'curbside-pickup'); ?></h3>
			<div class="curbside_pickup_instructions_for_pickup">
			<?php echo wpautop( $this->get_option_value('pickup_error_message') ); ?>
			</div>
			<!-- End .curbside_pickup_pickup_form -->
		</div>
		<!-- End .curbside_pickup_pickup_page -->
<?php
		$page_content = ob_get_contents();
		ob_end_clean();
		return apply_filters('curbside_pickup_pickup_error', $page_content, $pickup);
	}

	function get_too_early_message($pickup)
	{
		$msg = $this->get_option_value('too_early_message');
		$page_content = '';
		if ( !empty($msg) ) {
			ob_start();
?>
			<div class="curbside_pickup_pickup_page">
				<div class="curbside_pickup_too_early_message">
				<?php echo wpautop( $msg ); ?>
				</div>
				<!-- End .curbside_pickup_pickup_form -->
			</div>
			<!-- End .curbside_pickup_pickup_page -->
<?php
			$page_content = ob_get_contents();
			ob_end_clean();
		}
		return apply_filters('curbside_pickup_too_early_message', $page_content, $pickup, $msg);
	}

	function get_too_late_message($pickup)
	{
		$msg = $this->get_option_value('too_late_message');
		$page_content = '';
		if ( !empty($msg) ) {
			ob_start();
?>
			<div class="curbside_pickup_pickup_page">
				<div class="curbside_pickup_too_late_message">
				<?php echo wpautop( $msg ); ?>
				</div>
				<!-- End .curbside_pickup_pickup_form -->
			</div>
			<!-- End .curbside_pickup_pickup_page -->
<?php
			$page_content = ob_get_contents();
			ob_end_clean();
		}
		return apply_filters('curbside_pickup_too_late_message', $page_content, $pickup, $msg);
	}

	function is_customer_too_early($pickup)
	{
		// if there's no time on this order there's a larger issue, but they also can't be early. 
		$scheduled_time = get_post_meta($pickup->ID, 'scheduled_delivery_time', true);		
		if ( empty($scheduled_time) ) {
			return false;
		}

		$scheduled_ts = strtotime($scheduled_time);
		$cur_ts = current_time('timestamp');

		$allow_early_pickups = $this->get_option_value('allow_early_pickups', 1);
		$grace_period_minutes = ! empty($allow_early_pickups)
								? $this->get_option_value_ne('checkin_grace_period', 10)
								: 0;

		$grace_period_seconds = intval($grace_period_minutes) * 60; 
		$diff = ( $scheduled_ts - $cur_ts ); // calc number of seconds before scheduled time
		return ! ($diff <= $grace_period_seconds);
	}

	function is_customer_too_late($pickup)
	{
		// if there's no time on this order there's a larger issue, but they also can't be late
		$scheduled_time = get_post_meta($pickup->ID, 'scheduled_delivery_time', true);		
		if ( empty($scheduled_time) ) {
			return false;
		}
		
		// is the pickup marked late by the system? if not, its not late yet
		$status = $this->Manager->get_pickup_status($pickup->ID);
		if ( 'late' != $status ) {
			return false;
		}
		
		// pickup is marked late, but is there a grace period and are they within it?
		$scheduled_ts = strtotime($scheduled_time);
		$cur_ts = current_time('timestamp');

		$allow_late_pickups = $this->get_option_value('allow_late_pickups', 1);
		$grace_period_minutes = ! empty($allow_late_pickups)
								? $this->get_option_value_ne('late_checkin_grace_period', 180)
								: 0;

		$grace_period_seconds = intval($grace_period_minutes) * 60; 
		$diff = ( $cur_ts - $scheduled_ts ); // calc number of seconds past scheduled time
		return ! ($diff <= $grace_period_seconds);
	}
}

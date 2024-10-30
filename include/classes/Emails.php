<?php

namespace Curbside_Pickup;

class Emails
{
	function __construct()
	{
		$this->add_hooks();
	}

	function add_hooks()
	{
		add_action( 'init', array($this, 'process_email_queue') );
	}

	function send($id, $post_id, $details = [])
	{
		$to = rwmb_meta( 'customer_email', [], $post_id );
		var_dump (get_post_meta($post_id));
		var_dump($to);
		die('Send Email: ' . $id . ' for post: ' . $post_id);
	}

	function queue_email($email_id, $post_id)
	{
		$new_post_id = wp_insert_post( [
			'post_type' => 'cspu-email',
			'post_status' => 'publish',
			'meta_input' => [
				'email_id' => $email_id,
				'scheduled_pickup_id' => $post_id,
				'status' => 'new',
			],
		] );
	}

	function process_email_queue()
	{
		// find emails that need to be added and process them
		$new_emails = $this->get_emails_by_status('new');
		if ( !empty($new_emails) ) {
			foreach($new_emails as $email) {
				$this->update_meta($email->ID);
			}
		}

		// find queued emails and send them
		$queued_emails = $this->get_emails_by_status('queued');
		if ( !empty($queued_emails) ) {
			foreach($queued_emails as $email) {
				// Send the email!
				$this->send_email($email);
			}
		}
	}

	function send_email($email)
	{
		// check for required fields
		$email_to = get_post_meta($email->ID, 'email_to', true);
		$email_subject = get_post_meta($email->ID, 'email_subject', true);
		$email_body = get_post_meta($email->ID, 'email_body', true);
		if ( empty($email_to) || empty($email_subject) || empty($email_body) ) {
			// can't send, record error
			$this->log_error($email->ID, "Could not send: receiving address, subject, or body was empty.");
			update_post_meta($email->ID, 'status', 'failed');
			return;
		}

		// send the email
		$send_with_wc = $this->get_option_value('use_woocommerce_mailer');
		if ( class_exists( 'woocommerce' ) && !empty($send_with_wc) ) {
			$headers = "Content-Type: text/html\r\n";
			$email_body = $this->wrap_email_wc($email_body, $email_subject, WC()->mailer());
			$sent = WC()->mailer()->send( $email_to, $email_subject, $email_body, $headers );
		}
		else {
			$sent = wp_mail($email_to, $email_subject, $email_body);
		}

		if ( !$sent ) {
			// wp_mail produced an error
			$this->log_error($email, "wp_mail failed");
			update_post_meta($email->ID, 'status', 'failed');
			// @TODO: grab error and record here. will require filter,
			// e.g., https://developer.wordpress.org/reference/functions/wp_mail/#comment-1856
			return;
		}

		// record timestamp
		$ts = wp_date('Y-m-d H:i:s'); // MySQL datetime format

		update_post_meta($email->ID, 'time_sent', $ts);

		// set status to sent
		update_post_meta($email->ID, 'status', 'delivered');
	}
	
	function wrap_email_wc($email_body, $email_heading, $mailer)
	{
		ob_start();
		do_action( 'woocommerce_email_header', $email_heading, $mailer );
		echo nl2br($email_body);
		do_action( 'woocommerce_email_footer', $mailer );		
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}

	function log_error($email_id, $message)
	{
		$log = get_post_meta($email_id, 'error_log', true);
		if ( !empty($log) ) {
			$log .= "\n";
		}
		$ts = wp_date('Y-m-d H:i:s'); // MySQL datetime format
		$log .= sprintf('[%s] %s: %s', $ts, __('Error'), $message);
		update_post_meta($email_id, 'error_log', $log);
	}


	function update_meta($email_id)
	{
		// don't queue unless successfully processed at all steps
		$ready_to_queue = true;

		// need pickup data to do anything else, so abort if not able to be found
		$pickup_id = get_post_meta($email_id, 'scheduled_pickup_id', true);
		if ( empty($pickup_id) ) {
			return;
		}

		/*
		 * Fill in any required information that is not already set
		 */

		// set 'to' field if not set
		$email_to = get_post_meta($email_id, 'email_to', true);
		if ( empty($email_to) ) {
			$email_to = get_post_meta($pickup_id, 'customer_email', true);
			if ( !empty($email_to) ) {
				update_post_meta($email_id, 'email_to', $email_to);
			} else {
				$ready_to_queue = false;
			}
		}

		// set 'subject' field if not set
		$email_subject = get_post_meta($email_id, 'email_subject', true);
		if ( empty($email_subject) ) {
			$email_id_meta = get_post_meta($email_id, 'email_id', true);
			$email_subject = $this->get_email_subject($email_id_meta);
			if ( !empty($email_subject) ) {
				update_post_meta($email_id, 'email_subject', $email_subject);
			} else {
				$ready_to_queue = false;
			}
		}

		// set 'body' field if not set
		$email_body = get_post_meta($email_id, 'email_body', true);
		$email_id_meta = '';
		if ( empty($email_body) ) {
			$email_id_meta = get_post_meta($email_id, 'email_id', true);
			$email_body = $this->get_email_body($email_id_meta, $pickup_id);

			if ( !empty($email_body) ) {
				update_post_meta($email_id, 'email_body', $email_body);
			} else {
				$ready_to_queue = false;
			}
		}

		// set post title if not set
		// place the current post and $new_title into array
		$current_title = get_the_title($email_id);
		if ( empty($current_title) ) {
			$customer_name = get_post_meta($pickup_id, 'customer_name', true);
			$new_title = sprintf('%s - %s - %s', $customer_name, $email_to, $email_id_meta);
			$post_update = array(
				'ID'         => $email_id,
				'post_title' => $new_title
			);
			wp_update_post( $post_update );
		}

		// add to queue if ready
		if ( $ready_to_queue ) {
			update_post_meta($email_id, 'status', 'queued');
		}
	}

	function set_meta_value_to_option_value($email_id, $meta_key, $option_key)
	{
		// look for a current value in the meta field. if found, don't change it
		$meta_value = get_post_meta($email_id, $meta_key, true);
		if ( !empty($meta_value) ) {
			return true;
		}

		// no meta value, so try to use the option value
		$option_value = rwmb_meta( $option_key, ['object_type' => 'setting'], 'curbside_pickup' );
		if ( !empty($option_value) ) {
			// option value found, so copy the value to the meta field
			return update_post_meta($email_id, $meta_key, $option_value);
		}

		// option and meta both empty, no action taken
		return false;
	}

	function get_option_value($key, $default_value = '')
	{
		$opt = get_option('curbside_pickup');
		$opt = maybe_unserialize($opt);
		if ( isset($opt[$key]) ) {
			return maybe_unserialize($opt[$key]);
		} else {
			return $default_value;
		}
	}

	function get_email_subject($email_id)
	{
		switch($email_id)
		{
			case 'new_pickup':
				return $this->get_option_value('emails_confirmation_subject');
				//return rwmb_meta( 'emails_confirmation_subject', ['object_type' => 'setting'], 'curbside_pickup' );
			break;

			case 'rescheduled_pickup':
				return $this->get_option_value('emails_rescheduled_pickup_subject');
				//return rwmb_meta( 'emails_rescheduled_pickup_subject', ['object_type' => 'setting'], 'curbside_pickup' );
			break;

			case 'completed_pickup':
				return $this->get_option_value('emails_completed_pickup_subject');
				//return rwmb_meta( 'emails_completed_pickup_subject', ['object_type' => 'setting'], 'curbside_pickup' );
			break;

			default:
			break;
		}

		return'';
	}

	function get_email_body($email_id, $pickup_id)
	{
		switch($email_id)
		{
			case 'new_pickup':
				$email_body = $this->get_option_value('emails_confirmation_body');
				$email_body = $this->maybe_add_pickup_link($email_body);
				//return rwmb_meta( 'emails_confirmation_body', ['object_type' => 'setting'], 'curbside_pickup' );
			break;

			case 'rescheduled_pickup':
				$email_body = $this->get_option_value('emails_rescheduled_pickup_body');
				$email_body = $this->maybe_add_pickup_link($email_body);
				//return rwmb_meta( 'emails_rescheduled_pickup_body', ['object_type' => 'setting'], 'curbside_pickup' );
			break;

			case 'completed_pickup':
				$email_body = $this->get_option_value('emails_completed_pickup_body');
				//return rwmb_meta( 'emails_completed_pickup_body', ['object_type' => 'setting'], 'curbside_pickup' );
			break;

			default:
				$email_body = '';
			break;
		}

		// add signature if needed
		$email_body = $this->maybe_add_signature($email_body);

		// replace merge tags
		$email_body = $this->replace_merge_tags($email_body, $pickup_id);
		return $email_body;
	}

	// if no pickup_link appears in the email body, add it now
	function maybe_add_pickup_link($email_body)
	{
		if ( false === strpos($email_body, '{pickup_link}') ) {
			$email_body .= "\n\n{pickup_link}\n\n";
		}
		return $email_body;
	}

	// if a global signature is specified but its not present, add it now
	function maybe_add_signature($email_body)
	{
		// first check that signatures are enabled
		$include_signature = $this->get_option_value('include_email_signature');
		if ( empty($include_signature) ) {
			return $email_body;
		}

		// append the specified email signature now, unless its
		// already been added elsewhere in the email via a merge tag
		$signature = $this->get_option_value('global_email_signature');
		if ( false === strpos($email_body, '{signature}') && !empty($signature) ) {
			$email_body .= "\n\n{$signature}\n\n";
		}
		return $email_body;
	}

	function create_pickup_link($pickup_id)
	{
		$pickup_page_id = $this->get_option_value('pickup_page_id');
		$pickup_page_url = !empty($pickup_page_id)
						   ? get_the_permalink($pickup_page_id)
						   : get_home_url();
		$pickup_hash = get_post_meta($pickup_id, 'pickup_hash', true);
		$pickup_url = add_query_arg([
			'curbside_pickup_pickup_id' => $pickup_hash
		], $pickup_page_url);
		$pickup_url = apply_filters('curbside_pickup_pickup_url', $pickup_url, $pickup_id, $pickup_page_id);
		update_post_meta($pickup_id, 'pickup_url', $pickup_url);
		return $pickup_url;
	}



	function replace_merge_tags($email_body, $pickup_id)
	{
		$all_meta = get_post_meta($pickup_id);
		$merge_tags = [
			'pickup_link'  => 'pickup_link',
			'customer_name' => 'customer_name',
			'order_number' => 'order_number',
			'order_total' => 'order_total',
			'item_count' => 'item_count',
			'pickup_time' => 'scheduled_delivery_time',
			'signature' => 'signature',
		];

		foreach($merge_tags as $tag => $meta_key) {
			switch($tag) {
				case 'signature':
					$val = rwmb_meta( 'global_email_signature', ['object_type' => 'setting'], 'curbside_pickup' );
				break;

				case 'pickup_link':
					$val = $this->create_pickup_link($pickup_id);
				break;

				case 'pickup_time':
					// convert to friendly time string
					$date_format = 'F j, Y \a\t g:i a';
					$val = isset($all_meta[$meta_key]) && isset($all_meta[$meta_key][0])
						? date( $date_format, strtotime($all_meta[$meta_key][0]) )
						: '';
				break;

				default:
					$val = isset($all_meta[$meta_key]) && isset($all_meta[$meta_key][0])
						? $all_meta[$meta_key][0]
						: '';
				break;
			}
			$email_body = str_replace( '{' . $tag . '}', $val, $email_body );
		}

		return $email_body;
	}

	function get_emails_by_status($status)
	{
		$args = array(
			'post_type'  => 'cspu-email',
			'meta_query' => array(
				array(
					'key'   => 'status',
					'value' => $status,
				)
			),
			'post_status' => 'any',
		);
		return get_posts( $args );
	}
}

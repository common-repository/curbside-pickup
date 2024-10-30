<?php

namespace Curbside_Pickup;

class Admin_Email_Notifications extends Base_Class
{
	/*
	 * Constructor.
	 *
	 */
	function __construct()
	{
		$this->add_hooks();
	}

	/*
	 * Setup hooks and filters.
	 *
	 */
	function add_hooks()
	{
		add_action('curbside_pickup_pickup_created', array($this, 'new_pickup_created'), 10, 2);
		add_action('curbside_pickup_customer_arrived', array($this, 'customer_arrived'), 10, 4);
	}

	/*
	 * Hook on Curbside Pickup's curbside_pickup_pickup_created hook. Notifies all emails
	 * specified of the new order.
	 *
	 * @param int $pickup_id ID (post ID) of the pickup record.
	 * @param int $order_id ID (post ID) of the WooCommerce order (not always present)
	 *
	 * @return bool True if emails sent, false if not.
	 */
	function new_pickup_created($pickup_id, $order_id = false)
	{
		// see if it has any emails to notify
		$emails = $this->get_emails_to_notify('new_order', $pickup_id);
		if ( empty($emails) ) {
			return false;
		}

		// notify each email
		foreach($emails as $email) {
			$this->send_new_order_notification($pickup_id, $email);
		}
		return true;
	}

	/*
	 * Hook on Curbside Pickup's curbside_pickup_customer_arrived hook. Notifies emails
	 * specified that the customer has arrived.
	 *
	 * @param int $pickup Post record (CPT) represeting the pickup.
	 * @param string $customer_notes Notes left by the customer on the pickup form.
	 * @param string $vehicle_description Description of the customer vehicle (optional)
	 * @param string $space_number The space number the customer is parked in (optional)
	 *
	 * @return bool True if emails sent, false if not.
	 */
	function customer_arrived($pickup, $customer_notes = '', $vehicle_description = '', $space_number = '')
	{
		$pickup_id = $pickup->ID;

		// see if it has any emails to notify
		$emails = $this->get_emails_to_notify('arrival', $pickup_id);
		if ( empty($emails) ) {
			return false;
		}

		// notify each email
		foreach($emails as $email) {
			$this->send_arrival_notification($pickup_id, $email, $customer_notes, $vehicle_description, $space_number);
		}
		return true;
	}


	/*
	 * Sends an email notification to the provided email address notifying them
	 * of a new scheduled pickup.
	 *
	 * @param int $pickup_id ID (post ID) of the pickup.
	 * @param string $email_to The email to receive the notification
	 *
	 * @return bool Whether the email was successfully sent (result of WP's wp_mail)
	 */
	function send_new_order_notification(int $pickup_id, string $email_to)
	{
		// collect the pickup details to send
		$customer_name = get_post_meta($pickup_id, 'customer_name', true);
		$pickup_time = get_post_meta($pickup_id, 'scheduled_delivery_time', true);
		$friendly_time = !empty($pickup_time)
						 ? date('F j, Y, g:i a', strtotime($pickup_time))
						 : '';

		$pickup_details = $this->get_pickup_details_for_email($pickup_id, 'new_order');
		$view_details_link = $this->get_edit_post_link($pickup_id);

		$email_subject = sprintf( '%s: %s - %s',
								  __('New Pickup Scheduled', 'curbside-pickup'),
								  esc_html($customer_name),
								  $friendly_time );
		$email_subject = apply_filters('curbside_pickup_admin_emails_new_order_notification_subject', $email_subject, $pickup_id, $customer_name, $friendly_time, $email_to);

		$intro = sprintf( '%s %s.',
					      __('A new pickup has been scheduled for ', 'curbside-pickup'),
						  $friendly_time );

		$closing = sprintf( '<br>%s <a href="%s">%s.</a>',
							__('View the full details', 'curbside-pickup'),
							$view_details_link,
							__('here', 'curbside-pickup') );

		// combine it all into the email body
		$email_body = sprintf("%s<br>\n%s<br>\n%s", $intro, $pickup_details, $closing);
		$email_body = apply_filters('curbside_pickup_admin_emails_new_order_notification_body', $email_body, $pickup_id, $email_to);

		// send the email
		$sent = $this->send_mail($email_to, $email_subject, $email_body);
		if ( !$sent ) {
			// wp_mail produced an error
			return;
		}
	}

	/*
	 * Sends emails with HTML headers
	 *
	 * @param string $email_to The email address
	 * @param string $email_subject The email subject
	 * @param string $email_body The email body
	 *
	 * @return bool Whether the email was sent successfully
	 * 				(the result of WP's wp_mail call)
	 */
	function send_mail($email_to, $email_subject, $email_body)
	{
		$headers = array('Content-Type: text/html; charset=UTF-8');
		return wp_mail($email_to, $email_subject, $email_body, $headers);
	}

	/*
	 * Sends an email notification to the provided email address notifying them
	 * of a customer's arrival (i.e., customer completed the check-in form).
	 *
	 * @param int $pickup_id ID (post ID) of the pickup.
	 * @param string $email_to The email to receive the notification
	 * @param string $customer_notes Notes from the customer (optional).
	 * @param string $vehicle_description Description of the customer vehicle (optional).
	 * @param string $space_number The space number the customer is parked in (optional).
	 *
	 * @return bool Whether the email was successfully sent (result of WP's wp_mail)
	 */
	function send_arrival_notification(int $pickup_id, string $email_to, string $customer_notes = '', string $vehicle_description = '', string $space_number = '')
	{
		// collect the pickup details to send
		$customer_name = get_post_meta($pickup_id, 'customer_name', true);
		$internal_notes = get_post_meta($pickup_id, 'notes', true);
		$pickup_time = get_post_meta($pickup_id, 'scheduled_delivery_time', true);
		$friendly_time = !empty($pickup_time)
						 ? date('F j, Y, g:i a', strtotime($pickup_time))
						 : '';
		$pickup_details = $this->get_pickup_details_for_email($pickup_id, 'arrival');
		$view_details_link = get_edit_post_link($pickup_id);

		$email_subject = sprintf( '%s: %s - %s',
								  __('Customer Arrived', 'curbside-pickup'),
								  $customer_name,
								  $friendly_time );
		$email_subject = apply_filters('curbside_pickup_admin_emails_arrival_notification_subject', $email_subject, $pickup_id, $customer_name, $friendly_time, $email_to);

		$intro = sprintf( '%s %s',
						  esc_html($customer_name),
					      __('has just arrived to pickup their order.', 'curbside-pickup') );

		$closing = sprintf( '%s <a href=%s">%s</a>',
							__('View the full details', 'curbside-pickup'),
							$view_details_link,
							__('here', 'curbside-pickup') );

		$internal_notes = !empty($internal_notes)
						  ? '<h3>' . __('Internal Notes', 'curbside-pickup') . '</h3>' . esc_html($internal_notes) . "\n<br>"
						  : '';

		$customer_notes = !empty($customer_notes)
						  ? '<h3>' . __('Customer Notes', 'curbside-pickup') . '</h3>' . esc_html($customer_notes) . "\n<br>"
						  : '';

		$vehicle_description = !empty($vehicle_description)
						  ? '<h3>' . __('Vehicle Description', 'curbside-pickup') . '</h3>' . esc_html($vehicle_description) . "\n<br>"
						  : '';

		$space_number = !empty($space_number)
						  ? '<h3>' . __('Space Number', 'curbside-pickup') . '</h3>' . esc_html($space_number) . "\n<br>"
						  : '';

		// combine it all into the email body
		$email_body = sprintf("%s<br>\n%s%s%s%s%s<br>\n<br>\n%s",
							  $intro,
							  $internal_notes,
							  $customer_notes,
							  $vehicle_description,
							  $space_number,
							  $pickup_details,
							  $closing );
		$email_body = apply_filters('curbside_pickup_admin_emails_arrival_notification_body', $email_body, $pickup_id, $email_to);

		// send the email
		$sent = $this->send_mail($email_to, $email_subject, $email_body);
		if ( !$sent ) {
			// wp_mail produced an error
			return;
		}
	}

	/*
	 * Collects and formats relevant information about the pickup (date, customer name,
	 * order details, etc).
	 *
	 * @param int $pickup_id ID (post ID) of the pickup.
	 * @param string $type The type of email being sent ('arrival' or 'new_order')
	 *
	 * @return string HTML to be plugged into the email, with the formatted pickup/order information.
	 */
	private function get_pickup_details_for_email($pickup_id, $type)
	{
		// load pickup/order metadata
		$customer_name = get_post_meta($pickup_id, 'customer_name', true);
		$customer_email = get_post_meta($pickup_id, 'customer_email', true);
		$pickup_time = get_post_meta($pickup_id, 'scheduled_delivery_time', true);
		$friendly_time = !empty($pickup_time)
						 ? date('F j, Y, g:i a', strtotime($pickup_time))
						 : '';
		$item_count = get_post_meta($pickup_id, 'item_count', true);
		$order_total = get_post_meta($pickup_id, 'order_total', true);
		$order_items = $this->list_order_line_items($pickup_id, $total_quantity);
		$order_details_rows = [
			__('Customer Name', 'curbside-pickup') => $customer_name,
			__('Customer Email', 'curbside-pickup') => $customer_email,
			__('Pickup Time', 'curbside-pickup') => $friendly_time,
			__('Order Total', 'curbside-pickup') => $order_total,
			__('Item Count', 'curbside-pickup') => $item_count,
		];
		$order_details_rows = apply_filters('curbside_pickup_admin_email_notification_order_details', $order_details_rows, $pickup_id, $type);

		// create the order details table
		$order_details = '<table class="curbside_pickup_email_table" style="width: 100%; max-width: 480px;" cellpadding="0" cellspacing="0">';
		$order_details .= '<tbody>';
		foreach($order_details_rows as $order_details_key => $order_details_value) {
			$order_details .= sprintf( '<tr><td width="200px">%s:</td><td>%s</td></tr>',
									   $order_details_key,
									   esc_html($order_details_value) );
		}
		$order_details .= '</tbody>';
		$order_details .= '</table>';
		$order_details .= '<br>' .
						  '<h3>' . __('Order Items', 'curbside-pickup') . '</h3>' .
						  $order_items .
						  '<strong>' . __('Total', 'curbside-pickup') . ': </strong>' .
						  $total_quantity;


		// put the collected information inside a template
		$tmpl =
		'<div class="curbside_pickup_email_pickup_details">
			<h3>%s</h3>
			%s			
		</div>';

		$pickup_details = sprintf( $tmpl,
							__('Order Details', 'curbside-pickup'),
							$order_details );

		// allow filtering and return HTML
		return apply_filters('curbside_pickup_admin_email_notification_pickup_details', $pickup_details, $pickup_id);
	}

	/*
	 * Lists all items from the WooCommerce order associated with this pickup.
	 *
	 * @param int $pickup_id ID (post ID) of the pickup.
	 * @param int $total_quantity (By ref) Optional variable to receive the total item quantity.
	 *
	 * @return string HTML list (ul) of items, on new lines. Empty string if none found.
	 */
	private function list_order_line_items(int $pickup_id, &$total_quantity = 0)
    {
		$items = [];
		$order_id = get_post_meta($pickup_id, 'order_id', true);
		if ( empty($order_id) ) {
			return '';
		}

		$order = wc_get_order( $order_id );
		if ( false == $order ) {
			return '';
		}
		$out = '<ul>';
		foreach ( $order->get_items() as  $item_key => $item_values ) {
			$item_data = $item_values->get_data();
			$out .= sprintf( '<li>%s  × %d</li>',
					esc_html($item_data['name']),
					$item_data['quantity'] );
			$total_quantity += $item_data['quantity'];
		}
		$out .= '</ul>';
		return $out;
    }

	/*
	 * Gets the list of emails to notify from the settings
	 *
	 * @param string $type 'new_order' or 'arrival', depending on the type of notification
	 * @param int $pickup_id The associated pickup ID
	 *
	 * @return array List of emails. Empty array if none found.
	 */
	function get_emails_to_notify(string $type, int $pickup_id)
	{
		switch ( $type) {
			case 'new_order':
				$emails = $this->get_option_value('emails_to_notify_new_orders');
			break;

			case 'arrival':
				$emails = $this->get_option_value('emails_to_notify_arrivals');
			break;

			default:
				$emails = '';
			break;
		}
		$emails = $this->parse_email_list($emails);
		$emails = apply_filters('curbside_pickup_admin_emails_to_notify', $emails, $type, $pickup_id);
		$emails = apply_filters('curbside_pickup_admin_emails_to_notify_' . $type, $emails, $pickup_id);
		return $emails;
	}

	/*
	 * Parse list of emails (text) into an array.
	 * Handles emails split by new lines and commas.
	 *
	 * @param string $emails String potentially containing one or more emails.
	 *
	 * @return array List of emails. Empty array if none found or empty string provided.
	 */
	function parse_email_list($emails)
	{
		// if empty string provided, return empty array
		if ( empty( trim($emails) ) ) {
			return [];
		}

		$email_list = [];
		foreach( preg_split("/((\r?\n)|(\r\n?))/", $emails) as $line ){

			// split line on semicolons and commas
			$line = str_replace(';', ',', $line);
			$emails = explode(',', $line);
			$emails = array_map('trim', $emails);

			if ( !empty($emails) ) {
				$email_list = array_merge($emails, $email_list);
			}
		}
		return $email_list;
	}
	
	/*
	 * Retrieves the edit post link, regardless of whether the current user 
	 * can edit the post. This is a copy of WP's built in get_edit_post_link,
	 * except that the WP function returns an empty string if the current user
	 * is not logged in / doesn't have permission to edit the post in question).
	 *
	 * We need to be able to get the post link in order to send admin notification
	 * emails, hence this modified copy.
	 */	 
	function get_edit_post_link( $id = 0, $context = 'display' )
	{
		$post = get_post( $id );
		if ( ! $post ) {
			return;
		}
	 
		if ( 'revision' === $post->post_type ) {
			$action = '';
		} elseif ( 'display' === $context ) {
			$action = '&amp;action=edit';
		} else {
			$action = '&action=edit';
		}
	 
		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! $post_type_object ) {
			return;
		}
	 
		if ( $post_type_object->_edit_link ) {
			$link = admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) );
		} else {
			$link = '';
		}
		
		return $link;
	}
}
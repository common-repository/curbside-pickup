<?php

namespace Curbside_Pickup;

class Scheduled_Emails extends Base_Class
{
	function __construct(\Curbside_Pickup\Emails $emails)
	{
		$this->Emails = $emails;
		$this->add_hooks();
	}

	function add_hooks()
	{
		add_action( 'curbside_pickup_pickup_created', array($this, 'queue_new_pickup_email'), 10, 2 );
		add_action( 'curbside_pickup_pickup_rescheduled', array($this, 'queue_rescheduled_email'), 10, 2 );
		add_action( 'curbside_pickup_pickup_completed', array($this, 'queue_completed_pickup_email'), 10, 1 );
	}

	function queue_new_pickup_email($pickup_id, $order_id)
	{
		$do_send = $this->get_option_value('send_order_confirmation_email');
		if ( !empty($do_send) ) {
			$this->Emails->queue_email( 'new_pickup', $pickup_id );
		}
	}

	function queue_rescheduled_email($pickup_id, $new_date)
	{
		$do_send = $this->get_option_value('send_rescheduled_pickup_email');
		if ( !empty($do_send) ) {
			$this->Emails->queue_email( 'rescheduled_pickup', $pickup_id );
		}
	}

	function queue_completed_pickup_email($pickup_id)
	{
		$do_send = $this->get_option_value('send_completed_pickup_email');
		if ( !empty($do_send) ) {
			$this->Emails->queue_email( 'completed_pickup', $pickup_id );
		}
	}
}

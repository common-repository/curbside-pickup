<?php

namespace Curbside_Pickup;

class Dashboard extends Base_Class
{
	function __construct( \Curbside_Pickup\Manager $manager, \Curbside_Pickup\Scheduler $scheduler )
	{
		$this->Scheduler = $scheduler;
		$this->Manager = $manager;
		$this->add_hooks();
	}

	function add_hooks()
	{
		// ajax hooks for dashboard
		add_action( 'wp_ajax_curbside_pickup_refresh_dashboard', array($this, 'ajax_refresh_dashboard') );
		add_action( 'wp_ajax_curbside_pickup_update_order', array($this, 'ajax_update_order') );
		add_action( 'wp_ajax_curbside_pickup_load_order_data', array($this, 'ajax_load_order_data') );
	}

	function dashboard_page()
	{
		echo '<div class="wrap">';
			echo '<div id="curbside_pickup_dashboard" class="curbside_pickup_bootstrap">';
				echo '<div class="container-fluid">';
					echo '<div class="row">';
						echo '<div class="col">';
							echo '<h1 class="wp-heading-inline">' . __('Dashboard', 'curbside-pickup') . ' <span id="curbside_pickup_dashboard_loading_icon" class="d-none"><i class="fas fa-sync fa-spin"></i></span></h1>';
						echo '</div>'; // end .col-10
						echo '<div class="col-2 my-auto">';
							echo $this->get_locations_select();
						echo '</div>'; // end .col-2
						do_action('curbside_pickup_dashboard_filters');
					echo '</div>'; // end .row

					echo '<div class="row">';
						$this->output_dashboard_orders('waiting', 'Customers Waiting For Pickup Now', 'users');
					echo '</div>'; // end .row

					if ( $this->get_option_value('dashboard_show_late_panel', true) ) {
						echo '<div class="row">';
							$this->output_dashboard_orders('late', 'Missed Pickup Time', 'exclamation-circle');
						echo '</div>'; // end .row
					}

					if ( $this->get_option_value('dashboard_show_in_window_panel', true) ) {
						echo '<div class="row">';
							$this->output_dashboard_orders('in_window', 'Expected Soon', 'clock' );
						echo '</div>'; // end .row
					}

					if ( $this->get_option_value('dashboard_show_upcoming_panel', true) ) {
						echo '<div class="row">';
							$this->output_dashboard_orders('upcoming', 'Scheduled Later Today', 'calendar-alt');
						echo '</div>'; // end .row
					}

					if ( $this->get_option_value('dashboard_show_tomorrow_panel', true) ) {
						echo '<div class="row">';
							$this->output_dashboard_orders('tomorrow', 'Scheduled For Tomorrow', 'calendar-plus');
						echo '</div>'; // end .row
					}

					echo '</div>'; // end .container-fluid
				echo '<div id="curbside_pickup_toasts_container" aria-live="polite" aria-atomic="true"></div>';
			echo '</div>'; // end #curbside_pickup_dashboard.curbside_pickup_bootstrap
		echo '</div>'; // end .wrap
		//echo '<button id="playsound">Play Sound</button>';

		//echo '<div id="curbside_pickup_order_modal" style="display:none"></div>';

	}

	function get_locations_select()
	{
		if ( $this->locations_module_enabled() ) {
			$ls =  new Location_Selector([
				'input_id' 			=> 'curbside_pickup_dashboard_location',
				'input_name' 		=> '',
				'show_button' 		=> false,
				'show_form' 		=> false,
				'init_query_param' 	=> 'location_id',
			]);
			return $ls->get_output();
		}
		else {
			return '<input type="hidden" id="curbside_pickup_dashboard_location" value="0">';
		}
	}

	function output_dashboard_orders($status, $heading, $fa_icon = '')
	{
		$icon = !empty($fa_icon)
				? sprintf('<i class="fas fa-%s"></i>&nbsp;&nbsp;', $fa_icon)
				: '';
		print('<div class="col-md-12 mb-3">');
			printf( '<div class="curbside_pickup_dashboard_panel" data-status="%s">', $status );
				print( '<h4 class="curbside_pickup_dashboard_heading mb-0">' );
					printf( '%s%s <span class="curbside_pickup_dashboard_counter"></span>', $icon, $heading );
				print( '</h4>' ); // end .card-header
				$list_items = array();
				printf( '<div class="curbside_pickup_dashboard_panel_inner"><div class="curbside_pickup_dashboard_list row">%s</div></div>', implode("\n", $list_items) );
			print ('</div>'); // end .curbside_pickup_dashboard_heading
		print ('</div>'); // .col-md-12
	}

	function ajax_refresh_dashboard()
	{
		$location_id = $this->get_posted_location_id();
		$data = [
			'current_stats' => [
				'customers_waiting' => rand(0, 10),
				'late_pickups' => rand(0, 10),
				'current_pickups' => rand(0, 10),
				'upcoming_pickups' => rand(0, 10),
			],
			'daily_stats' => [
				'customers_served' => rand(0, 10),
				'completed_total' => rand(100, 700),
				'avg_order_value' => rand(10, 30),
				'open_orders' => rand(0, 10),
				'open_order_total' => rand(40, 250),
			],
			'waiting' => $this->get_customer_list('waiting', $location_id),
			'late' => $this->get_customer_list('late', $location_id),
			'in_window' => $this->get_customer_list('in_window', $location_id),
			'upcoming' => $this->get_customer_list('upcoming', $location_id),
			'tomorrow' => $this->get_customer_list('tomorrow', $location_id),
			'events' => $this->Manager->get_ajax_events($location_id),
		];
		$ajax_resp = [
			'status'  => 'ok',
			'data'  => $data,
		];
		echo json_encode($ajax_resp);
		wp_die();
	}

	function get_posted_location_id()
	{
		$location_id = !empty($_POST['location_id'])
					   ? intval($_POST['location_id'])
					   : 0;
		if ( empty($location_id) ) {
			$location_id = false;
		}
		return $location_id;
	}

	function get_customer_list($status, $location_id = 0)
	{
		switch ($status) {
			case 'late':
				// at least 30 minutes ago, but not arrived (status: pending)
				$start_time = get_date_from_gmt( '-1 week' );
				$end_time = get_date_from_gmt( '-30 minutes' );
				$orders = $this->Manager->get_orders_by_status('late', $start_time, $end_time, $location_id );
			break;

			case 'upcoming':
				// later today, not arrived (status: pending)
				$start_time = get_date_from_gmt( '+30 minutes' );
				$end_time = $this->local_day_end(); // end of today in local time
				$orders = $this->Manager->get_orders_by_status('pending', $start_time, $end_time, $location_id );
			break;

			case 'tomorrow':
				// later today, not arrived (status: pending)
				$start_time = $this->local_day_start('+1 day'); // start of tomorrow (midnight) in local time
				$end_time = $this->local_day_end('+1 day'); // end of tomorrow in local time
				$orders = $this->Manager->get_orders_by_status('pending', $start_time, $end_time, $location_id );
			break;

			case 'in_window':
				// delivery time is within 30 minutes (+/-), but they haven't arrived yet (status: pending)
				$start_time = get_date_from_gmt( '-30 minutes' );
				$end_time = get_date_from_gmt( '+30 minutes' );
				$orders = $this->Manager->get_orders_by_status('pending', $start_time, $end_time, $location_id );
			break;

			case 'waiting':
				// in the parking lot waiting (status: waiting)
				$orders = $this->Manager->get_orders_by_status('waiting', false, false, $location_id);
			break;

			default:
				$orders = [];
			break;
		}
		return array(
			'orders' => $this->format_orders_for_dashboard($orders, $status),
			'count' => count($orders),
		);
	}

	function format_orders_for_dashboard($orders, $status = '')
	{
		if ( empty($orders) ) {
			return '';
		}

		$output = '';
		foreach($orders as $order) {
			$card = new Dashboard_Card($order->ID);
			$output .= $card->render($status);
		}
		return $output;
	}
	
	function ajax_update_order()
	{
		$pickup_id = !empty($_POST['order_id'])
					 ? intval($_POST['order_id'])
					 : 0;
		$action = !empty($_POST['curbside_pickup_action'])
				  ? sanitize_text_field($_POST['curbside_pickup_action'])
				  : '';
		$message = '';

		switch ($action) {

			case 'reschedule':
				$new_date = !empty($_POST['new_date'])
						    ? sanitize_text_field($_POST['new_date'])
						    : '';
				$new_time = !empty($_POST['new_time'])
						    ? sanitize_text_field($_POST['new_time'])
						    : '';
				$this->Manager->reschedule_pickup($pickup_id, $new_date, $new_time);
				$message = __('Order rescheduled!', 'curbside-pickup');
			break;

			case 'complete':
				$this->Manager->complete_pickup($pickup_id);
				$message = __('Order completed!', 'curbside-pickup');
			break;

			case 'cancel':
				$this->Manager->cancel_pickup($pickup_id);
				$message = __('Order canceled!', 'curbside-pickup');
			break;

			case 'save_notes':
				$notes = !empty($_POST['notes'])
						 ? sanitize_textarea_field($_POST['notes'])
						 : '';
				$this->Manager->update_notes($pickup_id, $notes);
				$message = __('Notes updated!', 'curbside-pickup');
			break;


			default:
				$message = 'NOOP: ' . $action;
			break;

		}

		$ajax_resp = [
			'status' => 'ok',
			'data' => [
				'message' => $message
			]
		];


		echo json_encode($ajax_resp);
		wp_die();
	}

	function ajax_load_order_data()
	{
		$pickup_id = !empty($_POST['order_id'])
					 ? intval($_POST['order_id'])
					 : 0;
		$modal_html = '';
		
		if  ( !empty($pickup_id) ) {		
			$modal = new Update_Pickup_Modal($pickup_id);		
			$modal_html = $modal->render();
		}

		if ( !empty($modal_html) ) {
			$ajax_resp = [
				'status' => 'ok',
				'modal_html' => $modal_html,
			];
		}
		else {
			$ajax_resp = [
				'status' => 'fail',
			];
		}

		echo json_encode($ajax_resp);
		wp_die();
	}
}

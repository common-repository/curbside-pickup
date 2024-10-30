<?php

namespace Curbside_Pickup;

class Front_End extends Base_Class
{
	function __construct(\Curbside_Pickup\Scheduler $scheduler)
	{
		$this->Scheduler = $scheduler;
		$this->add_hooks();
	}

	function add_hooks()
	{
		// enqueue front-end CSS & JS
		//add_action( 'wp_enqueue_scripts', array($this, 'enqueue_css') );
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_js') );

		// ajax hooks for loading date/time options during checkout
		add_action( 'wp_ajax_nopriv_curbside_pickup_load_date_options', array($this, 'ajax_load_date_and_time_options') );
		add_action( 'wp_ajax_curbside_pickup_load_date_options', array($this, 'ajax_load_date_and_time_options') );
		add_action( 'wp_ajax_nopriv_curbside_pickup_load_time_options', array($this, 'ajax_load_time_options') );
		add_action( 'wp_ajax_curbside_pickup_load_time_options', array($this, 'ajax_load_time_options') );

		// output our options on checkout page
		add_action( 'curbside_pickup_display_checkout_fields', array($this, 'curbside_pickup_checkout_box'), 20, 0 );
	}

	function enqueue_css()
	{

	}

	function enqueue_js()
	{
		wp_enqueue_script( 'curbside_pickup', plugins_url('../../assets/js/curbside_pickup_front_end.js', __FILE__), array( 'jquery' ), '', true );
		$pickup_delay = $this->Scheduler->get_pickup_delay();
		$view_vars = [
			'order_delay_minutes' => $pickup_delay,
			'ajaxurl' => admin_url('admin-ajax.php'),
		];
		wp_localize_script( 'curbside_pickup', 'curbside_pickup', $view_vars);
	}

	function curbside_pickup_checkout_box()
	{
		$mode = $this->get_option_value('pickup_time_assignment', 'customer_selects');		
	?>
		<div id="curbside_pickup_checkout_options">
			<h4><?php echo htmlentities( $this->get_shipping_method_title(), ENT_QUOTES, 'UTF-8', false ); ?></h4>
			<?php if ( empty($mode) || 'customer_selects' == $mode ): ?>			
			<div class="curbside_pickup_checkout_fields">
			<?php
				$loc_count = $this->get_post_type_count('pickup-location');
				if ( $loc_count > 1 ) {			
					woocommerce_form_field( 'curbside_pickup_pickup_location' , array(
						'type'          => 'select',
						'class'         => array('form-row-wide curbside_pickup_pickup_location', 'select2'),
						'label'         => 'Pickup Location:',
						'required'      => true,
						'options' 		=> $this->Scheduler->get_location_options(),
					), WC()->checkout->get_value( 'curbside_pickup_pickup_location' ));
				}
				else if (1 == $loc_count) {
					printf( '<input type="hidden" id="%s" name="%s" value="%d" />', 
						    'curbside_pickup_pickup_location',
						    'curbside_pickup_pickup_location',
							$this->get_oldest_post_id('pickup-location') );
				}
				else if (0 == $loc_count) {
					printf( '<input type="hidden" id="%s" name="%s" value="0" />', 
						    'curbside_pickup_pickup_location',
						    'curbside_pickup_pickup_location' );
				}
				
				woocommerce_form_field( 'curbside_pickup_pickup_date' , array(
					'type'          => 'select',
					'class'         => array('form-row-wide curbside_pickup_pickup_date', 'select2'),
					'label'         => 'Pickup Date:',
					'required'      => true,
					'options' 		=> $this->Scheduler->get_date_options(),
				), WC()->checkout->get_value( 'curbside_pickup_pickup_date' ));

				woocommerce_form_field( 'curbside_pickup_pickup_time' , array(
					'type'          => 'select',
					'class'         => array('form-row-wide curbside_pickup_pickup_time', 'select2'),
					'label'         => 'Pickup Time:',
					'required'      => true,
					'options' 		=> $this->Scheduler->get_time_options(),
				), WC()->checkout->get_value( 'curbside_pickup_pickup_time' ));
			?>
			</div>
			<?php else: 
				$nat = $this->Scheduler->get_next_available_time();
				$nat_ts = strtotime($nat);				
				$nat_fmt = date('F j, Y, g:i a', $nat_ts);
				$nat_date = date('Y-m-d', $nat_ts);
				$nat_time = date('h:i', $nat_ts);
				echo sprintf( __('Pickup time: %s', 'curbside_pickup'), $nat_fmt );
				
				printf( '<input type="hidden" id="%s" name="%s" value="%s" />', 
						'curbside_pickup_pickup_date',
						'curbside_pickup_pickup_date',
						$nat_date );
						
				printf( '<input type="hidden" id="%s" name="%s" value="%s" />', 
						'curbside_pickup_pickup_time',
						'curbside_pickup_pickup_time',
						$nat_time );
				
			endif; ?>
		</div>
	<?php
	}

	function get_shipping_method_title()
	{
		//default
		$method_name = 'Curbside Pickup';
		
		// look for an instance of our shipping method, and grab its name
		foreach ( WC()->cart->get_shipping_packages() as $package_id => $package ) {
			// Check if a shipping for the current package exist
			if ( WC()->session->__isset( 'shipping_for_package_'.$package_id ) ) {
				// Loop through shipping rates for the current package
				foreach ( WC()->session->get( 'shipping_for_package_'.$package_id )['rates'] as $shipping_rate_id => $shipping_rate ) {
					$method_id   = $shipping_rate->get_method_id(); // The shipping method slug
					if ( 'curbside_pickup' == $method_id ) {
						$label_name  = $shipping_rate->get_label(); // The label name of the method
						$method_name = $label_name;
						break;
					}
				}
			}
		}
		
		// apply filters
		$method_name = apply_filters('curbside_pickup_get_shipping_method_name', $method_name);
		return $method_name;
	}

	function ajax_load_date_options()
	{
		$location_id = !empty($_POST['location_id'])
					   ? intval($_POST['location_id'])
					   : 0;
		$opts 				= $this->Scheduler->get_date_options($location_id);


		$first_day_hours = [];
		if ( !empty($opts) ) {
			$first_day = array_key_first($opts);
			$first_day_hours = $this->Scheduler->get_hours_for_date($first_day);
		}

		$data = [
			'options' => [
				'dates' => $this->array_to_options($opts),
				'first_hours' => $first_day_hours,
			]
		];

		$ajax_resp = [
			'status'  => 'ok',
			'data'  => $data,
		];
		echo json_encode($ajax_resp);
		wp_die();
	}

	function ajax_load_date_and_time_options()
	{
		$location_id = !empty($_POST['location_id'])
					   ? intval($_POST['location_id'])
					   : 0;

		$date_options = $this->Scheduler->get_date_options($location_id);
		$first_day_hours = [];
		if ( !empty($date_options) ) {
			$first_day = array_key_first($date_options);
			$first_day_hours = $this->Scheduler->get_hours_for_date($first_day, $location_id);
		}

		$data = [
			'options' => [
				'dates' => $this->array_to_options($date_options),
				'first_hours' => $this->array_to_options($first_day_hours),
			]
		];

		$ajax_resp = [
			'status'  => 'ok',
			'data'  => $data,
		];
		echo json_encode($ajax_resp);
		wp_die();
	}

	function ajax_load_time_options()
	{
		$location_id = !empty($_POST['location_id'])
					   ? intval($_POST['location_id'])
					   : 0;

		$selected_date = !empty($_POST['selected_date'])
						 ? sanitize_text_field($_POST['selected_date'])
						 : '';

		$opts = $this->Scheduler->get_time_options($selected_date, $location_id);
		$data = [
			'options' => $this->array_to_options($opts),
		];
		$ajax_resp = [
			'status'  => 'ok',
			'data'  => $data,
		];
		echo json_encode($ajax_resp);
		wp_die();
	}

	function array_to_options($opts)
	{
		$options_html = '';
		foreach($opts as $key => $val) {
			$options_html .= sprintf('<option value="%s">%s</option>', esc_html($key), esc_html($val));
		}
		return $options_html;
	}
}

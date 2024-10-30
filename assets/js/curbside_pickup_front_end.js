/*
 * Curbside Pickup - Free curbside pickup plugin for WordPress and WooCommerce
 * Copyright 2020 Illuminati Karate, Inc. All rights reserved.
 *
 * https://wpcurbside.com/
 *
 */
jQuery(function () {

	var $global_first_time_loaded = false;
	
	var  cache_buster = function() {
		return '?_cb=' + Math.random();
	};
	
	var is_chosen_shipping_method = function() {
		return ( 'curbside_pickup' == get_shipping_method() );
	};

	var get_shipping_method = function() {
		var shipping_method_input = jQuery('#shipping_method input[type="radio"]:checked');
		if ( ! shipping_method_input.length ) {
			shipping_method_input = jQuery('#shipping_method input[type="hidden"]:first');
		}
		return shipping_method_input.val();
	};

	var maybe_show_options_box = function () {
		var $options_box = jQuery('#curbside_pickup_checkout_options');
		if ( is_chosen_shipping_method() ) {
			$options_box.show();
			fancy_selects();
		}
		else {
			$options_box.hide();
		}
	};

	var add_minutes = function($start_date, $minutes_to_add) {
		return new Date( $start_date.getTime() + ($minutes_to_add * 60000) );
	};

	var time_has_passed = function($check_time) {

		// parse the user supplied time into a date (based on today)
		var $parts = $check_time.split(':'),
			$check_date = new Date();
		$check_date.setHours($parts[0], $parts[1]);

		// build in a buffer (default 30 minutes)
		var $now = new Date();
		var $offset = ( 'undefined' != typeof(curbside_pickup) && 'undefined' != typeof(curbside_pickup.order_delay_minutes) )
					  ? curbside_pickup.order_delay_minutes
					  : 30; // default 30 minutes
		$now = add_minutes($now, $offset);

		// now compare the two dates!
		return ($now > $check_date);
	};

	// special date parsing because JS is crazy and ends up with an off-by-one issue
	// depending on timezone (https://stackoverflow.com/a/14569783)
	var parse_date = function($date) {
		doo = new Date($date);
		return new Date( doo.getTime() - doo.getTimezoneOffset() * -60000 );
	};


	var is_today = function($check_date) {
		var $today = new Date();
		var $check = parse_date($check_date);
		return ($today.toDateString() === $check.toDateString());

	};

	var load_times_for_selected_location = function(ev) {
		var $location_input = jQuery('#curbside_pickup_pickup_location'),
			$date_input = jQuery('#curbside_pickup_pickup_date'),
			$time_input = jQuery('#curbside_pickup_pickup_time'),
			$now = new Date(),
			$params = {
				'action' : 'curbside_pickup_load_date_options',
				'location_id' : $location_input.val(),
				'ts' : $now.toLocaleString(),
			};

		$date_input.attr('disabled', 'disabled');
		$time_input.attr('disabled', 'disabled');

		jQuery.post(
			curbside_pickup.ajaxurl + cache_buster(),
			$params,
			function (response) {
				if ( response.data ) {
					$global_first_time_loaded = true;
					$date_input.removeAttr('disabled');
					$time_input.removeAttr('disabled');
					$date_input.html(response.data.options.dates);
					$time_input.html(response.data.options.first_hours);
					
					// remove any times that have already passed
					update_times_for_selected_date();
					
					// re-init select2 with new options
					fancy_selects();
				}
			},
			'json'
		);
	};
	
	var load_times_for_selected_date = function(ev) {
		var $now = new Date();
		var $params = {
			'action' : 'curbside_pickup_load_time_options',
			'location_id' : jQuery('#curbside_pickup_pickup_location').val(),
			'selected_date' : jQuery('#curbside_pickup_pickup_date').children('option:selected').val(),
			'ts' : $now.toUTCString(),
		};

		var $time_input = jQuery('#curbside_pickup_pickup_time');
		$time_input.attr('disabled', 'disabled');

		jQuery.post(
			curbside_pickup.ajaxurl + cache_buster(),
			$params,
			function (response) {
				if ( response.data ) {
					// replace time options with loaded times
					$time_input.removeAttr('disabled');
					$time_input.html(response.data.options);

					// filter available time list based on current time
					update_times_for_selected_date();
				}
			},
			'json'
		);
	};

	var update_times_for_selected_date = function(ev) {
		// find out the currently selected date
		var $time_input = jQuery('#curbside_pickup_pickup_time'),
			$date_input = jQuery('#curbside_pickup_pickup_date'),
			$current_date = $date_input.find('option:selected').val(),
			$i;

		// if it is today, disable any times that are already passed
		// (including a 1 hr buffer)
		if ( is_today($current_date) ) {
			// disable any past dates (+1hr)
			$time_input.find('option').each(function () {
				var $trg = jQuery(this);
				if ( time_has_passed( $trg.val() ) ) {
					//$trg.attr('disabled', 'disabled');
					$trg.remove();
				} else {
					$trg.removeAttr('disabled');
				}
			});

			// if there are no available times, move the date to tomorrow and enable all the times
			if ( 0 == $time_input.find('option:not([disabled])').length ) {
				// select tomorrow option, and enable all of its times
				$date_input.find('option:eq(1)')
						   .attr('selected', 'selected');

				// remove "today" option
				$date_input.find('option:eq(0)')
						   .remove();
				$date_input.trigger('change');

				// re-init selectWoo (select2) so correct options will be enabled / disabled
				$date_input.selectWoo();

				$time_input.find("option[disabled='disabled']")
						   .removeAttr('disabled');
			}

		} else {
			$time_input.find("option[disabled='disabled']")
					   .removeAttr('disabled');
		}

		// select first available time
		$time_input.find('option:not([disabled]):first').attr('selected', 'selected');

		// re-init selectWoo (select2) so correct options will be enabled / disabled
		$time_input.selectWoo();
	};

	var fancy_selects = function () {
		jQuery('#curbside_pickup_checkout_options select').selectWoo();
	};
	
	var updated_checkout = function (ev) {
		if ( !$global_first_time_loaded ) {
			load_times_for_selected_location();
		}
		
		//update_times_for_selected_date();
		fancy_selects();
		maybe_show_options_box();
	};

	var $wc_checkout = jQuery('.woocommerce-checkout');
	var $spt_select = jQuery('#curbside_pickup_pickup_time');
	if ( $wc_checkout && $spt_select && $spt_select.is('select') ) {
		$wc_checkout.on('change', '#curbside_pickup_pickup_location', function (ev) {
			load_times_for_selected_location();
			ev.stopImmediatePropagation();
		});
		$wc_checkout.on('updated_checkout', updated_checkout);
		$wc_checkout.on('change', '#curbside_pickup_pickup_date', function (ev) {
			load_times_for_selected_date();
			ev.stopImmediatePropagation();
		});
		//$wc_checkout.on('updated_checkout', load_times_for_selected_location);
		//$wc_checkout.on('updated_checkout', update_times_for_selected_date);
		//$wc_checkout.on('updated_checkout', load_times_for_selected_date);
		//$wc_checkout.on('updated_checkout', maybe_show_options_box);
		//$wc_checkout.on('updated_checkout', fancy_selects);
		fancy_selects();
		maybe_show_options_box();
		update_times_for_selected_date();
	}

});


/*
 * Curbside Pickup - Free curbside pickup plugin for WordPress and WooCommerce
 * Copyright 2020 Illuminati Karate, Inc. All rights reserved.
 *
 * https://wpcurbside.com/
 *
 */
(function ($) {
	
	if ( 'function' == typeof(console.log) ) {
		console.log('init Curbside Pickup Admin JS');
	}

	var $curbside_pickup_dash_interval = false;
	var $curbside_pickup_refresh_delay = 5010;
	var $curbside_pickup_audio = {};
	var $load_order_ajax_request = false;
	var $refresh_dash_ajax_request = false;
	$curbside_pickup_audio["music_box"] = new Audio();
	$curbside_pickup_audio["music_box"].src = curbside_pickup.notification_sound_src;

	// src: https://stackoverflow.com/a/2919363
	var nl2br = function(str, is_xhtml) {
		if (typeof str === 'undefined' || str === null) {
			return '';
		}
		var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
		return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
	};
	
	var escape_txt = function(str) {
		return jQuery("<div>").text(str).html();
	};
	
	var maybe_abort_load_order_ajax = function() {
		if ( false != $load_order_ajax_request ) {
			$load_order_ajax_request.abort();
			$load_order_ajax_request = false;
		}	
	};

	var maybe_abort_refresh_dashboard_ajax = function() {
		if ( false != $refresh_dash_ajax_request ) {
			$refresh_dash_ajax_request.abort();
			$refresh_dash_ajax_request = false;
		}	
	};

	var reset_refresh_interval = function() {
		
		// clear out any existing timers
		if ( $curbside_pickup_dash_interval ) {
			clearInterval($curbside_pickup_dash_interval);
		}

		// set a timer to periodically refresh 
		$curbside_pickup_dash_interval = setInterval(function () {
			refresh_dashboard();
		}, $curbside_pickup_refresh_delay);
		
		// refresh now (in one tick)
		setTimeout(function () {
			refresh_dashboard();
		}, 1);
	};
	
	var collapse_current_modal_form = function () {
		var $current_form = jQuery('#curbside_pickup_order_modal .collapse.show');
		var $form_id = $current_form.attr('id');
		var $btn = jQuery('#curbside_pickup_order_modal .curbside_pickup_update_modal_buttons > button[data-target="#' + $form_id + '"]');
		$current_form.collapse('hide');
		$btn.removeClass('active')
			.removeClass('btn-info');
		enable_primary_modal_buttons();		
	};
	
	var disable_primary_modal_buttons = function () {
		jQuery('.curbside_pickup_modal_button_complete_pickup').attr('disabled', true);
		jQuery('.curbside_pickup_modal_button_cancel_pickup').attr('disabled', true);
	};
	
	var enable_primary_modal_buttons = function () {
		jQuery('.curbside_pickup_modal_button_complete_pickup').attr('disabled', false);
		jQuery('.curbside_pickup_modal_button_cancel_pickup').removeAttr('disabled');
	};

	var disable_secondary_modal_buttons = function () {
		jQuery('#curbside_pickup_order_modal .curbside_pickup_update_modal_buttons > button').attr('disabled', true);
	};
	
	var enable_secondary_modal_buttons = function () {
		jQuery('#curbside_pickup_order_modal .curbside_pickup_update_modal_buttons > button').attr('disabled', false);
	};

	var show_loading_modal = function() {
		jQuery('#spinnerModal').remove();
		var $modal_html = '<div class="modal fade" tabindex="-1" role="dialog" id="spinnerModal">' + 
			'<div class="modal-dialog modal-dialog-centered text-center text-white text-large" role="document">' + 
				'<span class="fa fa-spinner fa-spin fa-3x w-100"></span>' + 
			'</div>' +
		'</div>';
		$modal = jQuery($modal_html);		
		jQuery('.curbside_pickup_bootstrap').append($modal);
		$modal.modal();		
		jQuery('#spinnerModal').on('click', function() {
			maybe_abort_load_order_ajax();
		});
	};
	
	var hide_loading_modal = function() {
		jQuery('#spinnerModal').modal('dispose');
		jQuery('#spinnerModal').remove();
		jQuery('.modal-backdrop.fade.show').remove();
	};

	var show_toast = function($heading, $body)
	{
		var $now = 'just now';
		var $t = jQuery('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">' +
		  '<div class="toast-header bg-info text-white">' +
			//'<img src="..." class="rounded mr-2" alt="...">' +
			'<strong class="mr-auto">' + $heading + '</strong>' +
			'<small class="text-white">' + $now + '</small>' +
			'<button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">' +
			  '<span aria-hidden="true">&times;</span>' +
			'</button>' +
		  '</div>' +
		  '<div class="toast-body">' +
			$body +
		  '</div>' +
	'</div>').toast({'delay': 8000});

		jQuery('#curbside_pickup_toasts_container').prepend($t);
		$t.toast('show');
	};

	var hide_show_by_value = function($target, $condition) {
		if ( $condition ) {
			$target.show();
		} else {
			$target.hide();
		}
	};

	var refresh_dashboard = function () {
		var load_data = function(callback) {
			var $now = new Date();
			var $params = {
				'action' : 'curbside_pickup_refresh_dashboard',
				'location_id' : jQuery('#curbside_pickup_dashboard_location').val(),
				'time' : $now.toUTCString()
			};
			
			// allow plugins to add data here using triggerHandler
			$update_params = jQuery('#curbside_pickup_dashboard').triggerHandler("filter_dashboard_params", $params);
			if ( typeof($update_params) != 'undefined' ) {
				$params = $update_params;
			}

			maybe_abort_refresh_dashboard_ajax();
			$refresh_dash_ajax_request = jQuery.post(
				ajaxurl,
				$params,
				function (response) {
					if ( response.data ) {
						callback(response.data);
					}
				},
				'json'
			);
		};

		var update_customer_panel = function($panel_id, $customers) {
			var $panel = jQuery('.curbside_pickup_dashboard_panel[data-status="' + $panel_id + '"]');
			var $panel_list = $panel.find('.curbside_pickup_dashboard_list');
			var $panel_counter = $panel.find('.curbside_pickup_dashboard_counter');
			$panel_list.html($customers.orders);
			if ( $customers.count > 0 ) {
				$panel_counter.html('(' + $customers.count + ')');
			} else {
				$panel_counter.html('');
			}

		};

		var update_dash = function($data) {

			// update the customers waiting now panel
			update_customer_panel('waiting', $data.waiting);

			// update the missed pickup window panel
			update_customer_panel('late', $data.late);

			// update the in pickup window now panel
			update_customer_panel('in_window', $data.in_window);

			// update the later today panel
			update_customer_panel('upcoming', $data.upcoming);

			// update the tomorrow panel
			update_customer_panel('tomorrow', $data.tomorrow);

			// update the current stats panel
			// update the daily stats panel

			// fire any events
			if ( $data.events.length > 0 ) {
				var $event_index,
					$event,
					$toast_body;

				for ( $event_index in $data.events) {
					$event = $data.events[$event_index];
					$toast_body = '<strong>' + $event.data.customer_name + '</strong><br><br>' + $event.data.customer_notes;
					show_toast('Customer Arrived', $toast_body);
				}

				$curbside_pickup_audio["music_box"].play();
			}
		};

		load_data( update_dash );

	};

	var show_order_modal = function ($order_id) {
		var round_time_to_quarter_hour = function(time) {

			if ( 'undefined' == typeof (time) ) {
				var timeToReturn = new Date();
			} else {
				var timeToReturn = new Date(time);
			}

			timeToReturn.setMilliseconds(Math.ceil(timeToReturn.getMilliseconds() / 1000) * 1000);
			timeToReturn.setSeconds(Math.ceil(timeToReturn.getSeconds() / 60) * 60);
			timeToReturn.setMinutes(Math.ceil(timeToReturn.getMinutes() / 15) * 15);

			return timeToReturn;
		}

		var get_next_time_interval = function() {
			var t = round_time_to_quarter_hour();
			return t.getHours().toString().padStart(2, '0') + ':' + t.getMinutes().toString().padEnd(2, '0');
		};

		var get_todays_date_formatted = function() {
			d = new Date();
			return d.toJSON().slice(0, 10);
		};
		
		var load_data = function(callback) {
			maybe_abort_load_order_ajax();
			var $now = new Date();
			var $params = {
				'action' : 'curbside_pickup_load_order_data',
				'order_id' : $order_id
			};
			
			show_loading_modal();
			$load_order_ajax_request = jQuery.post(
				ajaxurl,
				$params,
				function (response) {
					$load_order_ajax_request = false;
					hide_loading_modal();
					if ( response.status ) {
						callback(response);
					}
				},
				'json'
			);
		};

		var hide_modal = function() {
			jQuery('#curbside_pickup_order_modal').modal('hide');
		};

		var show_modal = function($response) {
			// rm any old modals
			jQuery('#curbside_pickup_order_modal').remove();
			
			// show the new modal
			var $new_modal = jQuery($response.modal_html);
			jQuery('.curbside_pickup_bootstrap').append($new_modal);
			$new_modal.modal();
			
			// fire event for plugis to hook to
			jQuery('#curbside_pickup_order_modal').trigger('modal_loaded');
		};

		hide_modal();
		load_data( show_modal );

	};

	var update_order = function ($order_id, $curbside_pickup_action, $params)
	{
		if ( typeof($params) == 'undefined' ) {
			$params = {};
		}

		$params['action'] = 'curbside_pickup_update_order';
		$params['order_id'] = $order_id;
		$params['pickup_id'] = $order_id;
		$params['curbside_pickup_action'] = $curbside_pickup_action;

		var remove_pickup_from_dashboard = function($pickup_id) {
			jQuery('.curbside_pickup_dashboard_customer_row[data-order-id="' + $pickup_id + '"]').remove();
		};

		var load_data = function(callback) {
			jQuery.post(
				ajaxurl,
				$params,
				function (response) {
					if ( response.data ) {
						callback(response.data, $params);
					}
				},
				'json'
			);
		};

		var handle_response = function($data, $params) {
			jQuery('body').trigger('curbside_pickup_order_updated', $data, $params);
			var $modal = jQuery('#curbside_pickup_order_modal');
			switch($params['curbside_pickup_action']) {
				case 'complete':
					$modal.modal('hide');
					remove_pickup_from_dashboard($params['pickup_id']);
					show_toast('Success', 'Pick-up Completed!');
				break;

				case 'cancel':
					$modal.modal('hide');
					remove_pickup_from_dashboard($params['pickup_id']);
					show_toast('Success', 'Pick-up canceled.');
				break;

				case 'reschedule':
					// todo: move to new column instead of waiting for refresh
					$modal.modal('hide');
					show_toast('Success', 'Pick-up rescheduled.');
				break;

				case 'save_notes':
					// update the input with this new value
					$modal.find('.curbside_pickup_notes').html( nl2br( escape_txt( $params['notes']) ) );
					jQuery('#curbside_pickup_edit_notes_form').collapse('hide');
					hide_show_by_value( $modal.find('.hide_if_no_notes'), $params['notes'] );
					show_toast('Success', 'Notes saved.');
				break;

				default:
				break;
			}
			reset_refresh_interval();
		};

		load_data( handle_response );

	};


	function ask_for_notification_permission() {
	  // function to actually ask the permissions
	  function handlePermission(permission) {
		// Whatever the user answers, we make sure Chrome stores the information
		if(!('permission' in Notification)) {
		  Notification.permission = permission;
		}

		// set the button to shown or hidden, depending on what the user answers
	  /*   if(Notification.permission === 'denied' || Notification.permission === 'default') {
		  jQuery('.curbside_pickup_btn_enable_permissions').css('display', 'block');
		} else {
		  jQuery('.curbside_pickup_btn_enable_permissions').css('display', 'block');
		}
	   */
	  }

	  // Let's check if the browser supports notifications
	  if (!('Notification' in window)) {
		//console.log("This browser does not support notifications.");
	  } else {
		if(checkNotificationPromise()) {
		  Notification.requestPermission()
		  .then((permission) => {
			handlePermission(permission);
		  })
		} else {
		  Notification.requestPermission(function(permission) {
			handlePermission(permission);
		  });
		}
	  }

	  function checkNotificationPromise() {
		try {
		  Notification.requestPermission().then();
		} catch(e) {
		  return false;
		}

		return true;
	  }
	}

	jQuery(function () {

		var show_customer_details = function(ev) {
			var $trg = jQuery(ev.currentTarget);
			var $order_id = $trg.data('order-id');

			// show the modal with the customer's info
			show_order_modal($order_id);
		};

		var handleModalButton = function (ev) {
			var $modal = jQuery('#curbside_pickup_order_modal'),
				$btn = jQuery(ev.currentTarget),
				$action = $btn.data('action'),
				$pickup_id = $btn.data('pickup-id');

			switch($action) {
				case 'reschedule':
					var $new_date = $modal.find('select[name="curbside_pickup_reschedule_date"]').val(),
						$new_time = $modal.find('select[name="curbside_pickup_reschedule_time"]').val();
					update_order($pickup_id, $action, {
						'new_date' : $new_date,
						'new_time' : $new_time,
					});
					collapse_current_modal_form();
				break;

				case 'complete':
					update_order($pickup_id, $action, {});
				break;

				case 'cancel':
					update_order($pickup_id, $action, {});
				break;

				case 'save_notes':
					var $notes = $modal.find('#curbside_pickup_edit_notes').val();
					update_order($pickup_id, $action, {
						'notes': $notes
					});
				break;

				default:
					$modal.modal('hide');
				break;




			}

		};

		// init dashboard
		jQuery('.curbside_pickup_btn_enable_permissions').on('click', ask_for_notification_permission);
		jQuery('#curbside_pickup_dashboard').on('click', '.curbside_pickup_dashboard_customer_row', show_customer_details);
		jQuery('body').on('click', '#curbside_pickup_order_modal .curbside_pickup_modal_button', handleModalButton);
		jQuery('#curbside_pickup_dashboard_location').on('change', reset_refresh_interval);
		jQuery(document).on('click', '.curbside_pickup_update_order_button', show_customer_details);
		jQuery('body').on('curbside_pickup_order_updated', function ($data, $params) {
			if ( 0 == jQuery('#curbside_pickup_dashboard').length ) {
				jQuery('body').data('hide_on_modal_close', true);
			}
		});
		jQuery('body').on('hide.bs.modal', '#curbside_pickup_order_modal', function (ev) {
			hide_loading_modal();
			if ( jQuery('body').data('hide_on_modal_close') ) {
				window.location.reload();
			}
		});
		jQuery('#curbside_pickup_dashboard').on('refresh_data', reset_refresh_interval);

		// init dashboard refresh timer
		var $dash = jQuery('#curbside_pickup_dashboard');
		if ( $dash.length > 0 ) {
			reset_refresh_interval();
		}

		// in the modals, collapse any other open panels when a panel is shown,
		// and update the active buttons
		var $bs = jQuery('.curbside_pickup_bootstrap');
		if ( $bs.length > 0 ) {
			jQuery('body').on('show.bs.collapse', '#curbside_pickup_order_modal .collapse', function ($ev) {
				var $trg = jQuery($ev.currentTarget)
					$modal = jQuery('#curbside_pickup_order_modal'),
					$id = $trg.attr('id');
					$btn = jQuery('#curbside_pickup_order_modal .curbside_pickup_update_modal_buttons > button[data-target="#' + $id + '"]');

				// hide any other inline forms that are open
				$modal.find('.collapse.show')
					  .not($trg)
					  .collapse('hide');
					  
				// remove the active classes from any other modal buttons
				$modal.find('.curbside_pickup_update_modal_buttons button.active')
					  .removeClass('active')
					  .removeClass('btn-info');
				
				// add the active classes to the current button
				$btn.addClass('active')
					.addClass('btn-info');
					
				disable_primary_modal_buttons();
			});

			// when an inline form is canceled, hide it and re-enable the primary modal buttons
			jQuery('body').on('hide.bs.collapse', '#curbside_pickup_order_modal .collapse', function ($ev) {
				var $form_id = jQuery($ev.currentTarget).attr('id');
				var $btn = jQuery('#curbside_pickup_order_modal .curbside_pickup_update_modal_buttons > button[data-target="#' + $form_id + '"]');
				$btn.removeClass('active')
					.removeClass('btn-info');
				enable_primary_modal_buttons();
			});			

			// special handlers for the primary Cancel Pickup button
			jQuery('body').on('click', '.curbside_pickup_modal_button_cancel_pickup', function ($ev) {
				disable_secondary_modal_buttons();
			});
			// special handlers for cancel button inside Cancel Pickup form
			jQuery('body').on('click', '#curbside_pickup_cancel_pickup_form .curbside_pickup_modal_button_cancel_form', function ($ev) {
				enable_secondary_modal_buttons();
			});
		}
	});

	jQuery('#playsound').click(function () {
		$curbside_pickup_audio["music_box"].play();
	});

})();

/* Time selectors */
(function () {

	jQuery(function () {

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


		var load_times_for_selected_date = function(ev) {
			var $now = new Date(),
				$modal = jQuery('#curbside_pickup_order_modal'),
				$location_id = $modal.find('input[name="curbside_pickup_delivery_location_id"]').val(),
				$selected_date = $modal.find('select[name="curbside_pickup_reschedule_date"]').val();
			var $params = {
				'action': 'curbside_pickup_load_time_options',
				'location_id': $location_id,
				'selected_date': $selected_date,
				'ts' : $now.toUTCString(),
			};

			jQuery.post(
				curbside_pickup.ajaxurl,
				$params,
				function (response) {
					if ( response.data ) {

						var $time_input = jQuery('#curbside_pickup_order_modal').find('select[name="curbside_pickup_reschedule_time"]');

						// replace time options with loaded times
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
			var $modal = jQuery('#curbside_pickup_order_modal'),
				$time_input = jQuery('select[name="curbside_pickup_reschedule_time"]', $modal),
				$date_input = jQuery('select[name="curbside_pickup_reschedule_date"]', $modal),
				$current_date = $date_input.find('option:selected').val(),
				$i;

			// if it is today, disable any times that are already passed
			// (including a 1 hr buffer)
			if ( is_today($current_date) ) {
				// disable any past dates (+1hr)
				$time_input.find('option').each(function () {
					var $trg = jQuery(this);
					if ( time_has_passed( $trg.val() ) ) {
						$trg.attr('disabled', 'disabled');
					} else {
						$trg.removeAttr('disabled');
					}
				});

				// if there are no available times, move the date to tomorrow and enable all the times
				if ( 0 == $time_input.find('option:not([disabled])').length ) {
					$date_input.find('option:eq(1)')
											   .attr('selected', 'selected');
					$time_input.find("option[disabled='disabled']")
							   .removeAttr('disabled');
				}

			} else {
				$time_input.find("option[disabled='disabled']")
						   .removeAttr('disabled');
			}

			// select first available time
			$time_input.find('option:not([disabled]):first').attr('selected', 'selected');
		};

		
		// reload time selectors when date is changed
		// NOTE: binding this to the body so that it doesnt have to be recreated 
		// 		 every time a modal is created
		jQuery('body').on('show.bs.collapse', '.curbside_pickup_modal_reschedule_fields', load_times_for_selected_date);
		jQuery('body').on('change', 'select[name="curbside_pickup_reschedule_date"]', load_times_for_selected_date);
		
		var loading_icon = jQuery('#curbside_pickup_dashboard_loading_icon');
		if ( loading_icon.length > 0 ) {
			jQuery( document ).ajaxStart(function() {
				loading_icon.removeClass('d-none');
				loading_icon.addClass('d-inline-block');
			});
			jQuery( document ).ajaxComplete(function() {
				loading_icon.removeClass('d-inline-block');
				loading_icon.addClass('d-none');
			});
		};
	});

	var Auto_Updating_Widget = function ($widget) {
		
		var $widget_slug = jQuery($widget).data('widget-slug');
		if  ( ! $widget_slug ) {
			return;
		}
		
		var $ajax_requests = [];
		
		var get_random_in_range = function($min, $max) {
		  return Math.floor(Math.random() * ($max - $min + 1) + $min);
		};
		
		var update_widget = function () {
			// maybe abort existing ajax request
			if ($ajax_requests[$widget_slug]) {
				$ajax_requests[$widget_slug].abort();
				$ajax_requests[$widget_slug] = false;
			}			
			
			var $params = {
				'action' : 'update_widget_' + $widget_slug,
			};

			$ajax_requests[$widget_slug] = jQuery.post(
				ajaxurl,
				$params,
				function (response) {
					if ( response.widget_body ) {
						jQuery('#' + response.widget_slug + ' .inside').html(response.widget_body);
					}
				},
				'json'
			);			
		};

		// update widget contents every ~5 seconds
		// (small jitter added to space the requests out, give server a break)
		setInterval( update_widget, get_random_in_range(5000, 5400) );
	};

	// init auto updating wp dashboard widgets
	jQuery('.curbside_pickup_wp_dashboard_widget').each(function () {
		Auto_Updating_Widget(this);
		return true;
	});

})(jQuery);
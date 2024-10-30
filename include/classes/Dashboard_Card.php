<?php

namespace Curbside_Pickup;

class Dashboard_Card extends Base_Class
{
	var $order_id;
	
	function __construct(int $order_id)
	{
		$this->order_id = $order_id;		
	}
	
	function render($status = '')
	{
		// load and extract view vars for the template
		$view_vars = $this->get_view_vars($status);
		extract ($view_vars);
		
		// render the template and return the output
		$tmpl_path = $this->get_template_path();
		ob_start();
		include( $tmpl_path );
		$output = ob_get_contents();
		ob_end_clean();
		return $output;		
	}
	
	function get_view_vars($status = '')
	{
		$colors = new Colors();
		$meta = get_post_meta($this->order_id);		
		$card_highlight_color = $this->get_card_highlight_color();
		$card_text_color = $colors->contrast($card_highlight_color);
		
		$vars['pickup_id'] = $this->order_id;		
		$vars['order_id'] = $this->order_id;		
		$vars['status'] = $status;
		$vars['badge_style'] = '';
		$vars['badge_text_style'] = '';
		
		if ( !empty($card_highlight_color) ) {
			$vars['badge_style'] = sprintf( 'style="background-color: %s !important;"',
									  htmlspecialchars($card_highlight_color) );
		}
		
		if ( !empty($card_text_color) ) {
			$colors = new Colors();
			$card_text_color = $colors->contrast($card_highlight_color);
			$vars['badge_text_style'] = sprintf( 'style="color: %s !important;"',
										htmlspecialchars($card_text_color) );
		}
		
		$vars['customer_name'] = mb_strimwidth($meta['customer_name'][0], 0, 20, '...'); // trim to max 20 chars
		$vars['customer_initials'] = $this->get_initials($meta['customer_name'][0]);
		$vars['order_total'] = $meta['order_total'][0];
		$vars['item_count'] = $meta['item_count'][0];

		$vars['pickup_location_name'] = $this->locations_module_enabled() && !empty($meta['delivery_location']) && !empty($meta['delivery_location'][0])
									  ? get_the_title($meta['delivery_location'][0])
									  : '';
		
		$date_format = 'g:i a';
		$vars['scheduled_pickup_time'] = ! empty($meta['scheduled_delivery_time'][0])
										 ? date($date_format, strtotime($meta['scheduled_delivery_time'][0]))
										 : '';
		
		$vars['arrival_time'] = ('waiting' == $status)
								? $this->friendly_time( $meta['arrival_time'][0] )
								: '';
		
		$vars['space_number'] = ! empty($meta['space_number'][0])
								? $meta['space_number'][0]
								: '';
				
		$vars['vehicle_description'] = ! empty($meta['vehicle_description'][0])
									   ? $meta['vehicle_description'][0]
									   : '';
				
		$vars['customer_notes'] = ! empty($meta['customer_notes'][0])
								  ? $meta['customer_notes'][0]
								  : '';
				
		$vars['internal_notes'] = ! empty($meta['notes'][0])
								  ? $meta['notes'][0]
								  : '';
		return apply_filters('curbside_pickup_dashboard_card_view_vars', $vars, $this->order_id, $meta);		
	}
	
	function get_template_path()
	{
		// look for a copy of the template in their theme first
		$template = locate_template('curbside-pickup/dashboard_card.php');

		// if not found, use the built in template
		if ( empty($template) ) {
			$template = plugin_dir_path(__FILE__) . '../../templates/dashboard_card.php';
		}
		
		return $template;
	}
	
	function get_card_highlight_color()
	{
		$color = apply_filters('curbside_pickup_dashboard_card_highlight_color', '', $this->order_id);
		return !empty($color)
			   ? $color
			   : '#1e73be'; // blue. @TODO output value from setting
	}	

	/* 
	 * Given a name, returns the initials. Limits the number of initials 
	 * specified by the value of $max_initials (default: 3)
	 *
	 * @param string $name Input string
	 * @param int $max_initials Optional. Max number of initials to return. 
	 * 							Pass -1 for unlimited.
	 *
	 * @return string The initials.
	 */
	function get_initials($name, $max_initials = 3)
	{
		if ( empty( trim($name) ) ) {
			return '';
		}
		
		$initials = [];
		$parts = explode(' ', $name);

		// grab the first letter of each word
		foreach ($parts as $part) {
			$initials[] = substr($part, 0, 1);
		}

		// trim to $max_initials if needed
		if ( $max_initials > 0 ) {
			$initials = array_slice($initials, 0, $max_initials);
		}

		// concatenate the initials  into a string and return
		return implode('', $initials);
	}
	
	function maybe_s($input, $to_add = 's', $alt = '')
	{
		if ( !empty($input) ) {
			return $to_add;
		} else {
			return $alt;
		}
	}
}


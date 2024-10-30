<?php

namespace Curbside_Pickup;

class WP_Dashboard_Widget extends Base_Class
{
	var $args = [];
	var $widget_title = 'Curbside Pickup';
	var $widget_slug = 'curbside_pickup_wp_dashboard_customers_widget';
	
	function __construct( \Curbside_Pickup\Manager $manager, $args = [] )
	{
		$this->Manager = $manager;
		$this->set_args($args);
		$this->add_hooks();
	}
	
	function set_args($args)
	{
		$this->args = $args;
	}

	function get_widget_title()
	{
		return $this->widget_title;
	}
	
	function get_widget_slug()
	{
		return $this->widget_slug;
	}
	
	function add_hooks()
	{
		add_action( 'wp_dashboard_setup', array($this, 'add_dashboard_widget') );
		add_action( 'wp_ajax_update_widget_' . $this->get_widget_slug(), array($this, 'ajax_update_widget') );
	}

	/**
	 * Add a widget to the dashboard.
	 *
	 * This function is hooked into the 'wp_dashboard_setup' action below.
	 */
	function add_dashboard_widget() {
		wp_add_dashboard_widget(
			$this->get_widget_slug(), // Widget slug.
			htmlspecialchars( $this->get_widget_title() ),
			array($this, 'render_dashboard_widget')
		); 
	}
	
	function ajax_update_widget()
	{
		ob_start();
		$this->render_dashboard_widget();
		$body = ob_get_contents();
		ob_end_clean();
		$resp = json_encode([
			'status' => 'OK',
			'widget_body' => $body,
			'widget_slug' => $this->get_widget_slug(),
		]);
		echo $resp;
		wp_die();
	}
	 
	/**
	 * Create the function to output the content of our Dashboard Widget.
	 */
	function render_dashboard_widget()
	{
		// echo widget body here
	}

	function output_footer()
	{
	?>
		<p><a class="button button-secondary" href="<?php echo admin_url('admin.php?page=curbside-pickup/dashboard'); ?>"><?php esc_html_e('View Dashboard', 'curbside-pickup');?> &raquo;</a></p>
	<?php
	}	
	
	function pickups_url($status)
	{
		echo admin_url('edit.php?post_type=scheduled-pickup&delivery-status=' . $status);
	}
}

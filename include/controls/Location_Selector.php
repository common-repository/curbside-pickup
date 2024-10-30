<?php
namespace Curbside_Pickup;

class Location_Selector
{
	var $options = [];
	var $default_options = [];

	function __construct($options = [])
	{
		$this->default_options = [
			'show_form' => true,
			'show_button' => true,
			'include_all_option' => true,
			'all_label' => __('All Locations', 'curbside-pickup'),
			'button_label' => __('Change Location', 'curbside-pickup'),
			'input_id' => '',
			'input_name' => 'curbside_pickup_selected_location',
			'init_query_param' => '',
		];
		$this->options = array_merge($this->default_options, $options);
	}

	public function get_output()
	{
		ob_start();
		$this->display();
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	public function display()
	{
		$current_url = add_query_arg( array() );
		$param = $this->options['input_name'];
		$init_query_param = $this->options['init_query_param'];
		$current_value = !empty($_POST[$param])
						 ? intval($_POST[$param])
						 : 0;

		// set to URL value on init load, if one was specified
		if ( empty ($current_value) 
			 && ! empty($init_query_param) 
			 && ! empty($_GET[$init_query_param]) ) {
			$current_value = intval($_GET[$init_query_param]);
		}
						 
		$locations = $this->get_all_locations();
?>
		<?php if ( !empty($this->options['show_form']) ): ?>
		<form
			action="<?php echo $current_url; ?>"
			method="POST"
			class="curbside_pickup_location_selector_form"
		>
		<?php endif; ?>

			<select
				class="curbside_pickup_location_selector form-control"
				<?php if ( !empty($this->options['input_id']) ): ?>id="<?php echo $this->options['input_id']; ?>"<?php endif; ?>
				<?php if ( !empty($this->options['input_name']) ): ?>name="<?php echo $this->options['input_name']; ?>"<?php endif; ?>
			>
				<?php if ( !empty($this->options['include_all_option']) ): ?>
				<option value="0" <?php if ( empty($current_value) ): ?>selected="selected"<?php endif; ?>><?php echo $this->options['all_label']; ?></option>
				<?php endif; ?>

				<?php foreach( $locations as $location ) : ?>
				<option
					value="<?php echo htmlentities($location->ID); ?>"
					<?php if ( $location->ID == $current_value ): ?>selected="selected"<?php endif; ?>
				><?php echo htmlentities( get_the_title($location->ID) ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php if ( !empty($this->options['show_button']) ): ?>
			<button
				class="button"
				type="submit"
			><?php echo $this->options['button_label']; ?></button>
			<?php endif; ?>

		<?php if ( !empty($this->options['show_form']) ): ?>
		</form>
		<?php endif; ?>
<?php
	}

	private function get_all_locations()
	{
		$args = array(
			'numposts' 	=> -1,
			'post_type' => 'pickup-location',
			'orderby' 	=> 'post_title',
			'order' 	=> 'ASC',
		);
		$locations = get_posts($args);
		return !empty($locations)
			   ? $locations
			   : [];
	}
}

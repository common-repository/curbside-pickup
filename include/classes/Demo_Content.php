<?php

	namespace Curbside_Pickup;

	class Demo_Content extends Base_Class
	{
		public function get_page($pagename)
		{
			$filename = plugin_dir_path( dirname( __FILE__ ) ) . '../../assets/pages/demo_content/' . $pagename . '.html';
			if ( file_exists($filename) ) {
				$content = file_get_contents($filename);
				return sprintf('<div class="admin_demo_content_wrapper">%s</div>', $content);
			}
			return '';
		}
		
		function check_icon()
		{
			return '<div class="curbside_pickup_checkbox"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"/></svg></div>';
		}
		
		function get_upgrade_url($campaign = 'freeplugin', $medium = 'general')
		{
			global $wp_version;
			//$file_data = get_file_data('/some/real/path/to/your/plugin', array('Version'), 'plugin');
			$base_url = 'https://goldplugins.com/special-offers/upgrade-to-curbside-pickup-pro/?';
			
			$params = array(
				'utm_source' => 'WordPress',
				'utm_campaign' => $campaign,
				'utm_medium' => $medium,
				'wp_version' => $wp_version,
				'plugin_version' => $this->get_plugin_version(),
			);
			// TODO: add days since install (buckets?)
			
			return $base_url . http_build_query($params);		
		}
		
		function get_plugin_version()
		{
			$cached_val = wp_cache_get( 'curbside_pickup_free_version' );
			if ( !empty($cached_val) ) {
				return $cached_val;
			}
			
			$all = get_plugins();
			if ( empty($all['curbside-pickup/curbside-pickup.php']) ) {
				return '';
			}

			if ( empty($all['curbside-pickup/curbside-pickup.php']['Version']) ) {
				return '';
			}
			
			$version = $all['curbside-pickup/curbside-pickup.php']['Version'];
			wp_cache_set( 'curbside_pickup_free_version', $version );
			return $version;
		}		

		public function pickup_locations_page()
		{
			if ( $this->is_pro() ) {
?>				
				<script type="text/javascript">
				window.location = '<?php echo admin_url('edit.php?post_type=pickup-location'); ?>';
				</script>				
<?php
			} else {
				wp_enqueue_style( 'curbside-pickup-demo_content' );
				echo $this->get_page('pickup_locations');
				echo $this->pickup_locations_page_demo_modal();
			}
		}
				
		public function pick_list_page()
		{
			if ( $this->is_pro() ) {
?>				
				<script type="text/javascript">
				window.location = '<?php echo admin_url('edit.php?post_type=pickup-location'); ?>';
				</script>				
<?php
			} else {
				wp_enqueue_style( 'curbside-pickup-demo_content' );
				echo $this->get_page('pickup_locations');
				echo $this->pick_list_page_demo_modal();
			}
		}
				
		public function order_list_page()
		{
			if ( $this->is_pro() ) {
?>				
				<script type="text/javascript">
				window.location = '<?php echo admin_url('edit.php?post_type=pickup-location'); ?>';
				</script>				
<?php
			} else {
				wp_enqueue_style( 'curbside-pickup-demo_content' );
				echo $this->get_page('pickup_locations');
				echo $this->order_list_page_demo_modal();
			}
		}
				
		function pickup_locations_page_demo_modal()
		{
			ob_start();
			?>
			<div class="curbside_pickup_demo_modal">
				<div class="curbside_pickup_demo_modal_top">
					<h2>Offer Pickup From All of Your Locations</h2>
					<p class="subhead"><strong>The free version of Curbside Pickup does not support multiple locations.</strong></p>
					<p>Once you upgrade to Curbside Pickup Pro, you will be able to offer your customers the choice of which location to pickup from.</p>
					<ul class="curbside_pickup_feature_list curbside_pickup_feature_list_left">
						<li><?php echo $this->check_icon(); ?> Customers Choose Their Pickup Location</li>
						<li><?php echo $this->check_icon(); ?> Each Location Can Have Its Own Schedule</li>
						<li><?php echo $this->check_icon(); ?> Notifications For New Orders</li>
						<li><?php echo $this->check_icon(); ?> Notifications For Customer Arrival</li>
					</ul>
					<ul class="curbside_pickup_feature_list curbside_pickup_feature_list_right">
						<li><?php echo $this->check_icon(); ?> Throttle Orders Per Location</li>
						<li><?php echo $this->check_icon(); ?> Provide Contact Information</li>
						<li><?php echo $this->check_icon(); ?> Filter Dashboard Orders By Location</li>
						<li><?php echo $this->check_icon(); ?> Move Orders Between Locations</li>
					</ul>
					<div style="clear:both"></div>
				</div>
				<div class="curbside_pickup_demo_modal_bottom">
					<a class="curbside_pickup_btn" href="<?php echo $this->get_upgrade_url('placeholders', 'pickup_locations_modal'); ?>" target="_blank">Upgrade To Curbside Pickup Pro Now</a>
					<p class="curbside_pickup_after_button_text"><em>and supercharge your business!</em></p>
				</div>
			</div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			return $output;	
		}
		
		function pick_list_page_demo_modal()
		{
			ob_start();
			?>
			<div class="curbside_pickup_demo_modal">
				<div class="curbside_pickup_demo_modal_top">
					<h2>Save Your Time With A Pick List</h2>
					<p class="subhead"><strong>The free version of Curbside Pickup does not support pick lists.</strong></p>
					<p>Once you upgrade to Curbside Pickup Pro, you'll get a dynamically generated Pick List to help you prepare your orders.</p>
					<ul class="curbside_pickup_feature_list curbside_pickup_feature_list_left">
						<li><?php echo $this->check_icon(); ?> Generate Pick Lists For Each Location</li>
						<li><?php echo $this->check_icon(); ?> See The Totals For Each Item</li>
						<li><?php echo $this->check_icon(); ?> Notifications For New Orders</li>
						<li><?php echo $this->check_icon(); ?> Notifications For Customer Arrival</li>
					</ul>
					<ul class="curbside_pickup_feature_list curbside_pickup_feature_list_right">
						<li><?php echo $this->check_icon(); ?> Throttle Orders Per Location</li>
						<li><?php echo $this->check_icon(); ?> Provide Contact Information</li>
						<li><?php echo $this->check_icon(); ?> Filter Dashboard Orders By Location</li>
						<li><?php echo $this->check_icon(); ?> Move Orders Between Locations</li>
					</ul>
					<div style="clear:both"></div>
				</div>
				<div class="curbside_pickup_demo_modal_bottom">
					<a class="curbside_pickup_btn" href="<?php echo $this->get_upgrade_url('placeholders', 'pickup_locations_modal'); ?>" target="_blank">Upgrade To Curbside Pickup Pro Now</a>
					<p class="curbside_pickup_after_button_text"><em>and supercharge your business!</em></p>
				</div>
			</div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			return $output;	
		}
		
		function order_list_page_demo_modal()
		{
			ob_start();
			?>
			<div class="curbside_pickup_demo_modal">
				<div class="curbside_pickup_demo_modal_top">
					<h2>Save Your Time With An Order List</h2>
					<p class="subhead"><strong>The free version of Curbside Pickup does not support order lists.</strong></p>
					<p>Once you upgrade to Curbside Pickup Pro, you'll get a dynamically generated Order List to help you prepare your orders.</p>
					<ul class="curbside_pickup_feature_list curbside_pickup_feature_list_left">
						<li><?php echo $this->check_icon(); ?> View Daily Orders in a Compact Grid View</li>
						<li><?php echo $this->check_icon(); ?> Generate Order Lists For Each Location</li>
						<li><?php echo $this->check_icon(); ?> Notifications For New Orders</li>
						<li><?php echo $this->check_icon(); ?> Notifications For Customer Arrival</li>
					</ul>
					<ul class="curbside_pickup_feature_list curbside_pickup_feature_list_right">
						<li><?php echo $this->check_icon(); ?> Throttle Orders Per Location</li>
						<li><?php echo $this->check_icon(); ?> Provide Contact Information</li>
						<li><?php echo $this->check_icon(); ?> Filter Dashboard Orders By Location</li>
						<li><?php echo $this->check_icon(); ?> Move Orders Between Locations</li>
					</ul>
					<div style="clear:both"></div>
				</div>
				<div class="curbside_pickup_demo_modal_bottom">
					<a class="curbside_pickup_btn" href="<?php echo $this->get_upgrade_url('placeholders', 'pickup_locations_modal'); ?>" target="_blank">Upgrade To Curbside Pickup Pro Now</a>
					<p class="curbside_pickup_after_button_text"><em>and supercharge your business!</em></p>
				</div>
			</div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			return $output;	
		}
	}
	
	



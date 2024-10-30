<?php
// Curbside Pickup Welcome Page template

ob_start();
$learn_more_url = 'https://goldplugins.com/special-offers/upgrade-to-curbside-pickup-pro/?utm_source=curbside_pickup_free&utm_campaign=welcome_screen_upgrade&utm_content=col_1_learn_more';
$settings_url = menu_page_url('curbside-pickup-settings', false);
$pro_registration_url =  menu_page_url('curbside-pickup-pro-registration', false);
$utm_str = '?utm_source=curbside_pickup_free&utm_campaign=welcome_screen_help_links';
$new_post_link = admin_url('post-new.php?post_type=scheduled-pickup&guided_tour=1');
$plugin_title = $is_pro ? 'Curbside Pickup' : 'Curbside Pickup Pro';
$banner_url = plugins_url('../../assets/img/banner-1544x500.png', __FILE__);
?>


<img src="<?php echo $banner_url; ?>" style="max-width: 100%; height: auto;">
<br>

<p class="aloha_tip"><strong>Tip:</strong> You can always access this page via the <strong>Curbside Pickup &raquo; About Plugin</strong> menu.</p>
<p class="aloha_intro"><strong>Congratulations! You have successfully installed <?php echo $plugin_title; ?>!</strong></p>
<p class="aloha_intro">You're just moments away from offering a convenient and safe Curbside Pickup experience to your customers. We've outlined some of the most common setup tasks here, but you can always find <a href="https://goldplugins.com/documentation/curbside-pickup-pro-documentation/?utm_campaign=view_docs&utm_source=aloha_docs&utm_banner=intro_text">our full documentation online</a>.</p>
<p class="aloha_intro">If you're already familiar with Curbside Pickup, you may wish to <a href="<?php echo $settings_url; ?>">continue to the Settings page</a>.</p>

<br>
<div id="jump_links" class="aloha_jump_links">
	Jump To: <a href="#woocommerce">Integrating With WooCommerce</a> | 
	<a href="#adding_scheduled_pickups">Adding Scheduled Pickups</a> | 
	<a href="#setting_up_pickup_page">Setting Up Your Pickup Page</a> | 
	<a href="#dashboard">Using The Dashboard</a>
</div>
<br>
<br>

<h3 id="woocommerce">Integrating With WooCommerce</h3>
<p>Curbside Pickup is designed to integrate directly with WooCommerce. Just offer Curbside Pickup as a Shipping Method in WooCommerce, and you're finished!</p>

<h4>How To Add Curbside Pickup as a WooCommerce Shipping Method:</h4>

<ol>
	<li><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=shipping&section='); ?>">Visit Your WooCommerce Shipping Settings page</a></li>
	<li>Click the 'Add shipping zone' button, or edit one of your existing shipping zones</li>
	<li>Click the 'Add shipping method' button</li>
	<li>In the dialog box, select Curbside Pickup and then click 'Add shipping method' button</li>
	<li>Save the  your changes to your Shipping Zone if needed</li>
</ol>
<p>Your customers will now be able to select Curbside Pickup when they check-out. They will be offered a choice of pickup times, based on your schedule and what other pikcups are already scheduled.</p>
<br>
<a href="#getting_started">Back To Top</a>
<br>
<br>
	

<h3 id="adding_scheduled_pickups">Adding Scheduled Pickups</h3>
<p>If you are using WooCommerce, you pickups will be created automatically when your customers choose Curbside Pickup at checkout. Your customers will be able to choose their desired pickup time, based on the schedule and throttling options you have chosen.</p>
<p>If you are not using WooCommerce, that's not a problem - you'll simply need to input your Scheduled Pickups manually.</p>
<h4>How To Manually Add A New Scheduled Pickup</h4>
<ol>
	<li>Visit the <a href="<?php echo admin_url('edit.php?post_type=scheduled-pickup'); ?>">Curbside Pickup &raquo; Scheduled Pickups menu</a> in your WordPress dashboard</li>
	<li>Click the <a href="<?php echo admin_url('post-new.php?post_type=scheduled-pickup'); ?>">Add New</a> button.</li>
	<li>Input the order details, and click <strong>Publish</strong>.</li>
</ol>
<p>You do not need to choose a title for the post, but of course you may. If you leave the title blank, we'll automatically set it for you.</li>
<p><strong>Note:</strong> don't worry about the Metadata or Check-In Details tabs at this time. The former records information about the order (such as the actual delivery time), and the latter keeps details about the customer's check in (for example, the check in time). All of these fields will be set by the software.</p>
<p>Your scheduled pickup will now appear in the Dashboard, in lists, and on your Order List. Manually entered pickups will not be able to list the individual items in the order, nor use the Pick List. This may become possible in a future update.</p>
<br>
<a href="#getting_started">Back To Top</a>
<br>
<br>
	

<h3 id="setting_up_pickup_page">Setting Up Your Pickup Page</h3>
<p>The Pickup Page is the page your customer's see when they click the Check-In Link in their email.</p>
<p>This page will include a form for your customer to let you know they have arrived, and optionally collect information from them such as special instructions, a space number, or a vehicle description.</p>
<p>You can specify which page to show your customer, as well as what information to collect on the <a href="<?php echo admin_url('admin.php?page=curbside-pickup-settings#tab-pickup-page'); ?>">Pickup Page settings page.</a></p>
<p>If you'd like to add content to that page in addition to the Pickup Form, simply add it to the page. The form will be displayed at the top of the page, followed by the page's content .</a></p>
<br>
<a href="#getting_started">Back To Top</a>
<br>
<br>
	

<h3 id="dashboard">Using The Dashboard</h3>
<p>The Dashboard helps you manage pickups as your customers arrive. We suggest you leave the Dashboard page open in a browser on your computer or tablet in the shop. It has (optional) audio and visual notifactions for new arrivals, and updates automatically.</p>
<p>You can also mark pickups as complete, reschedule, and cancel pickups right from the Dashboard. Simply click on any order to bring up the Update Order dialog.</p>
<p><a class="button" href="<?php echo admin_url('admin.php?page=curbside-pickup%2Fdashboard'); ?>">Click here to visit your Dashboard.</a></p>
<p>You can find more information about the Dashboard in our <a href="https://goldplugins.com/documentation/curbside-pickup-pro-documentation/the-dashboard/?utm_campaign=view_docs&utm_source=aloha_docs&utm_banner=dashboard_instructions">online documentation</a>.</p>
<br>
<a href="#getting_started">Back To Top</a>
<br>
<br>
	

<hr>
<br>
<h1>Curbside Pickup Resources</h1>
<div class="three_col">
	<div class="col">
		<?php if ($is_pro): ?>
			<h3>Curbside Pickup Pro: Active</h3>
			<p class="plugin_activated">Curbside Pickup Pro is licensed and active.</p>
			<a href="<?php echo $pro_registration_url; ?>">Registration Settings</a>
		<?php else: ?>
			<h3>Upgrade To Pro</h3>
			<p>Curbside Pickup Pro is the Professional, fully-functional version of Curbside Pickup, which features technical support and access to all Pro&nbsp;features.</p>
			<a class="button" href="<?php echo $learn_more_url; ?>">Click Here To Learn More</a>		
		<?php endif; ?>
	</div>
	<div class="col">
		<h3>Quick Links</h3>
		<ul>
			<li><a href="<?php echo $settings_url; ?>">Curbside Pickup settings</a></li>
			<li><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=shipping&section='); ?>">WooCommerce shipping zones settings</a></li>
			<li><a href="<?php echo $new_post_link; ?>">Add a new Scheduled Pickup</a></li>
			<li><a href="https://goldplugins.com/documentation/curbside-pickup-documentation/<?php echo $utm_str; ?>">Online documentation</a></li>
			<li><a href="https://goldplugins.com/documentation/curbside-pickup-documentation/curbside-pickup-faqs/<?php echo $utm_str; ?>">Frequently Asked Questions (FAQs)</a></li>
			<li><a href="https://goldplugins.com/contact/<?php echo $utm_str; ?>">Technical support</a></li>
		</ul>
	</div>
	<div class="col">
		<h3>Get Help</h3>
		<ul>
			<li><a href="https://goldplugins.com/documentation/curbside-pickup-documentation/<?php echo $utm_str; ?>">Online documentation</a></li>
			<li><a href="https://wordpress.org/support/plugin/curbside-pickup/<?php echo $utm_str; ?>">WordPress support forum</a></li>
			<li><a href="https://goldplugins.com/documentation/curbside-pickup-documentation/curbside-pickup-pro-changelog/<?php echo $utm_str; ?>">Recent changes (changelog)</a></li>
			<li><a href="https://goldplugins.com/<?php echo $utm_str; ?>">Gold Plugins website</a></li>
			<li><a href="https://goldplugins.com/contact/<?php echo $utm_str; ?>">Contact support</a></li>
		</ul>
	</div>
</div>

<div class="continue_to_settings">
	<p><a href="<?php echo $settings_url; ?>">Continue to Settings &raquo;</a></p>
</div>

<?php 
$content =  ob_get_contents();
ob_end_clean();
return $content;

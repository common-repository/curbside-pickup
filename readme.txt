=== Curbside Pickup ===
Plugin Name: Curbside Pickup for WooCommerce
Contributors: ghuger, richardgabriel
Tags: woocommerce, curbside-pickup, takeout, curbside, contactless, contactless-pickup
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 5.7.2
WC requires at least: 4.2.0
WC tested up to: 5.4.1
Stable tag: 2.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Curbside Pickup plugin for WooCommerce (or standalone). Provide a great curbside pickup experience for your customers!

== Description ==

Curbside Pickup is a complete system to manage your curbside pickup experience. It features custom pickup links for your customers and a Dashboard for your staff, so you'll be alerted right away when a customer arrives for pickup. Your customers can even provide special instructions, a parking space number, and/or a description of their vehicle to make it easier for your staff to find the customer and deliver their order.

Curbside Pickup works well for any business that wants to provide curbside pickup or takeout for their customers. It works with any theme that supports WooCommerce (and it works great as a standalone app too, if you don't happen to use WooCommerce).

Curbside Pickup integrates directly with WooCommerce. Simply select Curbside Pickup as a Shipping Option,a nd you're ready to provide curbside pickup to your customers!

Although Curbside Pickup integrates directly with WooCommerce, it also works great as a standalone app. You'll simply add your pickup orders manually, and the software will take over from there: sending emails, providing check-in services, and more.

= How It Works = 

Curbside Pickup for WordPress is a complete pickup curbside system, providing a smooth experience for your customers and your staff while minimizing contact between them. 

Here's an overview of the Curbside Pickup process:

1) For the zones in which you have enabled it, your customers will be able to choose Curbside Pickup as their shipping method during checkout. They'll be asked to choose the pickup time that's most convienient for them based on the schedule you have provided (or you can choose to have the system automatically choose the next available time).

2) After completing their order, your customer will receive an email that contains a personalized Check-In link along with any additional instructions you wish to provide. This link will also appear on their WooCommerce receipt page and their order confirmation email.

3) When your customer arrives at your shop for pickup, they will use the Check-In link to let you know they've arrived. If you'd like, you can also ask them to provide a parking space number, a description of their vehicle, and/or special instructions for your staff.

4) After your customer checks in, you will receive an email notification containing their order information and any instructions they've provided. You will also receive visual and audio alerts on your Dashboard page if you have it open in the browser.

5) Your staff will delivers the goods to your customer at their vehicle, and then click the Complete Order button to finish the transaction.



= Use the Dashboard to Stay On Top Of Your Curbside Pickup Queue =

At the heart of Curbside Pickup is the Dashboard. You can leave the Dashboard page open inside your business, and you'll be alerted whenever a customer arrives. You'll also see customers who are expected to arrive shortly, and customers who didn't arrive at all. You can show or hide any of these panels on Curbside Pickup's Settings page.

When a customer arrives and checks in using their custom link, you'll get an audio notification and receive a browser notification (both can be turned off, of course). This alerts your staff to take the order out to the customer. You can also ask your customers to provide a description of their vehicle, a space number, and/or instructions to your staff in order to make finding the car easier.

You can also reschedule, cancel, or mark any order as complete right from the dashboard. You can also leave internal notes on any order, which are only ever seen by your staff.

= Custom Pickup Links for Each Customer =

When your customers complete their checkout, they will receive an email with their pickup information, and a custom check-in link (this information will also be included on their WooCommerce email receipt and Thank You page).

When your customer arrives at your store for pickup, they simply click the pickup link in their email to be taken to a form on your website. Here they will provide their vehicle description, space number, and/or instructions for your staff and then click the button to check in. As soon as they check-in, your staff will receive a notification in the Dashboard.

= Let Your Customers Choose Their Own Pickup Times (requires WooCommerce) =

When your customers check out in your WooCommerce store, they will be offered a choice of available times for pickup. You have full control over what times are offered and how many orders can be scheduled at once (throttling).

Curbside Pickup offers fine grained control over the pickup schedule, allowing you to specify as many intervals on a given day as you like. You can also specify Holidays, on which no pickups are offered. Pickup intervals are specified on a day-of-the-week basis (e.g., Monday hours, Tuesday hours, etc). You can create as many schedules as you like and swap them in as needed (e.g., if you have special holiday hours).

Note: users who have the Curbside Pickup Pro module to support multiple locations can specify a different schedule for each location.

= Pro Version and Support Available =

The WordPress support pages are available to all customers and are an excellent source for getting help from other users for free. We also do our best to montitor these forums as we can.

The GoldPlugins team also provides direct email support to users who have purchased the Curbside Pickup Pro addon, available at GoldPlugins.com.

Curbside Pickup Pro adds to the already powerful Curbside Pickup plugin with features like the Order List & Pick-List, and supports businesses with multiple locations.

[Upgrade To Pro Now!](https://goldplugins.com/downloads/curbside-pickup-pro/ "Upgrade to Curbside Pickup Pro")

== Installation ==

Getting started with Curbside Pickup is a breeze. You can install it from the WordPress plugin directory by searching for "Curbside Pickup", or you can download the zip file and upload it directly.

How to Install Curbside Pickup Manually:

1. Unzip curbside-pickup.zip, and upload the `/curbside-pickup/` folder and all of its contents to your `/wp-content/plugins/` directory.
2. Activate Curbside Pickup through the 'Plugins' menu in WordPress

Once you've installed and activated the plugin, you'll want to add Curbside Pickup as an available Shipping Method in WooCommerce.

1. In your WordPress admin area, visit the WooCommerce -> Settings menu. 
2. On the Settings page, select the `Shipping` tab
3. Add a new shipping zone, or click on one of your existing Shipping Zones to modify it. You'll probably want to create a new zone for your local area.
4. Click the `Add shipping method` button, and then select Curbside Pickup from the drop down menu. Click `Add shipping method` to close the pop-up.
5. Click the `Save changes` button to finish.

That's all! Now customers who are eligible for this shipping zone will be able to select Curbside Pickup at checkout, and select a time that works for them.


== Frequently Asked Questions ==

Visit [here](https://goldplugins.com/documentation/curbside-pickup-documentation/curbside-pickup-faqs/?utm_source=curbside-pickup_wp_dir&utm_campaign=curbside-pickup_faqs "Curbside Pickup Pro - Frequently Asked Questions") for answers to common questions with the Curbside Pickup plugin.

== Screenshots ==

1. The Dashboard, where you can monitor for arriving customers.
2. The Update Order screen, where you can complete, reschedule, or cancel an order, as well as leaving notes.

== Changelog ==

= 2.1 =
* Fix errors with Stripe/Authorize.net Javascripts. 
* QOL improvements for Update Order modal.

= 2.0 =
* Dashboard updates
* Redesigned Update Pickup modal
* Huge speed improvements when loading date/time selectors
* New widgets for WordPress' dashboard
* New filters

= 1.20 =
* New hooks and filters.
* Code improvements.

= 1.19 =
* Fixes bugs related to unexpected server times.
* Allows pickups with no delays.

= 1.18 =
* Fixes bug whereby some users were shown the pickup page error message incorrectly.

= 1.17 =
* Allows admin users to preview the Pickup Page.

= 1.16 =
* New filter to allow pickup times to be prefilled.
* Use shipping method's instance title on checkout form.
* Various fixes.

= 1.15 =
* Pass the sent_to_admin flag through to an email filter to allow further customization.

= 1.14 =
* Bug fix
* Code cleanup and other various improvements.

= 1.13 =
* Bug fix
* Adds filters to allow checkout fields to be moved.

= 1.12 =
* Adds filters for Curbside Pickup's display in WooCommerce's Order Confirmation emails & pages.
* Compatibility with WP 5.5.3 and WooCommerce 4.6.1

= 1.11 =
* Adds ability to select/change your default pickup schedule.
* Bug fix: Rescue orders that lose their delivery status.

= 1.10 =
* Bug fix: Rescue orders that lose their delivery status.

= 1.9 =
* Bug fix: Sort pickups on the Dashboard based on local time instead of server time.

= 1.8 =
* Adds a new Order Details tab and a Quick Link to the full order details on the Edit Scheduled Pickup screen.
* Adds a Quick Link to the full order details on the Edit Scheduled Pickup screen.
* Bugfix for admin notification emails - view order details links.

= 1.7 =
* Fixes a bug with orders that are not using Curbside Pickup.

= 1.6 =
* Adds option to disallow late check-in, or allow it within a specified grace period.
* Adds option to change Pickup Page's heading
* Better descriptions for settings, reordering.

= 1.5 =
* Fixes bug where "Today" option could be shown briefly when all timeslots on the current day had passed.

= 1.4 =
* Adds Email Notifications when customers check-in
* Adds Email Notifications when new curbside pickups are scheduled

= 1.3 =
* Keep customers from checking in too early. 
* Adds Pickup Time Period option
* Adds Pickup Grace Period option
* Adds Customer Too Early Message option

= 1.2 =
* Fixes bug with location addresses not being displayed on receipts
* Admin UI updates.

= 1.1 =
* Adds phone number option to pickups
* Better handling when no pickup page specified
* Small fixes and updates

= 1.0 =
* Initial release.

== Upgrade Notice ==

**2.1** Version 2.1 now available! Fix errors with Stripe/Authorize.net. QOL improvements for Update Order modal.

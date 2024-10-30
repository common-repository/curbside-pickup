<?php

/*
 * These hooks are declared outside of any classes, so that they can be easily
 * removed/replaced by our users.
 */


/*
 * Hook: curbside_pickup_display_checkout_fields
 *
 * This hook is for displaying our checkout fields. By default its attached to
 * WooCommerce's woocommerce_checkout_order_review hook, but it can be easily 
 * moved by the user to another hook (i.e., if they want the fields to display elsewhere).
 */
function curbside_pickup_display_checkout_fields_hook()
{
	do_action('curbside_pickup_display_checkout_fields');
}

add_action('woocommerce_checkout_order_review', 'curbside_pickup_display_checkout_fields_hook', 20, 0);
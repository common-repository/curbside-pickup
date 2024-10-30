<?php

function send_to_postbin($post_data)
{
	if ( !is_array($post_data) ) {
		$post_data = [
			'debug' => $post_data,
		];
	}
	$url = 'https://postb.in/1589317152763-4460035418160';
	$response = wp_remote_post( $url, 
		array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $post_data	,
		)
	);
}

function write_to_debug($output)
{
	file_put_contents('/home/freshwp/public_html/wp-content/plugins/curbside-pickup/out.txt', maybe_serialize($output));
}

add_action( 'template_redirect', 'add_random_product_to_cart' );
function add_random_product_to_cart() {

	if ( ! is_admin() ) {
		
		// only add if cart is empty
		if ( count( WC()->cart->get_cart() ) > 0 ) {
			return;
		}

		$args = array(
			'posts_per_page'   => rand(1, 5),
			'orderby'          => 'rand',
			'post_type'        => 'product' ); 

		$random_products = get_posts( $args );
		if ( empty($random_products) ) {
			return;
		}
		
		foreach($random_products as $random_product) {						  
			$quantity = rand(1, 10);
			WC()->cart->add_to_cart( $random_product->ID, $quantity );
		}
	}
}
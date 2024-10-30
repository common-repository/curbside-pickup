<?php

namespace Curbside_Pickup;

class Database extends Base_Class
{
	function __construct()
	{
	}

	function find_pickup_by_hash($pickup_hash)
	{
		$query = array(
			'post_type'   => 'scheduled-pickup',
			'meta_query' => array(
				array (
					'key' => 'pickup_hash',
					'value' => $pickup_hash,

				)
			),
		);
		$result = new \WP_Query($query);
		if ( $result->have_posts() ) {
			return array_shift($result->posts);
		} else {
			return false;
		}

	}


}
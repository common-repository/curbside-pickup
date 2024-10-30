<?php

namespace Curbside_Pickup;

class Colors
{
	// src: https://stackoverflow.com/a/42921358
	function contrast($hex)
	{
		// hexColor RGB
		$R1 = hexdec(substr($hex, 1, 2));
		$G1 = hexdec(substr($hex, 3, 2));
		$B1 = hexdec(substr($hex, 5, 2));

		// Black RGB
		$blackColor = "#000000";
		$R2BlackColor = hexdec(substr($blackColor, 1, 2));
		$G2BlackColor = hexdec(substr($blackColor, 3, 2));
		$B2BlackColor = hexdec(substr($blackColor, 5, 2));

		 // Calc contrast ratio
		 $L1 = 0.2126 * pow($R1 / 255, 2.2) +
			   0.7152 * pow($G1 / 255, 2.2) +
			   0.0722 * pow($B1 / 255, 2.2);

		$L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
			  0.7152 * pow($G2BlackColor / 255, 2.2) +
			  0.0722 * pow($B2BlackColor / 255, 2.2);

		$contrastRatio = 0;
		if ($L1 > $L2) {
			$contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
		} else {
			$contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
		}

		// If contrast is more than 5, return black color
		if ($contrastRatio > 5) {
			return '#000000';
		} else { 
			// if not, return white color.
			return '#FFFFFF';
		}
	}
	
	// src: https://stackoverflow.com/a/31934345
	function hex_to_rgb($hex, $alpha = false)
	{
	   $hex      = str_replace('#', '', $hex);
	   $length   = strlen($hex);
	   $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
	   $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
	   $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
	   if ( $alpha ) {
		  $rgb['a'] = $alpha;
	   }
	   return $rgb;
	}
	
	function rgb_to_hex($rgb)
	{
		extract($rgb);
		return sprintf("#%02x%02x%02x", $r, $g, $b);
	}	
}


<?php


namespace geop;


// Convert a html color to an array of four values [r,g,b,a]
// between 0 and 255.
// a=255 opaque and a=0 transparent
// 
// The input color can be of format
// - #RRGGBBAA (8 chars hex)
// - #RRGGBB (6 chars hex)
// - #RGB (3 chars hex) 
// - rgb(r,g,b) where r,g,b are integers between 0 and 255, or floats between 0 and 1.0
// - rgb(r%,g%,b%) where r,g,b are percentages between 0% and 100%
// - rgba(r,g,b,a) same as for rgb but additional alpha component
// - a named color: transparent, black, white, red, green, blue, yellow, cyan, magenta
//
function colorhex2rgba($color) 
{
	$color = trim($color);
	
	if($color == "transparent") return [0,0,0,0];
	if($color == "black") return [0,0,0,255];
	if($color == "white") return [255,255,255,255];
	if($color == "silver") return [192,192,192,255];
	if($color == "gray") return [128,128,128,255];
	if($color == "red") return [255,0,0,255];
	if($color == "maroon") return [128,0,0,255];
	if($color == "green") return [0,128,0,255];
	if($color == "lime") return [0,255,0,255];
	if($color == "blue") return [0,0,255,255];
	if($color == "navy") return [0,0,128,255];
	if($color == "yellow") return [255,255,0,255];
	if($color == "olive") return [128,128,0,255];
	if($color == "aqua") return [0,255,255,255];
	if($color == "teal") return [0,128,128,255];
	if($color == "fuchsia") return [255,0,255,255];
	if($color == "purple") return [128,0,128,255];
	
	$defaultColor = [0,0,0,255];

	if (empty($color)) 
	{
		return $defaultColor;
	}

	if ($color[0] == '#') 
	{
		$color = substr($color, 1);
	}
	$isrgba = substr($color, 0, 5) == "rgba(";
	$isrgb = !$isrgba && substr($color, 0, 4) == "rgb(";
	if($isrgba || $isrgb)
	{
		if($isrgba) $color = substr($color, 5);
		elseif($isrgb) $color = substr($color, 4);
		$color = rtrim($color, ")");
		
		$col = $defaultColor;
		$parts = explode(",", $color);
		foreach($parts as $i => $colcomp)
		{
			if(stripos($colcomp, "%") !== false)
			{
				// floatval("25%") gives 25
				$col[$i] = round(255 * floatval($colcomp) / 100.0); 
			}
			elseif(stripos($colcomp, ".") !== false)
			{
				$col[$i] = round(255 * floatval($colcomp)); 
			}
			else
			{
				$col[$i] = intval($colcomp);
			}
			if($col[$i] > 255) $col[$i] = 255;
			if($col[$i] < 0) $col[$i] = 0;
		}
		return $col;
	}
	
	// Check if color has 8, 6 or 3 characters
	// If it has 8 characters, it's rgba
	// If it has 6 characters, it's rgb
	// If it has 3 characters, each character is repeated twice (e.g. #fff)
	if (strlen($color) == 8) 
	{
		return [ hexdec($color[0] . $color[1]), hexdec($color[2] . $color[3]), hexdec($color[4] . $color[5]), hexdec($color[6] . $color[7])];
	}
	elseif (strlen($color) == 6) 
	{
		return [ hexdec($color[0] . $color[1]), hexdec($color[2] . $color[3]), hexdec($color[4] . $color[5]), 255];
	} 
	elseif (strlen($color) == 3) 
	{
		return [ hexdec($color[0] . $color[0]), hexdec($color[1] . $color[1]), hexdec($color[2] . $color[2]), 255];
	} 
	
	return $defaultColor;
}


	
<?php


namespace geop;

$_color_map = [
	false => [0,0,0,0],
	0 => [0,0,0,0],
	"none" => [0,0,0,0],
	"off" => [0,0,0,0],
	"transparent" => [0,0,0,0],
	
	// These colors are extracted from the assets/colors/htmlcolorcodes.csv
	// Red
	"indianred" => [205, 92, 92],
	"lightcoral" => [240, 128, 128],
	"salmon" => [250, 128, 114],
	"darksalmon" => [233, 150, 122],
	"lightsalmon" => [255, 160, 122],
	"crimson" => [220, 20, 60],
	"red" => [255, 0, 0],
	"firebrick" => [178, 34, 34],
	"darkred" => [139, 0, 0],
	// Pink
	"pink" => [255, 192, 203],
	"lightpink" => [255, 182, 193],
	"hotpink" => [255, 105, 180],
	"deeppink" => [255, 20, 147],
	"mediumvioletred" => [199, 21, 133],
	"palevioletred" => [219, 112, 147],
	// Orange
	"lightsalmon" => [255, 160, 122],
	"coral" => [255, 127, 80],
	"tomato" => [255, 99, 71],
	"orangered" => [255, 69, 0],
	"darkorange" => [255, 140, 0],
	"orange" => [255, 165, 0],
	// Yellow
	"gold" => [255, 215, 0],
	"yellow" => [255, 255, 0],
	"lightyellow" => [255, 255, 224],
	"lemonchiffon" => [255, 250, 205],
	"lightgoldenrodyellow" => [250, 250, 210],
	"papayawhip" => [255, 239, 213],
	"moccasin" => [255, 228, 181],
	"peachpuff" => [255, 218, 185],
	"palegoldenrod" => [238, 232, 170],
	"khaki" => [240, 230, 140],
	"darkkhaki" => [189, 183, 107],
	// Purple
	"lavender" => [230, 230, 250],
	"thistle" => [216, 191, 216],
	"plum" => [221, 160, 221],
	"violet" => [238, 130, 238],
	"orchid" => [218, 112, 214],
	"fuchsia" => [255, 0, 255],
	"magenta" => [255, 0, 255],
	"mediumorchid" => [186, 85, 211],
	"mediumpurple" => [147, 112, 219],
	"rebeccapurple" => [102, 51, 153],
	"blueviolet" => [138, 43, 226],
	"darkviolet" => [148, 0, 211],
	"darkorchid" => [153, 50, 204],
	"darkmagenta" => [139, 0, 139],
	"purple" => [128, 0, 128],
	"indigo" => [75, 0, 130],
	"slateblue" => [106, 90, 205],
	"darkslateblue" => [72, 61, 139],
	"mediumslateblue" => [123, 104, 238],
	// Green
	"greenyellow" => [173, 255, 47],
	"chartreuse" => [127, 255, 0],
	"lawngreen" => [124, 252, 0],
	"lime" => [0, 255, 0],
	"limegreen" => [50, 205, 50],
	"palegreen" => [152, 251, 152],
	"lightgreen" => [144, 238, 144],
	"mediumspringgreen" => [0, 250, 154],
	"springgreen" => [0, 255, 127],
	"mediumseagreen" => [60, 179, 113],
	"seagreen" => [46, 139, 87],
	"forestgreen" => [34, 139, 34],
	"green" => [0, 128, 0],
	"darkgreen" => [0, 100, 0],
	"yellowgreen" => [154, 205, 50],
	"olivedrab" => [107, 142, 35],
	"olive" => [128, 128, 0],
	"darkolivegreen" => [85, 107, 47],
	"mediumaquamarine" => [102, 205, 170],
	"darkseagreen" => [143, 188, 139],
	"lightseagreen" => [32, 178, 170],
	"darkcyan" => [0, 139, 139],
	"teal" => [0, 128, 128],
	// Blue
	"aqua" => [0, 255, 255],
	"cyan" => [0, 255, 255],
	"lightcyan" => [224, 255, 255],
	"paleturquoise" => [175, 238, 238],
	"aquamarine" => [127, 255, 212],
	"turquoise" => [64, 224, 208],
	"mediumturquoise" => [72, 209, 204],
	"darkturquoise" => [0, 206, 209],
	"cadetblue" => [95, 158, 160],
	"steelblue" => [70, 130, 180],
	"lightsteelblue" => [176, 196, 222],
	"powderblue" => [176, 224, 230],
	"lightblue" => [173, 216, 230],
	"skyblue" => [135, 206, 235],
	"lightskyblue" => [135, 206, 250],
	"deepskyblue" => [0, 191, 255],
	"dodgerblue" => [30, 144, 255],
	"cornflowerblue" => [100, 149, 237],
	"mediumslateblue" => [123, 104, 238],
	"royalblue" => [65, 105, 225],
	"blue" => [0, 0, 255],
	"mediumblue" => [0, 0, 205],
	"darkblue" => [0, 0, 139],
	"navy" => [0, 0, 128],
	"midnightblue" => [25, 25, 112],
	// Brown
	"cornsilk" => [255, 248, 220],
	"blanchedalmond" => [255, 235, 205],
	"bisque" => [255, 228, 196],
	"navajowhite" => [255, 222, 173],
	"wheat" => [245, 222, 179],
	"burlywood" => [222, 184, 135],
	"tan" => [210, 180, 140],
	"rosybrown" => [188, 143, 143],
	"sandybrown" => [244, 164, 96],
	"goldenrod" => [218, 165, 32],
	"darkgoldenrod" => [184, 134, 11],
	"peru" => [205, 133, 63],
	"chocolate" => [210, 105, 30],
	"saddlebrown" => [139, 69, 19],
	"sienna" => [160, 82, 45],
	"brown" => [165, 42, 42],
	"maroon" => [128, 0, 0],
	// White
	"white" => [255, 255, 255],
	"snow" => [255, 250, 250],
	"honeydew" => [240, 255, 240],
	"mintcream" => [245, 255, 250],
	"azure" => [240, 255, 255],
	"aliceblue" => [240, 248, 255],
	"ghostwhite" => [248, 248, 255],
	"whitesmoke" => [245, 245, 245],
	"seashell" => [255, 245, 238],
	"beige" => [245, 245, 220],
	"oldlace" => [253, 245, 230],
	"floralwhite" => [255, 250, 240],
	"ivory" => [255, 255, 240],
	"antiquewhite" => [250, 235, 215],
	"linen" => [250, 240, 230],
	"lavenderblush" => [255, 240, 245],
	"mistyrose" => [255, 228, 225],
	// Gray
	"gainsboro" => [220, 220, 220],
	"lightgray" => [211, 211, 211],
	"silver" => [192, 192, 192],
	"darkgray" => [169, 169, 169],
	"gray" => [128, 128, 128],
	"dimgray" => [105, 105, 105],
	"lightslategray" => [119, 136, 153],
	"slategray" => [112, 128, 144],
	"darkslategray" => [47, 79, 79],
	"black" => [0, 0, 0],
];

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
	global $_color_map;
	if(is_string($color))
	{
		$color = strtolower(trim($color));
	}
	if(isset($_color_map[$color]))
	{
		$colvalues = $_color_map[$color];
		if(count($colvalues) < 4)
		{
			$colvalues[3] = 255;
		} 
		return $colvalues;
	}
	
	$defaultColor = [0, 0, 0, 255];

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


	
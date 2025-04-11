<?php
namespace MakeTissotIndicatrixApp;

require_once(__DIR__."/../geop.php");

use \geop\LatLon;
use \geop\Point;
use \geop\CRS_EPSG3857;

// Generate a geojson with circles to demonstrate the Tissot's Indicatrix for pseudo Mercator projection.

function makeGeoJson()
{
	$crs = new CRS_EPSG3857();

	$lats = [-80, -70, -60, -40, -20, 0, 20, 40, 60, 70, 80];
	$lons = [-160, -120, -80, -40, 0, 40, 80, 120, 160];
	$latlons = [];
	foreach($lats as $lat)
	{
		foreach($lons as $lon)
		{
			$latlons[] = new LatLon($lat, $lon);
		}
	}

	$features = [];

	$n = 20;
	// 200km radius
	$r = 200000;
	foreach($latlons as $latlon)
	{
		$p = $crs->project($latlon);
		// Scaling factor to multiply with to get the inflated distance
		// at given latitude
		$k = $crs->scalefactor($latlon->lat);
		$contour = [];
		for($i=0; $i<=$n; $i++)
		{
			$x = $p->x + $r * $k * cos($i * 2*M_PI/$n);
			$y = $p->y + $r * $k * sin($i * 2*M_PI/$n);
			$cp = $crs->unproject(new Point($x, $y));
			$contour[] = [$cp->lon, $cp->lat];
		}
		$features[] = ["type" => "Feature", "properties" => ["lat" => $latlon->lat, "k" => $k], "geometry" => 
			["type" => "Polygon", "coordinates" => [ $contour ]]
		];
	}
	$features[] = ["type" => "Feature", "properties" => ["lat" => 0, "k" => 1, "equator" => true], "geometry" => 
		["type" => "LineString", "coordinates" => [ [-180, 0], [180, 0]] ]
	];

	return ["type" => "FeatureCollection", "features" => $features];
}


$gjson = makeGeoJson();
$data = json_encode($gjson, JSON_PRETTY_PRINT);
file_put_contents(__DIR__."/circles.geojson", $data);


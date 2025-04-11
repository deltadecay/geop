<?php

namespace AppExample4;

require_once(__DIR__."/../geop.php");

use \geop\LatLon;
use \geop\Point;
use \geop\Map;
use \geop\CRS_EPSG3857;
use \geop\TileService;
use \geop\FileTileCache;
use \geop\MapRenderer;
use \geop\ImagickFactory;

// This example illustrates the Tissot's indicatrix of deformation of the 
// pseudo Mercator projection EPSG:3857. It loads a geojson with circles located at different latitudes.

$latlon = new LatLon(0, 0);
$zoom = 1;

$render_width = 512;
$render_height = 512;

$cachedir = __DIR__."/tilecache/";

// © OpenStreetMap
$tileservice = new TileService(["url" => "https://tile.openstreetmap.org/{z}/{x}/{y}.png"], new FileTileCache('osm', $cachedir));
$tileservice->setUserAgent("MakeMapApp v1.0");

$map = new Map(new CRS_EPSG3857());
// OSM has tile size of 256 pixels
$map->setTileSize(256);
$imgfactory = class_exists('Imagick') ? new ImagickFactory() : null;
$renderer = new MapRenderer($map, $tileservice, $imgfactory);

// Load geojson with circles
$data = file_get_contents(__DIR__."/circles.geojson");
$gjson = json_decode($data, true);

// Define style for the rendered geometries
$style = [
	'strokecolor' => '#3388ff',
	'fillcolor' => '#3388ff3f',
	'strokewidth' => 1,
	'strokelinecap' => 'round',
	'strokelinejoin' => 'round',
	//'strokemiterlimit' => 10,
	'pointradius' => 8,
];
$renderer->addGeoJsonLayer($gjson, ['swapxy' => false, 'style' => $style]);


$output = $renderer->renderMap($latlon, $zoom, $render_width, $render_height);
$mapimage = $output['image'];

if($imgfactory != null)
{
	$imgfactory->saveImageToFile($mapimage, __DIR__."/../assets/map4.webp");
	$imgfactory->clearImage($mapimage);
}
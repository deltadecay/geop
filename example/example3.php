<?php

namespace AppExample3;

require_once(__DIR__."/../geop.php");

use \geop\LatLon;
use \geop\Map;
use \geop\CRS_EPSG3857;
use \geop\TileService;
use \geop\FileTileCache;
use \geop\MapRenderer;
use \geop\TileLayer;
use \geop\GeoJsonLayer;
use \geop\ImagickFactory;

// Render a map with geojson
// Zoom is computed and depends on the boundingbox and the size of the map image.

$render_width = 640;
$render_height = 400;

$cachedir = __DIR__."/tilecache/";

// © Carto, under CC BY 3.0. Data by OpenStreetMap, under ODbL
$tileservice = new TileService(["url" => "https://cartodb-basemaps-c.global.ssl.fastly.net/rastertiles/voyager/{z}/{x}/{y}.png"], new FileTileCache('carto', $cachedir));
$tileservice->setUserAgent("MakeMapApp v1.0");

$map = new Map(new CRS_EPSG3857());
// OSM has tile size of 256 pixels
$map->setTileSize(256);
$imgfactory = class_exists('Imagick') ? new ImagickFactory() : null;
$renderer = new MapRenderer($map, $imgfactory);
$renderer->addLayer(new TileLayer($tileservice));

// Bounding box around Hamburg, Germany
list($latlon, $zoom) = $renderer->fitBounds(new LatLon(53.39861676102, 9.77002), new LatLon(53.705006628648, 10.211535), $render_width, $render_height);

// Add a geojson layer with some geometries
// The outer contour of the polygon is the above bounding box 
$gjson = file_get_contents(__DIR__."/hamburg.geojson");
// Define style for the rendered geometries
$style = [
	'strokecolor' => '#3388ff',
	'fillcolor' => '#3388ff3f',
	'strokewidth' => 3,
	'strokelinecap' => 'round',
	'strokelinejoin' => 'round',
	//'strokemiterlimit' => 10,
];
$renderer->addLayer(new GeoJsonLayer($gjson, ['swapxy' => false, 'pointradius' => 8, 'style' => $style]));


$output = $renderer->renderMap($latlon, $zoom, $render_width, $render_height);
$mapimage = $output['image'];

if($imgfactory != null)
{
	$imgfactory->saveImageToFile($mapimage, __DIR__."/../assets/map3.webp");
	$imgfactory->clearImage($mapimage);
}
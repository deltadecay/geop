<?php

namespace AppExample2;

require_once(__DIR__."/../geop.php");

use \geop\LatLon;
use \geop\Map;
use \geop\CRS_EPSG4326;
use \geop\WMSTileService;
use \geop\FileTileCache;
use \geop\MapRenderer;
use \geop\ImagickFactory;

// Render a map with WMS tiles in CRS EPSG:4326 by fitting a boundingbox into view. 
// Zoom is computed and depends on the boundingbox and the size of the map image.

$render_width = 640;
$render_height = 400;

$cachedir = __DIR__."/tilecache/";

// © OpenStreetMap contributors
// https://www.terrestris.de/en/openstreetmap-wms/
// See layers in the xml at https://ows.terrestris.de/osm/service?service=WMS&version=1.1.1&request=GetCapabilities
$tileservice = new WMSTileService([
	"url" => "https://ows.terrestris.de/osm/service?",
	"layers" => "OSM-WMS",
	//"headers" => ["" => ""]
	], new FileTileCache('terrestris-osm_crs4326', $cachedir));

$tileservice->setUserAgent("MakeMapApp v1.0");

// Instead of EPSG:3857 we can use EPSG:4326 for this WMS tile service
$map = new Map(new CRS_EPSG4326());
// OSM has tile size of 256 pixels
$map->setTileSize(256);
$imgfactory = class_exists('Imagick') ? new ImagickFactory() : null;
$renderer = new MapRenderer($map, $tileservice, $imgfactory);

// Bounding box around Italy
// Given corners of a boundingbox, compute the center and zoom which fits the boundingbox in the render size
list($latlon, $zoom) = $renderer->fitBounds(new LatLon(36.267, 6.577), new LatLon(47.374, 18.654), $render_width, $render_height);

$output = $renderer->renderMap($latlon, $zoom, $render_width, $render_height);
$mapimage = $output['image'];

if($imgfactory != null)
{
	$imgfactory->saveImageToFile($mapimage, __DIR__."/../assets/map2.webp");
	$imgfactory->clearImage($mapimage);
}
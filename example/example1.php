<?php

namespace AppExample1;

require_once(__DIR__."/../geop.php");

use \geop\LatLon;
use \geop\Map;
use \geop\CRS_EPSG3857;
use \geop\TileService;
use \geop\FileTileCache;
use \geop\MapRenderer;
use \geop\ImagickFactory;

// Given a lat lon position and zoom level, render a map with OpenStreetMap tiles

// Barcelona, Spain
$latlon = new LatLon(41.381073, 2.173224);
$zoom = 5;
$render_width = 640;
$render_height = 400;

$cachedir = __DIR__."/tilecache/";

// © OpenStreetMap
$tileservice = new TileService(["url" => "https://tile.openstreetmap.org/{z}/{x}/{y}.png"], new FileTileCache('osm', $cachedir));
$tileservice->setUserAgent("MakeMapApp v1.0");

$map = new Map(new CRS_EPSG3857());
// OSM has tile size of 256 pixels
$map->setTileSize(256);
$imgfactory = class_exists('Imagick') ? new ImagickFactory() : null;
$renderer = new MapRenderer($map, $tileservice, $imgfactory);

$output = $renderer->renderMap($latlon, $zoom, $render_width, $render_height);
$mapimage = $output['image'];

if($imgfactory != null)
{
	$imgfactory->saveImageToFile($mapimage, __DIR__."/../assets/map1.webp");
	$imgfactory->clearImage($mapimage);
}
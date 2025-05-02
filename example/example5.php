<?php

namespace AppExample5;

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

// Render a map with geojson where the polygons lie in the longitude boundary 180/-180
// Note! Some of the longitude values are < -180 in the first geometry, while > 180 in the second.
// Both polygons should be shown.

// Test to get multiple maps, and also multiple geojson geometries
$latlon = new LatLon(0, 0);
$zoom = 1;

$render_width = 640;
$render_height = 640;


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

// All these bounds should zoom in on the map and show two rectangular polygons 
// around Fiji and surrounding islands. The smaller rectangle is inside the larger one.

// Fitting bounds with longitude > 180
list($latlon, $zoom) = $renderer->fitBounds(new LatLon(-15.54536165388916, 176.6282312733598), new LatLon(-19.657268304867458, 181.86003737491774), $render_width-25, $render_height-25);

// Fitting bounds with longitude < -180
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(-16.668863248640264, -180.19396575096454), new LatLon(-17.0650454078054, -179.79589593891578), $render_width*0.1, $render_height*0.1);

// View bounds longitude on the left of 180 boundary, longitude < 180 
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(-15.54536165388916, 173.6282312733598), new LatLon(-19.657268304867458, 179.9995), $render_width, $render_height);

// View bounds longitude on the right of -180, longitude > -180 
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(-16.668863248640264, -179.9995), new LatLon(-17.0650454078054, -173.79589593891578), $render_width, $render_height);


// Add a geojson layer with some geometries
$gjson = file_get_contents(__DIR__."/fiji.geojson");
// Define style for the rendered geometries
$style = [
	'strokecolor' => '#3388ff',
	'fillcolor' => '#3388ff3f',
	'strokewidth' => 1,
	'strokelinecap' => 'round',
	'strokelinejoin' => 'round',
	//'strokemiterlimit' => 10,
];
$renderer->addLayer(new GeoJsonLayer($gjson, ['swapxy' => false, 'pointradius' => 8, 'style' => $style]));


$output = $renderer->renderMap($latlon, $zoom, $render_width, $render_height);
$mapimage = $output['image'];

if($imgfactory != null)
{
	$imgfactory->saveImageToFile($mapimage, __DIR__."/../assets/map5.webp");
	$imgfactory->clearImage($mapimage);
}
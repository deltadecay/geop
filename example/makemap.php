<?php

namespace MakeMapApp;

require_once(__DIR__."/../geop.php");

use \geop\LatLon;
use \geop\Map;
use \geop\CRS_EPSG3857;
use \geop\TileService;
use \geop\WMSTileService;
use \geop\FileTileCache;
use \geop\MapRenderer;
use \geop\TileLayer;
use \geop\GeoJsonLayer;
use \geop\MarkerLayer;
use \geop\TextLayer;
use \geop\PolygonLayer;
use \geop\PolyLineLayer;
use \geop\ImagickFactory;


$latlon = new LatLon(41.381073, 2.173224);
//$latlon = new LatLon(0, 0);
//$zoom = 1.584962;
$zoom = 5;
$render_width = 768;
$render_height = 768;

$cachedir = __DIR__."/tilecache/";



// © OpenStreetMap
$tileservice = new TileService(["url" => "https://tile.openstreetmap.org/{z}/{x}/{y}.png"], new FileTileCache('osm', $cachedir));

// © Carto, under CC BY 3.0. Data by OpenStreetMap, under ODbL
$tileservice = new TileService(["url" => "https://cartodb-basemaps-c.global.ssl.fastly.net/rastertiles/voyager/{z}/{x}/{y}.png"], new FileTileCache('carto', $cachedir));

/*
// OpenStreetMap contributors, by Wikimedia
$tileservice = new TileService([
	"url" => "https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png", 
	"headers" => ["Referer" => "https://wikishootme.toolforge.org/"], 
	"usecache" => true,
	"debug" => true], new FileTileCache('osm-wikimedia', $cachedir));
*/


// © Esri, Maxar, Earthstar Geographics, and the GIS User Community
//$tileservice = new TileService(["url" => "https://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}"], new FileTileCache('arcgis_world_imagery', $cachedir));

// © Esri, HERE, Garmin, USGS, Intermap, INCREMENT P, NRCan, Esri Japan, METI, Mapwithyou, NOSTRA, © OpenStreetMap contributors, and the GIS User Community
//$tileservice = new TileService(["url" => "https://services.arcgisonline.com/arcgis/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}"], new FileTileCache('arcgis_world_street', $cachedir));

// © Esri, HERE, Garmin, Intermap, INCREMENT P, GEBCO, USGS, FAO, NPS, NRCan, GeoBase, IGN, Kadaster NL, Ordnance Survey, Esri Japan, METI, Mapwithyou, NOSTRA, © OpenStreetMap contributors, and the GIS User Community
//$tileservice = new TileService(["url" => "https://services.arcgisonline.com/arcgis/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}"], new FileTileCache('arcgis_world_topo', $cachedir));

// © OpenStreetMap contributors
// https://www.terrestris.de/en/openstreetmap-wms/
// See layers in the xml at https://ows.terrestris.de/osm/service?service=WMS&version=1.1.1&request=GetCapabilities
/*$tileservice = new WMSTileService([
	"url" => "https://ows.terrestris.de/osm/service?",
	"layers" => "OSM-WMS",
	//"debug" => true,
	//"usecache" => false,
	//"headers" => ["" => ""]
	], new FileTileCache('terrestris-osm', $cachedir));
*/

$tileservice->setUserAgent("MakeMapApp v1.0");
//$tileservice->setUserAgent("Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KH TML, like Gecko) Version/18.3.1 Safari/605.1.15");

$map = new Map(new CRS_EPSG3857());
// OSM has tile size of 256 pixels
$map->setTileSize(256);
$imgfactory = class_exists('Imagick') ? new ImagickFactory() : null;
$renderer = new MapRenderer($map, $imgfactory);
$renderer->addLayer(new TileLayer($tileservice));

// Get center and zoom level from given lat lon bounds
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(35.999914, -9.30555), new LatLon(43.79495, 4.32936), $render_width-25, $render_height-25);
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(36.267375089908796, 6.57711885384677), new LatLon(47.374091600428585, 18.653561659143662), $render_width-25, $render_height-25);
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(55.31974042967917, 10.892585699156825), new LatLon(69.27194904018671, 23.906049057270423), $render_width-25, $render_height-25);
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(53.39861676102, 9.77002), new LatLon(53.705006628648, 10.211535), $render_width-25, $render_height-25);
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(13.642184233115, 100.3790645425), new LatLon(13.942729914378, 100.71996160915), $render_width-25, $render_height-25);





//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(-15.54536165388916, 176.6282312733598), new LatLon(-19.657268304867458, 181.86003737491774), $render_width-25, $render_height-25);
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(-16.668863248640264, -180.19396575096454), new LatLon(-17.0650454078054, -179.79589593891578), $render_width-680, $render_height-680);

//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(-15.54536165388916, 173.6282312733598), new LatLon(-19.657268304867458, 179.9995), $render_width, $render_height);
//list($latlon, $zoom) = $renderer->fitBounds(new LatLon(-16.668863248640264, -179.9995), new LatLon(-17.0650454078054, -173.79589593891578), $render_width, $render_height);



$gjson = file_get_contents(__DIR__."/fiji.geojson");
$style = [
	'strokecolor' => '#3388ff',
	'fillcolor' => '#3388ff3f',
	'strokewidth' => 1,
	'strokelinecap' => 'round',
	'strokelinejoin' => 'round',
	//'strokemiterlimit' => 10,
];
$renderer->addLayer(new GeoJsonLayer($gjson, ['swapxy' => false, 'pointradius' => 10, 'style' => $style]));

$markersLatLons = [
	new LatLon(41.381073, 2.173224), // Barcelona
	new LatLon(40.416682, -3.703628), // Madrid
	new LatLon(39.469728, -0.376063), // Valencia
];

$minlat = min($markersLatLons[0]->lat, $markersLatLons[1]->lat);
$maxlat = max($markersLatLons[0]->lat, $markersLatLons[1]->lat);
$minlon = min($markersLatLons[0]->lon, $markersLatLons[1]->lon);
$maxlon = max($markersLatLons[0]->lon, $markersLatLons[1]->lon);
srand(1337);
for($i=0; $i<10; $i++)
{
	// Add some random markers
	$u = rand(0, getrandmax()) / getrandmax();
	$v = rand(0, getrandmax()) / getrandmax();
	$markersLatLons[] = new LatLon($minlat + $u * ($maxlat - $minlat), $minlon + $v * ($maxlon - $minlon));
}

$msz = 1;
$renderer->addLayer(new MarkerLayer($markersLatLons, [
	// Marker from image when markericon set to path of image 
	//'markericon' => __DIR__."/../assets/marker-icon.png",
	'shadowicon' => __DIR__."/../assets/marker-shadow.png",
	'markersize' => [$msz*25, $msz*41],
	'shadowsize' => [$msz*41, $msz*41],
	'markerorigin' => [$msz*12, $msz*40],
	'shadoworigin' => [$msz*12, $msz*40],
	// If markericon not set, a marker symbol is rendered with size 
	//'markersize' => [$msz*20, $msz*30],
	'innerradius' => $msz*9,
	'innerfill' => 'rgba(90%, 50%, 20%, 0.5)',
]));

list($latlon, $zoom) = $renderer->fitBounds($markersLatLons[0], $markersLatLons[1], $render_width - 100, $render_height - 100);

$renderer->addLayer(new TextLayer($markersLatLons[1], "Madrid\nSpain", [
	"angle" => 30,
	"style" => [
		'strokecolor' => 'black',
		'fillcolor' => 'black',
		'strokewidth' => 0,
		'font' => __DIR__."/../assets/MesloLGS NF Regular.ttf",
		'fontsize' => 24,
		'textantialias' => true,
		'textalignment' => 'left',
		'textdecoration' => 'underline',
		'textkerning' => 0,
		'textlinespacing' => 0,
		'textwordspacing' => 0,
		'textundercolor' => 'transparent',
	]
]));

// A triangle of three cities
$citiesPoly = [
	new LatLon(41.381073, 2.173224), // Barcelona
	new LatLon(40.416682, -3.703628), // Madrid
	new LatLon(39.469728, -0.376063), // Valencia
	new LatLon(41.381073, 2.173224), // Barcelona
];
$renderer->addLayer(new PolygonLayer([$citiesPoly], [
	"style" => [
		'strokecolor' => 'rgba(100%, 0%, 0%, 1.0)',
		'fillcolor' => 'rgba(50%, 10%, 10%, 0.5)',
		'strokewidth' => 3,
	],
]));

$renderer->addLayer(new PolyLineLayer(array_slice($markersLatLons, 3), [
	"style" => [
		'strokecolor' => 'green',
		'strokewidth' => 4,
		'strokelinecap' => 'round',
		'strokelinejoin' => 'round',
	],
]));

$output = $renderer->renderMap($latlon, $zoom, $render_width, $render_height);
$mapimage = $output['image'];

if($mapimage != null && $imgfactory != null)
{
	$imgfactory->saveImageToFile($mapimage, __DIR__."/map.webp");
	$imgfactory->clearImage($mapimage);
}


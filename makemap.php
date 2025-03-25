<?php

namespace MakeMapApp;

require_once(__DIR__."/geop.php");

use \geop\LatLon;
use \geop\Map;
use \geop\CRS_EPSG3857;
use \geop\TileService;
use \geop\TileCache;
use \geop\MapRenderer;



$latlon = new LatLon(53.5504683, 9.9946400);
//$latlon = new LatLon(-16.79994, 179.99275);
$zoom = 10;
$render_width = 1200;
$render_height = 400;

$cachedir = __DIR__."/tilecache";
$tileservice = new TileService("https://tile.openstreetmap.org/{z}/{x}/{y}.png", new TileCache('osm', $cachedir));
//$tileservice = new TileService("https://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", new TileCache('arcgis_world_imagery', $cachedir));
//$tileservice = new TileService("https://services.arcgisonline.com/arcgis/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}", new TileCache('arcgis_world_street', $cachedir));
//$tileservice = new TileService("https://services.arcgisonline.com/arcgis/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}", new TileCache('arcgis_world_topo', $cachedir));
$map = new Map(new CRS_EPSG3857());
$map->setTileSize(256);

$renderer = new MapRenderer($map, $tileservice);

$render = $renderer->renderMap($latlon, $zoom, $render_width, $render_height);
$mapimage = $render['map'];
$pos = $render['pos']; 

// Marker and shadow
$marker_icon = new \Imagick();
if($marker_icon->readImage(__DIR__."/marker-icon.png"))
{
    $marker_shadow = new \Imagick();
    if($marker_shadow->readImage(__DIR__."/marker-shadow.png"))
    {
        // To position the shadow aligned with the marker, we must offset with the icon sizes
        $x = intval($pos->x - $marker_icon->getImageWidth()/2);
        $y = intval($pos->y- $marker_icon->getImageHeight());
        $mapimage->compositeImage($marker_shadow, \Imagick::COMPOSITE_SRCOVER, $x, $y, \Imagick::CHANNEL_ALL);
    }
    $x = intval($pos->x- $marker_icon->getImageWidth()/2);
    $y = intval($pos->y - $marker_icon->getImageHeight());
    $mapimage->compositeImage($marker_icon, \Imagick::COMPOSITE_SRCOVER, $x, $y, \Imagick::CHANNEL_ALL);
}

$mapimage->setImageFormat('webp');
$mapimage->writeImage("map.webp");
$mapimage->clear();


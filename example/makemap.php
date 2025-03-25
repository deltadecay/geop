<?php

namespace MakeMapApp;

require_once(__DIR__."/../geop.php");

use \geop\LatLon;
use \geop\Map;
use \geop\CRS_EPSG3857;
use \geop\TileService;
use \geop\TileCache;
use \geop\MapRenderer;
use \geop\ImagickFactory;


$latlon = new LatLon(41.381073, 2.173224);
$zoom = 5;
$render_width = 640;
$render_height = 400;

$cachedir = __DIR__."/tilecache/";
$tileservice = new TileService("https://tile.openstreetmap.org/{z}/{x}/{y}.png", new TileCache('osm', $cachedir));
//$tileservice = new TileService("https://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", new TileCache('arcgis_world_imagery', $cachedir));
//$tileservice = new TileService("https://services.arcgisonline.com/arcgis/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}", new TileCache('arcgis_world_street', $cachedir));
//$tileservice = new TileService("https://services.arcgisonline.com/arcgis/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}", new TileCache('arcgis_world_topo', $cachedir));
$map = new Map(new CRS_EPSG3857());
$map->setTileSize(256);
$imgfactory = class_exists('Imagick') ? new ImagickFactory() : null;
$renderer = new MapRenderer($map, $tileservice, $imgfactory);

$output = $renderer->renderMap($latlon, $zoom, $render_width, $render_height);
$mapimage = $output['image'];
$pos = $output['pos']; 

if($mapimage != null && $imgfactory != null)
{
    // Marker and shadow
    $marker_icon = $imgfactory->newImageFromFile(__DIR__."/../assets/marker-icon.png");
    if($marker_icon != null)
    {
        list($mw, $mh) = $imgfactory->getImageSize($marker_icon);
        $marker_shadow = $imgfactory->newImageFromFile(__DIR__."/../assets/marker-shadow.png");
        if($marker_shadow != null)
        {
            // To position the shadow aligned with the marker, we must offset with the icon sizes
            $x = intval($pos->x - $mw/2);
            $y = intval($pos->y - $mh);
            $imgfactory->drawImageIntoImage($mapimage, $marker_shadow, $x, $y);
        }
        $x = intval($pos->x- $mw/2);
        $y = intval($pos->y - $mh);
        $imgfactory->drawImageIntoImage($mapimage, $marker_icon, $x, $y);
    }

    $imgfactory->saveImageToFile($mapimage, __DIR__."/map.webp");
    $imgfactory->clearImage($mapimage);
}


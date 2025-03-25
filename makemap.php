<?php

namespace MakeMapApp;

require_once(__DIR__."/geop.php");

use \geop\Map;
use \geop\CRS_EPSG3857;
use \geop\LatLon;
use \geop\Point;
use \geop\TileService;
use \geop\TileCache;


function renderMap($lat, $lon, $zoom, $render_width, $render_height)
{
    $cp = new LatLon($lat, $lon);
    $map = new Map(new CRS_EPSG3857());
    $map->setTileSize(256);
    $cp_pixel = $map->latLonToMap($cp, $zoom);
    $topleft_pixel = new Point($cp_pixel->x - $render_width/2, $cp_pixel->y - $render_height/2); 
    $bottomright_pixel = new Point($cp_pixel->x + $render_width/2, $cp_pixel->y + $render_height/2); 

    $cp_tile = $map->getTile($cp_pixel, $zoom);
    // Note! These tile coordinates can be outside map bounds, negative or >= getNumTiles
    $topleft_tile = $map->getTile($topleft_pixel, $zoom);
    $bottomright_tile = $map->getTile($bottomright_pixel, $zoom);

    $cachedir = __DIR__."/tilecache";
    $tileservice = new TileService("https://tile.openstreetmap.org/{z}/{x}/{y}.png", new TileCache('osm', $cachedir));
    //$tileservice = new TileService( "https://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", new TileCache('arcgis_world_imagery', $cachedir));
    //$tileservice = new TileService("arcgis_world_street", "https://services.arcgisonline.com/arcgis/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}");
    //$tileservice = new TileService("arcgis_world_topo", "https://services.arcgisonline.com/arcgis/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}");

    // This is the image wisth of all the tiles fitting completely
    // then it will be cropped to render width and size
    $mapimgwidth = $map->getTileSize() * ($bottomright_tile->x - $topleft_tile->x + 1);
    $mapimgheight = $map->getTileSize() * ($bottomright_tile->y - $topleft_tile->y + 1);

    $mapimage = new \Imagick();
    $mapimage->newImage($mapimgwidth, $mapimgheight, new \ImagickPixel('#7f7f7f'));
    $mapimage->setImageFormat('png');

    $ntiles = $map->getNumTiles($zoom);

    // Fetch and compose tiles into the map image
    $offsety = 0;
    for($ty=$topleft_tile->y; $ty<=$bottomright_tile->y; $ty++)
    {
        $offsetx = 0;
        for($tx=$topleft_tile->x; $tx<=$bottomright_tile->x; $tx++)
        {
            // wrap tiles along longitude
            $wrapped_tx = ($tx + $ntiles)  % $ntiles;
            $tile = new Point($wrapped_tx, $ty);
            if($map->isTileValid($tile, $zoom))
            {
                $imgblob = $tileservice->fetchTile($tile->x, $tile->y, $zoom);
                if($imgblob != null)
                {
                    $tileimage = new \Imagick();
                    $tileimage->readImageBlob($imgblob);
                    $mapimage->compositeImage($tileimage, \Imagick::COMPOSITE_COPY, $offsetx, $offsety, \Imagick::CHANNEL_ALL);
                    $tileimage->clear();
                }
            }
            $offsetx += $map->getTileSize();
        }
        $offsety += $map->getTileSize();
    }

    $crop_offsetx = intval($topleft_pixel->x - $map->getTileSize() * $topleft_tile->x);
    $crop_offsety = intval($topleft_pixel->y - $map->getTileSize() * $topleft_tile->y);
    $mapimage->cropImage($render_width, $render_height, $crop_offsetx, $crop_offsety);

    // Marker and shadow
    $marker_icon = new \Imagick();
    if($marker_icon->readImage(__DIR__."/marker-icon.png"))
    {
        $marker_shadow = new \Imagick();
        if($marker_shadow->readImage(__DIR__."/marker-shadow.png"))
        {
            // To position the shadow aligned with the marker, we must offset with the icon sizes
            $x = intval($cp_pixel->x - $topleft_pixel->x - $marker_icon->getImageWidth()/2);
            $y = intval($cp_pixel->y - $topleft_pixel->y - $marker_icon->getImageHeight());
            $mapimage->compositeImage($marker_shadow, \Imagick::COMPOSITE_SRCOVER, $x, $y, \Imagick::CHANNEL_ALL);
        }
        $x = intval($cp_pixel->x - $topleft_pixel->x - $marker_icon->getImageWidth()/2);
        $y = intval($cp_pixel->y - $topleft_pixel->y - $marker_icon->getImageHeight());
        $mapimage->compositeImage($marker_icon, \Imagick::COMPOSITE_SRCOVER, $x, $y, \Imagick::CHANNEL_ALL);
    }

    return $mapimage;
}




$lat = 53.5504683;
$lon = 9.9946400;
$zoom = 17;
$render_width = 1200;
$render_height = 400;

$mapimage = renderMap($lat, $lon, $zoom, $render_width, $render_height);
$mapimage->writeImage("map.png");
$mapimage->clear();


/*
$tile = $map->getTile($cp_pixel, $zoom);
$osm = new TileService("osm", "https://tile.openstreetmap.org/{z}/{x}/{y}.png");
$osm->fetchTile($tile->x, $tile->y, $zoom);
*/

/*
$world_imagery = new TileService("arcgis_world_imagery", "https://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}");
$world_imagery->fetchTile($tile->x, $tile->y, $zoom);


$world_street = new TileService("arcgis_world_street", "https://services.arcgisonline.com/arcgis/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}");
$world_street->fetchTile($tile->x, $tile->y, $zoom);

$world_topo = new TileService("arcgis_world_topo", "https://services.arcgisonline.com/arcgis/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}");
$world_topo->fetchTile($tile->x, $tile->y, $zoom);
*/

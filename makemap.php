<?php

namespace MakeMapApp; 

require_once(__DIR__."/geop.php");

use \geop\Map;
use \geop\CRS_EPSG3857;
use \geop\LatLon;
use \geop\Point;
use \geop\TileService;

$map = new Map(new CRS_EPSG3857());
$map->setTileSize(256);
$zoom = 4;

$cp = new LatLon(63.8283, 20.2617);

$render_width = 800;
$render_height = 400;

$cp_pixel = $map->latLonToMap($cp, $zoom);
$topleft_pixel = new Point($cp_pixel->x - $render_width/2, $cp_pixel->y - $render_height/2); 
$bottomright_pixel = new Point($cp_pixel->x + $render_width/2, $cp_pixel->y + $render_height/2); 

$cp_tile = $map->getTile($cp_pixel, $zoom);
$topleft_tile = $map->getTile($topleft_pixel, $zoom);
$bottomright_tile = $map->getTile($bottomright_pixel, $zoom);

$tileservice = new TileService("osm", "https://tile.openstreetmap.org/{z}/{x}/{y}.png");
//$tileservice = new TileService("arcgis_world_imagery", "https://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}");
//$tileservice = new TileService("arcgis_world_street", "https://services.arcgisonline.com/arcgis/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}");
//$tileservice = new TileService("arcgis_world_topo", "https://services.arcgisonline.com/arcgis/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}");


// This is the image wisth of all the tiles fitting completely
// then it will be cropped to render width and size
$mapimgwidth = $map->getTileSize() * ($bottomright_tile->x - $topleft_tile->x + 1);
$mapimgheight = $map->getTileSize() * ($bottomright_tile->y - $topleft_tile->y + 1);

// Create map image with chosen background color
// and size $mapimgwidth x $mapimgheight
$mapimage = new \Imagick();
$mapimage->newImage($mapimgwidth, $mapimgheight, new \ImagickPixel('#7f7f7f'));
$mapimage->setImageFormat('png');

$ntiles = $map->getNumTiles($zoom);

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
            $tileimage = new \Imagick();
            $tileimage->readImageBlob($imgblob);
            $mapimage->compositeImage($tileimage, \Imagick::COMPOSITE_COPY, $offsetx, $offsety, \Imagick::CHANNEL_ALL);
        }
        $offsetx += $map->getTileSize();
    }
    $offsety += $map->getTileSize();
}


$crop_offsetx = intval($topleft_pixel->x - $map->getTileSize() * $topleft_tile->x);
$crop_offsety = intval($topleft_pixel->y - $map->getTileSize() * $topleft_tile->y);

$mapimage->cropImage($render_width, $render_height, $crop_offsetx, $crop_offsety);

$mapimage->writeImage("map.png");

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

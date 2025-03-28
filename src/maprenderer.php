<?php

namespace geop;

require_once(__DIR__."/tileservice.php");
require_once(__DIR__."/imagefactory.php");
require_once(__DIR__."/map.php");

use \geop\LatLon;
use \geop\Point;

class MapRenderer
{
    protected $tileservice = null;
    protected $map = null;
    protected $imagefactory = null;

    public function __construct(Map $map, TileService $tileservice, ImageFactory $imagefactory)
    {
        $this->map = $map;
        $this->tileservice = $tileservice;
        $this->imagefactory = $imagefactory;
    }

    public function renderMap(LatLon $latlon, $zoom, $render_width=640, $render_height=480, $bgcolor = '#7f7f7f')
    {
        $zoom = intval($zoom);
        $map = $this->map;
        if($map == null)
        {
            throw new \Exception("Map model not provided");
        }
        if($this->tileservice == null)
        {
            throw new \Exception("Tile service not provided");
        }
        if($zoom < 0 || $zoom > 19)
        {
            throw new \Exception("Valid values of zoom are in the range [0,19]");
        }
        $cp_pixel = $map->latLonToMap($latlon, $zoom);
        $topleft_pixel = new Point($cp_pixel->x - $render_width/2, $cp_pixel->y - $render_height/2); 
        $bottomright_pixel = new Point($cp_pixel->x + $render_width/2, $cp_pixel->y + $render_height/2); 

        // Note! These tile coordinates can be outside map bounds, negative or >= getNumTiles
        $topleft_tile = $map->getTile($topleft_pixel, $zoom);
        $bottomright_tile = $map->getTile($bottomright_pixel, $zoom);
    
        // This is the image with all the tiles fitting completely
        // Later it will be cropped to render width and height
        $mapimgwidth = $map->getTileSize() * ($bottomright_tile->x - $topleft_tile->x + 1);
        $mapimgheight = $map->getTileSize() * ($bottomright_tile->y - $topleft_tile->y + 1);
    
        $mapimage = null;
        if ($this->imagefactory != null)
        {
            $mapimage = $this->imagefactory->newImage($mapimgwidth, $mapimgheight, $bgcolor);
        }

        // This is the size of the map in valid tiles
        $ntiles = $map->getNumTiles($zoom);
        // but the tiles we iterate over can be negative
        // but we want to wrap all negative tiles to valid tiles along longitude
        // tmulx is the value to add to any negative tile before modulo to get
        // a valid tile, in the range [0, ntiles-1] (see below)
        $tmulx = intval(abs($topleft_tile->x)) * $ntiles;

        // Fetch and compose tiles into the map image row by row
        $offsety = 0;
        for($ty=$topleft_tile->y; $ty<=$bottomright_tile->y; $ty++)
        {
            $offsetx = 0;
            for($tx=$topleft_tile->x; $tx<=$bottomright_tile->x; $tx++)
            {
                // wrap tiles along longitude
                $wrapped_tx = ($tx + $tmulx) % $ntiles;
                $tile = new Point($wrapped_tx, $ty);
                if($map->isTileValid($tile, $zoom))
                {
                    $imgblob = $this->tileservice->fetchMapTile($map, $tile, $zoom);
                    if($imgblob != null && $this->imagefactory != null)
                    {
                        $tileimage = $this->imagefactory->newImageFromBlob($imgblob);
                        $this->imagefactory->drawImageIntoImage($mapimage, $tileimage, $offsetx, $offsety);
                        $this->imagefactory->clearImage($tileimage);
                    }
                }
                $offsetx += $map->getTileSize();
            }
            $offsety += $map->getTileSize();
        }
    
        $crop_offsetx = intval($topleft_pixel->x - $map->getTileSize() * $topleft_tile->x);
        $crop_offsety = intval($topleft_pixel->y - $map->getTileSize() * $topleft_tile->y);
        if ($this->imagefactory != null)
        {
            $this->imagefactory->cropImage($mapimage, $render_width, $render_height, $crop_offsetx, $crop_offsety);
        }

        $x = $cp_pixel->x - $topleft_pixel->x;
        $y = $cp_pixel->y - $topleft_pixel->y;
    
        return ['image' => $mapimage, 'pos' => new Point($x, $y)];
    }
}


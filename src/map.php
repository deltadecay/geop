<?php

namespace geop;

require_once(__DIR__."/crs.php");
require_once(__DIR__."/geometry.php");


class Map 
{
    private $tilesize = 256;
    private $crs = null;

    public function __construct(CRS $crs)
    {
        $this->crs = $crs;
    }

    public function setTileSize($tilesize) 
    {
        $this->tilesize = intval($tilesize);
    }
    public function getTileSize() 
    {
        return $this->tilesize;
    }

    public function isTileValid(Point $tile, $zoom)
    {
        $ntiles = $this->getNumTiles($zoom);
   
        return (0 <= $tile->y && $tile->y <= $ntiles-1 &&
        0 <= $tile->x && $tile->x <= $ntiles-1);
    }

    // The map is made up of tiles, 2^zoom in each direction
    // This returns the tile for a specific map pixel
    public function getTile(Point $pixel, $zoom)
    {
        //$ntiles = $this->getNumTiles($zoom);
        $tilesize = floatval($this->getTileSize());
        $tx = intval(floor($pixel->x / $tilesize));
        $ty = intval(floor($pixel->y / $tilesize));
        
        /*
        //if($tx < 0) $tx = 0;
        //if($tx > $ntiles-1) $tx = $ntiles-1;
        
        // tiles in x wrap around
        $tx = ($tx + $ntiles) % $ntiles;

        if($ty < 0) $ty = 0;
        if($ty > $ntiles-1) $ty = $ntiles-1;
        */
        return new Point($tx, $ty);
    }

    public function getNumTiles($zoom)
    {
        return pow(2, intval($zoom));
    }

    // Map size in pixels at specified zoom level (0,1,2,...)
    public function mapSize($zoom)
    {
        return $this->tilesize * $this->getNumTiles($zoom);
    }


    // Transform the lat lon position to a pixel position in the map
    public function latLonToMap(LatLon $latlon, $zoom)
    {
        $p = $this->crs->project($latlon);
        $mapsize = $this->mapSize($zoom);
        $m = $this->crs->crsToMapTransformation($mapsize);
        $pixel = $m->transform($p);
        return $pixel;
    }

    // Transform a map pixel position to lat lon
    public function mapToLatLon(Point $pixel, $zoom)
    {
        $mapsize = $this->mapSize($zoom);
        $m = $this->crs->mapToCrsTransformation($mapsize);
        $p = $m->transform($pixel);
        $latlon = $this->crs->unproject($p);
        return $latlon;
    }
}
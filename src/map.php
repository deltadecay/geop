<?php

namespace geop;

require_once(__DIR__."/crs.php");
require_once(__DIR__."/geometry.php");


// A tile map
class Map 
{
    private $tilesize = 256;
    private $crs = null;

    public function __construct(CRS $crs)
    {
        $this->crs = $crs;
    }

    public function getCrs()
    {
        return $this->crs;
    }

    public function getCrsName()
    {
        return $this->crs->getName();
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
        $tilesize = floatval($this->getTileSize());
        $tx = intval(floor($pixel->x / $tilesize));
        $ty = intval(floor($pixel->y / $tilesize));
        
        return new Point($tx, $ty);
    }

    public function getNumTiles($zoom)
    {
        $zoom = intval($zoom);
        if($zoom < 0 || $zoom > 30)
        {
            throw new \Exception("Valid values of zoom are in the range [0,30]");
        }
        return pow(2, $zoom);
    }

    // Map size in pixels at specified zoom level (0,1,2,...)
    public function mapSize($zoom)
    {
        return $this->tilesize * $this->getNumTiles($zoom);
    }

    // Transform a point in the projected crs to pixel position in the map
    public function crsToMap(Point $p, $zoom)
    {
        $mapsize = $this->mapSize($zoom);
        $m = $this->crs->crsToMapTransformation($mapsize);
        $pixel = $m->transform($p);
        return $pixel;
    }

    // Transform a map pixel position to a point in the projected crs
    public function mapToCrs(Point $pixel, $zoom)
    {
        $mapsize = $this->mapSize($zoom);
        $m = $this->crs->mapToCrsTransformation($mapsize);
        $p = $m->transform($pixel);
        return $p;
    }

    // Transform the lat lon position to a pixel position in the map
    public function latLonToMap(LatLon $latlon, $zoom)
    {
        $p = $this->crs->project($latlon);
        $pixel = $this->crsToMap($p, $zoom);
        return $pixel;
    }

    // Transform the lat lon position to the unit square [0,1)
    public function latLonToUnitSquare(LatLon $latlon)
    {
        $p = $this->crs->project($latlon);
        $m = $this->crs->crsToMapTransformation(1.0);
        $unitp = $m->transform($p);
        return $unitp;
    }

    // Transform a map pixel position to lat lon
    public function mapToLatLon(Point $pixel, $zoom)
    {
        $p = $this->mapToCrs($pixel, $zoom);
        $latlon = $this->crs->unproject($p);
        return $latlon;
    }

    // Transform a point in the unit square to lat lon
    public function unitSquareToLatLon(Point $unitp)
    {
        $m = $this->crs->mapToCrsTransformation(1.0);
        $p = $m->transform($unitp);
        $latlon = $this->crs->unproject($p);
        return $latlon;
    }

    // The tile bounds in map pixel coordinates
    // returns array with top-left and bottom-right corner.
    public function getTileMapBounds(Point $tile, $zoom)
    {
        $tilesize = floatval($this->getTileSize());
        $topleft = new Point($tile->x * $tilesize, $tile->y * $tilesize);
        $bottomright = new Point(($tile->x + 1) * $tilesize, ($tile->y + 1) * $tilesize);
        return [$topleft, $bottomright];
    }

    // The tile bounds in the projected crs coordinates
    // returns array with top-left and bottom-right corner.
    public function getTileCrsBounds(Point $tile, $zoom)
    {
        list($tl, $br) = $this->getTileMapBounds($tile, $zoom);
        $topleft = $this->mapToCrs($tl, $zoom);
        $bottomright = $this->mapToCrs($br, $zoom);
        return [$topleft, $bottomright];
    }

    // The tile bounds in lat lon coordinates
    // returns array with top-left and bottom-right corner.
    public function getTileLatLonBounds(Point $tile, $zoom)
    {
        list($tl, $br) = $this->getTileMapBounds($tile, $zoom);
        $topleft = $this->mapToLatLon($tl, $zoom);
        $bottomright = $this->mapToLatLon($br, $zoom);
        return [$topleft, $bottomright];
    }
}
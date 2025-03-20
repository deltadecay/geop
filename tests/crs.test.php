<?php

namespace GeometryTests;

use geop\CRS_EPSG3857;
use geop\Earth;

require_once(__DIR__."/../src/crs.php");

require_once(__DIR__."/../../pest/pest.php");

use function \pest\test;
use function \pest\expect;

use \geop\Point;
use \geop\LatLon;


test("CRS EPSG:3857 project", function(){
    $crs = new CRS_EPSG3857();

    $p = $crs->project(new LatLon(0, 0));
    expect($p->x)->toBeCloseTo(0, 5);
    expect($p->y)->toBeCloseTo(0, 5);

    $p = $crs->project(new LatLon(CRS_EPSG3857::MAX_LATITUDE, -180));
    expect($p->x)->toBeCloseTo(-20037508.34, 2);
    expect($p->y)->toBeCloseTo(20037508.34, 2);

    $p = $crs->project(new LatLon(-CRS_EPSG3857::MAX_LATITUDE, 180));
    expect($p->x)->toBeCloseTo(20037508.34, 2);
    expect($p->y)->toBeCloseTo(-20037508.34, 2);

    $p = $crs->project(new LatLon(60, 20));
    expect($p->x)->toBeCloseTo(2226389.8, 1);
    expect($p->y)->toBeCloseTo(8399737.9, 1);
});

test("CRS EPSG:3857 unproject", function(){
    $crs = new CRS_EPSG3857();

    $latlon = $crs->unproject(new Point(0, 0));
    expect($latlon->lat)->toBeCloseTo(0, 2);
    expect($latlon->lon)->toBeCloseTo(0, 2);

    $latlon = $crs->unproject(new Point(-20037508.34, 20037508.34));
    expect($latlon->lat)->toBeCloseTo(CRS_EPSG3857::MAX_LATITUDE, 2);
    expect($latlon->lon)->toBeCloseTo(-180, 2);

    $latlon = $crs->unproject(new Point(20037508.34, -20037508.34));
    expect($latlon->lat)->toBeCloseTo(-CRS_EPSG3857::MAX_LATITUDE, 2);
    expect($latlon->lon)->toBeCloseTo(180, 2);

    $latlon = $crs->unproject(new Point(2226389.8, 8399737.9));
    expect($latlon->lat)->toBeCloseTo(60, 5);
    expect($latlon->lon)->toBeCloseTo(20, 5);
});


test("distance from (0,0) to (0,180)", function(){
    $earth = new Earth();
    $distance = $earth->distance(new LatLon(0, 0), new LatLon(0, 180));
    expect($distance)->toBeCloseTo(20e6, -5);
});

test("distance Heathrow to JFK", function(){
    $earth = new Earth();
    $distance = $earth->distance(new LatLon(51.46775, -0.46975), new LatLon(40.64294, -73.80083));
    expect($distance)->toBeCloseTo(5540e3, -3);
});


test("latLonToMap", function(){
    $crs = new CRS_EPSG3857();
    $crs->setTileSize(256);
    $zoom = 2;

    $pixel = $crs->latLonToMap(new LatLon(60, 20), $zoom);
    expect($pixel->x)->toBeCloseTo(568.9, 0);
    expect($pixel->y)->toBeCloseTo(297.4, 0);

    $pixel = $crs->latLonToMap(new LatLon(CRS_EPSG3857::MAX_LATITUDE, -180), $zoom);
    expect($pixel->x)->toBeCloseTo(0, 0);
    expect($pixel->y)->toBeCloseTo(0, 0);

    $pixel = $crs->latLonToMap(new LatLon(-CRS_EPSG3857::MAX_LATITUDE, 180), $zoom);
    expect($pixel->x)->toBeCloseTo(1024, 0);
    expect($pixel->y)->toBeCloseTo(1024, 0);

    $pixel = $crs->latLonToMap(new LatLon(0, 0), $zoom);
    expect($pixel->x)->toBeCloseTo(512, 0);
    expect($pixel->y)->toBeCloseTo(512, 0);
});

test("mapToLatLon", function(){
    $crs = new CRS_EPSG3857();
    $crs->setTileSize(256);
    $zoom = 2;

    $latlon = $crs->mapToLatLon(new Point(568.9, 297.4), $zoom);
    expect($latlon->lat)->toBeCloseTo(60, 0);
    expect($latlon->lon)->toBeCloseTo(20, 0);

    $latlon = $crs->mapToLatLon(new Point(0, 0), $zoom);
    expect($latlon->lat)->toBeCloseTo(CRS_EPSG3857::MAX_LATITUDE, 5);
    // both -180 and 180 are equal correct longitudes
    expect(round(-360 + $latlon->lon) % 360)->toBeCloseTo(-180, 0);

    $latlon = $crs->mapToLatLon(new Point(1024, 1024), $zoom);
    expect($latlon->lat)->toBeCloseTo(-CRS_EPSG3857::MAX_LATITUDE, 5);
    // both -180 and 180 are equal correct longitudes
    expect(round(360 + $latlon->lon) % 360)->toBeCloseTo(180, 0);

    $latlon = $crs->mapToLatLon(new Point(512, 512), $zoom);
    expect($latlon->lat)->toBeCloseTo(0, 5);
    expect($latlon->lon)->toBeCloseTo(0, 5);
});

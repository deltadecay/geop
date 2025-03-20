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


test("CRS EPSG:3857", function(){
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
});


test("CRS EPSG:3857 distance from (0,0) to (0,180)", function(){
    $earth = new Earth();

    $distance = $earth->distance(new LatLon(0, 0), new LatLon(0, 180));

    expect($distance)->toBeCloseTo(20e6, -5);
});

test("CRS EPSG:3857 distance Heathrow to JFK", function(){
    $earth = new Earth();

    $distance = $earth->distance(new LatLon(51.46775, -0.46975), new LatLon(40.64294, -73.80083));

    expect($distance)->toBeCloseTo(5540e3, -3);
});


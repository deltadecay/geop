<?php

namespace GeometryTests;

require_once(__DIR__."/../src/map.php");
require_once(__DIR__."/../../pest/pest.php");

use function \pest\test;
use function \pest\expect;

use \geop\Point;
use \geop\LatLon;
use \geop\CRS_EPSG3857;
use \geop\Map;



test("latLonToMap", function(){
    $map = new Map(new CRS_EPSG3857());
    $map->setTileSize(256);
    $zoom = 2;

    $pixel = $map->latLonToMap(new LatLon(60, 20), $zoom);
    expect($pixel->x)->toBeCloseTo(568.9, 0);
    expect($pixel->y)->toBeCloseTo(297.4, 0);
    $tile = $map->getTile($pixel);

    $pixel = $map->latLonToMap(new LatLon(CRS_EPSG3857::MAX_LATITUDE, -180), $zoom);
    expect($pixel->x)->toBeCloseTo(0, 0);
    expect($pixel->y)->toBeCloseTo(0, 0);

    $pixel = $map->latLonToMap(new LatLon(-CRS_EPSG3857::MAX_LATITUDE, 180), $zoom);
    expect($pixel->x)->toBeCloseTo(1024, 0);
    expect($pixel->y)->toBeCloseTo(1024, 0);

    $pixel = $map->latLonToMap(new LatLon(0, 0), $zoom);
    expect($pixel->x)->toBeCloseTo(512, 0);
    expect($pixel->y)->toBeCloseTo(512, 0);
});

test("mapToLatLon", function(){
    $map = new Map(new CRS_EPSG3857());
    $map->setTileSize(256);
    $zoom = 2;

    $latlon = $map->mapToLatLon(new Point(568.9, 297.4), $zoom);
    expect($latlon->lat)->toBeCloseTo(60, 0);
    expect($latlon->lon)->toBeCloseTo(20, 0);

    $latlon = $map->mapToLatLon(new Point(0, 0), $zoom);
    expect($latlon->lat)->toBeCloseTo(CRS_EPSG3857::MAX_LATITUDE, 5);
    // both -180 and 180 are equal correct longitudes
    expect(round(-360 + $latlon->lon) % 360)->toBeCloseTo(-180, 0);

    $latlon = $map->mapToLatLon(new Point(1024, 1024), $zoom);
    expect($latlon->lat)->toBeCloseTo(-CRS_EPSG3857::MAX_LATITUDE, 5);
    // both -180 and 180 are equal correct longitudes
    expect(round(360 + $latlon->lon) % 360)->toBeCloseTo(180, 0);

    $latlon = $map->mapToLatLon(new Point(512, 512), $zoom);
    expect($latlon->lat)->toBeCloseTo(0, 5);
    expect($latlon->lon)->toBeCloseTo(0, 5);
});


test("getTile", function() {
    $map = new Map(new CRS_EPSG3857());
    $map->setTileSize(256);
    $zoom = 2;

    $pixel = $map->latLonToMap(new LatLon(60, 20), $zoom);
    $tile = $map->getTile($pixel, $zoom);
    expect($tile->x)->toBe(2);
    expect($tile->y)->toBe(1);

    $pixel = $map->latLonToMap(new LatLon(CRS_EPSG3857::MAX_LATITUDE-0.001, -179.999), $zoom);
    $tile = $map->getTile($pixel, $zoom);
    expect($tile->x)->toBe(0);
    expect($tile->y)->toBe(0);

    $pixel = $map->latLonToMap(new LatLon(-CRS_EPSG3857::MAX_LATITUDE+0.001, 179.999), $zoom);
    $tile = $map->getTile($pixel, $zoom);
    expect($tile->x)->toBe(3);
    expect($tile->y)->toBe(3);

    $pixel = $map->latLonToMap(new LatLon(0, 0), $zoom);
    $tile = $map->getTile($pixel, $zoom);
    expect($tile->x)->toBe(2);
    expect($tile->y)->toBe(2);
});
<?php

namespace GeometryTests;

require_once(__DIR__."/../src/geometry.php");

require_once(__DIR__."/../../pest/pest.php");

use function \pest\test;
use function \pest\expect;

use \geop\Point;
use \geop\LatLon;
use \geop\Matrix;

test("Point", function(){
	$p = new Point();
	expect($p->x)->toBe(0);
	expect($p->y)->toBe(0);
	$p = new Point(1, 2);
	expect($p->x)->toBe(1);
	expect($p->y)->toBe(2);
	$p = new Point([3, 4]);
	expect($p->x)->toBe(3);
	expect($p->y)->toBe(4);
});

test("LatLon", function(){
	$latlon = new LatLon();
	expect($latlon->lat)->toBe(0);
	expect($latlon->lon)->toBe(0);
	$latlon = new LatLon(60, 20);
	expect($latlon->lat)->toBe(60);
	expect($latlon->lon)->toBe(20);
	$latlon = new LatLon([60, 20]);
	expect($latlon->lat)->toBe(60);
	expect($latlon->lon)->toBe(20);
});


test("Matrix", function(){
	$m = new Matrix();
	expect($m->a)->toBe(1);
	expect($m->b)->toBe(0);
	expect($m->c)->toBe(0);
	expect($m->d)->toBe(0);
	expect($m->e)->toBe(1);
	expect($m->f)->toBe(0);
	$m = new Matrix(1,0,5,0,1,10);
	expect($m->a)->toBe(1);
	expect($m->b)->toBe(0);
	expect($m->c)->toBe(5);
	expect($m->d)->toBe(0);
	expect($m->e)->toBe(1);
	expect($m->f)->toBe(10);
	$m = new Matrix([1,0,5,0,1,10]);
	expect($m->a)->toBe(1);
	expect($m->b)->toBe(0);
	expect($m->c)->toBe(5);
	expect($m->d)->toBe(0);
	expect($m->e)->toBe(1);
	expect($m->f)->toBe(10);
});

test("Matrix transformations", function(){
	// First rotate 90 ccw, then translate -10,-10
	$m = Matrix::mul(Matrix::translation(-10, -10), Matrix::rotate(M_PI/2));
	$p = $m->transform(new Point(10, 0));
	expect($p->x)->toBeCloseTo(-10, 5);
	expect($p->y)->toBeCloseTo(0, 5);

	// First rotate 45 ccw, then scale 2, then rotate 45 cw 
	$m = Matrix::mul(Matrix::scale(2,2), Matrix::rotate(M_PI/4));
	$m = Matrix::mul(Matrix::rotate(-M_PI/4), $m);
	$p = $m->transform(new Point(5, 0));
	expect($p->x)->toBeCloseTo(10, 5);
	expect($p->y)->toBeCloseTo(0, 5);
});




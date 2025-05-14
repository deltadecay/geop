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
	expect($p->x)->toBeCloseTo(-10, 8);
	expect($p->y)->toBeCloseTo(0, 8);

	// First rotate 45 ccw, then scale 2, then rotate 45 cw 
	$m = Matrix::mul(Matrix::scale(2,2), Matrix::rotate(M_PI/4));
	$m = Matrix::mul(Matrix::rotate(-M_PI/4), $m);
	$p = $m->transform(new Point(5, 0));
	expect($p->x)->toBeCloseTo(10, 8);
	expect($p->y)->toBeCloseTo(0, 8);
});


test("Matrix inverse", function(){
	$p_org = new Point(10, 0);

	// First rotate 45 ccw, then translate -10,-3
	$m = Matrix::mul(Matrix::translation(-10, -3), Matrix::rotate(M_PI/4));
	$tp = $m->transform($p_org);

	// Inverse the transformation
	$m_inv = Matrix::inverse($m);
	// and transform the already transformed point
	$p = $m_inv->transform($tp);
	
	// now p should be equal to the original starting point
	expect($p->x)->toBeCloseTo($p_org->x, 8);
	expect($p->y)->toBeCloseTo($p_org->y, 8);

	// Reverse the order and negate of the above trasnformations
	$m2 = Matrix::mul(Matrix::rotate(-M_PI/4), Matrix::translation(10, 3));
	// This should be equal to the inverse
	expect($m_inv->a)->toBeCloseTo($m2->a, 8);
	expect($m_inv->b)->toBeCloseTo($m2->b, 8);
	expect($m_inv->c)->toBeCloseTo($m2->c, 8);
	expect($m_inv->d)->toBeCloseTo($m2->d, 8);
	expect($m_inv->e)->toBeCloseTo($m2->e, 8);
	expect($m_inv->f)->toBeCloseTo($m2->f, 8);
});




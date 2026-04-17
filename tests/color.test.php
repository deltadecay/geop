<?php


namespace GeometryTests;


require_once(__DIR__."/../src/color.php");

require_once(__DIR__."/../../pest/pest.php");

use function \pest\test;
use function \pest\expect;



test("colorhex2rgba, color by names", function(){
	$col = \geop\colorhex2rgba("transparent");
	expect($col)->toBe([0,0,0,0]);
	
	list($r, $g, $b, $a) = \geop\colorhex2rgba("red");
	
	expect($r)->toBe(255);
	expect($g)->toBe(0);
	expect($b)->toBe(0);
	expect($a)->toBe(255);

	$col = \geop\colorhex2rgba("black");
	expect($col)->toBe([0,0,0,255]);
	$col = \geop\colorhex2rgba("white");
	expect($col)->toBe([255,255,255,255]);
});


test("colorhex2rgba, color from html hexstring", function(){
	$col = \geop\colorhex2rgba("#ff7f00");
	expect($col)->toBe([255,127,0,255]);
	
	$col = \geop\colorhex2rgba("#ccc");
	expect($col)->toBe([204,204,204,255]);
	
	$col = \geop\colorhex2rgba("#bad");
	expect($col)->toBe([187,170,221,255]);

	$col = \geop\colorhex2rgba("#ff000080");
	expect($col)->toBe([255,0,0,128]);
});

test("colorhex2rgba, color from rgb() or rgba()", function(){
	
	$col = \geop\colorhex2rgba("rgb(255, 127, 0)");
	expect($col)->toBe([255,127,0,255]);
	
	$col = \geop\colorhex2rgba("rgba(255, 127, 0, 0.5)");
	expect($col)->toBe([255,127,0,128]);

	$col = \geop\colorhex2rgba("rgba(100%, 0%, 0%, 1.0)");
	expect($col)->toBe([255,0,0,255]);
	
	$col = \geop\colorhex2rgba("rgba(50%, 10%, 10%, 0.3)");
	expect($col)->toBe([128,26,26,77]);
});
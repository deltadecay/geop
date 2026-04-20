<?php


namespace geop;


interface ImageFactory
{
	public function newImage($width, $height, $bgcolor);
	public function newImageFromBlob($blob);
	public function newImageFromFile($filename);

	public function drawImageIntoImage($dstImage, $srcImage, $offsetx, $offsety);
	
	// Returns the cropped image. The original image is not modified.
	public function cropImage($image, $width, $height, $offsetx, $offsety);
	
	// Returns the resized image. The original image is not modified.
	public function resizeImage($image, $width, $height);
	public function clearImage($image);

	public function getImageSize($image);

	public function saveImageToFile($image, $filename, $format);

	public function newDrawing($image);
	public function drawDrawingIntoImage($image, $drawing);

}

interface Canvas
{
	public function setStyle($style);
	public function pushState();
	public function popState();
	public function setTransformation($matrix);
	public function drawPolygon($polygon);
	public function drawPolyline($polyline);
	public function drawCircle($point, $radius);
	public function drawRectangle($pointTopLeft, $pointBottomRight);
	public function drawImage($point, $width, $height, $image);
	public function drawText($point, $text);
	public function queryTextMetrics($text);
}


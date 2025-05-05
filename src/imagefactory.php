<?php


namespace geop;

require_once(__DIR__."/geometry.php");
require_once(__DIR__."/imageblob.php");

interface ImageFactory
{
	public function newImage($width, $height, $bgcolor);
	public function newImageFromBlob($blob);
	public function newImageFromFile($filename);

	public function drawImageIntoImage($dstImage, $srcImage, $offsetx, $offsety);
	public function cropImage($image, $width, $height, $offsetx, $offsety);
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
	public function drawImage($point, $width, $height, $image);
	public function drawText($point, $text);
}




if(class_exists('Imagick')):

class ImagickFactory implements ImageFactory
{
	public function __construct()
	{

	}
	public function newImage($width, $height, $bgcolor)
	{
		$image = new \Imagick();
		$image->newImage($width, $height, new \ImagickPixel($bgcolor));
		return $image;
	}

	public function newImageFromBlob($blob)
	{
		if($blob != null)
		{
			try 
			{
				$image = new \Imagick();
				$image->readImageBlob($blob);
			}
			catch(\ImagickException $ime)
			{
				echo "ImagickFactory::newImageFromBlob(): ".$ime->getMessage().PHP_EOL;
				$imgformat = imageblob_identify($blob);
				if($imgformat !== false)
				{
					echo "Imagick not built to support decoding: $imgformat".PHP_EOL;
				}
				$image = null;
			}
			return $image;
		}
		return null;
	}
	public function newImageFromFile($filename)
	{
		$image = new \Imagick();
		if($image->readImage($filename))
		{
			return $image;
		}
		return null;
	}
	public function drawImageIntoImage($dstImage, $srcImage, $offsetx, $offsety)
	{
		if($dstImage != null && $srcImage != null)
		{
			$dstImage->compositeImage($srcImage, \Imagick::COMPOSITE_SRCOVER, $offsetx, $offsety, \Imagick::CHANNEL_ALL);
		}
	}

	public function cropImage($image, $width, $height, $offsetx, $offsety)
	{
		if($image != null)
		{
			$image->cropImage($width, $height, $offsetx, $offsety);
		}
	}

	public function resizeImage($image, $width, $height)
	{
		if($image != null)
		{
			$blur = 1.0;
			$image->resizeImage(intval($width), intval($height), \Imagick::FILTER_LANCZOS, $blur);
		}
	}

	public function clearImage($image)
	{
		if($image != null)
		{
			$image->clear();
		}
	}

	public function getImageSize($image)
	{
		if($image != null)
		{
			return [$image->getImageWidth(), $image->getImageHeight()];
		}
		return null;
	}

	public function saveImageToFile($image, $filename, $format = '')
	{
		if($image != null)
		{
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if(strlen($ext) > 0 && strlen($format) == 0)
			{
				$format = $ext;
			}
			$image->setImageFormat($format);
			$image->writeImage($filename);        
		}
	}


	public function newDrawing($image)
	{
		return new ImagickCanvas($image);
	}

	public function drawDrawingIntoImage($image, $drawing)
	{
		if($image != null && $drawing != null)
		{
			if($drawing instanceof ImagickCanvas)
			{
				//echo $drawing->getVectorGraphics();
				$image->drawImage($drawing->getInternalDrawing());
			}
		}
	}
}


class ImagickCanvas implements Canvas
{
	private $drawing = null;

	public function getInternalDrawing()
	{
		return $this->drawing;
	}

	public function __construct($image)
	{
		$this->drawing = new \ImagickDraw();
		// Apply default style
		$style = [
			'strokeantialias' => true,
			'strokecolor' => '#3388ff',
			'fillcolor' => '#3388ff3f',
			'strokewidth' => 1,
			'strokelinecap' => 'butt',
			'strokelinejoin' => 'miter',
			'strokemiterlimit' => 10,
			
			'textantialias' => true,
			'fontsize' => 12,
			//'font' => '' // not setting default font
			'textalignment' => 'left',
			'textdecoration' => 'no',
			'textkerning' => 0,
			'textlinespacing' => 0,
			'textwordspacing' => 0,
			'textundercolor' => 'transparent',
		];
		$this->setStyle($style);
	}


	public function setStyle($style)
	{
		$drawing = $this->drawing;
		if($drawing == null)
		{
			return;
		}

		if(isset($style['strokeantialias']))
		{
			$drawing->setStrokeAntialias(!!$style['strokeantialias']);
		}
		// These two can be set with stroke and fill color
		//$drawing->setStrokeOpacity(1.0);
		//$drawing->setFillOpacity(1.0);

		//$strokecolor = isset($style['strokecolor']) ? $style['strokecolor'] : '#3388ff';
		if(isset($style['strokecolor']) && is_string($style['strokecolor']))
		{
			$drawing->setStrokeColor(new \ImagickPixel($style['strokecolor']));
		}

		//$strokewidth = isset($style['strokewidth']) ? $style['strokewidth'] : 4;
		if(isset($style['strokewidth']) && is_numeric($style['strokewidth']))
		{
			$drawing->setStrokeWidth($style['strokewidth']);
		}

		if(isset($style['strokelinecap']))
		{
			$strokelinecap = is_string($style['strokelinecap']) ? $style['strokelinecap'] : "butt";
			$linecaps = [
				"undefined" => \Imagick::LINECAP_UNDEFINED,
				"butt" => \Imagick::LINECAP_BUTT,
				"round" => \Imagick::LINECAP_ROUND, 
				"square" => \Imagick::LINECAP_SQUARE,
			];
			$linecap = isset($linecaps[$strokelinecap]) ? $linecaps[$strokelinecap] : \Imagick::LINECAP_BUTT;
			$drawing->setStrokeLineCap($linecap);
		}

		if(isset($style['strokelinejoin']))
		{
			$strokelinejoin = is_string($style['strokelinejoin']) ? $style['strokelinejoin'] : "miter";
			$linejoins = [
				"undefined" => \Imagick::LINEJOIN_UNDEFINED ,
				"miter" => \Imagick::LINEJOIN_MITER,
				"mitre" => \Imagick::LINEJOIN_MITER,
				"round" => \Imagick::LINEJOIN_ROUND, 
				"bevel" => \Imagick::LINEJOIN_BEVEL,
			];
			$linejoin = isset($linejoins[$strokelinejoin]) ? $linejoins[$strokelinejoin] : \Imagick::LINEJOIN_MITER;
			$drawing->setStrokeLineJoin($linejoin);
		}

		// Support both spellings, mitre and miter
		if(isset($style['strokemitrelimit']) && is_numeric($style['strokemitrelimit']))
		{
			$drawing->setStrokeMiterLimit($style['strokemitrelimit']);
		}
		if(isset($style['strokemiterlimit']) && is_numeric($style['strokemiterlimit']))
		{
			$drawing->setStrokeMiterLimit($style['strokemiterlimit']);
		}

		if(isset($style['fillcolor']) && is_string($style['fillcolor']))
		{
			$drawing->setFillColor(new \ImagickPixel($style['fillcolor']));
		}	

		if(isset($style['textantialias']))
		{
			$drawing->setTextAntialias(!!$style['textantialias']);
		}

		if(isset($style['font']) && is_string($style['font']))
		{
			$drawing->setFont($style['font']);
		}

		if(isset($style['fontsize']) && is_numeric($style['fontsize']))
		{
			$drawing->setFontSize($style['fontsize']);
		}

		if(isset($style['textalignment']))
		{
			$textalignment = is_string($style['textalignment']) ? $style['textalignment'] : "left";
			$alignments = [
				"undefined" => \Imagick::ALIGN_UNDEFINED,
				"left" => \Imagick::ALIGN_LEFT,
				"center" => \Imagick::ALIGN_CENTER, 
				"right" => \Imagick::ALIGN_RIGHT,
			];
			$align = isset($alignments[$textalignment]) ? $alignments[$textalignment] : \Imagick::ALIGN_LEFT;
			$drawing->setTextAlignment($align);
		}

		if(isset($style['textdecoration']))
		{
			$textdecoration = is_string($style['textdecoration']) ? $style['textdecoration'] : "no";
			$decorations = [
				"no" => \Imagick::DECORATION_NO,
				"none" => \Imagick::DECORATION_NO,
				"off" => \Imagick::DECORATION_NO,
				"underline" => \Imagick::DECORATION_UNDERLINE,
				"overline" => \Imagick::DECORATION_OVERLINE, 
				"linethrough" => \Imagick::DECORATION_LINETROUGH,
			];
			$decoration = isset($decorations[$textdecoration]) ? $decorations[$textdecoration] : \Imagick::DECORATION_NO;
			$drawing->setTextDecoration($decoration);
		}

		if(isset($style['textkerning']) && is_numeric($style['textkerning']))
		{
			$drawing->setTextKerning(floatval($style['textkerning']));
		}

		if(isset($style['textlinespacing']) && is_numeric($style['textlinespacing']))
		{
			$drawing->setTextInterlineSpacing(floatval($style['textlinespacing']));
		}

		if(isset($style['textwordspacing']) && is_numeric($style['textwordspacing']))
		{
			$drawing->setTextInterwordSpacing(floatval($style['textwordspacing']));
		}

		if(isset($style['textundercolor']) && is_string($style['textundercolor']))
		{
			$drawing->setTextUnderColor(new \ImagickPixel($style['textundercolor']));
		}	
		//$drawing->setFontWeight(100); //100-900
		//$drawing->setFontStretch(\Imagick::STRETCH_NORMAL);
		//$drawing->setFontStyle(\Imagick::STYLE_NORMAL);
	}


	public function pushState()
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			$drawing->push();
		}
	}
	public function popState()
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			$drawing->pop();
		}
	}
	public function setTransformation($matrix = null)
	{
		$drawing = $this->drawing;
		if($drawing != null && $matrix != null)
		{
			if($matrix instanceof Matrix)
			{
				$affine = [
					"sx" => $matrix->a, "rx" => $matrix->b, "tx" => $matrix->c,
					"ry" => $matrix->d, "sy" => $matrix->e, "ty" => $matrix->f];
			} 
			else
			{
				$affine = $matrix;
			}
			$drawing->affine($affine);
		}
	}

	private function pointToImagickPoint(Point $p) 
	{
		return ['x' => $p->x, 'y' => $p->y];
	}

	public function drawPolygon($polygon)
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			$savedfillrule = $drawing->getFillRule();
			$drawing->setFillRule(\Imagick::FILLRULE_EVENODD);
			// Polygons are made up of one or several rings, first is the outer contour
			// and the rest are inner contours defining holes.
			$nrings = count($polygon);
			if($nrings == 1)
			{
				$points = array_map([$this, "pointToImagickPoint"], $polygon[0]);
				// Polygon without holes
				$drawing->polygon($points);
			}
			else
			{
				// Polygon has holes, use paths to build the contours
				$drawing->pathStart();
				foreach($polygon as $ring)
				{
					$npoints = count($ring);
					if($npoints > 0)
					{
						$drawing->pathMoveToAbsolute($ring[0]->x, $ring[0]->y);
						for($i=1; $i<$npoints; $i++)
						{
							$drawing->pathLineToAbsolute($ring[$i]->x, $ring[$i]->y);
						}
					}
				}
				$drawing->pathFinish();
			}
			$drawing->setFillRule($savedfillrule);
		}
	}

	public function drawPolyline($polyline)
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{

			$savedfillopacity = $drawing->getFillOpacity();
			// Turn off fill
			$drawing->setFillOpacity(0.0);

			$points = array_map([$this, "pointToImagickPoint"], $polyline);
			$drawing->polyline($points);
			$drawing->setFillOpacity($savedfillopacity);
		}
	}

	public function drawCircle($point, $radius)
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			$x = $point->x;
			$y = $point->y;
			$drawing->circle($x, $y, $x + $radius, $y);
		}
	}

	public function drawImage($point, $width, $height, $image)
	{
		$drawing = $this->drawing;
		if($drawing != null && $image != null)
		{
			$x = $point->x;
			$y = $point->y;
			$drawing->composite(\Imagick::COMPOSITE_SRCOVER, $x, $y, $width, $height, $image);
		}
	}

	public function drawText($point, $text)
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			$x = $point->x;
			$y = $point->y;
			if($drawing->getFont() !== false)
			{
				$drawing->annotation($x, $y, $text);
			}
		}
	}

}


endif;
<?php

namespace geop;

require_once(__DIR__."/geometry.php");
require_once(__DIR__."/imageblob.php");
require_once(__DIR__."/imagefactory.php");
require_once(__DIR__."/color.php");


if (function_exists("\\imagecreatetruecolor")):
 

class GDImageFactory implements ImageFactory
{
	public function __construct()
	{

	}
	public function newImage($width, $height, $bgcolor)
	{
		$image = \imagecreatetruecolor($width, $height);
		if ($image !== false) 
		{
			\imagealphablending($image, true);
			
			list($r, $g, $b, $a) = colorhex2rgba($bgcolor);
			
			if($a == 255)
			{
				$col = \imagecolorallocate($image, $r, $g, $b);
			}
			else
			{
				// GD alpha is opaque=0, transparent=127
				$alpha = 127 - intval($a / 2);
				$col = \imagecolorallocatealpha($image, $r, $g, $b, $alpha);
				//\imagesavealpha($image, false);
			}
			\imagefill($image, 0, 0, $col);
		}
		return $image;
	}

	public function newImageFromBlob($blob)
	{
		if($blob != null)
		{
			$image = \imagecreatefromstring($blob);
			if($image !== false)
			{
				\imagealphablending($image, true);
			} 
			else
			{
				echo "GDImageFactory::newImageFromBlob(): Failed to create image from blob".PHP_EOL;
				$imgformat = imageblob_identify($blob);
				if($imgformat !== false)
				{
					echo "GD not built to support decoding: $imgformat".PHP_EOL;
				}
				$image = null;
			}
			return $image;	
		}
		return null;
	}

	
	public function newImageFromFile($filename)
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$image = false;
		if($ext == "jpg" || $ext == "jpeg")
		{
			$image = \imagecreatefromjpeg($filename);
		}
		elseif($ext == "png")
		{
			$image = \imagecreatefrompng($filename);
		}
		elseif($ext == "webp")
		{
			$image = \imagecreatefromwebp($filename);
		}
		elseif($ext == "gif")
		{
			$image = \imagecreatefromgif($filename);
		}
		elseif($ext == "bmp")
		{
			$image = \imagecreatefrombmp($filename);
		}
		elseif($ext == "tga")
		{
			$image = \imagecreatefromtga($filename);
		}
		
		return $image !== false ? $image : null;
	}
	
	
	public function drawImageIntoImage($dstImage, $srcImage, $offsetx, $offsety)
	{
		if($dstImage != null && $srcImage != null)
		{
			//$dstImage->compositeImage($srcImage, \Imagick::COMPOSITE_SRCOVER, $offsetx, $offsety, \Imagick::CHANNEL_ALL);
			
			$srcw = \imagesx($srcImage);
			$srch = \imagesy($srcImage);
			$dstw = $srcw;
			$dsth = $srch;
			\imagecopyresampled($dstImage, $srcImage, $offsetx, $offsety, 0, 0, $dstw, $dsth, $srcw, $srch);
		}
	}

	// Returns the cropped image. The original image is not modified.
	public function cropImage($image, $width, $height, $offsetx, $offsety)
	{
		if($image != null)
		{
			$cropped = \imagecrop($image, ['x' => $offsetx, 'y' => $offsety, 'width' => $width, 'height' => $height]);
			return $cropped !== false ? $cropped : null;
		}
		return null;
	}

	// Returns the resized image. The original image is not modified.
	public function resizeImage($image, $width, $height)
	{
		if($image != null)
		{
			$resized = \imagecreatetruecolor($width, $height);
			if($resized !== false)
			{
				$srcw = \imagesx($image);
				$srch = \imagesy($image);
				\imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $srcw, $srch);
				return $resized;
			}
		}
		return null;
	}

	public function clearImage($image)
	{
		if(PHP_VERSION_ID < 80000)
		{
			// Before php 8.0 image resources must be freed
			if($image != null)
			{
				\imagedestroy($image);
			}
		}
	}

	public function getImageSize($image)
	{
		if($image != null)
		{
			return [\imagesx($image), \imagesy($image)];
		}
		return null;
	}

	public function saveImageToFile($image, $filename, $format = '')
	{
		if($image != null)
		{
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			if(strlen($ext) > 0 && strlen($format) == 0)
			{
				$format = $ext;
			}
			if($format == "png")
			{
				return \imagepng($image, $filename);
			}
			elseif($format == "jpg" || $format == "jpeg")
			{
				return \imagejpeg($image, $filename);
			}
			elseif($format == "gif")
			{
				return \imagegif($image, $filename);
			}
			elseif($format == "webp")
			{
				return \imagewebp($image, $filename);
			}
			elseif($format == "bmp")
			{
				return \imagebmp($image, $filename);
			}
		}
		return false;
	}



	public function newDrawing($image)
	{
		return new GDImageCanvas($image);
	}

	public function drawDrawingIntoImage($image, $drawing)
	{
		if($image != null && $drawing != null)
		{
			if($drawing instanceof GDImageCanvas)
			{
				$this->drawImageIntoImage($image, $drawing->getInternalDrawing(), 0, 0);
			}
		}
	}
}

class GDImageCanvas implements Canvas
{
	private $drawing = null;
	private $image = null;

	// The current state
	private $state = ['style' => [], 'transform' => null];
	// The drawing state is a stack of style and transform settings. This allows for nested transformations and styles.
	private $stateStack = [];
	
	public function getInternalDrawing()
	{
		return $this->drawing;
	}

	public function __construct($image)
	{
		if(!$image)
		{
			throw new \Exception("image cannot be null");
		}
		$this->image = $image;

		$width = \imagesx($image);
		$height = \imagesy($image);
		
		$this->drawing = \imagecreatetruecolor($width, $height);
		/*// The internal drawing is a transparent image
		$bgcol = \imagecolorallocatealpha($this->drawing, 0, 0, 0, 127);
		\imagealphablending($this->drawing, true);
		\imagefill($this->drawing, 0, 0, $bgcol);
		*/
		// Antialias looks at existing pixel values so instead of black transparent image 
		// we must use the current image. Otherwise there will be black pixel shadows.
		\imagealphablending($this->drawing, true);
		\imagecopy($this->drawing, $image, 0, 0, 0, 0, $width, $height);
		
		//\imagesetclip($this->drawing, 0, 0, $width-1, $height-1);
		
		//$this->drawing = $image;

		// GD doesn't have a drawing mode, so this style (and transform) must be saved as a state		
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
		$this->setTransformation(Matrix::identity());
	}

	
	public function setStyle($style)
	{
		$drawing = $this->drawing;
		if($drawing == null)
		{
			return;
		}

		// NOTE! Only a few of the options are supported in GD, compared to Imagick.
		
		if(isset($style['strokeantialias']))
		{
			// Antialias doesn't work well with imagesetthickness
			//\imageantialias($drawing, !!$style['strokeantialias']);
		}

		if(isset($style['strokecolor']) && is_string($style['strokecolor']))
		{
			// used in the draw* methods
		}

		if(isset($style['strokewidth']) && is_numeric($style['strokewidth']))
		{
			\imagesetthickness($drawing, $style['strokewidth']);
		}

		if(isset($style['strokelinecap']))
		{
			/*$strokelinecap = is_string($style['strokelinecap']) ? $style['strokelinecap'] : "butt";
			$linecaps = [
				"undefined" => \Imagick::LINECAP_UNDEFINED,
				"butt" => \Imagick::LINECAP_BUTT,
				"round" => \Imagick::LINECAP_ROUND, 
				"square" => \Imagick::LINECAP_SQUARE,
			];
			$linecap = isset($linecaps[$strokelinecap]) ? $linecaps[$strokelinecap] : \Imagick::LINECAP_BUTT;
			$drawing->setStrokeLineCap($linecap);
			*/
		}

		if(isset($style['strokelinejoin']))
		{
			/*$strokelinejoin = is_string($style['strokelinejoin']) ? $style['strokelinejoin'] : "miter";
			$linejoins = [
				"undefined" => \Imagick::LINEJOIN_UNDEFINED ,
				"miter" => \Imagick::LINEJOIN_MITER,
				"mitre" => \Imagick::LINEJOIN_MITER,
				"round" => \Imagick::LINEJOIN_ROUND, 
				"bevel" => \Imagick::LINEJOIN_BEVEL,
			];
			$linejoin = isset($linejoins[$strokelinejoin]) ? $linejoins[$strokelinejoin] : \Imagick::LINEJOIN_MITER;
			$drawing->setStrokeLineJoin($linejoin);*/
		}

		// Support both spellings, mitre and miter
		if(isset($style['strokemitrelimit']) && is_numeric($style['strokemitrelimit']))
		{
			//$drawing->setStrokeMiterLimit($style['strokemitrelimit']);
		}
		if(isset($style['strokemiterlimit']) && is_numeric($style['strokemiterlimit']))
		{
			//$drawing->setStrokeMiterLimit($style['strokemiterlimit']);
		}

		if(isset($style['fillcolor']) && is_string($style['fillcolor']))
		{
			// used in the draw* methods
		}	

		if(isset($style['textantialias']))
		{
			//$drawing->setTextAntialias(!!$style['textantialias']);
		}

		if(isset($style['font']) && is_string($style['font']))
		{
			// used in drawText
		}

		if(isset($style['fontsize']) && is_numeric($style['fontsize']))
		{
			// used in drawText
		}

		if(isset($style['textalignment']))
		{
			/*$textalignment = is_string($style['textalignment']) ? $style['textalignment'] : "left";
			$alignments = [
				"undefined" => \Imagick::ALIGN_UNDEFINED,
				"left" => \Imagick::ALIGN_LEFT,
				"center" => \Imagick::ALIGN_CENTER, 
				"right" => \Imagick::ALIGN_RIGHT,
			];
			$align = isset($alignments[$textalignment]) ? $alignments[$textalignment] : \Imagick::ALIGN_LEFT;
			$drawing->setTextAlignment($align);*/
		}

		if(isset($style['textdecoration']))
		{
			/*$textdecoration = is_string($style['textdecoration']) ? $style['textdecoration'] : "no";
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
			*/
		}

		if(isset($style['textkerning']) && is_numeric($style['textkerning']))
		{
			//$drawing->setTextKerning(floatval($style['textkerning']));
		}

		if(isset($style['textlinespacing']) && is_numeric($style['textlinespacing']))
		{
			//$drawing->setTextInterlineSpacing(floatval($style['textlinespacing']));
		}

		if(isset($style['textwordspacing']) && is_numeric($style['textwordspacing']))
		{
			//$drawing->setTextInterwordSpacing(floatval($style['textwordspacing']));
		}

		if(isset($style['textundercolor']) && is_string($style['textundercolor']))
		{
			//$drawing->setTextUnderColor(new \ImagickPixel($style['textundercolor']));
		}	
		//$drawing->setFontWeight(100); //100-900
		//$drawing->setFontStretch(\Imagick::STRETCH_NORMAL);
		//$drawing->setFontStyle(\Imagick::STYLE_NORMAL);
		
		$this->state['style'] = $style;
	}


	public function pushState()
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			//$drawing->push();
			array_push($this->stateStack, $this->state);
		}
	}
	public function popState()
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			//$drawing->pop();
			if(count($this->stateStack) > 0)
			{
				$state = array_pop($this->stateStack);
				if($state !== null)
				{
					$this->setStyle($state['style']);
					
					// Do not use setTransformation as it left multiplies the previous transform with new one. 
					//$this->setTransformation($state['transform']);	
					// We just want to reset the transform to the previous state
					$this->state['transform'] = $state['transform'];
				}
			}
		}
	}
	
	// Apply matrix to the current 
	// new transform = current * matrix
	// This mimicks the behavior of the function Imagick::affine.
	public function setTransformation($matrix = null)
	{
		$drawing = $this->drawing;
		if($drawing != null && $matrix != null)
		{
			if($matrix instanceof Matrix)
			{
				//$this->state['transform'] =  $matrix;
				$currTransform = $this->getTransform();
				$this->state['transform'] =  Matrix::mul($currTransform, $matrix);
			}
		}
	}
	
	private function getStyle()
	{
		$style = isset($this->state['style']) ? $this->state['style'] : [];
		return $style;
	}
	
	private function getTransform()
	{
		/**
		 * @var Matrix $m
		 */
		$m = isset($this->state['transform']) && ($this->state['transform'] instanceof Matrix) ? $this->state['transform'] : Matrix::identity();
		return $m;
	}

	// Returns GD color or null if all transparent (alpha=0  -> GD_alpha=127)
	private function getGDColor($colorStr)
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			list($r, $g, $b, $a) = colorhex2rgba($colorStr);
			if($a == 255)
			{
				$col = \imagecolorallocate($drawing, $r, $g, $b);
			}
			else
			{
				// GD alpha is opaque=0, transparent=127
				$alpha = 127 - intval($a / 2);
				if($alpha == 127)
					$col = null;
				else
					$col = \imagecolorallocatealpha($drawing, $r, $g, $b, $alpha);
			}
			return $col;
		}
		return null;
	}
	
	
	public function drawPolygon($polygon)
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			// Polygons are made up of one or several rings, first is the outer contour
			// and the rest are inner contours defining holes.

			$m = $this->getTransform();
			$style = $this->getStyle();
			
			$nrings = count($polygon);
			if($nrings > 0)
			{
				$ri = 0;
				foreach($polygon as $ring)
				{
					$ishole = $ri > 0;
					
					$points = [];					
					// Do not include the last closing point since in GD polygons always closed.
					for($i=0; $i<count($ring)-1; $i++)
					{
						$p = $ring[$i];
						$tp = $m->transform($p);
						$points[2*$i] = $tp->x;
						$points[2*$i + 1] = $tp->y;
					}
					
					$c = $this->getGDColor($style['fillcolor']);
					if($ishole)
					{
						// Since GD doesn't support polygons with holes, we resort to non-optimal solution
						
						// If the ring is a hole, disable blending and draw transparent polygon overwriting anything in image
						// of course this is not correct, as anything drawn behind the polygon holes will be removed.
						\imagealphablending($drawing, false);
						$c = \imagecolorallocatealpha($drawing, 0, 0, 0, 127);
					}
					if($c !== null)
					{
						\imagefilledpolygon($drawing, $points, $c);	
					}	
					
					$c = $this->getGDColor($style['strokecolor']);
					if($style['strokewidth'] > 0 && $c !== null)
					{
						\imagepolygon($drawing, $points, $c);
					}	
					$ri++;
				}
				\imagealphablending($drawing, true);
			} 

		}
	}

	public function drawPolyline($polyline)
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			$m = $this->getTransform();
			$points = [];
			$i = 0;
			foreach($polyline as $p)
			{
				$tp = $m->transform($p);
				$points[2*$i] = $tp->x;
				$points[2*$i + 1] = $tp->y;
				$i++;
			}
			$numpoints = $i;
			$style = $this->getStyle();
			$c = $this->getGDColor($style['strokecolor']);
			if($style['strokewidth'] > 0 && $c !== null)
			{
				if($numpoints >= 3)
				{
					\imageopenpolygon($drawing, $points, $c);
				}
				else
				{
					$last = 2 * ($numpoints - 1);
					\imageline($drawing, intval($points[0]), intval($points[1]), intval($points[$last]), intval($points[$last+1]), $c);
				}
			}
		}
	}

	public function drawCircle($point, $radius)
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			$m = $this->getTransform();
			$p = $m->transform($point);

			$style = $this->getStyle();
			$c = $this->getGDColor($style['fillcolor']);
			if($c !== null)
			{
				\imagefilledellipse($drawing, intval($p->x), intval($p->y), intval($radius * 2), intval($radius) * 2, $c);				
			}
			$c = $this->getGDColor($style['strokecolor']);
			if($style['strokewidth'] > 0 && $c !== null)
			{
				//\imageellipse($drawing, intval($p->x), intval($p->y), intval($radius * 2), intval($radius * 2), $c);		
		
				// Drawing an ellipse in GD doesn't support setting the stroke thickness, so a solutions is to draw multiple ellipses
				// beside each other. This is not perfect as some pixels won't be colored.
				$i = 0;
				$thickness = 2 * intval($style['strokewidth']);
				$elipsew = intval($radius * 2 - $thickness/2) + 1;
				$elipseh = intval($radius * 2 - $thickness/2) + 1;
				$x = intval($p->x);
				$y = intval($p->y);
				while ($i < $thickness) 
				{
					\imageellipse($drawing, $x, $y, $elipsew, $elipseh, $c);
					$elipsew++;
					$elipseh++;
					$i++;
				}		
			}
		}
	}

	public function drawRectangle($pointTopLeft, $pointBottomRight)
	{
		$drawing = $this->drawing;
		if($drawing != null)
		{
			$m = $this->getTransform();
			// Must transform all four corners of the rectangle
			$p1 = $m->transform($pointTopLeft);
			$p2 = $m->transform(new Point($pointTopLeft->x, $pointBottomRight->y));
			$p3 = $m->transform($pointBottomRight);
			$p4 = $m->transform(new Point($pointBottomRight->x, $pointTopLeft->y));

			$style = $this->getStyle();
			$c = $this->getGDColor($style['fillcolor']);
			if($c !== null)
			{
				//\imagefilledrectangle($drawing, $p1->x, $p1->y, $p2->x, $p2->y, $c);		
				\imagefilledpolygon($drawing, [$p1->x, $p1->y, $p2->x, $p2->y, $p3->x, $p3->y, $p4->x, $p4->y], $c);	
			}
			$c = $this->getGDColor($style['strokecolor']);
			if($style['strokewidth'] > 0 && $c !== null)
			{
				//\imagerectangle($drawing, $p1->x, $p1->y, $p2->x, $p2->y, $c);	
				// The above draws axis aligned, but we have transformed points, thus must draw with polygon.
				\imagepolygon($drawing, [$p1->x, $p1->y, $p2->x, $p2->y, $p3->x, $p3->y, $p4->x, $p4->y], $c);
			}
		}
	}

	public function drawImage($point, $width, $height, $image)
	{
		$drawing = $this->drawing;
		if($drawing != null && $image != null)
		{
			$m = $this->getTransform();
			$p = $m->transform($point);
			//$drawing->composite(\Imagick::COMPOSITE_SRCOVER, $x, $y, $width, $height, $image);
			// 
			$srcw = \imagesx($image);
			$srch = \imagesy($image);
			\imagecopyresampled($drawing, $image, intval($p->x), intval($p->y), 0, 0, $width, $height, $srcw, $srch);
		}
	}

	public function drawText($point, $text)
	{
		// TODO: GD doesn't support newline characters in the text, nor alignment
		// so a custom layouting is needed
		
		$drawing = $this->drawing;
		if($drawing != null)
		{
			$m = $this->getTransform();
			$p = $m->transform($point);
			
			$style = $this->getStyle();
			
			$c = $this->getGDColor($style['fillcolor']);
			if($c === null)
			{
				$c = $this->getGDColor($style['strokecolor']);
			}
			
			$fontfile = '';
			if(isset($style['font']) && is_string($style['font']))
			{
				$fontfile = $style['font'];
			}
			$fontsize = 12;
			if(isset($style['fontsize']) && is_numeric($style['fontsize']))
			{
				$fontsize = $style['fontsize'];
			}
			
			$p0 = $m->transform(new Point(0,0));
			$p1 = $m->transform(new Point(1,0));
			$angle = rad2deg(atan2($p1->y - $p0->y, $p1->x - $p0->x));
			
			$linespacing = 0;
			if(isset($style['textlinespacing']) && is_numeric($style['textlinespacing']))
			{
				$linespacing = floatval($style['textlinespacing']);
			}
			
			$textalignment = is_string($style['textalignment']) ? $style['textalignment'] : "left";
			$alignmulw = 0;
			if($textalignment == "left") $alignmulw = 0;
			elseif($textalignment == "center") $alignmulw = -0.5;
			elseif($textalignment == "right") $alignmulw = -1;
			
			$textdecoration = is_string($style['textdecoration']) ? $style['textdecoration'] : "";
			$decomulh = 0;
			if($textdecoration == "underline") $decomulh = 0;
			elseif($textdecoration == "overline") $decomulh = 1.0;
			elseif($textdecoration == "linethrough") $decomulh = 0.5;
			
			if($fontfile != '')
			{
				$bbox = \imagettfbbox($fontsize, 0, $fontfile, "d", ['linespacing' => 0]);
				$decoh = abs($bbox[7] - $bbox[1]);
				
				$lines = explode("\n", $text);
				foreach($lines as $i => $line)
				{
					// Use angle=0 to fetch the axis aligned text, so we know the width and height
					// We pass linespacing=0 in the options to imagettfbbox and imagettftext
					// since it doesn't really work and does not support line breaks anyway.
					$bbox = \imagettfbbox($fontsize, 0, $fontfile, $line, ['linespacing' => 0]);
					$texth = abs($bbox[7] - $bbox[1]);
					$textw = abs($bbox[2] - $bbox[0]);
				
					//\imagerectangle($drawing, $p->x + $bbox[6], $p->y + $bbox[7], $p->x + $bbox[2], $p->y + $bbox[3], $c);
					
					// new-line vector for the i:th line
					$nldx = cos(deg2rad($angle - 90)) * $i*($texth + $linespacing);
					$nldy = -sin(deg2rad($angle - 90)) * $i*($texth + $linespacing);
					
					// alignment vector
					$aligndx = cos(deg2rad($angle)) * $alignmulw * $textw;
					$aligndy = -sin(deg2rad($angle)) * $alignmulw * $textw;
					
					$x = intval($p->x + $nldx + $aligndx);
					$y = intval($p->y + $nldy + $aligndy);
					\imagettftext($drawing, $fontsize, $angle, $x, $y, $c, $fontfile, $line, ['linespacing' => 0]);	
	
					if($textdecoration != "")
					{
						// decoration vector
						$decodx = cos(deg2rad($angle + 90)) * $decomulh * $decoh;
						$decody = -sin(deg2rad($angle + 90)) * $decomulh * $decoh;
						
						$x1 = intval($p->x + $nldx + $aligndx + $decodx);
						$y1 = intval($p->y + $nldy + $aligndy + $decody);
						$x2 = intval($p->x + $nldx + $aligndx + $decodx + cos(deg2rad($angle))*$textw);
						$y2 = intval($p->y + $nldy + $aligndy + $decody - sin(deg2rad($angle))*$textw);
						\imageline($drawing, $x1, $y1, $x2, $y2, $c);
					}
				}
			}
		}
	}

	public function queryTextMetrics($text)
	{
		$metrics = [];
		$drawing = $this->drawing;
		if($this->image != null && $drawing != null)
		{
			//$metrics = $this->image->queryFontMetrics($drawing, $text);
			$style = $this->getStyle();
			
			$fontfile = '';
			if(isset($style['font']) && is_string($style['font']))
			{
				$fontfile = $style['font'];
			}
			$fontsize = 12;
			if(isset($style['fontsize']) && is_numeric($style['fontsize']))
			{
				$fontsize = $style['fontsize'];
			}
			
			$options = [];
			if(isset($style['textlinespacing']) && is_numeric($style['textlinespacing']))
			{
				$options['linespacing'] = floatval($style['textlinespacing']);
			}
			if($fontfile != '')
			{
				$bbox = \imagettfbbox($fontsize, 0, $fontfile, $text, $options);				
			}
		}
		return $metrics;
	}
	
}

endif;

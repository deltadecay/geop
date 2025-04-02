<?php


namespace geop;

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

    public function newDrawing();
    public function drawStyle($drawing, $style);
    public function drawDrawingIntoImage($image, $drawing);
    public function drawPushState($drawing);
    public function drawPopState($drawing);
    public function drawTransformation($drawing, $matrix);
    public function drawPolygon($drawing, $polygon);
    public function drawPolyline($drawing, $points);
    public function drawCircle($drawing, $x, $y, $radius);
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
            $image = new \Imagick();
            $image->readImageBlob($blob);
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


    public function newDrawing()
    {
        $drawing = new \ImagickDraw();
        // Apply default style
        $this->drawStyle($drawing, null);
        return $drawing;
    }

    public function drawStyle($drawing, $style)
    {
        $strokecolor = isset($style['strokecolor']) ? $style['strokecolor'] : '#3388ff';
        $fillcolor = isset($style['fillcolor']) ? $style['fillcolor'] : '#3388ff3f'; 
        $strokewidth = isset($style['strokewidth']) ? $style['strokewidth'] : 4;

        $strokelinecap = isset($style['strokelinecap']) ? $style['strokelinecap'] : "butt";
        $linecaps = [
            "undefined" => \Imagick::LINECAP_UNDEFINED,
            "butt" => \Imagick::LINECAP_BUTT,
            "round" => \Imagick::LINECAP_ROUND, 
            "square" => \Imagick::LINECAP_SQUARE,
        ];
        $linecap = isset($linecaps[$strokelinecap]) ? $linecaps[$strokelinecap] : \Imagick::LINECAP_BUTT;

        $strokelinejoin = isset($style['strokelinejoin']) ? $style['strokelinejoin'] : "miter";
        $linejoins = [
            "undefined" => \Imagick::LINEJOIN_UNDEFINED ,
            "miter" => \Imagick::LINEJOIN_MITER,
            "mitre" => \Imagick::LINEJOIN_MITER,
            "round" => \Imagick::LINEJOIN_ROUND, 
            "bevel" => \Imagick::LINEJOIN_BEVEL,
        ];
        $linejoin = isset($linejoins[$strokelinejoin]) ? $linejoins[$strokelinejoin] : \Imagick::LINEJOIN_MITER;
        $miterlimit = isset($style['strokemiterlimit']) ? $style['strokemiterlimit'] : (isset($style['strokemitrelimit']) ? $style['strokemitrelimit'] : 10);

        if($drawing != null)
        {
            // These two can be set with stroke and fill color
            //$drawing->setStrokeOpacity(1.0);
            //$drawing->setFillOpacity(1.0);
            $drawing->setStrokeColor(new \ImagickPixel($strokecolor));
            $drawing->setStrokeWidth($strokewidth);
            $drawing->setStrokeLineCap($linecap);
            $drawing->setStrokeLineJoin($linejoin);
            $drawing->setStrokeMiterLimit($miterlimit);
            $drawing->setFillColor(new \ImagickPixel($fillcolor));
        }
    }

    public function drawDrawingIntoImage($image, $drawing)
    {
        if($image != null && $drawing != null)
        {
            $image->drawImage($drawing);
        }
    }


    public function drawPushState($drawing)
    {
        if($drawing != null)
        {
            $drawing->push();
        }
    }
    public function drawPopState($drawing)
    {
        if($drawing != null)
        {
            $drawing->pop();
        }
    }
    public function drawTransformation($drawing, $matrix = null)
    {
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

    public function drawPolygon($drawing, $polygon)
    {
        if($drawing != null)
        {
            //$drawing->setFillRule(\Imagick::FILLRULE_NONZERO);
            $drawing->setFillRule(\Imagick::FILLRULE_EVENODD);
            $drawing->pathStart();

            // Polygon is made up of one or several rings, first is the outer contour
            // and the rest are holes.
            foreach($polygon as $ring)
            {
                $npoints = count($ring);
                if($npoints > 0)
                {
                    $drawing->pathMoveToAbsolute($ring[0]['x'], $ring[0]['y']);
                    for($i=1; $i<$npoints; $i++)
                    {
                        $drawing->pathLineToAbsolute($ring[$i]['x'], $ring[$i]['y']);
                    }
                }
            }
            $drawing->pathFinish();
        }
    }

    public function drawPolyline($drawing, $points)
    {
        if($drawing != null)
        {
            // Turn off fill
            $savedfillopacity = $drawing->getFillOpacity();
            $drawing->setFillOpacity(0.0);
            $drawing->polyline($points);
            $drawing->setFillOpacity($savedfillopacity);
        }
    }

    public function drawCircle($drawing, $x, $y, $radius)
    {
        if($drawing != null)
        {
            $drawing->circle($x, $y, $x + $radius, $y + $radius);
        }
    }
}
    
endif;
<?php


namespace geop;

interface ImageFactory
{
    public function newImage($width, $height, $bgcolor);
    public function newImageFromBlob($blob);
    public function newImageFromFile($filename);

    public function drawImageIntoImage($dstImage, $srcImage, $offsetx, $offsety);
    public function cropImage($image, $width, $height, $offsetx, $offsety);

    public function clearImage($image);

    public function getImageSize($image);

    public function saveImageToFile($image, $filename, $format);
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
}
    
endif;
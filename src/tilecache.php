<?php

namespace geop;

require_once(__DIR__."/http.php");

class TileCache
{
    private $name;
    private $cachedir = "tilecache";
    private $format = '';

    public function __construct($name='tileservice')
    {
        $this->name = $name;
    }

    public function setFormat($format)
    {
        $this->format = strtolower($format);
    }

    private function getTilePath($x, $y, $z)
    {
        $path = $this->cachedir . "/" . $this->name . "/$z/$x/";
        return $path;
    }

    private function getTilePathFilename($x, $y, $z)
    {
        $file = $this->getTilePath($x, $y, $z) . "$y";
        if(strlen($this->format) > 0)
        {
            $file .= "." . $this->format;
        }
        return $file;
    }
        
    public function hasTile($x, $y, $z)
    {
        $file = $this->getTilePathFilename($x, $y, $z);
        return file_exists($file);
    }

    public function loadTile($x, $y, $z)
    {
        $file = $this->getTilePathFilename($x, $y, $z);
        return file_get_contents($file);
    }

    public function saveTile($x, $y, $z, $blob)
    {
        $path = $this->getTilePath($x, $y, $z);
        if(!is_dir($path))
        {
            mkdir($path, 0755, true);
        }
        $file = $this->getTilePathFilename($x, $y, $z);
        file_put_contents($file, $blob);
    }
}

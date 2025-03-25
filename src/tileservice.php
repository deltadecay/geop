<?php

namespace geop;

require_once(__DIR__."/http.php");
require_once(__DIR__."/tilecache.php");


class TileService
{
    private $urltemplate = '';
    private $cache = null;
    private $useragent = 'TileService';

    public function __construct($urltemplate, $cache = null)
    {
        $this->urltemplate = $urltemplate;
        if($cache == null)
        {
            $cache = new FileTileCache();
        }
        $this->cache = $cache;
    }

    public function setUserAgent($useragent)
    {
        $this->useragent = $useragent;
    }

    public function fetchTile($x, $y, $z)
    {
        $x = intval($x);
        $y = intval($y);
        $z = intval($z);
        
        $url = str_replace(['{x}', '{y}', '{z}'], [$x, $y, $z], $this->urltemplate);
        $format = pathinfo($url, PATHINFO_EXTENSION);
        $this->cache->setFormat($format);

        if($this->cache->hasTile($x, $y, $z))
        {
            return $this->cache->loadTile($x, $y, $z);
        }

        $headers = [
            "User-Agent: " . $this->useragent,
        ];
        //echo "Fetch from $url\n";
        $res = http_get($url , $headers);

        $blob = null;
        if ($res['httpcode'] == 200)
        {
            $blob = $res['body'];
            $mimetype = isset($res['headers']['content-type']) ? $res['headers']['content-type'][0] : '';
            //$etag = isset($res['headers']['etag']) ? $res['headers']['etag'][0] : '';
                      
            $this->cache->saveTile($x, $y, $z, $blob);
        }
        else
        {
            //$res['httpcode']
            $error = $res['error'];
        }

        return $blob;
    }

}



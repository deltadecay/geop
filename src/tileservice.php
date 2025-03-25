<?php

namespace geop;

require_once(__DIR__."/http.php");
require_once(__DIR__."/tilecache.php");


class TileService
{
    private $urltemplate = '';
    private $cache = null;

    public function __construct($urltemplate, $cache = null)
    {
        $this->urltemplate = $urltemplate;
        if($cache == null)
        {
            $cache = new FileTileCache();
        }
        $this->cache = $cache;
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
            echo "Load from cache\n";
            return $this->cache->loadTile($x, $y, $z);
        }

        $headers = [
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Safari/605.1.15",
        ];
        echo "Fetch from $url\n";
        $res = http_get($url , $headers);

        $blob = null;
        if ($res['httpcode'] == 200)
        {
            $blob = $res['body'];
            $mimetype = isset($res['headers']['content-type']) ? $res['headers']['content-type'][0] : '';
            //$etag = isset($res['headers']['etag']) ? $res['headers']['etag'][0] : '';
            echo "Save to cache\n";            
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



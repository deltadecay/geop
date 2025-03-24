<?php

namespace geop;

require_once(__DIR__."/http.php");
require_once(__DIR__."/tilecache.php");


class TileService
{
    private $urltemplate = '';
    private $name = '';
    private $cache = null;

    public function __construct($name, $urltemplate)
    {
        $this->name = $name;
        $this->urltemplate = $urltemplate;
        $this->cache = new TileCache($name);
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

            /*
            if(strlen($format) == 0 && strlen($mimetype) > 0)
            {
                // If no format from url, try reading from the content-type mimetype
                unset($matches);
                //if(preg_match("/^(application|audio|example|image|message|model|multipart|text|video)\/(x(?=[-\.])|(?:prs|vnd)+(?=\.))?(?:(?!x)[-\.])?([^\s]+?)(?:\+(xml|json|ber|der|fastinfoset|wbxml|zip|cbor))?$/", "image/png", $matches) > 0)
                if(preg_match("/^image\/(x(?=[-\.])|(?:prs|vnd)+(?=\.))?(?:(?!x)[-\.])?([^\s]+?)(?:\+(xml|json|ber|der|fastinfoset|wbxml|zip|cbor))?$/", $mimetype, $matches) > 0)
                {
                    // prefix: x-, x., vnd., prs.
                    $prefix = $matches[1];
                    $format = $matches[2];
                    // example svg+xml
                    $afterplus = count($matches)>3 ? $matches[3] : '';
                }
            }
            */
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



<?php

namespace geop;

require_once(__DIR__."/http.php");
require_once(__DIR__."/tilecache.php");
require_once(__DIR__."/map.php");
require_once(__DIR__."/geometry.php");
require_once(__DIR__."/imageblob.php");


class TileService
{
	protected $options = [];
	protected $cache = null;
	protected $useragent = '';

	public function __construct($options, $cache = null)
	{
		$this->options = $options;
		if(!isset($options['url']) || strlen($options['url']) == 0)
		{
			throw new \Exception("options has no url defined.");
		}
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

	protected function makeUrl(Map $map, Point $tile, $zoom)
	{
		$x = intval($tile->x);
		$y = intval($tile->y);
		$z = intval($zoom);
		$url = str_replace(['{x}', '{y}', '{z}'], [$x, $y, $z],$this->options['url']);
		return $url;
	}

	protected function makeHeaders(Map $map, Point $tile, $zoom)
	{
		$headers = [];
		if(strlen($this->useragent) > 0)
		{
			$headers[] = "User-Agent: " . $this->useragent;
		} 
		if(isset($this->options['headers']) && is_array($this->options['headers']))
		{
			foreach($this->options['headers'] as $key => $value)
			{
				$headers[] = "$key: $value";
			}
		}
		return $headers;
	}

	public function fetchMapTile(Map $map, Point $tile, $zoom)
	{
		$x = intval($tile->x);
		$y = intval($tile->y);
		$z = intval($zoom);
		
		$debug = isset($this->options['debug']) ? !!$this->options['debug'] : false;
		$usecache = isset($this->options['usecache']) ? !!$this->options['usecache'] : true;

		if($usecache && $this->cache->hasTile($x, $y, $z)) 
		{
			if($debug) 
				echo "Load /$z/$x/$y from cache\n";
			return $this->cache->loadTile($x, $y, $z);
		}

		$url = $this->makeUrl($map, $tile, $zoom);
		$headers = $this->makeHeaders($map, $tile, $zoom);
		
		if($debug) 
			echo "Fetch /$z/$x/$y from $url\n";

		$res = http_get($url, $headers);

		$blob = null;
		if($res['httpcode'] == 200)
		{
			$blob = $res['body'];
			$mimetype = isset($res['headers']['content-type']) ? $res['headers']['content-type'][0] : '';
			//$etag = isset($res['headers']['etag']) ? $res['headers']['etag'][0] : '';
				
			$blobimgformat = imageblob_identify($blob);
			if($blobimgformat !== false)
			{
				if($usecache)
				{
					if($debug) 
						echo "Save /$z/$x/$y to cache ($blobimgformat)\n";
					$this->cache->saveTile($x, $y, $z, $blob);
				}
			}
			else
			{
				if($debug)
				{
					echo "Unknown image format\n";
					if(str_contains($mimetype, "text") ||
						str_contains($mimetype, "json") ||
						str_contains($mimetype, "html") ||
						str_contains($mimetype, "xml"))
					{
						echo $blob."\n";
					}
				}
				$blob = null;
			}
		}
		else
		{
			//$res['httpcode']
			$error = $res['error'];
			if($debug)
				echo $res['httpcode']." ".$error."\n";
		}

		return $blob;
	}

}

class WMSTileService extends TileService
{
	protected function makeUrl(Map $map, Point $tile, $zoom)
	{
		// WMS example:

		// See layers in GetCapabilities
		// https://ows.mundialis.de/services/service?request=GetCapabilities

		// Tile example:
		// https://ows.mundialis.de/services/service?&service=WMS&request=GetMap&layers=TOPO-OSM-WMS&styles=&format=image%2Fjpeg&transparent=false&version=1.1.1&width=256&height=256&srs=EPSG%3A3857&bbox=2504688.5428486555,6261721.357121641,3757032.814272984,7514065.628545967

		list($tl, $br) = $map->getTileCrsBounds($tile, $zoom);

		// Note! The bounding box is lower left and upper right
		$bbox = $tl->x . "," . $br->y . "," . $br->x . "," .$tl->y;

		$defaults = [
			"service" => "WMS",
			"request" => "GetMap",
			"layers" => '',
			"styles" => '',
			"format" => "image/jpeg",
			"transparent" => "false",
			"version" => "1.1.1",
			"width" => $map->getTileSize(),
			"height" => $map->getTileSize(),
			"srs" => $map->getCrsName(),
			"bbox" => $bbox,
		];

		$url = $this->options['url'];
		$query = parse_url($url, PHP_URL_QUERY);
		
		// 1. Get query params from url
		$urlparams = [];
		if($query != null && strlen($query) > 0)
		{
			parse_str($query, $urlparams);
		}

		foreach($defaults as $key => $defvalue)
		{
			// 2. If options has the param, then use that instead of query param
			if(array_key_exists($key, $this->options))
			{
				$urlparams[$key] = $this->options[$key];
			}
			// 3. If neither query or options has the param, then use defaults
			if(!array_key_exists($key, $urlparams))
			{
				$urlparams[$key] = $defvalue;
			}
		}

		if(strlen($urlparams['layers']) == 0)
		{
			throw new \Exception("layers has not been set for this WMS tile service");
		}
		
		// Remove anything after ? in the url and append the new url params
		$qpos = stripos($url, "?");
		if($qpos !== false)
		{
			$url = substr($url, 0, $qpos);
		}
		$url .= "?" . http_build_query($urlparams);
		return $url;
	}
}

<?php

namespace geop;

require_once(__DIR__."/tileservice.php");
require_once(__DIR__."/imagefactory.php");
require_once(__DIR__."/map.php");
require_once(__DIR__."/geometry.php");
require_once(__DIR__."/layer.php");

class MapRenderer
{
	protected $map = null;
	protected $imagefactory = null;

	protected $layers = [];

	public function __construct(Map $map, ImageFactory $imagefactory)
	{
		$this->map = $map;
		$this->imagefactory = $imagefactory;
	}

	// Given two corner points on a bounding box in lat lon coordinates, compute the center point and zoom level
	// that fits the bounds inside a rendered image with given width and height.
	public function fitBounds(LatLon $p1, LatLon $p2, $render_width, $render_height, $maxzoom = 19)
	{
		$maxzoom = intval($maxzoom);
		if($this->map == null)
		{
			throw new \Exception("Map model not provided");
		}

		// Cannot take mid point in lat lon since after projection
		// lat scales according to a scaling factor the further away from equator it is, so
		// it is not linear and thus we cannot take the mid point in lat lon coordinates.
		//$cp = new LatLon(($p1->lat + $p2->lat)*0.5, ($p1->lon + $p2->lon)*0.5);
		
		$unitp1 = $this->map->latLonToUnitSquare($p1);
		$unitp2 = $this->map->latLonToUnitSquare($p2);
		// Points in unit square are after proj and thus we can take mid point
		$midunitp = new Point(($unitp1->x + $unitp2->x)*0.5, ($unitp1->y + $unitp2->y)*0.5);

		// Width and height in the unit square
		$uw = abs($unitp2->x - $unitp1->x);
		$uh = abs($unitp2->y - $unitp1->y);

		// zoom = log2(rendersize / (tilesize * unitsize)), where size is width or height, log2(x) = log(x)/log(2)
		$oolog2 = 1.0 / log(2.0); 
		$zoom_w = $maxzoom;
		if($uw > 0.0)
		{
			$zoom_w  = (log($render_width / ($this->map->getTileSize() * $uw)) * $oolog2);
		}
		$zoom_h = $maxzoom;
		if($uh > 0.0)
		{
			$zoom_h  = (log($render_height / ($this->map->getTileSize() * $uh)) * $oolog2);
		}
		$zoom = min($zoom_w, $zoom_h);
		if($zoom < 0) $zoom = 0;
		if($zoom > $maxzoom) $zoom = $maxzoom;

		$latlon = $this->map->unitSquareToLatLon($midunitp);
		return [$latlon, $zoom];
	}

	public function addLayer(Layer $layer)
	{
		$this->layers[] = $layer;
	}


	public function renderMap(LatLon $latlon, $zoom, $render_width=640, $render_height=480, $bgcolor = '#7f7f7f')
	{
		$map = $this->map;
		if($map == null)
		{
			throw new \Exception("Map model not provided");
		}
		if($zoom < 0 || $zoom > 30)
		{
			throw new \Exception("Valid values of zoom are in the range [0,30]");
		}

		$mapimage = null;
		if ($this->imagefactory != null)
		{
			$mapimage = $this->imagefactory->newImage($render_width, $render_height, $bgcolor);
		}

		// Render the layers
		foreach ($this->layers as $layer)
		{
			$layer->render($this->imagefactory, $mapimage, $map, $latlon, $zoom);
		}

		// Compute with real fractional zoom
		$cp_pixel = $map->latLonToMap($latlon, $zoom);
		$topleft_pixel = new Point($cp_pixel->x - $render_width/2, $cp_pixel->y - $render_height/2); 
		$bottomright_pixel = new Point($cp_pixel->x + $render_width/2, $cp_pixel->y + $render_height/2); 
		
		// The topleft and bottomright corners in lat lon
		$topleft = $map->mapToLatLon($topleft_pixel, $zoom);
		$bottomright = $map->mapToLatLon($bottomright_pixel, $zoom);

		return ['image' => $mapimage, 'topleft' => $topleft, 'bottomright' => $bottomright];
	}



}


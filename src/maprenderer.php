<?php

namespace geop;

require_once(__DIR__."/tileservice.php");
require_once(__DIR__."/imagefactory.php");
require_once(__DIR__."/map.php");
require_once(__DIR__."/geometry.php");

class MapRenderer
{
	protected $tileservice = null;
	protected $map = null;
	protected $imagefactory = null;

	protected $layers = [];

	public function __construct(Map $map, TileService $tileservice, ImageFactory $imagefactory)
	{
		$this->map = $map;
		$this->tileservice = $tileservice;
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


	public function addGeoJsonLayer($geojson, $options = [])
	{
		if(!is_array($options))
		{
			$options = [];
		}
		if(is_string($geojson))
		{
			$geojson = json_decode($geojson, true);
		}
		$featurecoll = null;
		$type = strtolower($geojson['type']);
		if($type == "featurecollection")
		{
			$featurecoll = $geojson;
		}
		elseif($type == "feature")
		{
			$featurecoll = ["type" => "FeatureCollection", "features" => [ $geojson ]];
		}
		elseif(in_array($type, ["point", "multipoint", "linestring", "multilinestring", "polygon", "multipolygon", "geometrycollection"]))
		{
			$featurecoll = [
				"type" => "FeatureCollection", 
				"features" => [
					[ "type" => "Feature", "geometry" => $geojson, /*"properties" => ["name" => ""] */ ]
				]
			];
		}
		
		if($featurecoll != null)
		{
			$this->layers[] = ["type" => "geojson", "geojson" => $featurecoll, "options" => $options]; 
		}
	}


	public function renderMap(LatLon $latlon, $zoom, $render_width=640, $render_height=480, $bgcolor = '#7f7f7f')
	{
		$izoom = intval($zoom);
		$map = $this->map;
		if($map == null)
		{
			throw new \Exception("Map model not provided");
		}
		if($this->tileservice == null)
		{
			throw new \Exception("Tile service not provided");
		}
		if($izoom < 0 || $izoom > 30)
		{
			throw new \Exception("Valid values of zoom are in the range [0,30]");
		}
		$cp_pixel = $map->latLonToMap($latlon, $izoom);
		$topleft_pixel = new Point($cp_pixel->x - $render_width/2, $cp_pixel->y - $render_height/2); 
		$bottomright_pixel = new Point($cp_pixel->x + $render_width/2, $cp_pixel->y + $render_height/2); 

		// Note! These tile coordinates can be outside map bounds, negative or >= getNumTiles
		$topleft_tile = $map->getTile($topleft_pixel, $izoom);
		$bottomright_tile = $map->getTile($bottomright_pixel, $izoom);

		// This is the image with all the tiles fitting completely
		// Later it will be cropped to render width and height
		$mapimgwidth = $map->getTileSize() * ($bottomright_tile->x - $topleft_tile->x + 1);
		$mapimgheight = $map->getTileSize() * ($bottomright_tile->y - $topleft_tile->y + 1);

		$mapimage = null;
		if ($this->imagefactory != null)
		{
			$mapimage = $this->imagefactory->newImage($mapimgwidth, $mapimgheight, $bgcolor);
		}

		// This is the size of the map in valid tiles
		$ntiles = $map->getNumTiles($izoom);
		// but the tiles we iterate over can be negative
		// but we want to wrap all negative tiles to valid tiles along longitude
		// tmulx is the value to add to any negative tile before modulo to get
		// a valid tile, in the range [0, ntiles-1] (see below)
		$tmulx = intval(abs($topleft_tile->x)) * $ntiles;

		// Fetch and compose tiles into the map image row by row
		$offsety = 0;
		for($ty=$topleft_tile->y; $ty<=$bottomright_tile->y; $ty++)
		{
			$offsetx = 0;
			for($tx=$topleft_tile->x; $tx<=$bottomright_tile->x; $tx++)
			{
				// wrap tiles along longitude
				$wrapped_tx = ($tx + $tmulx) % $ntiles;
				$tile = new Point($wrapped_tx, $ty);
				if($map->isTileValid($tile, $izoom))
				{
					$imgblob = $this->tileservice->fetchMapTile($map, $tile, $izoom);
					if($imgblob != null && $this->imagefactory != null)
					{
						$tileimage = $this->imagefactory->newImageFromBlob($imgblob);
						$this->imagefactory->drawImageIntoImage($mapimage, $tileimage, $offsetx, $offsety);
						$this->imagefactory->clearImage($tileimage);
					}
				}
				$offsetx += $map->getTileSize();
			}
			$offsety += $map->getTileSize();
		}

		$scale = pow(2, $zoom - $izoom);
		if($scale > 1.0)
		{
			if ($this->imagefactory != null)
			{
				$this->imagefactory->resizeImage($mapimage, intval($mapimgwidth*$scale), intval($mapimgheight*$scale));
			}
		}

		$midp_offsetx = ($render_width*$scale - $render_width) / 2;
		$midp_offsety = ($render_height*$scale - $render_height) / 2;

		$crop_offsetx = intval(($topleft_pixel->x - $map->getTileSize() * $topleft_tile->x) * $scale + $midp_offsetx);
		$crop_offsety = intval(($topleft_pixel->y - $map->getTileSize() * $topleft_tile->y) * $scale + $midp_offsety);

		if ($this->imagefactory != null)
		{
			$this->imagefactory->cropImage($mapimage, $render_width, $render_height, $crop_offsetx, $crop_offsety);
		}

		$x = $cp_pixel->x - $topleft_pixel->x;
		$y = $cp_pixel->y - $topleft_pixel->y;


		// Compute with real fractional zoom
		$cp_pixel = $map->latLonToMap($latlon, $zoom);
		$topleft_pixel = new Point($cp_pixel->x - $render_width/2, $cp_pixel->y - $render_height/2); 
		$bottomright_pixel = new Point($cp_pixel->x + $render_width/2, $cp_pixel->y + $render_height/2); 

		// The topleft and bottomright corners in lat lon
		$topleft = $map->mapToLatLon($topleft_pixel, $zoom);
		$bottomright = $map->mapToLatLon($bottomright_pixel, $zoom);


		// Handle the wrapped copies of geometries to render
		$wrapstart = floor($topleft_pixel->x / $map->mapSize($zoom));
		$wrapend = floor($bottomright_pixel->x / $map->mapSize($zoom));
		$maxwrap = max(abs($wrapstart), $wrapend);
		// If render only main, still include both -1 and 1 as there can be geometries at the boundaries
		// Note! This forces always three renders 
		if($maxwrap == 0)
			$maxwrap = 1;
		$wrapcopystart = -$maxwrap;
		$wrapcopyend = $maxwrap; 

		// The map wraps at boundary -180/180 longitude. Some geometries can be represented
		// at longitude around 180 while others around -180, but these should render
		// in the same map. Easiest solution is to render multiple copies of the geometries
		for($wrapcopy=$wrapcopystart; $wrapcopy<=$wrapcopyend; $wrapcopy++)
		{
			//echo "WrapCopy=$wrapcopy\n";
			// Translate with the width of map for each wrap copy. 
			// wrapcopy=0 is the original map
			$originMatrix = Matrix::translation(-($topleft_pixel->x + $wrapcopy * $map->mapSize($zoom)), -$topleft_pixel->y);
				
			// Draw layers
			$drawing = null;
			if ($this->imagefactory != null)
			{
				$drawing = $this->imagefactory->newDrawing($mapimage);
				$this->imagefactory->drawTransformation($drawing, $originMatrix);
			}
			foreach ($this->layers as $layer)
			{
				$options = $layer['options'];
				if ($this->imagefactory != null && isset($options['style']))
				{
					$this->imagefactory->drawStyle($drawing,$options['style']);
				}
				if($layer['type'] == 'geojson')
				{
					$geojson = $layer['geojson'];
					$this->drawGeoJsonLayer($drawing, $geojson, $options, $map, $zoom);
				}
			}
			if ($this->imagefactory != null)
			{
				$this->imagefactory->drawDrawingIntoImage($mapimage, $drawing);
			}
		}

		return ['image' => $mapimage, 'pos' => new Point($x, $y), 'topleft' => $topleft, 'bottomright' => $bottomright];
	}


	private function drawGeoJsonLayer($drawing, $geojson, $options, $map, $zoom)
	{
		$type = strtolower($geojson['type']);
		if($type == "featurecollection")
		{
			foreach($geojson['features'] as $feature)
			{
				$type = strtolower($feature['type']);
				if($type == "feature")
				{
					$geom = $feature['geometry'];
					$this->drawGeometry($drawing, $geom, $options, $map, $zoom);
				}
			}
		}
	}



	private function drawGeometry($drawing, $geom, $options, $map, $zoom)
	{
		$type = strtolower($geom['type']);
		if($type == "point")
		{
			$point = $geom['coordinates'];
			$this->drawPoint($drawing, $point, $options, $map, $zoom);
		}
		elseif($type == "multipoint")
		{
			foreach($geom['coordinates'] as $point)
			{
				$this->drawPoint($drawing, $point, $options, $map, $zoom);
			}
		}
		elseif($type == "linestring")
		{
			$linestring = $geom['coordinates'];
			$this->drawLineString($drawing, $linestring, $options, $map, $zoom);
		}
		elseif($type == "multilinestring")
		{
			foreach($geom['coordinates'] as $linestring)
			{
				$this->drawLineString($drawing, $linestring, $options, $map, $zoom);
			}
		}
		elseif($type == "polygon")
		{
			$polygon = $geom['coordinates'];
			$this->drawPolygon($drawing, $polygon, $options, $map, $zoom);
		}
		elseif($type == "multipolygon")
		{
			foreach($geom['coordinates'] as $polygon)
			{
				$this->drawPolygon($drawing, $polygon, $options, $map, $zoom);
			}
		}
		elseif($type == "geometrycollection")
		{
			foreach($geom['geometries'] as $geometry)
			{
				$this->drawGeometry($drawing, $geometry, $options, $map, $zoom);
			}
		}
	}

	private function getLatLonIndices($options)
	{
		$LAT = 1;
		$LON = 0;
		if(isset($options['swapxy']) && !!$options['swapxy'])
		{
			$LAT = 0;
			$LON = 1;
		}
		return [$LAT, $LON];
	}

	private function drawPoint($drawing, $point, $options, $map, $zoom)
	{
		list($LAT, $LON) = $this->getLatLonIndices($options);

		$pixel = $map->latLonToMap(new LatLon($point[$LAT], $point[$LON]), $zoom);
		if ($this->imagefactory != null)
		{
			$radius = isset($options['style']['pointradius']) ? $options['style']['pointradius'] : 1;
			$this->imagefactory->drawCircle($drawing, $pixel, $radius);
		}
	}

	private function drawLineString($drawing, $linestring, $options, $map, $zoom)
	{
		list($LAT, $LON) = $this->getLatLonIndices($options);

		$polyline_pixel = [];
		foreach($linestring as $point)
		{
			$pixel = $map->latLonToMap(new LatLon($point[$LAT], $point[$LON]), $zoom);
			$polyline_pixel[] = $pixel; 
		}
		if ($this->imagefactory != null)
		{
			$this->imagefactory->drawPolyline($drawing, $polyline_pixel);
		}
	}

	private function drawPolygon($drawing, $polygon, $options, $map, $zoom)
	{
		list($LAT, $LON) = $this->getLatLonIndices($options);

		$poly_pixel = [];
		foreach($polygon as $ring)
		{
			$ring_pixel = [];
			foreach($ring as $point)
			{
				$pixel = $map->latLonToMap(new LatLon($point[$LAT], $point[$LON]), $zoom);
				$ring_pixel[] = $pixel;
			}
			$poly_pixel[] = $ring_pixel;
		}
		if ($this->imagefactory != null)
		{
			$this->imagefactory->drawPolygon($drawing, $poly_pixel);
		}
	}



}


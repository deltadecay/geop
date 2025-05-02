<?php

namespace geop;

require_once(__DIR__."/map.php");
require_once(__DIR__."/imagefactory.php");
require_once(__DIR__."/tileservice.php");
require_once(__DIR__."/geometry.php");

abstract class Layer
{

	public function render(ImageFactory $imagefactory, $mapimage, Map $map, LatLon $latlon, $zoom)
	{
		throw new \Exception("render() not implemented");
	}
}


class TileLayer extends Layer
{
	protected $tileservice = null;
	protected $options = [];

	public function __construct(TileService $tileservice, $options = [])
	{
		if(!is_array($options))
		{
			$options = [];
		}
		if($tileservice == null)
		{
			throw new \Exception("Tile service not provided");
		}
		$this->tileservice = $tileservice;
	}

	public function render(ImageFactory $imagefactory, $mapimage, Map $map, LatLon $latlon, $zoom)
	{
		if ($imagefactory == null)
		{
			return;
		}
		list($render_width, $render_height) = $imagefactory->getImageSize($mapimage);

		$izoom = intval($zoom);
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

		$tilemapimage = null;
		$tilemapimage = $imagefactory->newImage($mapimgwidth, $mapimgheight, 'transparent');

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
					if($imgblob != null)
					{
						$tileimage = $imagefactory->newImageFromBlob($imgblob);
						$imagefactory->drawImageIntoImage($tilemapimage, $tileimage, $offsetx, $offsety);
						$imagefactory->clearImage($tileimage);
					}
				}
				$offsetx += $map->getTileSize();
			}
			$offsety += $map->getTileSize();
		}

		$scale = pow(2, $zoom - $izoom);
		if($scale > 1.0)
		{
			$imagefactory->resizeImage($tilemapimage, intval($mapimgwidth*$scale), intval($mapimgheight*$scale));
		}

		$midp_offsetx = ($render_width*$scale - $render_width) / 2;
		$midp_offsety = ($render_height*$scale - $render_height) / 2;

		$crop_offsetx = intval(($topleft_pixel->x - $map->getTileSize() * $topleft_tile->x) * $scale + $midp_offsetx);
		$crop_offsety = intval(($topleft_pixel->y - $map->getTileSize() * $topleft_tile->y) * $scale + $midp_offsety);


		//$imagefactory->cropImage($tilemapimage, $render_width, $render_height, $crop_offsetx, $crop_offsety);
		//$imagefactory->drawImageIntoImage($mapimage, $tilemapimage, 0, 0);
		$imagefactory->drawImageIntoImage($mapimage, $tilemapimage, -$crop_offsetx, -$crop_offsety);
	}
}


class GeoJsonLayer extends Layer
{
	protected $geojson = null;
	protected $options = [];

	public function __construct($geojson, $options = [])
	{
		if(!is_array($options))
		{
			$options = [];
		}
		$this->options = $options;
		$this->geojson = $this->makeFeatureCollection($geojson);
	}

	private function makeFeatureCollection($geojson)
	{
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
					[ "type" => "Feature", "geometry" => $geojson, 
						//"properties" => ["name" => ""]  
					]
				]
			];
		}
		
		return $featurecoll;
	}

	public function render(ImageFactory $imagefactory, $mapimage, Map $map, LatLon $latlon, $zoom)
	{
		if($imagefactory == null)
		{
			return;
		}
		$options = $this->options;

		list($render_width, $render_height) = $imagefactory->getImageSize($mapimage);

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
			$drawing = $imagefactory->newDrawing($mapimage);
			$drawing->setTransformation($originMatrix);
			if(isset($options['style']))
			{
				$drawing->setStyle($options['style']);
			}
			$this->drawGeoJsonLayer($drawing, $this->geojson, $options, $map, $zoom);

			$imagefactory->drawDrawingIntoImage($mapimage, $drawing);
		}
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
		if ($drawing != null)
		{
			$radius = isset($options['pointradius']) ? $options['pointradius'] : 1;
			$drawing->drawCircle($pixel, $radius);
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
		if ($drawing != null)
		{
			$drawing->drawPolyline($polyline_pixel);
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
		if ($drawing != null)
		{
			$drawing->drawPolygon($poly_pixel);
		}
	}

}



class MarkerLayer extends Layer
{
	protected $markerLatlons = null;
	protected $options = [];

	public function __construct($markerLatlons, $options = [])
	{
		if(!is_array($options))
		{
			$options = [];
		}
		$this->options = $options;
		if(!is_array($markerLatlons))
		{
			if($markerLatlons instanceof LatLon)
			{
				$markerLatlons = [ $markerLatlons ];
			}
		}
		$this->markerLatlons = $markerLatlons;
	}

	public function render(ImageFactory $imagefactory, $mapimage, Map $map, LatLon $latlon, $zoom)
	{
		if($imagefactory == null)
		{
			return;
		}
		$options = $this->options;

		list($render_width, $render_height) = $imagefactory->getImageSize($mapimage);
		$cp_pixel = $map->latLonToMap($latlon, $zoom);
		$topleft_pixel = new Point($cp_pixel->x - $render_width/2, $cp_pixel->y - $render_height/2); 
		
		$originMatrix = Matrix::translation(-$topleft_pixel->x, -$topleft_pixel->y);


		/**
		 * @var Canvas $drawing
		 */
		$drawing = $imagefactory->newDrawing($mapimage);
		$drawing->setTransformation($originMatrix);

		$marker_icon = null;
		$marker_shadow = null;
		if(isset($options['markericon']))
		{
			$marker_icon = $imagefactory->newImageFromFile($options['markericon']);
			if(isset($options['shadowicon']))
			{
				$marker_shadow = $imagefactory->newImageFromFile($options['shadowicon']);
			}
		}

		if($marker_icon != null)
		{
			$markersize = $imagefactory->getImageSize($marker_icon);
			$custommarkersize = isset($options['markersize']) ? $options['markersize'] : $markersize;
			// No need to resize since drawImage draws the size specified
			/*if($markersize != $custommarkersize)
			{
				$imagefactory->resizeImage($marker_icon, $custommarkersize[0], $custommarkersize[1]);
			}*/	
			$customshadowsize = [0, 0];
			if($marker_shadow != null)
			{
				$shadowsize = $imagefactory->getImageSize($marker_shadow);
				$customshadowsize = isset($options['shadowsize']) ? $options['shadowsize'] : $shadowsize;
				// No need to resize since drawImage draws the size specified
				/*if($shadowsize != $customshadowsize)
				{
					$imagefactory->resizeImage($marker_shadow, $customshadowsize[0], $customshadowsize[1]);
				}*/	
			}

			foreach($this->markerLatlons as $markerLatLon)
			{
				$pos = $map->latLonToMap($markerLatLon, $zoom);
	
				if($marker_shadow != null)
				{
					$shadoworigin = isset($options['shadoworigin']) ? new Point($options['shadoworigin']) : new Point(0, 0);
					$x = intval($pos->x - $shadoworigin->x);
					$y = intval($pos->y - $shadoworigin->y);

					$drawing->drawImage(new Point($x, $y), $customshadowsize[0], $customshadowsize[1], $marker_shadow);
				}
				
				$markerorigin = isset($options['markerorigin']) ? new Point($options['markerorigin']) : new Point(0, 0);
				$x = intval($pos->x - $markerorigin->x);
				$y = intval($pos->y - $markerorigin->y);

				$drawing->drawImage(new Point($x, $y), $custommarkersize[0], $custommarkersize[1], $marker_icon);
			}
		}
		else
		{
			$markerwidth = 25;
			$markerheight = 41;
			$markersize = isset($options['markersize']) ? $options['markersize'] : [$markerwidth, $markerheight];
			if(is_numeric($markersize))
			{
				$markerwidth = $markersize;
				$markerheight = $markersize;
			}
			elseif(is_array($markersize) && count($markersize) > 0)
			{
				$markerwidth = $markersize[0];
				$markerheight = count($markersize) > 1 ? $markersize[1] : $markerwidth;
			}
			$style = [
				'strokecolor' => '#114488',
				'fillcolor' => '#3388ff',
				'strokewidth' => 1,
				'strokelinecap' => 'butt',
				'strokelinejoin' => 'miter',
				'strokemiterlimit' => 10,
			];
			if(isset($options['style']))
			{
				$style['strokecolor'] = $options['style']['strokecolor'];
				$style['fillcolor'] = $options['style']['fillcolor'];
				$style['strokewidth'] = $options['style']['strokewidth'];
				$style['strokelinecap'] = $options['style']['strokelinecap'];
				$style['strokelinejoin'] = $options['style']['strokelinejoin'];
				$style['strokemiterlimit'] = $options['style']['strokemiterlimit'];
			}
			$drawing->setStyle($style);

			$sz = $markerwidth;
			$r = $sz / 2.0;
			$tipy = $markerheight - $r; // Distance from tip to center of circle
			$innerRadius = isset($options['innerradius']) ? $options['innerradius'] : $r / 2.0;
			if($innerRadius > $r) 
			{
				$innerRadius = $r;
			}

			// Build the marker symbol polygon in a local coordinate system
			// with the tip in origin, x axis to the right and y axis up
			$polypoints = [ new Point(0, 0) ];
			for($a=215; $a>=-35; $a -= 5)
			{
				$ang = $a * M_PI / 180.0;
				$polypoints[] = new Point($r * cos($ang), $tipy + $r * sin($ang));
			}
			$polypoints[] = $polypoints[0];
			$hole = [];
			for($a=0; $a<=360; $a += 5)
			{
				$ang = $a * M_PI / 180.0;
				$hole[] = new Point($innerRadius * cos($ang), $tipy + $innerRadius * sin($ang));
			}
			$hole[] = $hole[0];


			foreach($this->markerLatlons as $markerLatLon)
			{
				$pos = $map->latLonToMap($markerLatLon, $zoom);
		
				// Save current state (transformation, fillcolor) so we can restore (by popping) for each of the markers
				$drawing->pushState();
				/* Note! Transformations read in reverse order: first reflect, then translate
				$drawing->setTransformation(Matrix::translation($pos->x, $pos->y));
				// The mapimage coordinates grow from top to bottom: (0,0) is in upper left, but our symbol
				// is specified in a right hand coordinates system with x to right and y up,
				// so we must reflect y when drawing the polygon
				$drawing->setTransformation(Matrix::reflection(1, -1));
				*/
				// Instead of two transformations, we can combine them to one due to
				// the translation coming after the reflection
				$drawing->setTransformation(new Matrix(1, 0, $pos->x, 0, -1, $pos->y));
				$drawing->drawPolygon([$polypoints, $hole]);
				$style['fillcolor'] = isset($options['innerfill']) ? $options['innerfill'] : '#ffffff';
				$drawing->setStyle($style);
				$drawing->drawCircle(new Point(0, $tipy), $innerRadius);
				$drawing->popState();
			}
		}

		$imagefactory->drawDrawingIntoImage($mapimage, $drawing);
	}
}

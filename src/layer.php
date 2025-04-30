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

/*
class TileLayer extends Layer
{
	protected $tileservice = null;
	public function __construct(TileService $tileservice)
	{
		$this->tileservice = $tileservice;
	}

	public function render(ImageFactory $imagefactory, $mapimage, Map $map, LatLon $latlon, $zoom)
	{

	}
}
*/

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
			$drawing->drawTransformation($originMatrix);
			if(isset($options['style']))
			{
				$drawing->drawStyle($options['style']);
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
			$radius = isset($options['style']['pointradius']) ? $options['style']['pointradius'] : 1;
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
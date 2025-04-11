<?php

namespace geop;

require_once(__DIR__."/geometry.php");


abstract class CRS
{
	public function getName()
	{
		return '';
	}

	// Project a lat lon position to a location in the CRS
	public function project(LatLon $latlon)
	{
		//throw new \Exception("not implemented");
		return new Point();
	}

	// Unproject a location in the CRS to a lat lon position
	public function unproject(Point $p)
	{
		//throw new \Exception("not implemented");
		return new LatLon();
	}

	public function scalefactor($latitude)
	{
		//throw new \Exception("not implemented");
		return 1;
	}

	// Get the transformation matrix that transforms from the CRS to
	// map pixels
	public function crsToMapTransformation($mapsize)
	{
		//throw new \Exception("not implemented");
		return Matrix::identity();
	}

	// Get the transformation matrix that transforms from map pixels to the CRS
	public function mapToCrsTransformation($mapsize)
	{
		//throw new \Exception("not implemented");
		return Matrix::identity();
	}
}

// We use the convention that the earth is a sphere with radius R = 6371009
// Why:
//
// https://en.wikipedia.org/wiki/Earth_ellipsoid
//
// Radii according wgs84
// $R_e = 6378137;          // Equatorial radius in meters
// $R_p = 6356752.3142;     // Polar radius
//
// https://en.wikipedia.org/wiki/Earth_radius#Global_radii
//
// The arithmetic mean radius
// $R = (2*$R_e + $R_p) / 3;        // 6371008.7714 m
//
class Earth extends CRS
{
	// Compute the great circle distance between two locations
	// using Haversine formula
	public function distance(LatLon $l1, LatLon $l2)
	{
		// Haversine formula
		// Adapted from https://www.movable-type.co.uk/scripts/latlong.html
		$R = 6371009; // metres
		$to_rad = M_PI / 180.0;

		$ph1 = $l1->lat * $to_rad; 
		$ph2 = $l2->lat * $to_rad;
		$dph = ($l2->lat - $l1->lat) * $to_rad;
		$dl = ($l2->lon - $l1->lon) * $to_rad;

		$a = sin($dph/2) * sin($dph/2) +
			cos($ph1) * cos($ph2) *
			sin($dl/2) * sin($dl/2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		$d = $R * $c; // in metres
		return $d;
	}

}


// Pseudo Mercator projection (spherical model)
class CRS_EPSG3857 extends Earth
{
	const R = 6378137; // Earth equatorial radius
	const MAX_LATITUDE = 85.051128779807; // atan(sinh(M_PI))*180/M_PI;

	public function getName()
	{
		return 'EPSG:3857';
	}

	public function project(LatLon $latlon)
	{
		// Spherical mercator projection
		// https://en.m.wikipedia.org/wiki/Mercator_projection

		$lat = max(min($latlon->lat, self::MAX_LATITUDE), -self::MAX_LATITUDE);
		$to_rad = M_PI / 180.0;
		// x,y in range (-R*pi, R*pi)
		$x = ($latlon->lon * $to_rad) * self::R;
		$y = log(tan(M_PI_4 + ($lat * $to_rad)/2)) * self::R;
		return new Point($x, $y);
	}

	public function unproject(Point $p)
	{
		$to_deg = 180.0 / M_PI;
		$lon = ($p->x / self::R) * $to_deg;
		$lat = (2 * atan(exp($p->y / self::R)) - M_PI_2) * $to_deg;
		return new LatLon($lat, $lon);
	}


	// Given latitude in degrees, this computes the scaling factor at that latitude when projected to
	// the pseudo mercator projection 3857 (spherical model)
	//
	// When a geometry is transformed from 4326 to 3857 it will be projected and scaled up the further away from the equator it is.
	// At latitude 60 degrees it is 2x the actual size.
	//
	// Assumes input latitude is in the range (-90,90), poles not included as they are singularities, scaling would be infinite.
	public function scalefactor($latitude)
	{
		$latitude = floatval($latitude);
		if($latitude <= -90 || $latitude >= 90)
		{
			throw new \Exception("Latitude must be in the range (-90,90)");
		}
		// The secant (sec = 1/cos) of latitude is the scaling factor
		return 1.0 / cos($latitude * M_PI / 180.0);
	}

	// Transformation matrix to transform from crs to map pixels [0,$mapsize)
	public function crsToMapTransformation($mapsize)
	{
		$scale = $mapsize * 0.5 / (M_PI * self::R);
		/*return Matrix::mul(
			Matrix::translation(0.5*$mapsize, 0.5*$mapsize),
			Matrix::scale($scale, -$scale)); */
		// Since translate comes after scale we can use the constructor
		// to save on the matrix mul
		return new Matrix($scale, 0, 0.5*$mapsize,
							0, -$scale, 0.5*$mapsize);
	}

	public function mapToCrsTransformation($mapsize)
	{
		// This is the inverse of crsToMapTransformation
		$scale = (M_PI * self::R) * 2 / $mapsize;
		return Matrix::mul(
			Matrix::scale($scale, -$scale),
			Matrix::translation(-0.5*$mapsize, -0.5*$mapsize));
	}
}



// World Mercator projection (spheroid model (ellipsoid of revolution))
class CRS_EPSG3395 extends Earth
{
	const R = 6378137; // Earth equatorial radius
	const R_MINOR = 6356752.3142; // Polar radius
	const MAX_LATITUDE = 89.5; 

	public function getName()
	{
		return 'EPSG:3395';
	}

	public function project(LatLon $latlon)
	{
		// https://wiki.openstreetmap.org/wiki/Mercator#Elliptical_(true)_Mercator_Projection
		// https://en.wikipedia.org/wiki/Mercator_projection#Generalization_to_the_ellipsoid

		$lat = max(min($latlon->lat, self::MAX_LATITUDE), -self::MAX_LATITUDE);
		$to_rad = M_PI / 180.0;
		$x = ($latlon->lon * $to_rad) * self::R;

		$ratio = self::R_MINOR / self::R;
		$e = sqrt(1 - $ratio * $ratio);
		$phi = $lat * $to_rad;
		$con = $e * sin($phi);
		$con = pow((1.0 - $con) / (1.0 + $con), $e / 2);
		$ts = tan(0.5 * ((M_PI_2) - $phi)) / $con;
		$y = -self::R * log(max($ts, 1E-10));
		return new Point($x, $y);
	}

	public function unproject(Point $p)
	{
		$to_deg = 180.0 / M_PI;
		$lon = ($p->x / self::R) * $to_deg;

		$ratio = self::R_MINOR / self::R;
		$e = sqrt(1 - $ratio * $ratio);
		$ts = exp(-$p->y / self::R);
		$phi = M_PI_2 - 2 * atan($ts);
		$dphi = 1.0;
		$i = 0;
		while((abs($dphi) > 1E-8) && ($i < 15))
		{
			$con = $e * sin($phi);
			$con = pow((1.0 - $con) / (1.0 + $con), $e / 2);
			$dphi = M_PI_2 - 2 * atan($ts * $con) - $phi;
			$phi += $dphi;
			$i++;
		}
		$lat = $phi * $to_deg;
		return new LatLon($lat, $lon);
	}


	// Given latitude in degrees, this computes the scaling factor at that latitude when projected 
	//
	// When a geometry is transformed from 4326 to 3395 it will be projected and scaled up the further away from the equator it is.
	//
	// Assumes input latitude is in the range (-90,90), poles not included as they are singularities, scaling would be infinite.
	public function scalefactor($latitude)
	{
		$latitude = floatval($latitude);
		if($latitude <= -90 || $latitude >= 90)
		{
			throw new \Exception("Latitude must be in the range (-90,90)");
		}
		// https://en.wikipedia.org/wiki/Mercator_projection#Generalization_to_the_ellipsoid
		$to_rad = M_PI / 180.0;
		$ratio = self::R_MINOR / self::R;
		$e = sqrt(1 - $ratio * $ratio);
		$phi = $latitude * $to_rad;
		$con = $e * sin($phi);

		return (1.0 / cos($phi)) * sqrt(1 - $con*$con);
	}

	// Transformation matrix to transform from crs to map pixels [0,$mapsize)
	public function crsToMapTransformation($mapsize)
	{
		$scale = $mapsize * 0.5 / (M_PI * self::R);
		return new Matrix($scale, 0, 0.5*$mapsize,
							0, -$scale, 0.5*$mapsize);
	}

	public function mapToCrsTransformation($mapsize)
	{
		// This is the inverse of crsToMapTransformation
		$scale = (M_PI * self::R) * 2 / $mapsize;
		return Matrix::mul(
			Matrix::scale($scale, -$scale),
			Matrix::translation(-0.5*$mapsize, -0.5*$mapsize));
	}
}




class CRS_EPSG4326 extends Earth
{
	public function getName()
	{
		return 'EPSG:4326';
	}

	public function project(LatLon $latlon)
	{
		return new Point($latlon->lon, $latlon->lat);
	}

	public function unproject(Point $p)
	{
		return new LatLon($p->y, $p->x);
	}


	// Transformation matrix to transform from crs to map pixels [0,$mapsize)
	public function crsToMapTransformation($mapsize)
	{
		$scale = $mapsize * 0.5 / 180.0;
		return new Matrix($scale, 0, 0.5*$mapsize,
							0, -$scale, 0.5*$mapsize);
	}

	public function mapToCrsTransformation($mapsize)
	{
		// This is the inverse of crsToMapTransformation
		$scale = 180 * 2 / $mapsize;
		return Matrix::mul(
			Matrix::scale($scale, -$scale),
			Matrix::translation(-0.5*$mapsize, -0.5*$mapsize));
	}
}



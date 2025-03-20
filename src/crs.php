<?php

namespace geop;

require_once(__DIR__."/geometry.php");

interface CRS 
{
    public function project(LatLon $latlon);
    public function unproject(Point $p);

    public function scalefactor($latitude);
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
class Earth 
{

    // Compute the great circle distance between two locations
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

class CRS_EPSG3857 extends Earth implements CRS
{
    const R = 6378137; // Earth equatorial radius
    const MAX_LATITUDE = 85.051128779807; // atan(sinh(M_PI))*180/M_PI;

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


}

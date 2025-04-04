<?php

namespace geop;

class Point
{
    public $x = 0;
    public $y = 0;

    public function __construct(...$p)
    {
        if(count($p) > 0)
        {
            if(is_array($p[0]))
            {
                $this->x = floatval($p[0][0]);
                $this->y = floatval($p[0][1]);
            } 
            else
            {
                $this->x = floatval($p[0]);
                $this->y = floatval($p[1]);
            }
        }
    }

    public function distance(Point $p)
    {
        $dx = $this->x - $p->x;
        $dy = $this->y - $p->y;
        return sqrt($dx*$dx + $dy*$dy);
    }
}


class LatLon
{
    public $lat;
    public $lon;

    public function __construct(...$p)
    {
        if(count($p) > 0)
        {
            if(is_array($p[0]))
            {
                $this->lat = self::clamplatitude(floatval($p[0][0]));
                //$this->lon = self::wraplongitude(floatval($p[0][1]));
                $this->lon = floatval($p[0][1]);
            } 
            else
            {
                $this->lat = self::clamplatitude(floatval($p[0]));
                //$this->lon = self::wraplongitude(floatval($p[1]));
                $this->lon = floatval($p[1]);
            }
        }
    }

    public static function clamplatitude($lat)
    {
        /*if($lat < -90 || $lat > 90)
        {
            throw new \Exception("Latitude must be in the range [-90,90]");
        }*/
        if($lat < -90) return -90;
        if($lat > 90) return 90;
        return $lat;
    }

    // Wrap a longitude value so it is in the range [-180,180]
    // Assumes input longitude is in the range [-360,360]
    public static function wraplongitude($lng)
    {
        if($lng < -360 || $lng > 360)
        {
            throw new \Exception("Longitude must be in the range [-360,360]");
        }
        if($lng < -180) return $lng + 360;
        if($lng > 180) return $lng - 360;
        return $lng;
    }
}


// Affine transformation matrix
class Matrix
{
    // a b c
    // d e f
    // 0 0 1
    public $a = 1, $b = 0, $c = 0, 
            $d = 0, $e = 1, $f = 0;

    public function __construct(...$m)
    {
        if(count($m) > 0)
        {
            if(is_array($m[0]))
            {
                $this->a = floatval($m[0][0]);
                $this->b = floatval($m[0][1]);
                $this->c = floatval($m[0][2]);
                $this->d = floatval($m[0][3]);
                $this->e = floatval($m[0][4]);
                $this->f = floatval($m[0][5]);
            } 
            else
            {
                $this->a = floatval($m[0]);
                $this->b = floatval($m[1]);
                $this->c = floatval($m[2]);
                $this->d = floatval($m[3]);
                $this->e = floatval($m[4]);
                $this->f = floatval($m[5]);
            }
        }
    }


    public function copy()
    {
        return new Matrix($this->a, $this->b, $this->c, $this->d, $this->e, $this->f);
    }

    public function transform(Point $p)
    {
        $x = $this->a * $p->x + $this->b * $p->y + $this->c;
        $y = $this->d * $p->x + $this->e * $p->y + $this->f;
        return new Point($x, $y);
    }

    public static function mul(Matrix $m1, Matrix $m2)
    {
        $a = $m1->a*$m2->a + $m1->b*$m2->d;
        $b = $m1->a*$m2->b + $m1->b*$m2->e;
        $c = $m1->a*$m2->c + $m1->b*$m2->f + $m1->c;
        $d = $m1->d*$m2->a + $m1->e*$m2->d;
        $e = $m1->d*$m2->b + $m1->e*$m2->e;
        $f = $m1->d*$m2->c + $m1->e*$m2->f + $m1->f;
        return new Matrix($a, $b, $c, $d, $e, $f);
    }


    public static function identity()
    {
        return new Matrix();
    }
    
    public static function translation($tx=0, $ty=0)
    {
        return new Matrix(1, 0, $tx, 0, 1, $ty);
    }
    
    public static function scale($sx=1, $sy=1)
    {
        return new Matrix($sx, 0, 0, 0, $sy, 0);
    }
    
    public static function reflection($rx=1, $ry=1)
    {
        return new Matrix($rx, 0, 0, 0, $ry, 0);
    }
    
    public static function rotate($theta)
    {
        $c = cos($theta);
        $s = sin($theta);
        return new Matrix($c, -$s, 0, $s, $c, 0);
    }
    
}

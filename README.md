# Geop 

Working with maps in the spherical mercator model (crs EPSG:3857) in php.

* Project/unproject lat/lon points
* Render maps with tile providers such as OpenStreetMap


## Example: Render a map image

Below is an example showing how to render a map image of size 640 x 480 pixels centered
at latitude 41.381073 and longitude 2.173224 and zoom level 5.

```php
<?php
require_once("geop.php");
use \geop\LatLon;
use \geop\Map;
use \geop\CRS_EPSG3857;
use \geop\TileService;
use \geop\FileTileCache;
use \geop\MapRenderer;
use \geop\ImagickFactory;

$latlon = new LatLon(41.381073, 2.173224);
$zoom = 5;

$tileservice = new TileService("https://tile.openstreetmap.org/{z}/{x}/{y}.png", 
                            new FileTileCache('osm'));

$map = new Map(new CRS_EPSG3857());
// OSM has tile size of 256 pixels
$map->setTileSize(256);
$imgfactory = new ImagickFactory();

$renderer = new MapRenderer($map, $tileservice, $imgfactory);
$output = $renderer->renderMap($latlon, $zoom, 640, 480);
$imgfactory->saveImageToFile($output['image'], "assets/map1.webp");
```

![Map](assets/map1.webp)



## Demo app makemap.php

The file **[example/makemap.php](example/makemap.php)** contains a simple demo app that renders a map with specified size at a given lat/lon location and zoom level. It uses OpenStreetMap tiles. A marker is rendered to show the position. The marker image is from the Leaflet library.


## Requirements

Developed in php and tested in 5.6, 8.2 and 8.4. Imagick extension is needed to use the
**ImagickFactory**. See the interface **[ImageFactory](src/imagefactory.php)** for what to implement for a custom
image factory.

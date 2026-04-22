<?php

# Colors from https://www.colorhexa.com/color-names
$file = fopen(__DIR__.'/colorhexa.csv', 'r');
while (($line = fgets($file)) !== false) {
    $data = str_getcsv($line, ";", '"', '\\');
    $name = strtolower(trim(str_replace(' ', '', $data[0])));
    $r = $data[2];
    $g = $data[3];
    $b = $data[4];
    echo "\"$name\" => [$r,$g,$b],\n";
}
fclose($file);



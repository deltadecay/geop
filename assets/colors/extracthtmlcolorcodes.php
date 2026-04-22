<?php

# Colors from https://htmlcolorcodes.com/color-names/
$file = fopen(__DIR__.'/htmlcolorcodes.csv', 'r');
while (($line = fgets($file)) !== false) {
    $data = str_getcsv($line, ";", '"', '\\');
    $name = strtolower(trim(str_replace(' ', '', $data[0])));
    list($r, $g, $b) = explode(",", $data[2]);
    echo "\"$name\" => [$r,$g,$b],\n";
}
fclose($file);



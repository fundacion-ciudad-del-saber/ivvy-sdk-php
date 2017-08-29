<?php
require dirname(__DIR__) . '/vendor/autoload.php';

$apiKey = $argv[1];
$apiSecret = $argv[2];

$ivvy = (new Fcds\Ivvy\IvvyFactory)->newInstance($apiKey, $apiSecret);

if ($ivvy->ping()) {
    echo "Connection successful\n";
} else {
    echo "Couldn't connect to the API server. Check iVvy's credentials\n";
}

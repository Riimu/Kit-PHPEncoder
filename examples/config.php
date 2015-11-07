<?php

require __DIR__ . '/../src/autoload.php';

// Configuration script asks user for database details
$config = [
    'database' => 'mydb',
    'hostname' => 'localhost',
    'username' => 'dbuser',
    'password' => 'dbpass'
];

// Store the configuration into a file
$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
file_put_contents('dbconf.php', '<?php return ' . $encoder->encode($config) . ';');

echo "Contents of the config file:" . PHP_EOL;
readfile('dbconf.php');
echo PHP_EOL . PHP_EOL;

// Load the config from the file
$dbconf = require 'dbconf.php';

echo "Array read from the config file:" . PHP_EOL;
print_r($dbconf);

unlink('dbconf.php');

<?php

set_include_path(__DIR__ . '/../src');
spl_autoload_register();

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

// Load the config from the file
$dbconf = require 'dbconf.php';

var_dump($dbconf);

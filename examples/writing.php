<?php

/* This simple example demonstrates how to encode a simple array using default
   settings and while using minimal whitespace */

require_once __DIR__ . '/../vendor/autoload.php';

$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.0]]);

echo PHP_EOL;

echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.0]], [
    'whitespace' => false,
]);

<?php

require __DIR__ . '/../src/autoload.php';

$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.0]]);

echo PHP_EOL;

echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.0]], [
    'whitespace' => false,
]);

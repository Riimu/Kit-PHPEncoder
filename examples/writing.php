<?php

require '../src/PHPEncoder.php';

$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.1]]);

echo PHP_EOL;

$encoder->setIndent(false);
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.1]]);

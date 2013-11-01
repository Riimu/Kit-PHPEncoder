<?php

set_include_path(__DIR__ . '/../src');
spl_autoload_register();

$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.1]]);

echo PHP_EOL;

$encoder->setIndent(false);
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.1]]);

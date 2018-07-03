<?php

/* Shows an example of how you can take advantage of class resolution in the
   generated code. */

require_once __DIR__ . '/../vendor/autoload.php';

$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder([
    'string.classes' => [
        'Riimu\\',
        'PHPUnit\\Framework\\TestCase',
        'DateTime',
    ],
    'string.imports' => [
        'Riimu\\Kit\\PHPEncoder\\' => '',
        'PHPUnit\\Framework\\TestCase' => 'TestCase',
    ],
]);


echo "<?php

namespace Riimu\Kit\PHPEncoder;

use PHPUnit\Framework\TestCase;

var_dump(";

echo $encoder->encode([
    \PHPUnit\Framework\TestCase::class,
    \Riimu\Kit\PHPEncoder\PHPEncoder::class,
    \Riimu\Kit\PHPEncoder\Encoder\Encoder::class,
    \DateTime::class,
    \DateTimeInterface::class, // Will be encoded as plain string, since it's not allowed by string.classes
]);

echo ");\n";

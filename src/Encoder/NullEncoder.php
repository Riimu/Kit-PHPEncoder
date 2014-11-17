<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

use Riimu\Kit\PHPEncoder\RecursiveEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NullEncoder implements Encoder
{
    private static $defaultOptions = [
        'null.capitalize' => false,
    ];

    public function getDefaultOptions()
    {
        return self::$defaultOptions;
    }

    public function supports($value)
    {
        return $value === null;
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        return $options['null.capitalize'] ? 'NULL' : 'null';
    }
}

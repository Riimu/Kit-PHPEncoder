<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for null values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2020 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class NullEncoder implements Encoder
{
    /** @var array Default values for options in the encoder */
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

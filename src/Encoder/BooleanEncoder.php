<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for boolean values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2020 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BooleanEncoder implements Encoder
{
    /** @var array Default values for options in the encoder */
    private static $defaultOptions = [
        'boolean.capitalize' => false,
    ];

    public function getDefaultOptions()
    {
        return self::$defaultOptions;
    }

    public function supports($value)
    {
        return \is_bool($value);
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        if ($options['boolean.capitalize']) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return $value ? 'true' : 'false';
    }
}

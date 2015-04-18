<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for integer values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class IntegerEncoder implements Encoder
{
    public function getDefaultOptions()
    {
        return [];
    }

    public function supports($value)
    {
        return is_integer($value);
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        $string = (string) $value;

        if ($value === 1 << (PHP_INT_SIZE * 8 - 1)) {
            $string = sprintf('(int)%s%s', $options['whitespace'] ? ' ' : '', $string);
        }

        return $string;
    }
}

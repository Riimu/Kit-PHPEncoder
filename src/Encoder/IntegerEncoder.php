<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for integer values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class IntegerEncoder implements Encoder
{
    /** @var array Default values for options in the encoder */
    private static $defaultOptions = [
        'integer.type' => 'decimal',
    ];

    public function getDefaultOptions()
    {
        return self::$defaultOptions;
    }

    public function supports($value)
    {
        return is_int($value);
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        $encoders = [
            'binary' => function ($value) {
                return $this->encodeBinary($value);
            },
            'octal' => function ($value) {
                return $this->encodeOctal($value);
            },
            'decimal' => function ($value, $options) {
                return $this->encodeDecimal($value, $options);
            },
            'hexadecimal' => function ($value) {
                return $this->encodeHexadecimal($value);
            },
        ];

        if (!isset($encoders[$options['integer.type']])) {
            throw new \InvalidArgumentException('Invalid integer encoding type');
        }

        $callback = $encoders[$options['integer.type']];

        return $callback((int) $value, $options);
    }

    public function encodeBinary($integer)
    {
        return sprintf('%s0b%s', $this->sign($integer), decbin(abs($integer)));
    }

    public function encodeOctal($integer)
    {
        return sprintf('%s0%s', $this->sign($integer), decoct(abs($integer)));
    }

    public function encodeDecimal($integer, $options)
    {
        if ($integer === 1 << (PHP_INT_SIZE * 8 - 1)) {
            return sprintf('(int)%s%s', $options['whitespace'] ? ' ' : '', $integer);
        }

        return var_export($integer, true);
    }

    public function encodeHexadecimal($integer)
    {
        return sprintf('%s0x%s', $this->sign($integer), dechex(abs($integer)));
    }

    private function sign($integer)
    {
        if ($integer < 0) {
            return '-';
        }

        return '';
    }
}

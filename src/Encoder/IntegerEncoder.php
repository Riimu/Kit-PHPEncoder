<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for integer values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2020 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class IntegerEncoder implements Encoder
{
    /** @var array Default values for options in the encoder */
    private static $defaultOptions = [
        'integer.type' => 'decimal',
    ];

    /** @var \Closure[] Encoders for different types of integers */
    private $encoders;

    /**
     * IntegerEncoder constructor.
     */
    public function __construct()
    {
        $this->encoders = [
            'binary' => function ($value) {
                return $this->encodeBinary($value);
            },
            'octal' => function ($value) {
                return $this->encodeOctal($value);
            },
            'decimal' => function ($value, $options) {
                return $this->encodeDecimal($value, $options);
            },
            'hexadecimal' => function ($value, $options) {
                return $this->encodeHexadecimal($value, $options);
            },
        ];
    }

    public function getDefaultOptions()
    {
        return self::$defaultOptions;
    }

    public function supports($value)
    {
        return \is_int($value);
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        if (!isset($this->encoders[$options['integer.type']])) {
            throw new \InvalidArgumentException('Invalid integer encoding type');
        }

        $callback = $this->encoders[$options['integer.type']];

        return $callback((int) $value, $options);
    }

    /**
     * Encodes an integer into binary representation.
     * @param int $integer The integer to encode
     * @return string The PHP code representation for the integer
     */
    public function encodeBinary($integer)
    {
        return sprintf('%s0b%b', $this->sign($integer), abs($integer));
    }

    /**
     * Encodes an integer into octal representation.
     * @param int $integer The integer to encode
     * @return string The PHP code representation for the integer
     */
    public function encodeOctal($integer)
    {
        return sprintf('%s0%o', $this->sign($integer), abs($integer));
    }

    /**
     * Encodes an integer into decimal representation.
     * @param int $integer The integer to encode
     * @param array $options The integer encoding options
     * @return string The PHP code representation for the integer
     */
    public function encodeDecimal($integer, $options)
    {
        if ($integer === 1 << (\PHP_INT_SIZE * 8 - 1)) {
            return sprintf('(int)%s%d', $options['whitespace'] ? ' ' : '', $integer);
        }

        return var_export($integer, true);
    }

    /**
     * Encodes an integer into hexadecimal representation.
     * @param int $integer The integer to encode
     * @param array $options The integer encoding options
     * @return string The PHP code representation for the integer
     */
    public function encodeHexadecimal($integer, $options)
    {
        if ($options['hex.capitalize']) {
            return sprintf('%s0x%X', $this->sign($integer), abs($integer));
        }

        return sprintf('%s0x%x', $this->sign($integer), abs($integer));
    }

    /**
     * Returns the negative sign for negative numbers.
     * @param int $integer The number to test for negativity
     * @return string The minus sign for negative numbers and empty string for positive numbers
     */
    private function sign($integer)
    {
        if ($integer < 0) {
            return '-';
        }

        return '';
    }
}

<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for float values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FloatEncoder implements Encoder
{
    /** @var array Default values for options in the encoder */
    private static $defaultOptions = [
        'float.integers' => false,
        'float.precision' => 17,
    ];

    public function getDefaultOptions()
    {
        return self::$defaultOptions;
    }

    public function supports($value)
    {
        return is_float($value);
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        if (is_nan($value)) {
            return 'NAN';
        } elseif (is_infinite($value)) {
            return $value < 0 ? '-INF' : 'INF';
        } elseif ($options['float.integers'] && round($value) === $value) {
            return number_format($value, 0, '.', '');
        }

        return $this->enforceType($this->getFloatString($value, $options['float.precision']));
    }

    /**
     * Converts the float value into string representation.
     * @param float $float Value to convert
     * @param integer|false $precision Number of decimals in the number
     * @return string The given float value as a string
     */
    public function getFloatString($float, $precision)
    {
        if ($precision === false) {
            return (string) $float;
        }

        $current = ini_get('precision');
        ini_set('precision', (int) $precision);
        $output = (string) $float;
        ini_set('precision', $current);

        return $output;
    }

    /**
     * Ensures that the float representation will be parsed as float value.
     * @param string $string Float string representation
     * @return string Float value as string with enforced type
     */
    public function enforceType($string)
    {
        return $string . (preg_match('/^[-+]?\d+$/', $string) ? '.0' : '');
    }
}

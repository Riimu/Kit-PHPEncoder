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
    /** The maximum value that can be accurately represented by a float */
    const FLOAT_MAX = 9007199254740992.0;

    /** @var array Default values for options in the encoder */
    private static $defaultOptions = [
        'float.integers'  => false,
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
        }

        return $this->encodeNumber($value, $options);
    }

    /**
     * Encodes the number as a PHP number representation.
     * @param float $float The number to encode
     * @param array $options The float encoding options
     * @return string The PHP code representation for the number
     */
    private function encodeNumber($float, array $options)
    {
        if ($this->isInteger($float, $options['float.integers'])) {
            return number_format($float, 0, '.', '');
        } elseif ($float === 0.0) {
            return '0.0';
        }

        $precision = $options['float.precision'];

        if ($precision === false) {
            $precision = ini_get('serialize_precision');
        }

        return $this->encodeFloat($float, $precision);
    }

    /**
     * Tells if the number can be encoded as an integer.
     * @param float $float The number to test
     * @param bool|string $allowIntegers Whether integers should be allowed
     * @return bool True if the number can be encoded as an integer, false if not
     */
    private function isInteger($float, $allowIntegers)
    {
        if (!$allowIntegers || round($float) !== $float) {
            return false;
        } elseif (abs($float) < self::FLOAT_MAX) {
            return true;
        }

        return $allowIntegers === 'all';
    }

    /**
     * Encodes the number using a floating point representation.
     * @param float $float The number to encode
     * @param int $precision The maximum precision of encoded floats
     * @return string The PHP code representation for the number
     */
    private function encodeFloat($float, $precision)
    {
        $precision = max(1, (int) $precision);
        $log = (int) floor(log(abs($float), 10));

        if (abs($float) < self::FLOAT_MAX && $log > -5 && abs($log) < $precision) {
            return $this->formatFloat($float, $precision - $log - 1);
        }

        // Deal with overflow that results from rounding
        $log += (int) (round(abs($float) / pow(10, $log), $precision - 1) / 10);
        $string = $this->formatFloat($float / pow(10, $log), $precision - 1);

        return sprintf('%sE%+d', $string, $log);
    }

    /**
     * Formats the number as a decimal number.
     * @param float $float The number to format
     * @param int $digits The maximum number of decimal digits
     * @return string The number formatted as a decimal number
     */
    private function formatFloat($float, $digits)
    {
        $digits = max((int) $digits, 1);
        $string = rtrim(number_format($float, $digits, '.', ''), '0');

        return substr($string, -1) === '.' ? $string . '0' : $string;
    }
}

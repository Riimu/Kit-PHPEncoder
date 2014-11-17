<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for array values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ArrayEncoder implements Encoder
{
    /** @var array Default values for options in the encoder */
    private static $defaultOptions = [
        'array.short' => true,
        'array.base' => 0,
        'array.indent' => 4,
        'array.align' => false,
        'array.inline' => 70,
        'array.omit' => true,
        'array.eol' => false,
    ];

    public function getDefaultOptions()
    {
        return self::$defaultOptions;
    }

    public function supports($value)
    {
        return is_array($value);
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        if ($value === []) {
            return $this->wrap('', $options['array.short']);
        } elseif (!$options['whitespace']) {
            return $this->wrap(
                implode(',', $this->getPairs($value, '', $options['array.omit'], $encode)),
                $options['array.short']
            );
        }

        if (is_string($lines = $this->getLines($value, $options, $encode))) {
            return $lines;
        }

        $indent = $this->buildIndent($options['array.base'], $options['array.indent'], $depth + 1);
        $last = $this->buildIndent($options['array.base'], $options['array.indent'], $depth);
        $eol = $options['array.eol'] === false ? PHP_EOL : (string) $options['array.eol'];

        return $this->wrap(
            $eol . $indent . implode(',' . $eol .$indent, $lines) . ',' . $eol .$last,
            $options['array.short']
        );
    }

    /**
     * Wraps the array code using short or long array notation.
     * @param string $string Array string representation to wrap
     * @param boolean $short True to use short notation, false to use long notation
     * @return string The array wrapped appropriately
     */
    private function wrap($string, $short)
    {
        return sprintf($short ? '[%s]' : 'array(%s)', $string);
    }

    /**
     * Builds the indentation based on the options.
     * @param string|integer $base The base indentation
     * @param string|integer $indent A single indentation level
     * @param integer $depth The level of indentation
     * @return string The indentation for the current depth
     */
    private function buildIndent($base, $indent, $depth)
    {
        $base = is_int($base) ? str_repeat(' ', $base) : (string) $base;

        return $depth === 0 ? $base : $base . str_repeat(
            is_int($indent) ? str_repeat(' ', $indent) : (string) $indent,
            $depth
        );
    }

    /**
     * Returns the code representation for the array values and keys.
     * @param array $array Array to convert into code
     * @param array $options List of encoder options
     * @param callable $encode Callback used to encode values
     * @return string|string[] Array encoded as strings or simple array as a string
     */
    private function getLines(array $array, array $options, callable $encode)
    {
        if ($options['array.align']) {
            return $this->getAlignedPairs($array, $encode);
        }

        $lines = $this->getPairs($array, ' ', $options['array.omit'], $encode, $inline);

        if ($inline && $options['array.inline'] !== false) {
            $output = $this->wrap(implode(', ', $lines), $options['array.short']);

            if (!preg_match('/[\r\n\t]/', $output) &&
                ($options['array.inline'] === true || strlen($output) <= (int) $options['array.inline'])
            ) {
                return $output;
            }
        }

        return $lines;
    }

    /**
     * Returns the array as aligned key and value pairs.
     * @param array $array Array to convert into code
     * @param callable $encode Callback used to encode values
     * @return string[] List of array key and value pairs as strings
     */
    private function getAlignedPairs($array, callable $encode)
    {
        $keys = [];
        $values = [];

        foreach ($array as $key => $value) {
            $keys[] = $encode($key, 1);
            $values[] = $encode($value, 1);
        }

        $format = sprintf('%%-%ds => %%s', max(array_map('strlen', $keys)));
        $pairs = [];

        for ($i = 0, $count = count($keys); $i < $count; $i++) {
            $pairs[] = sprintf($format, $keys[$i], $values[$i]);
        }

        return $pairs;
    }

    /**
     * Encodes the array as code pairs according to given parameters
     * @param array $array Array to convert into code
     * @param string $space Whitespace between array assignment operator
     * @param boolean $omit True to omit unnecessary keys, false to not
     * @param callable $encode Callback used to encode values
     * @param boolean $inline Set to true, if all the keys were omitted, false otherwise     *
     * @return string[] List of array key and value pairs as strings
     */
    private function getPairs($array, $space, $omit, callable $encode, & $inline = true)
    {
        $pairs = [];
        $nextIndex = 0;
        $inline = true;
        $format = '%s' . $space . '=>' . $space . '%s';

        foreach ($array as $key => $value) {
            if ($key === $nextIndex && $omit) {
                $pairs[] = $encode($value, 1);
            } else {
                $pairs[] = sprintf($format, $encode($key, 1), $encode($value, 1));
                $inline = false;
            }

            if (is_int($key) && $key >= $nextIndex) {
                $nextIndex = $key + 1;
            }
        }

        return $pairs;
    }
}

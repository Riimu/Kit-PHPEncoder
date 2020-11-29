<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for array values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2020 Riikka Kalliomäki
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
        return \is_array($value);
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
        } elseif ($options['array.align']) {
            return $this->getAlignedArray($value, $depth, $options, $encode);
        }

        return $this->getFormattedArray($value, $depth, $options, $encode);
    }

    /**
     * Returns the PHP code for aligned array accounting for omitted keys and inline arrays.
     * @param array $array Array to encode
     * @param int $depth Current indentation depth of the output
     * @param array $options List of encoder options
     * @param callable $encode Callback used to encode values
     * @return string The PHP code representation for the array
     */
    private function getAlignedArray(array $array, $depth, array $options, callable $encode)
    {
        $next = 0;
        $omit = $options['array.omit'];

        foreach (array_keys($array) as $key) {
            if ($key !== $next++) {
                $omit = false;
                break;
            }
        }

        return $omit
            ? $this->getFormattedArray($array, $depth, $options, $encode)
            : $this->buildArray($this->getAlignedPairs($array, $encode), $depth, $options);
    }

    /**
     * Returns the PHP code for the array as inline or multi line array.
     * @param array $array Array to encode
     * @param int $depth Current indentation depth of the output
     * @param array $options List of encoder options
     * @param callable $encode Callback used to encode values
     * @return string The PHP code representation for the array
     */
    private function getFormattedArray(array $array, $depth, array $options, callable $encode)
    {
        $lines = $this->getPairs($array, ' ', $options['array.omit'], $encode, $omitted);

        if ($omitted && $options['array.inline'] !== false) {
            $output = $this->getInlineArray($lines, $options);

            if ($output !== false) {
                return $output;
            }
        }

        return $this->buildArray($lines, $depth, $options);
    }

    /**
     * Returns the code for the inline array, if possible.
     * @param string[] $lines Encoded key and value pairs
     * @param array $options List of encoder options
     * @return string|false Array encoded as single line of PHP code or false if not possible
     */
    private function getInlineArray(array $lines, array $options)
    {
        $output = $this->wrap(implode(', ', $lines), $options['array.short']);

        if (preg_match('/[\r\n\t]/', $output)) {
            return false;
        } elseif ($options['array.inline'] === true || \strlen($output) <= (int) $options['array.inline']) {
            return $output;
        }

        return false;
    }

    /**
     * Builds the complete array from the encoded key and value pairs.
     * @param string[] $lines Encoded key and value pairs
     * @param int $depth Current indentation depth of the output
     * @param array $options List of encoder options
     * @return string Array encoded as PHP code
     */
    private function buildArray(array $lines, $depth, array $options)
    {
        $indent = $this->buildIndent($options['array.base'], $options['array.indent'], $depth + 1);
        $last = $this->buildIndent($options['array.base'], $options['array.indent'], $depth);
        $eol = $options['array.eol'] === false ? \PHP_EOL : (string) $options['array.eol'];

        return $this->wrap(
            sprintf('%s%s%s,%1$s%s', $eol, $indent, implode(',' . $eol . $indent, $lines), $last),
            $options['array.short']
        );
    }

    /**
     * Wraps the array code using short or long array notation.
     * @param string $string Array string representation to wrap
     * @param bool $short True to use short notation, false to use long notation
     * @return string The array wrapped appropriately
     */
    private function wrap($string, $short)
    {
        return sprintf($short ? '[%s]' : 'array(%s)', $string);
    }

    /**
     * Builds the indentation based on the options.
     * @param string|int $base The base indentation
     * @param string|int $indent A single indentation level
     * @param int $depth The level of indentation
     * @return string The indentation for the current depth
     */
    private function buildIndent($base, $indent, $depth)
    {
        $base = \is_int($base) ? str_repeat(' ', $base) : (string) $base;

        return $depth === 0 ? $base : $base . str_repeat(
            \is_int($indent) ? str_repeat(' ', $indent) : (string) $indent,
            $depth
        );
    }

    /**
     * Returns each encoded key and value pair with aligned assignment operators.
     * @param array $array Array to convert into code
     * @param callable $encode Callback used to encode values
     * @return string[] Each of key and value pair encoded as php
     */
    private function getAlignedPairs(array $array, callable $encode)
    {
        $keys = [];
        $values = [];

        foreach ($array as $key => $value) {
            $keys[] = $encode($key, 1);
            $values[] = $encode($value, 1);
        }

        $format = sprintf('%%-%ds => %%s', max(array_map('strlen', $keys)));
        $pairs = [];

        for ($i = 0, $count = \count($keys); $i < $count; $i++) {
            $pairs[] = sprintf($format, $keys[$i], $values[$i]);
        }

        return $pairs;
    }

    /**
     * Returns each key and value pair encoded as array assignment.
     * @param array $array Array to convert into code
     * @param string $space Whitespace between array assignment operator
     * @param bool $omit True to omit unnecessary keys, false to not
     * @param callable $encode Callback used to encode values
     * @param bool $omitted Set to true, if all the keys were omitted, false otherwise
     * @return string[] Each of key and value pair encoded as php
     */
    private function getPairs(array $array, $space, $omit, callable $encode, &$omitted = true)
    {
        $pairs = [];
        $nextIndex = 0;
        $omitted = true;
        $format = '%s' . $space . '=>' . $space . '%s';

        foreach ($array as $key => $value) {
            if ($omit && $this->canOmitKey($key, $nextIndex)) {
                $pairs[] = $encode($value, 1);
            } else {
                $pairs[] = sprintf($format, $encode($key, 1), $encode($value, 1));
                $omitted = false;
            }
        }

        return $pairs;
    }

    /**
     * Tells if the key can be omitted from array output based on expected index.
     * @param int|string $key Current array key
     * @param int $nextIndex Next expected key that can be omitted
     * @return bool True if the key can be omitted, false if not
     */
    private function canOmitKey($key, &$nextIndex)
    {
        $result = $key === $nextIndex;

        if (\is_int($key)) {
            $nextIndex = max($key + 1, $nextIndex);
        }

        return $result;
    }
}

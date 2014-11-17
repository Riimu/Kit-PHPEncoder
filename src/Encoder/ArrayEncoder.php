<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ArrayEncoder implements Encoder
{
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

        return $this->wrap("$eol$indent" . implode(",$eol$indent", $lines) . ",$eol$last", $options['array.short']);
    }

    private function wrap($string, $short)
    {
        return sprintf($short ? '[%s]' : 'array(%s)', $string);
    }

    private function buildIndent($base, $indent, $depth)
    {
        $base = is_int($base) ? str_repeat(' ', $base) : (string) $base;

        return $depth === 0 ? $base : $base . str_repeat(
            is_int($indent) ? str_repeat(' ', $indent) : (string) $indent,
            $depth
        );
    }

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
     * Encodes keys and values ands aligns the keys with whitespace.
     * @param array $array Array to encode into strings
     * @return string[] Key and values encoded as strings
     */
    private function getAlignedPairs($array, callable $encode)
    {
        $keys = [];
        $values = [];

        foreach ($array as $key => $value) {
            $keys[] = $encode($key, +1);
            $values[] = $encode($value, +1);
        }

        $format = sprintf('%%-%ds => %%s', max(array_map('strlen', $keys)));
        $pairs = [];

        for ($i = 0, $count = count($keys); $i < $count; $i++) {
            $pairs[] = sprintf($format, $keys[$i], $values[$i]);
        }

        return $pairs;
    }

    /**
     * Encodes the keys and values of an array into strings.
     * @param array $array Array to encode into strings
     * @return string[] Key and values encoded as strings
     */
    private function getPairs($array, $ws, $omit, callable $encode, & $inline = null)
    {
        $pairs = [];
        $nextIndex = 0;
        $inline = true;
        $format = "%s$ws=>$ws%s";

        foreach ($array as $key => $value) {
            if ($key === $nextIndex && $omit) {
                $pairs[] = $encode($value, +1);
            } else {
                $pairs[] = sprintf($format, $encode($key, +1), $encode($value, +1));
                $inline = false;
            }

            if (is_int($key) && $key >= $nextIndex) {
                $nextIndex = $key + 1;
            }
        }

        return $pairs;
    }
}

<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for string values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StringEncoder implements Encoder
{
    /** @var array Default values for options in the encoder */
    private static $defaultOptions = [
        'string.escape' => true,
    ];

    public function getDefaultOptions()
    {
        return self::$defaultOptions;
    }

    public function supports($value)
    {
        return is_string($value);
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        if (!$options['string.escape'] || preg_match('/^[\x20-\x7E]*$/', $value)) {
            return $this->getSingleQuotedString($value);
        }

        return $this->getDoubleQuotedString($value);
    }

    /**
     * Returns the string wrapped in single quotes and escape appropriately.
     * @param string $string String to wrap
     * @return string The string wrapped in single quotes
     */
    private function getSingleQuotedString($string)
    {
        return sprintf("'%s'", strtr($string, ["'" => "\\'", '\\' => '\\\\']));
    }

    /**
     * Returns the string wrapped in double quotes and all but print characters escaped.
     * @param string $string String to wrap and escape
     * @return string The string wrapped in double quotes and escape correctly
     */
    private function getDoubleQuotedString($string)
    {
        return sprintf('"%s"', preg_replace_callback(
            '/[^\x20-\x7E]/',
            function ($matches) {
                return sprintf('\x%02x', ord($matches[0]));
            },
            strtr($string, [
                "\n" => '\n',
                "\r" => '\r',
                "\t" => '\t',
                '$'  => '\$',
                '"'  => '\"',
                '\\' => '\\\\',
            ])
        ));
    }
}

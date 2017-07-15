<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for string values.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StringEncoder implements Encoder
{
    /** @var array Default values for options in the encoder */
    private static $defaultOptions = [
        'string.escape' => true,
        'string.binary' => false,
        'string.utf8' => false,
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
        $value = (string) $value;

        if (preg_match('/[^\x20-\x7E]/', $value)) {
            if ($this->isBinaryString($value, $options)) {
                return $this->encodeBinaryString($value);
            } elseif ($options['string.escape']) {
                return $this->getDoubleQuotedString($value, $options);
            }
        }

        return $this->getSingleQuotedString($value);
    }

    private function isBinaryString($string, $options)
    {
        if (!$options['string.binary']) {
            return false;
        }

        // UTF-8 validity test without mbstring extension
        $pattern =
            '/^(?>
                [\x00-\x7F]+                       # ASCII
              | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
              |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding over longs
              | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
              |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
              |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
              | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
              |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
            )*$/x';

        return !preg_match($pattern, $string);
    }

    private function encodeBinaryString($string)
    {
        return sprintf("base64_decode('%s')", base64_encode($string));
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
     * @param array $options String encoding options
     * @return string The string wrapped in double quotes and escape correctly
     */
    private function getDoubleQuotedString($string, $options)
    {
        $string = strtr($string, [
            "\n" => '\n',
            "\r" => '\r',
            "\t" => '\t',
            '$'  => '\$',
            '"'  => '\"',
            '\\' => '\\\\',
        ]);

        if ($options['string.utf8']) {
            $string = $this->encodeUtf8($string);
        }

        return sprintf('"%s"', preg_replace_callback(
            '/[^\x20-\x7E]/',
            function ($matches) {
                return sprintf('\x%02x', ord($matches[0]));
            },
            $string
        ));
    }

    /**
     * Encodes all multibyte UTF-8 characters into PHP7 string encoding
     * @param string $string The string to encoder
     * @return string The string with all the multibyte characters encoded
     */
    private function encodeUtf8($string)
    {
        $pattern =
            '/  [\xC2-\xDF][\x80-\xBF]
              |  \xE0[\xA0-\xBF][\x80-\xBF]
              | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}
              |  \xED[\x80-\x9F][\x80-\xBF]
              |  \xF0[\x90-\xBF][\x80-\xBF]{2}
              | [\xF1-\xF3][\x80-\xBF]{3}
              |  \xF4[\x80-\x8F][\x80-\xBF]{2}/x';

        return preg_replace_callback($pattern, function ($match) {
            return sprintf('\u{%s}', dechex($this->getCodePoint($match[0])));
        }, $string);
    }

    /**
     * Returns the unicode code point for the given multibyte UTF-8 character.
     * @param string $bytes The multibyte character
     * @return int The code point for the multibyte character
     */
    private function getCodePoint($bytes)
    {
        if (strlen($bytes) === 2) {
            return ((ord($bytes[0]) & 0b11111) << 6)
                | (ord($bytes[1]) & 0b111111);
        }

        if (strlen($bytes) === 3) {
            return ((ord($bytes[0]) & 0b1111) << 12)
                | ((ord($bytes[1]) & 0b111111) << 6)
                | (ord($bytes[2]) & 0b111111);
        }

        return ((ord($bytes[0]) & 0b111) << 18)
            | ((ord($bytes[1]) & 0b111111) << 12)
            | ((ord($bytes[2]) & 0b111111) << 6)
            | (ord($bytes[3]) & 0b111111);
    }
}

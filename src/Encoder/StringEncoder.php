<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StringEncoder implements Encoder
{
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

    private function getSingleQuotedString($string)
    {
        return sprintf("'%s'", strtr($string, ["'" => "\\'", '\\' => '\\\\']));
    }

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
                '$' => '\$',
                '"' => '\"',
                '\\' => '\\\\',
            ])
        ));
    }
}

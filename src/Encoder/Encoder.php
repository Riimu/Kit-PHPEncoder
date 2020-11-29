<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Interface for different types of value encoders.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2020 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Encoder
{
    /**
     * Returns a list of options and their default values as an associative array.
     * @return array List of options and their default values
     */
    public function getDefaultOptions();

    /**
     * Tells if the encoder supports encoding the given value.
     * @param mixed $value Value to test
     * @return bool True if the value can be encoded, false otherwise
     */
    public function supports($value);

    /**
     * Generates the PHP code representation for the given value.
     * @param mixed $value Value to encode
     * @param int $depth Current indentation depth of the output
     * @param array $options List of encoder options
     * @param callable $encode Callback used to encode values
     * @return string The PHP code that represents the given value
     */
    public function encode($value, $depth, array $options, callable $encode);
}

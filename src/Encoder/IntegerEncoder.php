<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

use Riimu\Kit\PHPEncoder\RecursiveEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class IntegerEncoder implements Encoder
{
    public function getDefaultOptions()
    {
        return [];
    }

    public function supports($value)
    {
        return is_integer($value);
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        return (string) $value;
    }
}

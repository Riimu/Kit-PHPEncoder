<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

/**
 * Encoder for GMP number types.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2020 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class GMPEncoder implements Encoder
{
    public function getDefaultOptions()
    {
        return [];
    }

    public function supports($value)
    {
        return \is_object($value) && \get_class($value) === \GMP::class;
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        return sprintf('gmp_init(%s)', $encode(gmp_strval($value)));
    }
}

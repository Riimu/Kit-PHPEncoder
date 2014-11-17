<?php

namespace Riimu\Kit\PHPEncoder\Encoder;

use Riimu\Kit\PHPEncoder\RecursiveEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Encoder
{
    public function getDefaultOptions();
    public function supports($value);
    public function encode($value, $depth, array $options, callable $encode);
}

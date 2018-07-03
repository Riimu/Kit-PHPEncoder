<?php

namespace Riimu\Kit\PHPEncoder;

use PHPUnit\Framework\TestCase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015-2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class EncodingTestCase extends TestCase
{
    /**
     * Asserts that expected code is generated and it evaluates to the expected value.
     * @param string $code The expected code
     * @param mixed $value The initial value and the expected evaluated value
     * @param PHPEncoder|array $encoder The encoder to use or options for the encoder
     * @param mixed $initial Initial value, if different from expected value
     */
    protected function assertEncode($code, $value, $encoder = [], $initial = null)
    {
        if (\is_array($encoder)) {
            $encoder = new PHPEncoder($encoder);
        }

        $output = $encoder->encode(\func_num_args() < 4 ? $value : $initial);
        $this->assertSame($code, $output);
        $this->assertSame($value, eval("return $output;"));
    }
}

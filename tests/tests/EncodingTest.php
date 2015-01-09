<?php

namespace Riimu\Kit\PHPEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class EncodingTest extends \PHPUnit_Framework_TestCase
{
    public function testOptions()
    {
        $mock = $this->getMock('Riimu\Kit\PHPEncoder\Encoder\Encoder', ['getDefaultOptions', 'supports', 'encode']);
        $mock->expects($this->any())->method('getDefaultOptions')->will($this->returnValue(['test' => false]));
        $mock->expects($this->any())->method('supports')->will($this->returnValue(true));
        $mock->expects($this->any())->method('encode')->will($this->returnCallback(function ($value, $depth, $options, $encoder) {
            return !empty($options['test']) ? strtoupper($value) : $value;
        }));

        $this->assertSame('text', (new PHPEncoder([], [$mock]))->encode('text'));
        $this->assertSame('text', (new PHPEncoder(['test' => false], [$mock]))->encode('text'));
        $this->assertSame('TEXT', (new PHPEncoder(['test' => true], [$mock]))->encode('text'));
        $this->assertSame('text', (new PHPEncoder([], [$mock]))->encode('text', ['test' => false]));
        $this->assertSame('TEXT', (new PHPEncoder([], [$mock]))->encode('text', ['test' => true]));

        $encoder = new PHPEncoder(['test' => true], [$mock]);
        $encoder->setOption('test', false);
        $this->assertSame('text', $encoder->encode('text'));
    }

    public function testNull()
    {
        $this->assertEncode(null, 'null', new PHPEncoder());
        $this->assertEncode(null, 'NULL', new PHPEncoder(['null.capitalize' => true]));
    }

    public function testBoolean()
    {
        $this->assertEncode(true, 'true', new PHPEncoder());
        $this->assertEncode(false, 'false', new PHPEncoder());
        $this->assertEncode(true, 'TRUE', new PHPEncoder(['boolean.capitalize' => true]));
        $this->assertEncode(false, 'FALSE', new PHPEncoder(['boolean.capitalize' => true]));
    }

    public function testInteger()
    {
        $this->assertEncode(1, '1', new PHPEncoder());
        $this->assertEncode(-733, '-733', new PHPEncoder());
    }

    public function testFloatDefaultPrecision()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(1.1, '1.1000000000000001', $encoder);
        $this->assertEncode(0.1, '0.10000000000000001', $encoder);
        $this->assertEncode(1.0e+32, '1.0000000000000001E+32', $encoder);
        $this->assertEncode(1.0e-32, '1.0000000000000001E-32', $encoder);
        $this->assertEncode(-1.0e+32, '-1.0000000000000001E+32', $encoder);
        $this->assertEncode(-1.0e-32, '-1.0000000000000001E-32', $encoder);
    }

    public function testFloatSmallPrecision()
    {
        $encoder = new PHPEncoder(['float.precision' => 14]);
        $this->assertEncode(1.1, '1.1', $encoder);
        $this->assertEncode(0.1, '0.1', $encoder);
        $this->assertEncode(1.0e+32, '1.0E+32', $encoder);
        $this->assertEncode(1.0e-32, '1.0E-32', $encoder);
        $this->assertEncode(-1.0e+32, '-1.0E+32', $encoder);
        $this->assertEncode(-1.0e-32, '-1.0E-32', $encoder);
    }

    public function testPHPDefaultPrecision()
    {
        $this->assertEncode(1.1, (string) 1.1, new PHPEncoder(['float.precision' => false]));
    }

    public function testFloat()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(0.0, '0.0', $encoder);
        $this->assertEncode(1.0, '1.0', $encoder);
        $this->assertEncode(-42.0, '-42.0', $encoder);

        $this->assertEncode(INF, 'INF', $encoder);
        $this->assertEncode(-INF, '-INF', $encoder);
        $this->assertEncode(NAN, 'NAN', $encoder);

        $this->assertEncode(999999999999999.0, '999999999999999.0', $encoder);
        $this->assertEncode(8888888888888888.0, '8888888888888888.0', $encoder);

        $encoder->setOption('float.precision', 14);
        $float = $encoder->encode(999999999999999.0);
        $this->assertSame('1.0E+15', $float);
        $this->assertNotEquals(999999999999999.0, eval("return $float;"));

        $encoder->setOption('float.integers', true);
        $this->assertEncode(199999999999999, '199999999999999', $encoder, 199999999999999.0);
        $this->assertEncode(999999999999999, '999999999999999', $encoder, 999999999999999.0);
        $this->assertEncode(1.0e-32, '1.0E-32', $encoder);
    }

    public function testString()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode('1', "'1'", $encoder);
        $this->assertEncode('"\\\'', "'\"\\\\\\''", $encoder);
        $this->assertEncode("\r", '"\r"', $encoder);
        $this->assertEncode(" ", "' '", $encoder);
        $this->assertEncode("~", "'~'", $encoder);
        $this->assertEncode("\t\$foo", '"\t\$foo"', $encoder);
        $this->assertEncode("\t{\$foo}", '"\t{\$foo}"', $encoder);
        $this->assertEncode("\x00", '"\x00"', $encoder);

        $encoder->setOption('string.escape', false);
        $this->assertEncode("\r", "'\r'", $encoder);
    }

    public function testGMPEncoding()
    {
        if (!function_exists('gmp_init')) {
            $this->markTestSkipped('Missing GMP library');
        }

        $encoder = new PHPEncoder();
        $gmp = gmp_init('123');
        $string = $encoder->encode($gmp);

        $this->assertSame('gmp_init(\'123\')', $string);
        $this->assertSame(0, gmp_cmp($gmp, eval('return ' . $string . ';')));
    }

    public function testInvalidOption()
    {
        $this->setExpectedException('InvalidArgumentException');
        new PHPEncoder(['NoSuchOption' => true]);
    }

    public function testInvalidOptionOnEncode()
    {
        $this->setExpectedException('InvalidArgumentException');
        (new PHPEncoder())->encode([], ['NoSuchOption' => true]);
    }

    public function testMaxDepth()
    {
        $encoder = new PHPEncoder();
        $encoder->setOption('recursion.max', 2);
        $this->assertNotEmpty($encoder->encode([1, [2, 3]]));

        $encoder->setOption('recursion.max', 1);
        $this->setExpectedException('RuntimeException');
        $encoder->encode([1, [2, 3]]);
    }

    public function testMissingEncoder()
    {
        $encoder = new PHPEncoder([], []);
        $this->setExpectedException('InvalidArgumentException');
        $encoder->encode(null);
    }

    public function testUnknownType()
    {
        $fp = fopen(__FILE__, 'r');
        fclose($fp);

        $this->setExpectedException('InvalidArgumentException');
        (new PHPEncoder())->encode($fp);
    }

    public function testArrayRecursion()
    {
        $foo = [1];
        $foo[1] = & $foo;

        $this->setExpectedException('RuntimeException');
        (new PHPEncoder())->encode($foo);
    }

    public function testIgnoredArrayRecursion()
    {
        $foo = [1];
        $foo[1] = & $foo;

        $this->assertSame(
            [1, null],
            eval('return ' .(new PHPEncoder(['recursion.ignore' => true]))->encode($foo) . ';')
        );
    }

    public function testMaxDeathOnNoRecursionDetection()
    {
        $foo = [1];
        $foo[1] = & $foo;

        $encoder = new PHPEncoder([
            'recursion.detect' => false,
            'recursion.max' => 5
        ]);

        $this->setExpectedException('RuntimeException');
        $encoder->encode($foo);
    }

    private function assertEncode($value, $string, PHPEncoder $encoder, $initial = null)
    {
        $output = $encoder->encode(func_num_args() < 4 ? $value : $initial);
        $this->assertSame($string, $output);

        if (is_double($value) && is_nan($value)) {
            $this->assertTrue(is_nan(eval("return $output;")));
        } else {
            $this->assertSame($value, eval("return $output;"));
        }
    }
}

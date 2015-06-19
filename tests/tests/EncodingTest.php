<?php

namespace Riimu\Kit\PHPEncoder;

use Riimu\Kit\PHPEncoder\Encoder\FloatEncoder;

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
        $mock->expects($this->any())->method('encode')->will($this->returnCallback(
            function ($value, $depth, $options, $encoder) {
                return !empty($options['test']) ? strtoupper($value) : $value;
            }
        ));

        $this->assertSame('text', (new PHPEncoder([], [$mock]))->encode('text'));
        $this->assertSame('text', (new PHPEncoder(['test' => false], [$mock]))->encode('text'));
        $this->assertSame('TEXT', (new PHPEncoder(['test' => true], [$mock]))->encode('text'));
        $this->assertSame('text', (new PHPEncoder([], [$mock]))->encode('text', ['test' => false]));
        $this->assertSame('TEXT', (new PHPEncoder([], [$mock]))->encode('text', ['test' => true]));

        $encoder = new PHPEncoder(['test' => true], [$mock]);
        $encoder->setOption('test', false);
        $this->assertSame('text', $encoder->encode('text'));
    }

    public function testNullEncoding()
    {
        $this->assertEncode(null, 'null', new PHPEncoder());
        $this->assertEncode(null, 'NULL', new PHPEncoder(['null.capitalize' => true]));
    }

    public function testBooleanEncoding()
    {
        $this->assertEncode(true, 'true', new PHPEncoder());
        $this->assertEncode(false, 'false', new PHPEncoder());
        $this->assertEncode(true, 'TRUE', new PHPEncoder(['boolean.capitalize' => true]));
        $this->assertEncode(false, 'FALSE', new PHPEncoder(['boolean.capitalize' => true]));
    }

    public function testIntegerEncoding()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(0, '0', $encoder);
        $this->assertEncode(1, '1', $encoder);
        $this->assertEncode(-1, '-1', $encoder);
        $this->assertEncode(1337, '1337', $encoder);
        $this->assertEncode(-1337, '-1337', $encoder);
    }

    public function testMaximumInteger()
    {
        $this->assertEncode(PHP_INT_MAX, (string) PHP_INT_MAX, new PHPEncoder());
    }

    public function testMinimumInteger()
    {
        $this->assertEncode(-PHP_INT_MAX - 1, '(int) ' . (-PHP_INT_MAX - 1), new PHPEncoder());
        $this->assertEncode(-PHP_INT_MAX - 1, '(int)' . (-PHP_INT_MAX - 1), new PHPEncoder(['whitespace' => false]));
    }

    public function testFloatEncoding()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(0.0, '0.0', $encoder);
        $this->assertEncode(1.0, '1.0', $encoder);
        $this->assertEncode(-1.0, '-1.0', $encoder);
        $this->assertEncode(1337.0, '1337.0', $encoder);
        $this->assertEncode(-1337.0, '-1337.0', $encoder);
    }

    public function testFloatPrecision()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(1.1, '1.1000000000000001', $encoder);
        $this->assertEncode(0.1, '0.10000000000000001', $encoder);

        $encoder->setOption('float.precision', 14);
        $this->assertEncode(1.1, '1.1', $encoder);
        $this->assertEncode(0.1, '0.1', $encoder);
    }

    public function testFloatExponents()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(1.0e+32, '1.0E+32', $encoder);
        $this->assertEncode(1.0e-32, '1.0E-32', $encoder);
        $this->assertEncode(-1.0e+32, '-1.0E+32', $encoder);
        $this->assertEncode(-1.0e-32, '-1.0E-32', $encoder);
    }

    public function testPHPDefaultPrecision()
    {
        $float = 1.12345678901234567890;
        $this->assertEncode($float, var_export($float, true), new PHPEncoder(['float.precision' => false]));
    }

    public function testInfiniteFloat()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(INF, 'INF', $encoder);
        $this->assertEncode(-INF, '-INF', $encoder);
    }

    public function testNanFloat()
    {
        //$this->assertEncode(NAN, 'NAN', $encoder);
    }

    public function testFloatIntegers()
    {
        $encoder = new PHPEncoder(['float.integers' => true]);
        $this->assertEncode(0, '0', $encoder, 0.0);
        $this->assertEncode(1, '1', $encoder, 1.0);
        $this->assertEncode(-1, '-1', $encoder, -1.0);
        $this->assertEncode(1337, '1337', $encoder, 1337.0);
        $this->assertEncode(-1337, '-1337', $encoder, -1337.0);
    }

    public function testMaximumFloatIntegers()
    {
        $encoder = new PHPEncoder(['float.integers' => true]);
        $positive = FloatEncoder::FLOAT_MAX - 1;
        $negative = -FloatEncoder::FLOAT_MAX + 1;

        $this->assertEncode(FloatEncoder::FLOAT_MAX, '9.0071992547409927E+15', $encoder);
        $this->assertEncode(-FloatEncoder::FLOAT_MAX, '-9.0071992547409927E+15', $encoder);
        $this->assertEncode(number_format($positive, 0, '', '') + 0, '9007199254740991', $encoder, $positive);
        $this->assertEncode(number_format($negative, 0, '', '') + 0, '-9007199254740991', $encoder, $negative);
    }

    public function testLargeFloatIntegers()
    {
        $this->assertEncode(1.0E+20, '1.0E+20', new PHPEncoder(['float.integers' => true]));
        $this->assertEncode(1.0E+20, '100000000000000000000', new PHPEncoder(['float.integers' => 'all']));
    }

    public function testFloatRounding()
    {
        $encoder = new PHPEncoder(['float.precision' => 14]);
        $value = 999999999999999.0;

        $float = $encoder->encode($value);
        $this->assertSame('1.0E+15', $float);
        $this->assertNotEquals($value, eval("return $float;"));
    }

    public function testFloatPresentation()
    {
        $encoder = new PHPEncoder();

        $this->assertEncode(FloatEncoder::FLOAT_MAX - 1, '9007199254740991.0', $encoder);
        $this->assertEncode(-FloatEncoder::FLOAT_MAX + 1, '-9007199254740991.0', $encoder);
        $this->assertEncode(FloatEncoder::FLOAT_MAX, '9.0071992547409927E+15', $encoder);
        $this->assertEncode(-FloatEncoder::FLOAT_MAX, '-9.0071992547409927E+15', $encoder);

        $this->assertEncode(0.0001, '0.0001', $encoder);
        $this->assertEncode(0.00001, '1.0E-5', $encoder);

        $encoder->setOption('float.precision', 2);

        $this->assertEncode(10.0, '10.0', $encoder);
        $this->assertEncode(100.0, '1.0E+2', $encoder);
        $this->assertEncode(0.1, '0.1', $encoder);
        $this->assertEncode(0.01, '1.0E-2', $encoder);
    }

    public function testStringEncoding()
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

    public function testInvalidOptionOnConstructor()
    {
        $this->setExpectedException('Riimu\Kit\PHPEncoder\InvalidOptionException');
        new PHPEncoder(['NoSuchOption' => true]);
    }

    public function testSettingAnInvalidOption()
    {
        $this->setExpectedException('Riimu\Kit\PHPEncoder\InvalidOptionException');
        (new PHPEncoder())->setOption('NoSuchOption', true);
    }

    public function testInvalidOptionOnEncode()
    {
        $this->setExpectedException('Riimu\Kit\PHPEncoder\InvalidOptionException');
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
        $this->assertSame($value, eval("return $output;"));
    }
}

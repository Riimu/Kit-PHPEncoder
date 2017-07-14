<?php

namespace Riimu\Kit\PHPEncoder;

use Riimu\Kit\PHPEncoder\Encoder\Encoder;
use Riimu\Kit\PHPEncoder\Encoder\FloatEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class EncodingTest extends EncodingTestCase
{
    public function testOptions()
    {
        $mock = $this->getMockBuilder(Encoder::class)
            ->setMethods(['getDefaultOptions', 'supports', 'encode'])
            ->getMock();

        $mock->expects($this->any())->method('getDefaultOptions')->willReturn(['test' => false]);
        $mock->expects($this->any())->method('supports')->willReturn(true);
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
        $this->assertEncode('null', null);
        $this->assertEncode('NULL', null, ['null.capitalize' => true]);
    }

    public function testBooleanEncoding()
    {
        $this->assertEncode('true', true);
        $this->assertEncode('false', false);
        $this->assertEncode('TRUE', true, ['boolean.capitalize' => true]);
        $this->assertEncode('FALSE', false, ['boolean.capitalize' => true]);
    }

    public function testIntegerEncoding()
    {
        $this->assertEncode('0', 0);
        $this->assertEncode('1', 1);
        $this->assertEncode('-1', -1);
        $this->assertEncode('1337', 1337);
        $this->assertEncode('-1337', -1337);
    }

    public function testMaximumInteger()
    {
        $this->assertEncode((string) PHP_INT_MAX, PHP_INT_MAX);
    }

    public function testMinimumInteger()
    {
        $this->assertEncode('(int) ' . (-PHP_INT_MAX - 1), -PHP_INT_MAX - 1);
        $this->assertEncode('(int)' . (-PHP_INT_MAX - 1), -PHP_INT_MAX - 1, ['whitespace' => false]);
    }

    public function testFloatEncoding()
    {
        $this->assertEncode('0.0', 0.0);
        $this->assertEncode('1.0', 1.0);
        $this->assertEncode('-1.0', -1.0);
        $this->assertEncode('1337.0', 1337.0);
        $this->assertEncode('-1337.0', -1337.0);
    }

    public function testFloatPrecision()
    {
        $this->assertEncode('1.1000000000000001', 1.1);
        $this->assertEncode('0.10000000000000001', 0.1);

        $this->assertEncode('1.1', 1.1, ['float.precision' => 14]);
        $this->assertEncode('0.1', 0.1, ['float.precision' => 14]);
    }

    public function testFloatExponents()
    {
        $this->assertEncode('1.0E+32', 1.0e+32);
        $this->assertEncode('1.0E-32', 1.0e-32);
        $this->assertEncode('-1.0E+32', -1.0e+32);
        $this->assertEncode('-1.0E-32', -1.0e-32);
    }

    public function testUsingIniPrecision()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped();
        }

        $float = 1.1234567890123456;
        $serialize = ini_set('serialize_precision', 13);

        $this->assertEncode('1.123456789012', $float, ['float.precision' => false]);

        ini_set('serialize_precision', $serialize);
    }

    public function testInfiniteFloat()
    {
        $this->assertEncode('INF', INF);
        $this->assertEncode('-INF', -INF);
    }

    public function testNanFloat()
    {
        $code = (new PHPEncoder())->encode(NAN);
        $this->assertSame('NAN', $code);

        $value = eval("return $code;");
        $this->assertInternalType('float', $value);
        $this->assertNan($value);
    }

    public function testFloatIntegers()
    {
        $encoder = new PHPEncoder(['float.integers' => true]);
        $this->assertEncode('0', 0, $encoder, 0.0);
        $this->assertEncode('1', 1, $encoder, 1.0);
        $this->assertEncode('-1', -1, $encoder, -1.0);
        $this->assertEncode('1337', 1337, $encoder, 1337.0);
        $this->assertEncode('-1337', -1337, $encoder, -1337.0);
    }

    public function testMaximumFloatIntegers()
    {
        $encoder = new PHPEncoder(['float.integers' => true]);
        $positive = FloatEncoder::FLOAT_MAX - 1;
        $negative = -FloatEncoder::FLOAT_MAX + 1;

        $this->assertEncode('9.0071992547409927E+15', FloatEncoder::FLOAT_MAX, $encoder);
        $this->assertEncode('-9.0071992547409927E+15', -FloatEncoder::FLOAT_MAX, $encoder);
        $this->assertEncode('9007199254740991', number_format($positive, 0, '', '') + 0, $encoder, $positive);
        $this->assertEncode('-9007199254740991', number_format($negative, 0, '', '') + 0, $encoder, $negative);
    }

    public function testLargeFloatIntegers()
    {
        $this->assertEncode('1.0E+20', 1.0E+20, ['float.integers' => true]);
        $this->assertEncode('100000000000000000000', 1.0E+20, ['float.integers' => 'all']);
    }

    public function testFloatRounding()
    {
        $encoder = new PHPEncoder(['float.precision' => 14]);
        $value = 999999999999999.0;

        $float = $encoder->encode($value);
        $this->assertSame('1.0E+15', $float);

        $evaluated = eval("return $float;");
        $this->assertInternalType('float', $evaluated);
        $this->assertNotSame($value, $evaluated);
    }

    public function testFloatPresentation()
    {
        $this->assertEncode('9007199254740991.0', FloatEncoder::FLOAT_MAX - 1);
        $this->assertEncode('-9007199254740991.0', -FloatEncoder::FLOAT_MAX + 1);
        $this->assertEncode('9.0071992547409927E+15', FloatEncoder::FLOAT_MAX);
        $this->assertEncode('-9.0071992547409927E+15', -FloatEncoder::FLOAT_MAX);

        $this->assertEncode('0.0001', 0.0001);
        $this->assertEncode('1.0E-5', 0.00001);

        $encoder = new PHPEncoder();
        $encoder->setOption('float.precision', 2);

        $this->assertEncode('10.0', 10.0, $encoder);
        $this->assertEncode('1.0E+2', 100.0, $encoder);
        $this->assertEncode('0.1', 0.1, $encoder);
        $this->assertEncode('1.0E-2', 0.01, $encoder);
    }

    public function testStringEncoding()
    {
        $this->assertEncode("'1'", '1');
        $this->assertEncode("'\"\\\\\\''", '"\\\'');
        $this->assertEncode('"\r"', "\r");
        $this->assertEncode("' '", ' ');
        $this->assertEncode("'~'", '~');
        $this->assertEncode('"\t\$foo"', "\t\$foo");
        $this->assertEncode('"\t{\$foo}"', "\t{\$foo}");
        $this->assertEncode('"\x00"', "\x00");

        $this->assertEncode("'\r'", "\r", ['string.escape' => false]);
    }

    public function testGMPEncoding()
    {
        if (!function_exists('gmp_init')) {
            $this->markTestSkipped('Missing GMP library');
        }

        $gmp = gmp_init('123');
        $string = (new PHPEncoder())->encode($gmp);

        $this->assertSame('gmp_init(\'123\')', $string);
        $this->assertSame(0, gmp_cmp($gmp, eval('return ' . $string . ';')));
    }

    public function testInvalidOptionOnConstructor()
    {
        $this->expectException(InvalidOptionException::class);
        new PHPEncoder(['NoSuchOption' => true]);
    }

    public function testSettingAnInvalidOption()
    {
        $this->expectException(InvalidOptionException::class);
        (new PHPEncoder())->setOption('NoSuchOption', true);
    }

    public function testInvalidOptionOnEncode()
    {
        $this->expectException(InvalidOptionException::class);
        (new PHPEncoder())->encode([], ['NoSuchOption' => true]);
    }

    public function testMaxDepth()
    {
        $encoder = new PHPEncoder();
        $encoder->setOption('recursion.max', 2);
        $this->assertNotEmpty($encoder->encode([1, [2, 3]]));

        $encoder->setOption('recursion.max', 1);
        $this->expectException(\RuntimeException::class);
        $encoder->encode([1, [2, 3]]);
    }

    public function testMissingEncoder()
    {
        $encoder = new PHPEncoder([], []);
        $this->expectException(\InvalidArgumentException::class);
        $encoder->encode(null);
    }

    public function testUnknownType()
    {
        $fp = fopen(__FILE__, 'rb');
        fclose($fp);

        $this->expectException(\InvalidArgumentException::class);
        (new PHPEncoder())->encode($fp);
    }

    public function testArrayRecursion()
    {
        $foo = [1];
        $foo[1] = & $foo;

        $this->expectException(\RuntimeException::class);
        (new PHPEncoder())->encode($foo);
    }

    public function testIgnoredArrayRecursion()
    {
        $foo = [1];
        $foo[1] = & $foo;

        $this->assertSame(
            [1, null],
            eval('return ' . (new PHPEncoder(['recursion.ignore' => true]))->encode($foo) . ';')
        );
    }

    public function testMaxDeathOnNoRecursionDetection()
    {
        $foo = [1];
        $foo[1] = & $foo;

        $encoder = new PHPEncoder([
            'recursion.detect' => false,
            'recursion.max'    => 5,
        ]);

        $this->expectException(\RuntimeException::class);
        $encoder->encode($foo);
    }
}

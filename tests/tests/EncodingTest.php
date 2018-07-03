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
        $this->assertEncode((string) \PHP_INT_MAX, \PHP_INT_MAX);
    }

    public function testMinimumInteger()
    {
        $this->assertEncode('(int) ' . (-\PHP_INT_MAX - 1), -\PHP_INT_MAX - 1);
        $this->assertEncode('(int)' . (-\PHP_INT_MAX - 1), -\PHP_INT_MAX - 1, ['whitespace' => false]);
    }

    public function testIntegerTypes()
    {
        $this->assertEncode('0b110011000000011111001001', 13371337, ['integer.type' => 'binary']);
        $this->assertEncode('-0b110011000000011111001001', -13371337, ['integer.type' => 'binary']);
        $this->assertEncode('063003711', 13371337, ['integer.type' => 'octal']);
        $this->assertEncode('-063003711', -13371337, ['integer.type' => 'octal']);
        $this->assertEncode('13371337', 13371337, ['integer.type' => 'decimal']);
        $this->assertEncode('-13371337', -13371337, ['integer.type' => 'decimal']);
        $this->assertEncode('0xcc07c9', 13371337, ['integer.type' => 'hexadecimal']);
        $this->assertEncode('-0xcc07c9', -13371337, ['integer.type' => 'hexadecimal']);
        $this->assertEncode('0xCC07C9', 13371337, ['integer.type' => 'hexadecimal', 'hex.capitalize' => true]);
        $this->assertEncode('-0xCC07C9', -13371337, ['integer.type' => 'hexadecimal', 'hex.capitalize' => true]);
    }

    public function testInvalidIntegerType()
    {
        $encoder = new PHPEncoder();

        $this->expectException(\InvalidArgumentException::class);
        $encoder->encode(1, ['integer.type' => 'invalid']);
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
        $float = 1.1234567890123456;
        $serialize = ini_set('serialize_precision', 13);

        $this->assertEncode('1.123456789012', $float, ['float.precision' => false]);

        ini_set('serialize_precision', $serialize);
    }

    public function testInfiniteFloat()
    {
        $this->assertEncode('INF', \INF);
        $this->assertEncode('-INF', -\INF);
    }

    public function testNanFloat()
    {
        $code = (new PHPEncoder())->encode(\NAN);
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
        $this->assertEncode('2000000000', 2000000000, $encoder, 2000000000.0);

        $this->assertEncode('0xf', 15, ['float.integers' => true, 'integer.type' => 'hexadecimal'], 15.0);
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

    public function testFloatExport()
    {
        $this->assertEncode('1.123', 1.123, ['float.export' => true]);
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
        $this->assertEncode('"\xff"', "\xFF");
        $this->assertEncode('"\xFF"', "\xFF", ['hex.capitalize' => true]);
        $this->assertEncode("'\r'", "\r", ['string.escape' => false]);
    }

    public function testBinaryStrings()
    {
        $encoder = new PHPEncoder(['string.binary' => true, 'string.escape' => false]);

        $this->assertEncode("base64_decode('AP8Q')", "\x00\xFF\x10", $encoder);
        $this->assertEncode("'ABC'", 'ABC', $encoder);
        $this->assertEncode("'åäöÅÄÖ'", 'åäöÅÄÖ', $encoder);
    }

    public function testUtf8String()
    {
        $encoder = new PHPEncoder(['string.utf8' => true]);

        $this->assertEncode('"\nA"', "\nA", $encoder);
        $this->assertSame('"\nA\u{c4}\x00"', $encoder->encode("\nAÄ\x00"));

        if (version_compare(\PHP_VERSION, '7', '<')) {
            $this->assertSame('"\u{a2}"', $encoder->encode("\xC2\xA2"));
            $this->assertSame('"\u{20ac}"', $encoder->encode("\xE2\x82\xAC"));
            $this->assertSame('"\u{10348}"', $encoder->encode("\xF0\x90\x8D\x88"));
            $this->assertSame('"\u{e5}\u{e4}\u{f6}\u{c5}\u{c4}\u{d6}"', $encoder->encode('åäöÅÄÖ'));
        } else {
            $this->assertEncode('"\u{a2}"', "\u{a2}", $encoder);
            $this->assertEncode('"\u{20ac}"', "\u{20ac}", $encoder);
            $this->assertEncode('"\u{10348}"', "\u{10348}", $encoder);
            $this->assertEncode('"\u{e5}\u{e4}\u{f6}\u{c5}\u{c4}\u{d6}"', 'åäöÅÄÖ', $encoder);
        }

        $encoder->setOption('hex.capitalize', true);
        $this->assertSame('"\nA\u{C4}\x00"', $encoder->encode("\nAÄ\x00"));
    }

    public function testClassStrings()
    {
        $encoder = new PHPEncoder(['string.classes' => [self::class]]);
        $this->assertEncode('\\' . self::class . '::class', self::class, $encoder);
    }

    public function testImportedClassString()
    {
        $encoder = new PHPEncoder([
            'string.classes' => [\DateTime::class, PHPEncoder::class, FloatEncoder::class, Encoder::class],
            'string.imports' => ['\\' => '', __NAMESPACE__ . '\\' => 'Encoder', Encoder::class => 'EncoderInterface'],
        ]);

        $this->assertSame('DateTime::class', $encoder->encode(\DateTime::class));
        $this->assertSame('Encoder\PHPEncoder::class', $encoder->encode(PHPEncoder::class));
        $this->assertSame('Encoder\Encoder\FloatEncoder::class', $encoder->encode(FloatEncoder::class));
        $this->assertSame('EncoderInterface::class', $encoder->encode(Encoder::class));
        $this->assertSame("'DateTimeInterface'", $encoder->encode(\DateTimeInterface::class));

        $encoder = new PHPEncoder([
            'string.classes' => [self::class],
            'string.imports' => [__NAMESPACE__ . '\\' => ''],
        ]);

        $code = sprintf('namespace ' . __NAMESPACE__ . '; return %s;', $encoder->encode(self::class));
        $this->assertSame('namespace Riimu\Kit\PHPEncoder; return EncodingTest::class;', $code);
        $this->assertSame(self::class, eval($code));
    }

    public function testGMPEncoding()
    {
        if (!\function_exists('gmp_init')) {
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
            'recursion.max' => 5,
        ]);

        $this->expectException(\RuntimeException::class);
        $encoder->encode($foo);
    }
}

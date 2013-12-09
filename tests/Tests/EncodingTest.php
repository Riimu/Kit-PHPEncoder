<?php

namespace Tests;

use Riimu\Kit\PHPEncoder\PHPEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class EncodingTest extends \PHPUnit_Framework_TestCase
{
    public function testNull()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(null, 'null', $encoder);
    }

    public function testBoolean()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(true, 'true', $encoder);
        $this->assertEncode(false, 'false', $encoder);
    }

    public function testInteger()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode(1, '1', $encoder);
        $this->assertEncode(-733, '-733', $encoder);
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
        $encoder = new PHPEncoder();
        $encoder->setFloatPrecision(14);
        $this->assertEncode(1.1, '1.1', $encoder);
        $this->assertEncode(0.1, '0.1', $encoder);
        $this->assertEncode(1.0e+32, '1.0E+32', $encoder);
        $this->assertEncode(1.0e-32, '1.0E-32', $encoder);
        $this->assertEncode(-1.0e+32, '-1.0E+32', $encoder);
        $this->assertEncode(-1.0e-32, '-1.0E-32', $encoder);
    }

    public function testPHPDefaultPrecision()
    {
        $encoder = new PHPEncoder();
        $encoder->setFloatPrecision(false);
        $this->assertEncode(1.0, '1.0', $encoder);
    }

    public function testFloat()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode((float) 0, '0.0', $encoder);
        $this->assertEncode((float) 1, '1.0', $encoder);
        $this->assertEncode((float) -42, '-42.0', $encoder);

        $this->assertEncode(INF, 'INF', $encoder);
        $this->assertEncode(-INF, '-INF', $encoder);
        $this->assertEncode(NAN, 'NAN', $encoder);

        $this->assertEncode(999999999999999, '999999999999999.0', $encoder);
        $this->assertEncode(8888888888888888, '8888888888888888.0', $encoder);

        $encoder->setFloatPrecision(14);
        $float = $encoder->encode(999999999999999);
        $this->assertSame('1.0E+15', $float);
        $this->assertNotEquals(999999999999999, eval("return $float;"));

        $encoder->setBigIntegers(true);
        $this->assertEncode(199999999999999, '199999999999999', $encoder);
        $this->assertEncode(999999999999999, '999999999999999', $encoder);
        $this->assertEncode(1.0e-32, '1.0E-32', $encoder);
    }

    public function testString()
    {
        $encoder = new PHPEncoder();
        $this->assertEncode('1', "'1'", $encoder);
        $this->assertEncode('"\\\'', "'\"\\\\\\''", $encoder);
    }

    public function testNumericArray()
    {
        $e = PHP_EOL;
        $encoder = new PHPEncoder();
        $this->assertEncode([], "[]", $encoder);

        $encoder->setIndent(false);
        $this->assertEncode([1], "[1]", $encoder);
        $this->assertEncode([7, 8, 8, 9], "[7,8,8,9]", $encoder);

        $encoder->setIndent(1, 0);
        $this->assertEncode([1], "[$e 1,$e]", $encoder);
        $this->assertEncode([7, 8, 8, 9], "[$e 7,$e 8,$e 8,$e 9,$e]", $encoder);
    }

    public function testAssociativeArray()
    {
        $e = PHP_EOL;
        $encoder = new PHPEncoder();

        $encoder->setIndent(false);
        $this->assertEncode([1 => 1], "[1=>1]", $encoder);
        $this->assertEncode([1 => 1, 0 => 0], "[1=>1,0=>0]", $encoder);
        $this->assertEncode(['foo' => 'bar', 1 => true], "['foo'=>'bar',1=>true]", $encoder);

        $encoder->setIndent("\t", ' ');
        $this->assertEncode(['foo' => 'bar', 1 => true],
            "[$e \t'foo' => 'bar',$e \t1 => true,$e ]", $encoder);
    }

    public function testAlignedKeys()
    {
        $e = PHP_EOL;
        $encoder = new PHPEncoder();
        $encoder->setIndent(2);
        $encoder->setAlignKeys(true);

        $this->assertEncode(['a' => 1, 'bb' => 2, 'cccc' => 3, 'ddd' => 4, 5],
            "[$e  'a'    => 1,$e  'bb'   => 2,$e  'cccc' => 3,$e  'ddd'  => 4,$e  0      => 5,$e]",
            $encoder);
    }

    public function testMultiLevelArray()
    {
        $e = PHP_EOL;
        $encoder = new PHPEncoder();

        $encoder->setIndent(false);
        $this->assertEncode([1,[2,3],4,[[5,6],[7,8]]], "[1,[2,3],4,[[5,6],[7,8]]]", $encoder);

        $encoder->setIndent(2, 1);
        $this->assertEncode([1,[2,3],4,[[5,6],[7,8]]],
            "[$e   1,$e   [$e     2,$e     3,$e   ],$e   4,$e   [$e     " .
            "[$e       5,$e       6,$e     ],$e     [$e       7,$e       8,$e     ],$e   ],$e ]",
            $encoder);
    }

    public function testMaxDepthSuccess()
    {
        $encoder = new PHPEncoder();
        $encoder->setMaxDepth(3);
        $this->assertNotEmpty($encoder->encode([1,[2,3],4,[[5,6],[7,8]]]));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMaxDepthFailure()
    {
        $encoder = new PHPEncoder();
        $encoder->setMaxDepth(2);
        $encoder->encode([1,[2,3],4,[[5,6],[7,8]]]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidType()
    {
        $encoder = new PHPEncoder();
        $encoder->encode(imagecreate(10, 10));
    }

    private function assertEncode($value, $string, $encoder)
    {
        $output = $encoder->encode($value);
        $this->assertSame($string, $output);

        if (is_double($value) && is_nan($value)) {
            $this->assertTrue(is_nan(eval("return $output;")));
        } else {
            $this->assertSame($value, eval("return $output;"));
        }
    }
}

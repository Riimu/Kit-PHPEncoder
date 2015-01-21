<?php

namespace Riimu\Kit\PHPEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ArrayEncodingTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyArray()
    {
        $this->assertEncode('[]', [], new PHPEncoder());
    }

    public function testInlineArray()
    {
        $encoder = new PHPEncoder();

        $this->assertEncode("[1]", [1], $encoder);
        $this->assertEncode("[7, 8, 8, 9]", [7, 8, 8, 9], $encoder);
    }

    public function testInlineWithMultiLineString()
    {
        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    'foo
bar',
]
RESULT
            ),
            ['foo' . PHP_EOL . 'bar'],
            new PHPEncoder(['string.escape' => false])
        );
    }

    public function testDisabledInline()
    {
        $encoder = new PHPEncoder(['array.inline' => false]);

        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    1,
]
RESULT
            ),
            [1],
            $encoder
        );

        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    'foo',
    'bar',
    'baz',
]
RESULT
            ),
            ['foo', 'bar', 'baz'],
            $encoder
        );
    }

    public function testInlineMaximumLength()
    {
        $encoder = new PHPEncoder(['array.inline' => 14]);

        $this->assertEncode("['0123456789']", ['0123456789'], $encoder);
        $encoder->setOption('array.inline', 13);
        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    '0123456789',
]
RESULT
            ),
            ['0123456789'],
            $encoder
        );
    }

    public function testArraysWithoutWhitespace()
    {
        $encoder = new PHPEncoder(['whitespace' => false]);

        $this->assertEncode("[1=>1]", [1 => 1], $encoder);
        $this->assertEncode("[1=>1,0=>0]", [1 => 1, 0 => 0], $encoder);
        $this->assertEncode("['foo'=>'bar',1=>true]", ['foo' => 'bar', 1 => true], $encoder);
        $this->assertEncode("[1,2,3,4]", [1, 2, 3, 4], $encoder);
    }

    public function testLongArrayNotation()
    {
        $encoder = new PHPEncoder(['array.short' => false]);

        $this->assertEncode('array()', [], $encoder);
        $this->assertEncode('array(1, 2)', [1, 2], $encoder);
        $this->assertEncode(
            $this->format(
                <<<'RESULT'
array(
    'foo' => 'a',
    'bar' => 'b',
)
RESULT
            ),
            ['foo' => 'a', 'bar' => 'b'],
            $encoder
        );

        $encoder->setOption('whitespace', false);
        $this->assertEncode("array('foo'=>'a','bar'=>'b')", ['foo' => 'a', 'bar' => 'b'], $encoder);
    }

    public function testWhitespace()
    {
        $this->assertEncode(
            "[\n1 => 1,\n0 => 0,\n]",
            [1 => 1, 0 => 0],
            new PHPEncoder(['array.eol' => "\n", 'array.indent' => 0])
        );

        $this->assertEncode(
            "[\r1 => 1,\r0 => 0,\r]",
            [1 => 1, 0 => 0],
            new PHPEncoder(['array.eol' => "\r", 'array.indent' => 0])
        );

        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
   'foo' => 'bar',
   1 => true,
 ]
RESULT
            ),
            ['foo' => 'bar', 1 => true],
            new PHPEncoder(['array.base' => 1, 'array.indent' => 2])
        );

        $this->assertEncode(
            $this->format(
                <<<RESULT
[
\t\t'foo' => 'bar',
\t\t1 => true,
\t]
RESULT
            ),
            ['foo' => 'bar', 1 => true],
            new PHPEncoder([
                'array.base' => "\t",
                'array.indent' => "\t",
            ])
        );
    }

    public function testMultiLevelArray()
    {
        $this->assertEncode(
            "[1,[2,3],4,[[5,6],[7,8]]]",
            [1, [2, 3], 4, [[5, 6], [7, 8]]],
            new PHPEncoder(['whitespace' => false])
        );

        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    1,
    [
        2,
        3,
    ],
    4,
    [
        [
            5,
            6,
        ],
        [
            7,
            8,
        ],
    ],
]
RESULT
            ),
            [1, [2, 3], 4, [[5, 6], [7, 8]]],
            new PHPEncoder(['array.inline' => false])
        );

        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    'foo' => [1, 2],
    'bar' => [3, 4],
]
RESULT
            ),
            ['foo' => [1, 2], 'bar' => [3, 4]],
            new PHPEncoder()
        );

        $this->assertEncode("[[1,2],[3,4]]", [[1, 2], [3, 4]], new PHPEncoder(['whitespace' => false]));
    }

    public function testNotOmittingKeys()
    {
        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    0 => 7,
    1 => 8,
    2 => 8,
    3 => 9,
]
RESULT
            ),
            [7, 8, 8, 9],
            new PHPEncoder(['array.omit' => false])
        );
    }

    public function testAlignedKeys()
    {
        $encoder = new PHPEncoder(['array.align' => true]);

        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    'a'    => 1,
    'bb'   => 2,
    'cccc' => 3,
    'ddd'  => 4,
    0      => 5,
]
RESULT
            ),
            ['a' => 1, 'bb' => 2, 'cccc' => 3, 'ddd' => 4, 5],
            $encoder
        );

        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    0  => 'a',
    10 => 'b',
]
RESULT
            ),
            [0 => 'a', 10 => 'b'],
            $encoder
        );
    }

    public function testAlignedArrayOmitsDefaultKeys()
    {
        $encoder = new PHPEncoder(['array.align' => true, 'array.inline' => false]);
        $input = [0 => 'The Doctor', 1 => 'Martha Jones', 2 => 'Rose Tyler', 3 => 'Clara'];

        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    'The Doctor',
    'Martha Jones',
    'Rose Tyler',
    'Clara',
]
RESULT
            ),
            $input,
            $encoder
        );

        $encoder->setOption('array.omit', false);
        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    0 => 'The Doctor',
    1 => 'Martha Jones',
    2 => 'Rose Tyler',
    3 => 'Clara',
]
RESULT
            ),
            $input,
            $encoder
        );
    }

    public function testInlineOnAlignedArray()
    {
        $this->assertEncode(
            "['The Doctor', 'Martha Jones', 'Rose Tyler', 'Clara']",
            [0 => 'The Doctor', 1 => 'Martha Jones', 2 => 'Rose Tyler', 3 => 'Clara'],
            new PHPEncoder(['array.omit' => true, 'array.align' => true])
        );
    }

    /**
     * Formats system dependent line endings to correct format.
     * @param string $string String to format
     * @param string|boolean $eol Line ending to use or false for system default
     * @return string The text formatted using correct line endings
     */
    private function format($string, $eol = false)
    {
        if ($eol === false) {
            $eol = PHP_EOL;
        }

        return preg_replace('/\r\n|\r|\n/', $eol, $string);
    }

    /**
     * Assert that expected code is generated (and it results in original value)
     * @param string $expected Expected generated code
     * @param mixed $value Original value to encode
     * @param PHPEncoder $encoder Encoder used to encode the value
     * @param null $initial
     */
    private function assertEncode($expected, $value, PHPEncoder $encoder, $initial = null)
    {
        $output = $encoder->encode(func_num_args() < 4 ? $value : $initial);
        $this->assertSame($expected, $output);

        if (is_double($value) && is_nan($value)) {
            $this->assertTrue(is_nan(eval("return $output;")));
        } else {
            $this->assertSame($value, eval("return $output;"));
        }
    }
}

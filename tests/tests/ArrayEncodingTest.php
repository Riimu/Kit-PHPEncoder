<?php

namespace Riimu\Kit\PHPEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ArrayEncodingTest extends EncodingTestCase
{
    public function testEmptyArray()
    {
        $this->assertEncode('[]', []);
    }

    public function testInlineArray()
    {
        $this->assertEncode('[1]', [1]);
        $this->assertEncode('[7, 8, 8, 9]', [7, 8, 8, 9]);
    }

    public function testOmittedKeySkips()
    {
        $this->assertEncode(
            $this->format(
                <<<'RESULT'
[
    1 => 1,
    2,
    10 => 10,
    3 => 3,
    4 => 4,
    11,
    12,
    13,
    15 => 15,
]
RESULT
            ),
            [1 => 1, 2 => 2, 10 => 10, 3 => 3, 4 => 4, 11 => 11, 12 => 12, 13 => 13, 15 => 15]
        );
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
            ['string.escape' => false]
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

        $this->assertEncode('[1=>1]', [1 => 1], $encoder);
        $this->assertEncode('[1=>1,0=>0]', [1 => 1, 0 => 0], $encoder);
        $this->assertEncode("['foo'=>'bar',1=>true]", ['foo' => 'bar', 1 => true], $encoder);
        $this->assertEncode('[1,2,3,4]', [1, 2, 3, 4], $encoder);
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
            [
                'array.eol'    => "\n",
                'array.indent' => 0,
            ]
        );

        $this->assertEncode(
            "[\r1 => 1,\r0 => 0,\r]",
            [1 => 1, 0 => 0],
            [
                'array.eol'    => "\r",
                'array.indent' => 0,
            ]
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
            [
                'array.base'   => 1,
                'array.indent' => 2,
            ]
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
            [
                'array.base'   => "\t",
                'array.indent' => "\t",
            ]
        );
    }

    public function testMultiLevelArray()
    {
        $this->assertEncode(
            '[1,[2,3],4,[[5,6],[7,8]]]',
            [1, [2, 3], 4, [[5, 6], [7, 8]]],
            ['whitespace' => false]
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
            ['array.inline' => false]
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
            ['foo' => [1, 2], 'bar' => [3, 4]]
        );

        $this->assertEncode('[[1,2],[3,4]]', [[1, 2], [3, 4]], ['whitespace' => false]);
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
            ['array.omit' => false]
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
        $input = ['The Doctor', 'Martha Jones', 'Rose Tyler', 'Clara'];

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
            ['The Doctor', 'Martha Jones', 'Rose Tyler', 'Clara'],
            ['array.omit' => true, 'array.align' => true]
        );
    }

    /**
     * Formats system dependent line endings to correct format.
     * @param string $string String to format
     * @param string|bool $eol Line ending to use or false for system default
     * @return string The text formatted using correct line endings
     */
    private function format($string, $eol = false)
    {
        if ($eol === false) {
            $eol = PHP_EOL;
        }

        return preg_replace('/\r\n|\r|\n/', $eol, $string);
    }
}

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
        $this->assertEncode([], '[]', new PHPEncoder());
        $this->assertEncode([], 'array()', new PHPEncoder(['array.short' => false]));
    }

    public function testArrayWithoutWhitespace()
    {
        $this->assertEncode([1, 2], '[1,2]', new PHPEncoder(['whitespace' => false]));
        $this->assertEncode([1, 2], 'array(1,2)', new PHPEncoder([
            'whitespace' => false,
            'array.short' => false
        ]));
        $this->assertEncode(['foo'=>'bar','baz'=>'foo'], "['foo'=>'bar','baz'=>'foo']", new PHPEncoder(['whitespace' => false]));
    }

    public function testNumericArray()
    {
        $e = PHP_EOL;
        $encoder = new PHPEncoder(['array.indent' => 1, 'array.inline' => false]);
        $this->assertEncode([1], "[$e 1,$e]", $encoder);
        $this->assertEncode([7, 8, 8, 9], "[$e 7,$e 8,$e 8,$e 9,$e]", $encoder);
        $this->assertEncode([1], "array($e 1,$e)", new PHPEncoder([
            'array.indent' => 1,
            'array.short' => false,
            'array.inline' => false
        ]));
    }

    public function testInlineArray()
    {
        $encoder = new PHPEncoder(['array.indent' => 1]);
        $this->assertEncode([1], "[1]", $encoder);
        $this->assertEncode([7, 8, 8, 9], "[7, 8, 8, 9]", $encoder);
        $this->assertEncode([1, 2], "array(1, 2)", new PHPEncoder([
            'array.indent' => 1,
            'array.short' => false,
        ]));
    }

    public function testBadInline()
    {
        $encoder = new PHPEncoder(['array.indent' => 1, 'array.eol' => "\n", 'string.escape' => false]);
        $this->assertEncode(["foo\nbar"], "[\n 'foo\nbar',\n]", $encoder);
    }

    public function testNoInline()
    {
        $encoder = new PHPEncoder(['array.indent' => 1, 'array.inline' => false, 'array.eol' => "\n"]);
        $this->assertEncode([1], "[\n 1,\n]", $encoder);
        $this->assertEncode([7, 8, 8, 9], "[\n 7,\n 8,\n 8,\n 9,\n]", $encoder);

        $encoder->setOption('array.short', false);
        $this->assertEncode([1, 2], "array(\n 1,\n 2,\n)", $encoder);
    }

    public function testTooLongForInline()
    {
        $encoder = new PHPEncoder(['array.indent'  => 1, 'array.inline' => 14, 'array.eol' => "\n"]);
        $this->assertEncode(['0123456789'], "['0123456789']", $encoder);

        $encoder->setOption('array.inline', 13);
        $this->assertEncode(['0123456789'], "[\n '0123456789',\n]", $encoder);
    }

    public function testAssociativeArray()
    {
        $encoder = new PHPEncoder(['whitespace' => false]);
        $this->assertEncode([1 => 1], "[1=>1]", $encoder);
        $this->assertEncode([1 => 1, 0 => 0], "[1=>1,0=>0]", $encoder);
        $this->assertEncode(['foo' => 'bar', 1 => true], "['foo'=>'bar',1=>true]", $encoder);

        $encoder->setOption('whitespace', true);
        $encoder->setOption('array.base', ' ');
        $encoder->setOption('array.indent', "\t");

        $e = PHP_EOL;
        $this->assertEncode(
            ['foo' => 'bar', 1 => true],
            "[$e \t'foo' => 'bar',$e \t1 => true,$e ]",
            $encoder
        );
    }

    public function testIndent()
    {
        $this->assertEncode(
            ['foo' => 'bar', 1 => true],
            "[\n \t'foo' => 'bar',\n \t1 => true,\n ]",
            new PHPEncoder([
                'array.indent' => "\t",
                'array.base' => ' ',
                'array.eol' => "\n",
            ])
        );
    }

    public function testAlignedKeys()
    {
        $this->assertEncode(
            ['a' => 1, 'bb' => 2, 'cccc' => 3, 'ddd' => 4, 5],
            "[\n  'a'    => 1,\n  'bb'   => 2,\n  'cccc' => 3,\n  'ddd'  => 4,\n  0      => 5,\n]",
            new PHPEncoder([
                'array.indent' => 2,
                'array.align' => true,
                'array.eol' => "\n",
            ])
        );
    }

    public function testMultiLevelArray()
    {
        $this->assertEncode(
            [1, [2, 3], 4, [[5, 6], [7, 8]]],
            "[1,[2,3],4,[[5,6],[7,8]]]",
            new PHPEncoder(['whitespace' => false])
        );

        $e = PHP_EOL;
        $this->assertEncode(
            [1, [2, 3], 4, [[5, 6], [7, 8]]],
            "[$e   1,$e   [$e     2,$e     3,$e   ],$e   4,$e   [$e     " .
            "[$e       5,$e       6,$e     ],$e     [$e       7,$e       8,$e     ],$e   ],$e ]",
            new PHPEncoder(['array.indent' => 2, 'array.base' => 1, 'array.inline' => false])
        );

        $this->assertEncode(
            ['foo' => [1, 2], 'bar' => [3, 4]],
            "[$e 'foo' => [1, 2],$e 'bar' => [3, 4],$e]",
            new PHPEncoder(['array.indent' => 1])
        );
    }

    public function testNotOmittingKeys()
    {
        $this->assertEncode(
            [7, 8, 8, 9],
            "[\n 0 => 7,\n 1 => 8,\n 2 => 8,\n 3 => 9,\n]",
            new PHPEncoder([
                'array.omit' => false,
                'array.indent' => 1,
                'array.eol' => "\n",
            ])
        );
    }

    public function testAlignedArrayOmitsDefaultKeys()
    {
        $encoder = new PHPEncoder(['array.omit' => true, 'array.align' => true, 'array.inline' => false]);

        $input  = [0 => 'The Doctor', 1 => 'Martha Jones', 2 => 'Rose Tyler', 3 => 'Clara'];
        $output = $encoder->encode($input);

        $expected = <<<EOP
[
    'The Doctor',
    'Martha Jones',
    'Rose Tyler',
    'Clara',
]
EOP;

        $this->assertEquals(
            $expected,
            $output,
            'Encoding did not produce aligned output with redundant numeric keys omitted.'
        );
    }

    public function testInlineOnAlignedArray()
    {
        $encoder = new PHPEncoder(['array.omit' => true, 'array.align' => true]);
        $input  = [0 => 'The Doctor', 1 => 'Martha Jones', 2 => 'Rose Tyler', 3 => 'Clara'];
        $output = $encoder->encode($input);

        $this->assertEquals(
            "['The Doctor', 'Martha Jones', 'Rose Tyler', 'Clara']",
            $output,
            'Encoding did not produce aligned output with redundant numeric keys omitted.'
        );
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

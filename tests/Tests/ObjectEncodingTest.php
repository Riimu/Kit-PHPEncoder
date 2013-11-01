<?php

namespace Tests;

use Riimu\Kit\PHPEncoder\PHPEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ObjectEncodingTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $encoder = new PHPEncoder();
        $encoder->setObjectFlags(PHPEncoder::OBJECT_SERIALIZE);
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $obj->baz = true;

        $this->assertSame(
            'unserialize(\'O:8:"stdClass":2:{s:3:"foo";s:3:"bar";s:3:"baz";b:1;}\')',
            $encoder->encode($obj));
        $this->assertEquals($obj, eval('return ' . $encoder->encode($obj) . ';'));
    }

    public function testStringConversion()
    {
        $encoder = new PHPEncoder();
        $encoder->setObjectFlags(PHPEncoder::OBJECT_STRING);
        $mock = $this->getMock('testMockObject', ['__toString']);
        $mock->expects($this->once())->method('__toString')->will($this->returnValue('Mocked'));
        $this->assertSame('Mocked', eval('return ' . $encoder->encode($mock) . ';'));
    }

    public function testPHPValue()
    {
        $encoder = new PHPEncoder();
        $mock = $this->getMock('testMockObjectWithPHPValue', ['toPHPValue']);
        $mock->expects($this->once())->method('toPHPValue')->will($this->returnValue([]));
        $this->assertSame([], eval('return ' . $encoder->encode($mock) . ';'));
    }

    public function testPHP()
    {
        $encoder = new PHPEncoder();
        $mock = $this->getMock('testMockObjectWithPHP', ['toPHP']);
        $mock->expects($this->once())->method('toPHP')->will($this->returnValue('"Mocked"'));
        $this->assertSame('"Mocked"', $encoder->encode($mock));
    }

    public function testPropertiesArray()
    {
        $e = PHP_EOL;
        $encoder = new PHPEncoder();
        $obj = new \testMockObject();

        $encoder->setIndent(false);
        $encoder->setObjectFlags(PHPEncoder::OBJECT_PROPERTIES);
        $this->assertSame("['baz'=>'C']", $encoder->encode($obj));

        $encoder->setIndent(1);
        $encoder->setObjectFlags(PHPEncoder::OBJECT_PROPERTIES | PHPEncoder::OBJECT_CAST);
        $this->assertSame("(object) [$e 'baz' => 'C',$e]", $encoder->encode($obj));

        $std = new \stdClass();
        $std->baz = 'C';
        $this->assertEquals($std, eval('return ' . $encoder->encode($obj) . ';'));
    }

    public function testIteratingArray()
    {
        $array = ['foo' => 'bar', [1, 2], 3, 10 => 1337, 11 => 7, 6 => 6];
        $encoder = new PHPEncoder();
        $encoder->setIndent(false);
        $encoder->setObjectFlags(PHPEncoder::OBJECT_ITERATE);
        $this->assertSame("['foo'=>'bar',[1,2],3,10=>1337,7,6=>6]", $encoder->encode(new \ArrayObject($array)));
        $this->assertSame($array, eval('return ' . $encoder->encode(new \ArrayObject($array)) . ';'));
    }

    public function testArrayCasting()
    {
        $encoder = new PHPEncoder();
        $encoder->setIndent(false);
        $encoder->setObjectFlags(PHPEncoder::OBJECT_ARRAY);
        $obj = new \stdClass();
        $this->assertSame("[]", $encoder->encode($obj));
        $mock = new \testMockObject();
        $this->assertSame("['\0testMockObject\0foo'=>'A','\0*\0bar'=>'B','baz'=>'C']", $encoder->encode($mock));
        $this->assertSame(["\0testMockObject\0foo"=>'A',"\0*\0bar"=>'B','baz'=>'C'],
            eval('return ' . $encoder->encode($mock) . ';'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoObjectHandling()
    {
        $encoder = new PHPEncoder();
        $encoder->setObjectFlags(0);
        $encoder->encode(new \stdClass());
    }
}

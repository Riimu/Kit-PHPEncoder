<?php

require_once __DIR__ . '/../src/PHPEncoder.php';

class TestMockObject
{
    private $foo = 'A';
    protected $bar = 'B';
    public $baz = 'C';
    public function __toString()
    {

    }
}

class ExtendsTestMockObject extends TestMockObject
{
    private $fooC = 'D';
    public $bazC = 'E';
}

class TestMockObjectWithPHPValue extends TestMockObject
{
    public function toPHPValue()
    {

    }
}

class TestMockObjectWithPHP extends TestMockObjectWithPHPValue
{
    public function toPHP()
    {

    }
}

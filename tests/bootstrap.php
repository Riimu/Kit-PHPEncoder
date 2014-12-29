<?php

require __DIR__ . '/../src/autoload.php';

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

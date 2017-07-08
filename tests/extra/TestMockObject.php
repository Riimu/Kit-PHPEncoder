<?php

namespace Riimu\Kit\PHPEncoder\Test;

class TestMockObject
{
    private $foo = 'A';
    protected $bar = 'B';
    public $baz = 'C';

    public function __toString()
    {
        return '';
    }
}

<?php

require_once __DIR__ . '/../src/PHPEncoder.php';

class testMockObject {
    private   $foo = 'A';
    protected $bar = 'B';
    public    $baz = 'C';
    public function __toString() { }
}
class testMockObjectWithPHPValue extends testMockObject {
    public function toPHPValue() { }
}
class testMockObjectWithPHP extends testMockObjectWithPHPValue {
    public function toPHP() { }
}

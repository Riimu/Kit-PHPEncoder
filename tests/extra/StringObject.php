<?php

namespace Test;

class StringObject
{
    public function toPHPValue()
    {
        return 'Wrong';
    }

    public function __toString()
    {
        return 'Stringed';
    }
}

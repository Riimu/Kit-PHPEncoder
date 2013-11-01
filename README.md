# PHP code generating library for variables #

While PHP provides `json_encode()` to natively convert variables into JSON,
there is no equivalent function for generating PHP code from variables. This
library provides similar functionality, except that the generated code is in
PHP.

It is good to note, however, that in many cases there are alternatives that are
better than dynamically generating PHP. Configuration files stored in JSON or
other configuration file formats usually offer better portability and caching
them using something like memcached mitigates the performance hit from parsing
those formats.

But, for those rare cases, like when memcached is not available or you just
need a way to save database configuration into a file, the PHP Encoder library
can help you in creating those files.

API documentation for the classes can be generated using apigen.

## Usage ##

Using the library is mostly quite simple. In most cases, there is one method
`encode()` that you need to concern yourself about, which simply produces the
PHP code for the provided variable.

For example:

```php
<?php
$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.1]]);

/* The above outputs:
[
    'foo' => 'bar',
    [
        1,
        true,
        false,
        null,
        1.1,
    ],
]*/

$encoder->setIndent(false);
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.1]]);

// The above outputs: ['foo'=>'bar',[1,true,false,null,1.1]]
```

## Notes and limitiations ##

  * The encoder does not detect variable references. Each reference is traversed
    as if it was any other variable.
  * In order to avoid infinite loops on recursive array, maximum depth can be
    set using `setMaxDepth($depth)`. This defaults to 20. Setting it to false
    disables the limit.
  * The library does not handle resources. If resource is encountered, the
    library will throw an exception.
  * When encoding arrays, the output will omit any unnecessary numeric keys.
    Thus [0=>'a',1=>'b'] will output['a','b']. However, the order of elements
    is maintained. Thus, [1=>'b',0=>'a'] will ouput [1=>'b',0=>'a']. When
    `setAlignKeys($state)` is set to true, keys will not be omitted.
  * The library uses PHP_EOL as the end of line character (depends on system)
  * Precision may be lost when using float values. This is simply due to the
    fact that binary to decimal conversion may not be accurate with fractions.
  * Some accuracy may be gained by setting `setBigIntegers($state)` to true,
    which attempts to encode floats as integers if they do not have fractions.
  * Float type is maintained using `(float)` cast when the number would
    otherwise be parsed as an integer.
  * Due to being PHP 5.4 library, the outputted arrays will use short array
    notation, i.e. "[" and "]".

## Formatting ##

There are couple of ways to change how the output is formatted, which, in
particular, affects how arrays are outputted:

  * `setIndent($indent, $baseIndent = 0)` allows you to define how arrays are
    indented. $indent defines how much each level is indented and $baseIndent
    defines how much should the base indentation level be. Both arguments may
    be strings or integers. If string is provided, then that string is used as
    is, if number is provided, then that many spaces is used. Additionally, you
    may provide `false` as the argument to remove all unnecessary whitespace
    from the provided code.
  * `setAlignKeys($state)` allows you to align array keys in a column. In other
    words, if the array keys have different lengths, then the `=>` sign is
    aligned in the same place on each row. Enabling this setting if indent is
    set to false does not make much sense as there are no line breaks.

## Objects ##

There is no object notation in PHP similar to JSON. Because of this, this library
does not assume that there is one solution for every possible case for handling
objects. In order to provide a good solution, the library provides following
possible approaches.

First and foremost, each encountered object is checked for method 'toPHP' or
'toPHPValue'. If the method 'toPHP' is detected, it's called and the return
value is inserted into the outputted code directly. The difference to
'toPHPValue' is, that value returned by 'toPHPValue' is encoded into PHP by
the library, instead of being inserted directly into code.

If neither of these methods exist, then default object handling is used. This
behavior can be changed by using the `setObjectFlags($flags)` method. Following
flags exist:

  * `PHPEncoder::OBJECT_STRING` converts all objects into string
  * `PHPEncoder::OBJECT_SERIALIZE` serializes objects and wraps them around `unserialize()`
  * `PHPEncoder::OBJECT_ARRAY` casts objects into array
  * `PHPEncoder::OBJECT_ITERATE` iterates over object to convert into array
  * `PHPEncoder::OBJECT_PROPERTIES` iterates over all public properties of the
    object to turn into array.

When using, either 'OBJECT_ARRAY', 'OBJECT_ITERATE' or 'OBJECT_PROPERTIES', you
may combine them with the flag `PHPEncoder::OBJECT_CAST` in order to add an
`(object)` cast in front of the array, which will cause the array to be turned
into stdClass instance on runtime.

The default object handing is `self::OBJECT_PROPERTIES | self::OBJECT_CAST`, in
order to replicate `json_encode()` functionality.

## Credits ##

This library is copyright 2013 to Riikka Kalliomäki
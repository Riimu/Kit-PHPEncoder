# PHP value encoder #

This library encodes values as PHP code similar to how `json_encode()` encodes
values as JSON. The purpose of this library is to make it easier to write data
into files directly as PHP instead of using auxiliary formats such as ini files
or JSON.

A common use case could be writing database configuration into file based on
what user entered in a installation script. They can't exactly be stored in
a database and using formats like JSON for just database configuration may be
an overkill.

It is good to note, however, that storing dynamic data in PHP files is not
always the best idea. For larger configuration files, it's usually advisable to
use other formats such as JSON, because these tend to be easier to edit and
errors in those configurations won't cause PHP parse errors. Data that can be
edited though admin interfaces is also usually best stored in a database.

Encoding values as PHP files may be a good idea, when you have bunch of static
data that is often required but doesn't really change that much, such as
database configuration values.

API documentation for the classes can be generated using apigen.

## Usage ##

Using the library is mostly quite simple. In most cases, there is one method
`encode()` that you need to concern yourself about, which simply produces the
PHP code for the provided value.

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

An example of common use case could be like:

```PHP
// Configuration script asks user for database details
$config = [
    'database' => 'mydb',
    'hostname' => 'localhost',
    'username' => 'dbuser',
    'password' => 'dbpass'
];

// Store the configuration into a file
$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
file_put_contents('dbconf.php', '<?php return ' . $encoder->encode($config) . ';');

// Load the config from the file
$dbconf = require 'dbconf.php';
```

## Notes and limitiations ##

  * The encoder does not detect variable references. Each reference is traversed
    as if it was any other variable.
  * In order to avoid infinite loops on recursive arrays or objects, maximum
    encoding depth can be set using `setMaxDepth($depth)`. This defaults to 20.
    Setting it to false disables the limit.
  * The library does not handle resources. If resource is encountered, the
    library will throw an exception.
  * When encoding arrays, the output will omit any unnecessary numeric keys.
    Thus [0=>'a',1=>'b'] will output['a','b']. However, the order of elements
    is preserved. Thus, [1=>'b',0=>'a'] will ouput [1=>'b',0=>'a']. When
    `setAlignKeys($state)` is set to true, keys will not be omitted.
  * The library uses PHP_EOL as the end of line character (depends on system)
  * Special care should be taken when encoding objects and floats. See below
    for notes about encoding these types.
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
  * `setEscapeStrings($state)` defaults to true, which encodes strings using
    escape sequences whenever they contain ascii control characters or bytes
    with value of 127 or greater. This helps to ensure the string contents
    remain intact when the data is transferred or stored in a file.

## Floats ##

Encoding floating point values can be tricky due to problems related to
precision. Mostly the issue is with meeting expectations vs. maintaining
accuracy.

The default behavior of the encoding library is to preserve accuracy and to
preserve type. This means that, for example, if you have a floating point value
of "1", if will be written as "1.0" in order to preserve the type. In addition,
the library will write 17 significant digits of float as opposed to 14, which is
default in many PHP installations.

These behaviors can be changed using `setFloatPrecision($precision)` which
can be used to change the number of significant digits in floats (or use false
for PHP default) and `setBigIntegers($state)` which can be enabled to write any
floating point value that does not have fractions as an integer representation
(i.e. without scientific notation and the ".0" part used to preserve type).

Smaller precision may sometimes be more desirable, if higher precision is not
required, as high precision may lead to unexpected outputs. For example,
encoding the value of "1.1" using 17 digits of precision will produce
"1.1000000000000001". This is not a flaw in the library, but rather a side
effect of how floating point values work.

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

This library is copyright 2013 - 2014 to Riikka Kalliomäki

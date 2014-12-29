# PHP Variable Exporting #

PHPEncoder is a PHP library for exporting variables into PHP code similar to the
built in function `var_export()`. Compared to the built in function, this 
library provides a more customizable alternative that makes it easier to 
dynamically create configuration files and cache files in the format you desire.

The purpose of this library is to address some of the problems with
`var_export()`. For example, there are no options to control the amount of
whitespace in the output, which sometimes results in too verbose output. This
library also offers more options in how to export objects.

The large number of customization options in this library allows you to create
code that fits your purposes. You can create very compact code, when you need to
limit the size of the output, or you can create code in the style that actually
fits in any of your dynamically generated PHP files.

The API documentation, which can be generated using Apigen, can be read online
at: http://kit.riimu.net/api/phpencoder/

[![Build Status](https://travis-ci.org/Riimu/Kit-PHPEncoder.svg?branch=master)](https://travis-ci.org/Riimu/Kit-PHPEncoder)
[![Coverage Status](https://coveralls.io/repos/Riimu/Kit-PHPEncoder/badge.png?branch=master)](https://coveralls.io/r/Riimu/Kit-PHPEncoder?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Riimu/Kit-PHPEncoder/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Riimu/Kit-PHPEncoder/?branch=master)

## Requirements ##

In order to use this library, the following requirements must be met:

  * PHP version 5.4

## Installation ##

This library can be installed via [Composer](http://getcomposer.org/). To do
this, download `composer.phar` and require this library as dependency. For
example:

```
$ php -r "readfile('https://getcomposer.org/installer');" | php
$ php composer.phar require riimu/kit-phpencoder:2.*
```

Alternatively, you add the dependency to your `composer.json` and run `composer
install`. For example:

```json
{
    "require": {
        "riimu/kit-phpencoder": "2.*"
    }
}
```

If you installed the library via Composer. You can load the library by including
the `vendor/autoload.php` file. If you do not want to use Composer, you can
download the latest release and include the `src/autoload.php` file instead.

## Usage ##

The most relevant method provided by this library is the `encode()` method
provided by `PHPEncoder`. The method takes any value as an argument and returns
the PHP code representation for that value.

For example:

```php
<?php

require 'vendor/autoload.php';
$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.0]]);
```

This would create the following output:

``` 
[
    'foo' => 'bar',
    [1, true, false, null, 1.0],
]
```

Of course, the most important feature of this library is the ability to
customize the created the PHP code. As the second argument, the `encode()`
method takes an array of options, which can be used to customise the returned
PHP code. For example:

```php
<?php

require 'vendor/autoload.php';
$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
echo $encoder->encode(['foo' => 'bar', [1, true, false, null, 1.0]], [
    'array.inline' => false,
    'array.omit' => false,
    'array.indent' => 2,
    'boolean.capitalize' => true,
    'null.capitalize' => true,
]);
```

This would create the following output:

```
[
  'foo' => 'bar',
  0 => [
    0 => 1,
    1 => TRUE,
    2 => FALSE,
    3 => NULL,
    4 => 1.0,
  ],
]
```

### Options ###

Encoding options allow you to customize the output of the `encode()` method. It
is possible to set these options in three different ways:

  * Options can be provided as an array to the `PHPEncoder` constructor.
  * Option values can be set via the `setOption()` method.
  * Options can be passed as an array as the second argument to the `encode()` method.
  
Note that options passed to the `encode()` method are only temporary and do not
apply to following calls.

### List of Options ###

  * **whitespace** : &lt;boolean&gt; (true)  
    When set to false, generation of all extra whitespace is disabled.
    
  * **null.capitalize** : &lt;boolean&gt; (false)  
    When set to true, nulls are written in upper case instead of lower case. 
 
  * **boolean.capitalize** : &lt;boolean&gt; (false)  
    When set to true, true and false are written in upper case instead of lower case.
    
  * **float.integers** : &lt;boolean&gt; (false)  
    When set to true, float values that represent integers are encoded as
    integers instead of floats (e.g. '2.0' will be simply written as '2').
    
  * **float.precision** : &lt;integer|false&gt; (17)  
    Maximum number of decimals in floats. If set to false, the PHP ini setting
    'precision' is used instead. Note that due to the way floating point values
    work, more than 17 digits does not provide any additional precision.
    
  * **string.escape** : &lt;boolean&gt; (true)  
    When set to true, all strings containing bytes outside the 32-126 ASCII
    range will be written with double quotes and the characters outside the
    range will be escaped.
    
  * **array.short** : &lt;boolean&gt; (true)  
    When set to true, arrays are enclosed using square brackets `[]` instead of
    the `array()` notation.
    
  * **array.base** : &lt;integer|string&gt; (0)  
    Base indentation for arrays as number of spaces or as a string. Useful, when
    generating code into files with specific level of indentation.
    
  * **array.indent** : &lt;integer|string&gt; (0)  
    Amount of indentation for single level of indentation as number of spaces or
    a string.
    
  * **array.align** : &lt;boolean&gt; (false)  
    When set to true, array assignment operators `=>` are aligned to the same
    column using spaces.
    
  * **array.inline** : &lt;boolean|integer&gt; (70)  
    When set to true, any array that can be written without any array keys will
    be written in a single line. If an integer is provided instead, the array
    will be written as a single line only if it does not exceed that number of
    characters.
     
  * **array.omit** : &lt;boolean&gt; (true)  
    When set to true, any redundant array key will not be written. (e.g. the
    array `[0 => 'a', 1 => 'b']` would be written just as `['a', 'b']`)
  
  * **array.eol** : &lt;string|false&gt; (false)  
    The end of line character used by array output. When set to false, the
    default `PHP_EOL` will be used instead.
    
  * **object.method** : &lt;boolean&gt; (true)  
    When set to true, any encoded object will be checked for methods `toPHP()`
    and `toPHPValue()`. If the method `toPHP()` exists, the returned string will
    be used as the PHP code representation of the object. If the method
    `toPHPValue()` exists instead, the returned value will be encoded as PHP and
    used as the code representation of the object.
    
  * **object.format** : &lt;string&gt; ('vars')  
    Default object encoding format. The possible values are:
    
     * `string` casts the object to string and then encodes that string as PHP.
     * `serialize` serializes the object and wraps it with `unseriealize()`
     * `export` mimics the `var_export()` object representation
     * `array` casts the object to an array and encodes that array 
     * `vars` turns object into an array using `get_object_vars()`
     * `iterate` turns the object into an array by iterating over it with `foreach`
    
  * **object.cast** : &lt;boolean&gt; (true)
    Whether to add an `(object)` cast in front of arrays generated from objects
    when using `vars`, `array` or `iterate` formats.
    
  * **recursion.detect** : &lt;boolean&gt; (true)  
    When set to true, the encoder will attempt to detect circular references in
    arrays and objects to avoid infinite loops.
    
  * **recursion.ignore** : &lt;boolean&gt; (false)  
    When set to true, any circular reference will be replaced with null instead
    of throwing an exception.
    
  * **recursion.max** : &lt;integer|false&gt; (false)  
    Maximum number of levels when encoding arrays and objects. Exception is
    thrown when the maximum is exceeded. Set to false to have no limit.

## Credits ##

This library is copyright 2013 - 2014 to Riikka Kalliomäki.

See LICENSE for license and copying information.

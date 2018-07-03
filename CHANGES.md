# Changelog #

## v2.4.0 (2018-07-03) ##

  * Added `string.classes` option, which allows to define an array of classes or
    namespaces to encode using the `::class` format, when encountered as strings
  * Added `string.imports` options, which allows to define the used imports to write
    the `::class` format strings using shorter imported notation
  * Support for HHVM has been dropped, as HHVM no longer aims for PHP compatibility
  * Added travis builds for PHP 7.2
  * Change some rules in the used coding standard 

## v2.3.0 (2017-07-15) ##

  * Added `string.utf8` option which causes the string encoder to escape all
    valid multibyte UTF-8 characters using the PHP7 unicode code point syntax.
  * Added `string.binary` option which causes the string encoder to encode all
    non UTF-8 strings using a `base64_encode()`.
  * Added `integer.type` option that accepts values `binary`, `octal`, `decimal`
    or `hexadecimal` which can be used to change the output syntax of integers.
  * Added `hex.capitalize` option that causes all hexadecimal character in
    output to appear in upper case
  * Added `float.export` option that forces float encoder to use `var_export`
    for encoding floating point numbers
  * Float encoder now delegates integer encoding to the integer encoder (to
    allow different integer types).

## v2.2.0 (2017-07-08) ##

  * Increase the minimum PHP version requirement to 5.6
  * Update to latest coding standards
  * Update tests to work with PHPUnit 6
  * Update travis build to test for PHP 7.1

## v2.1.3 (2015-11-08) ##

  * Ensure the tests run on both HHVM and PHP7

## v2.1.2 (2015-08-22) ##

  * The GMP encoder no longer tries to encode classes that extend the GMP class

## v2.1.1 (2015-08-08) ##

  * Fixed object encoder not throwing an exception on some incorrect
    object.format values
  * Fixed coding standards issues around the code and api documentation
  * Improved Travis build process

## v2.1.0 (2015-04-18) ##

  * Encoder options with `null` default value will now be recognized
  * The integer encoder will now add an `(int)` cast in front of integers, if
    their value equals `PHP_INT_MAX * -1 - 1`.
  * If `float.integers` is set to `true`, the float encoder will now only encode
    floats as integers if the value is accurately represented by the float. Set
    the value to `"all"` to restore the previous behavior.
  * The float encoder no longer breaks if the PHP locale uses comma as a decimal
    separator.
  * The float encoder now behaves slightly differently when deciding whether to
    use the exponential float notation or not.
  * The float encoder now uses `serialize_precision` when the option `precision`
    is set to `false`
  * Several methods will now throw an InvalidOptionException if any invalid
    encoder options have been provided

## v2.0.2 (2015-01-21) ##

  * `array.align` will now respect `array.omit` and `array.inline` settings if
    all the keys in the array can be omitted.
  * Clarified documentation on how these settings are intended to work
    together.

## v2.0.1 (2015-01-11) ##

  * Improvements on code quality, documentation and tests

## v2.0.0 (2014-12-29) ##

  * Encoding is now separated into external encoding classes
  * Various options are now set via PHPEncoder::setOption() instead
  * GMP encoding now supports PHP 5.6.0 and later
  * Encoder now detects and throws an exception on recursion
  * Null and boolean capitalization can now be customized
  * It's possible to choose from long or short array notation
  * Encoder now supports inlining simple arrays
  * The end of line character for arrays can now be modified

## v1.5.0 (2014-07-10) ##

  * Added new object conversion flags OBJECT_VARS and OBJECT_SET_STATE.
  * The default object conversion is now OBJECT_VARS | OBJECT_CAST
  * Documentation now acknowledges the existence of var_export

## v1.4.0 (2014-05-31) ##

  * GMP Integer resources are now encoded using 'gmp_init()'
  * The library now correctly disables align keys when whitespace is disabled
  * Code cleanup and documentation fixes

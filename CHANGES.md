# Changelog #

## v2.0.0 (?) ##

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

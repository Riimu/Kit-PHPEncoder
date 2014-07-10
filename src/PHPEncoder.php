<?php

namespace Riimu\Kit\PHPEncoder;

/**
 * PHPEncoder provides a way to convert variables into PHP code.
 *
 * PHPEncoder provides functionality similar to var_export(), but allows more
 * customization and wider range of features. This makes it easier to dynamically
 * generate PHP files such as configuration or cache files when you don't have
 * to worry about producing PHP code from dynamic variables.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class PHPEncoder
{
    /**
     * Current indentation level of the output.
     * @var integer
     */
    private $depth;

    /**
     * Maximum allow indentation level in the output.
     * @var integer|boolean
     */
    private $maxDepth;

    /**
     * Whether to output bigger integers as integers.
     * @var boolean
     */
    private $bigIntegers;

    /**
     * Determines the maximum number of digits in floats.
     * @var integer|boolean
     */
    private $floatPrecision;

    /**
     * Whether to encode strings using escape sequences or not.
     * @var boolean
     */
    private $escapeStrings;

    /**
     * Combination of flags for handling objects.
     * @var integer
     */
    private $objectFlags;

    /**
     * Whether to align keys in the output or not.
     * @var boolean
     */
    private $alignKeys;

    /**
     * Base level of indentation for all code.
     * @var integer|string
     */
    private $baseIndent;

    /**
     * One level of indentation for the code.
     * @var integer|string|false
     */
    private $indent;

    /**
     * Optional whitespace character used for code spacing.
     * @var string
     */
    private $space;

    /**
     * @var integer Flag for converting objects into strings.
     */
    const OBJECT_STRING = 1;

    /**
     * @var integer Flag for serializing encountered objects.
     */
    const OBJECT_SERIALIZE = 2;

    /**
     * @var integer Flag for casting objects into arrays.
     */
    const OBJECT_ARRAY = 4;

    /**
     * @var integer Flag for iterating over object to generate an array.
     */
    const OBJECT_ITERATE = 8;

    /**
     * @var integer Flag to get object's public properties as an array.
     */
    const OBJECT_PROPERTIES = 16;

    /**
     * @var integer Flag to cast any generated object array into object.
     */
    const OBJECT_CAST = 32;

    /**
     * @var integer Flag to get object's variables as an array.
     */
    const OBJECT_VARS = 64;

    /**
     * @var integer Flag to convert objects into __set_state calls.
     */
    const OBJECT_SET_STATE = 128;

    /**
     * Creates a new PHPEncoder instance.
     */
    public function __construct()
    {
        $this->maxDepth = 20;
        $this->bigIntegers = false;
        $this->floatPrecision = 17;
        $this->escapeStrings = true;
        $this->objectFlags = self::OBJECT_VARS | self::OBJECT_CAST;
        $this->alignKeys = false;
        $this->baseIndent = 0;
        $this->indent = 4;
        $this->space = ' ';
    }

    /**
     * Sets the maximum allowed depth for output.
     *
     * If the maximum allowed depth is reached, an exception will be thrown. If
     * false is provided, no limit is used.
     *
     * @param integer|boolean $depth Maximum depth or false for no limit.
     */
    public function setMaxDepth($depth)
    {
        $this->maxDepth = (int) $depth;
    }

    /**
     * Sets whether to output floats without fractions as integers.
     *
     * When enabled, any float that has no fractions, i.e. if
     * round($float) == $float, will be outputted as integer. The ".0" postfix
     * will not be added to floats (which is used to preserve the type) and the
     * number will be written without the use of scientific notation. The
     * default value is false.
     *
     * @param boolean $state True to output big integers, false to not
     */
    public function setBigIntegers($state)
    {
        $this->bigIntegers = (boolean) $state;
    }

    /**
     * Sets the number of significant digits used in floats.
     *
     * This method can be used to change the number of digits in outputted
     * floats. You may also set it to false to use PHP's default value. Most
     * PHP installations default to 14, but this may cause loss of precision in
     * some cases. Because of this, the library defaults to 17, which may
     * generate unexpected output in some cases like "0.1", but will provide
     * more accurate result in general.
     *
     * @param integer|boolean $precision Number of significant digits in floats
     */
    public function setFloatPrecision($precision)
    {
        $this->floatPrecision = $precision === false ? false : max(1, (int) $precision);
    }

    /**
     * Sets whether to encode strings using escape sequences.
     *
     * When enabled, any string which contains control characters or bytes with
     * value beyond 127 will be encoded using string escape sequences. This
     * includes tabs and new line characters, but not spaces. This ensures that
     * string data remains intact when stored or transferred and that the files
     * are treated correctly as text files. This option defaults to true.
     *
     * @param boolean $state True to enable, false to disable.
     */
    public function setEscapeStrings($state)
    {
        $this->escapeStrings = (boolean) $state;
    }

    /**
     * Sets the flags for object handling in output
     * @param integer $flags Object handling flags
     */
    public function setObjectFlags($flags)
    {
        $this->objectFlags = $flags;
    }

    /**
     * Whether to align array keys vertically in output.
     * @param boolean $state True to align, false to not
     */
    public function setAlignKeys($state)
    {
        $this->alignKeys = (bool) $state;
    }

    /**
     * Sets the indentation used in the output.
     *
     * Both arguments may be provided either as a string or an integer. If
     * string is provided, that string is used as is for the indentation.
     * Integer indicates the number of spaces to use. You may also provide
     * false as the first argument, in which case all optional whitespace from
     * the output will be omitted (including new lines and spaces). Disabling
     * whitespace will also disable array key alignment.
     *
     * @param integer|string|boolean $indent Indentation for each level or false for none
     * @param integer|string $base Base level of indentation
     */
    public function setIndent($indent, $base = 0)
    {
        $this->baseIndent = is_string($base) ? $base : (int) $base;

        if ($indent === false) {
            $this->alignKeys = false;
            $this->indent = false;
            $this->space = '';
        } else {
            $this->indent = is_string($indent) ? $indent : (int) $indent;
            $this->space = ' ';
        }
    }

    /**
     * Encodes the given variable as PHP code.
     * @param mixed $variable Value to encode as PHP
     * @return string The value encoded as PHP code
     */
    public function encode($variable)
    {
        $this->depth = 0;
        return $this->encodeValue($variable);
    }

    /**
     * Encodes the variable correctly depending on type.
     * @param mixed $value Value to encode
     * @return string The value encoded as PHP
     * @throws \InvalidArgumentException If the value has unknown argument type
     */
    private function encodeValue($value)
    {
        switch (true) {
            case is_bool($value):
                return $this->encodeBoolean($value);
            case is_int($value):
                return $this->encodeInteger($value);
            case is_float($value):
                return $this->encodeFloat($value);
            case is_string($value):
                return $this->encodeString($value);
            case is_array($value):
                return $this->encodeArray($value);
            case is_object($value):
                return $this->encodeObject($value);
            case is_resource($value):
                return $this->encodeResource($value);
            case $value === null:
                return 'null';
            default:
                throw new \InvalidArgumentException('Cannot encode value type: ' . gettype($value));
        }
    }

    /**
     * Encodes boolean type value.
     * @param boolean $boolean Boolean value to encode
     * @return string Either 'true' or 'false'
     */
    private function encodeBoolean($boolean)
    {
        return $boolean ? 'true' : 'false';
    }

    /**
     * Encodes integer type value.
     * @param integer $integer Integer value to encode
     * @return string A numeric string
     */
    private function encodeInteger($integer)
    {
        return (string) $integer;
    }

    /**
     * Encodes float type value.
     * @param float $float Float value to encode
     * @return string A numeric string
     */
    private function encodeFloat($float)
    {
        if (is_infinite($float) || is_nan($float)) {
            return (string) $float;
        } elseif ($this->bigIntegers && round($float) === $float) {
            return number_format($float, 0, '.', '');
        }

        if ($this->floatPrecision === false) {
            $number = (string) $float;
        } else {
            $previous = ini_get('precision');
            ini_set('precision', (string) $this->floatPrecision);
            $number = (string) $float;
            ini_set('precision', $previous);
        }

        return preg_match('/^[-+]?\d+$/', $number)
            ? "$number.0" : $number;
    }

    /**
     * Encodes string type value.
     * @param string $string String value to encode
     * @return string Escaped and single quote enclosed string
     */
    private function encodeString($string)
    {
        if (!$this->escapeStrings || preg_match('/^[\x20-\x7E]*$/', $string)) {
            return "'" . strtr($string, ["'" => "\\'", '\\' => '\\\\']) . "'";
        }

        return '"' . preg_replace_callback(
            '/[\x00-\x1F\x7F-\xFF]/',
            function ($matches) {
                return '\x' . sprintf('%02x', ord($matches[0]));
            },
            strtr($string, [
                '\\' => '\\\\',
                "\n" => '\n',
                "\r" => '\r',
                "\t" => '\t',
                "\v" => '\v',
                "\e" => '\e',
                "\f" => '\f',
                '$' => '\$',
                '"' => '\"',
            ])
        ) . '"';
    }

    /**
     * Encodes array type value.
     * @param array $array Array to encode
     * @return string Array presented in string format
     * @throws \RuntimeException If maximum depth has been reached
     */
    private function encodeArray($array)
    {
        if ($this->depth > $this->maxDepth && $this->maxDepth !== false) {
            throw new \RuntimeException('Max encoding depth exceeded');
        } elseif ($array === []) {
            return '[]';
        }

        $indent = $this->getIndent(++$this->depth);
        $pairs = $this->alignKeys ? $this->getAlignedPairs($array) : $this->getPairs($array);
        $this->depth--;

        if ($indent === false) {
            return '[' . implode(',', $pairs) . ']';
        }

        return '[' . PHP_EOL .
            $indent . implode(',' . PHP_EOL . $indent, $pairs) . ',' . PHP_EOL .
            $this->getIndent($this->depth) . ']';
    }

    /**
     * Encodes keys and values ands aligns the keys with whitespace.
     * @param array $array Array to encode into strings
     * @return string[] Key and values encoded as strings
     */
    private function getAlignedPairs($array)
    {
        $keys = array_map([$this, 'encodeValue'], array_keys($array));
        $maxKeyLength = $this->alignKeys ? max(array_map('strlen', $keys)) : 0;
        $pairs = [];

        foreach ($array as $value) {
            $pairs[] = str_pad($keys[count($pairs)], $maxKeyLength) . ' => ' .
                $this->encodeValue($value);
        }

        return $pairs;
    }

    /**
     * Encodes the keys and values of an array into strings.
     * @param array $array Array to encode into strings
     * @return string[] Key and values encoded as strings
     */
    private function getPairs($array)
    {
        $pairs = [];
        $nextIndex = 0;

        foreach ($array as $key => $value) {
            if ($key === $nextIndex && !$this->alignKeys) {
                $pairs[] = $this->encodeValue($value);
            } else {
                $pairs[] = $this->encodeValue($key) .
                    $this->space . '=>' . $this->space .
                    $this->encodeValue($value);
            }

            if (is_int($key) && $key >= $nextIndex) {
                $nextIndex = $key + 1;
            }
        }

        return $pairs;
    }

    /**
     * Encodes object as a string
     * @param object $object Object to encode
     * @return string Object represented as string depending on object handling
     */
    private function encodeObject($object)
    {
        if (method_exists($object, 'toPHP')) {
            $output = (string) $object->toPHP();
        } elseif (method_exists($object, 'toPHPValue')) {
            $output = $this->encodeValue($object->toPHPValue());
        } elseif ($this->objectFlags & self::OBJECT_STRING) {
            $output = $this->encodeValue((string) $object);
        } elseif ($this->objectFlags & self::OBJECT_SERIALIZE) {
            $output = 'unserialize(' . $this->encodeValue(serialize($object)) .')';
        } elseif ($this->objectFlags & self::OBJECT_SET_STATE) {
            $output = '\\' . get_class($object) . '::__set_state(' .
                $this->encodeValue($this->getAllProperties($object)) .
                ')';
        } else {
            $output = $this->encodeValue($this->getObjectProperties($object));

            if ($this->objectFlags & self::OBJECT_CAST) {
                $output = '(object)' . $this->space . $output;
            }
        }

        return $output;
    }

    /**
     * Encodes the resource as a string
     * @param resource $value PHP resource to encode
     * @return string The resource encoded as string
     * @throws \InvalidArgumentException if the resource type is not supported
     */
    private function encodeResource($value)
    {
        if (get_resource_type($value) === 'GMP integer') {
            return 'gmp_init(\'' . gmp_strval($value) . '\')';
        }

        throw new \InvalidArgumentException('Unsupported resource type: ' . get_resource_type($value));
    }

    /**
     * Returns the indentation string at given level.
     * @param integer $depth Level of indentation, 0 being the base
     * @return string The indentation string
     */
    private function getIndent($depth)
    {
        if ($this->indent === false) {
            return false;
        }

        $base = is_int($this->baseIndent) ? str_repeat(' ', $this->baseIndent) : $this->baseIndent;
        $indent = is_int($this->indent) ? str_repeat(' ', $this->indent) : $this->indent;
        return $base . str_repeat($indent, $depth);
    }

    /**
     * Gets the properties of an object as an array.
     * @param object $object Object to convert to an array
     * @return array Array of properties and values
     * @throws \RuntimeException If object encoding is disabled
     */
    private function getObjectProperties($object)
    {
        $array = [];

        if ($this->objectFlags & self::OBJECT_ARRAY) {
            $array = (array) $object;
        } elseif ($this->objectFlags & self::OBJECT_ITERATE) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        } elseif ($this->objectFlags & self::OBJECT_PROPERTIES) {
            foreach ((new \ReflectionClass($object))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                $array[$property->getName()] = $property->getValue($object);
            }
        } elseif ($this->objectFlags & self::OBJECT_VARS) {
            $array = get_object_vars($object);
        } else {
            throw new \RuntimeException('Object encoding disabled, cannot encode objects');
        }

        return $array;
    }

    /**
     * Returns array of all properties of the object and it's parents.
     * @param object $object Object to turn into array
     * @return array Properties of the object and it's parents
     */
    private function getAllProperties($object)
    {
        $class = new \ReflectionClass($object);
        $visibility = \ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED
            | \ReflectionProperty::IS_PUBLIC;
        $values = [];

        do {
            foreach ($class->getProperties($visibility) as $property) {
                if (!isset($values[$property->getName()])) {
                    $property->setAccessible(true);
                    $values[$property->getName()] = $property->getValue($object);
                }
            }

            $class = $class->getParentClass();
            $visibility = \ReflectionProperty::IS_PRIVATE;
        } while ($class);

        return $values;
    }
}

<?php

namespace Riimu\Kit\PHPEncoder;

/**
 * PHPEncoder provides a way to convert variables into PHP code.
 *
 * Goal of PHPEncoder is to provide function similar to json_encode(), but
 * instead if outputting JSON, it outputs PHP. This makes it easier to
 * dynamically generate PHP files such as configuration files when you don't
 * have to worry about producing PHP code from dynamical variables.
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
     * @var integer|false
     */
    private $maxDepth;

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
     * One level of indendation for the code.
     * @var integer|string
     */
    private $indent;

    /**
     * Optional whitespace character used for code spacing.
     * @var string
     */
    private $space;

    /**
     * End of line character used in the output.
     * @var string
     */
    private $eol;

    /**
     * Flag for converting objects into strings.
     */
    const OBJECT_STRING = 1;

    /**
     * Flag for serializing encountered objects.
     */
    const OBJECT_SERIALIZE = 2;

    /**
     * Flag for casting objects into arrays.
     */
    const OBJECT_ARRAY = 4;

    /**
     * Flag for iterating over object to generate an array.
     */
    const OBJECT_ITERATE = 8;

    /**
     * Flag to get object's public properties as an array.
     */
    const OBJECT_PROPERTIES = 16;

    /**
     * Flag to cast any generated object array into object.
     */
    const OBJECT_CAST = 32;

    /**
     * Creates a new PHPEncoder instance.
     */
    public function __construct()
    {
        $this->maxDepth = 20;
        $this->objectFlags = self::OBJECT_PROPERTIES | self::OBJECT_CAST;
        $this->alignKeys = false;
        $this->baseIndent = 0;
        $this->indent = 4;
        $this->space = ' ';
        $this->eol = PHP_EOL;
    }

    /**
     * Sets the maximum allowed depth for output.
     *
     * If the maximum allowed depth is reached, an exception will be thrown. If
     * false is provided, no limit is used.
     *
     * @param integer|false $depth Maximum depth or false for no limit.
     */
    public function setMaxDepth($depth)
    {
        $this->maxDepth = (int) $depth;
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
     * false as the first agument, in which case all optional whitespace from
     * the output will be omitted (including new lines and spaces).
     *
     * @param integer|string|false $indent Indenation for each level or false for none
     * @param integer|string $base Base level of indentation
     */
    public function setIndent($indent, $base = 0)
    {
        $this->baseIndent = is_string($base) ? $base : (int) $base;
        $this->indent = is_string($indent)
            ? $indent : ($indent === false ? false : (int) $indent);
        $this->space = $indent === false ? '' : ' ';
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
     * @throws \RuntimeException If maximum depth has been reached
     */
    private function encodeValue($value)
    {
        if ($this->depth++ > $this->maxDepth && $this->maxDepth !== false) {
            throw new \RuntimeException('Max encoding depth exceeded');
        }

        if (is_null($value)) {
            $output = $this->encodeNull($value);
        } elseif (is_bool($value)) {
            $output = $this->encodeBoolean($value);
        } elseif (is_int($value)) {
            $output = $this->encodeInteger($value);
        } elseif (is_float($value)) {
            $output = $this->encodeFloat($value);
        } elseif (is_string($value)) {
            $output = $this->encodeString($value);
        } elseif (is_array($value)) {
            $output = $this->encodeArray($value);
        } elseif (is_object($value)) {
            $output = $this->encodeObject($value);
        } else {
            throw new \RuntimeException("Cannot encode value type: " . gettype($value));
        }

        $this->depth--;
        return $output;
    }

    /**
     * Encodes null type value.
     * @param null $null Null
     * @return string The string 'null'
     */
    private function encodeNull($null)
    {
        return 'null';
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
        $number = (string) $float;
        return preg_match('/^[-+]?\d+$/', $number)
            ? '(float)' . $this->space . $number : $number;
    }

    /**
     * Encodes string type value.
     * @param string $string String value to encode
     * @return string Escaped and single quote enclosed string
     */
    private function encodeString($string)
    {
        return "'" . strtr($string, ["'" => "\\'", "\\" => "\\\\"]) . "'";
    }

    /**
     * Encodes array type value.
     * @param array $array Array to encode
     * @return string Array presented in string format
     */
    private function encodeArray($array)
    {
        $indent = $this->getIndent($this->depth);
        $pairs = [];

        if ($array === []) {
            return '[]';
        } else {
            $nextNumber = 0;

            if ($this->alignKeys) {
                $maxKeyLength = 0;
                $keys = [];

                foreach (array_keys($array) as $key) {
                    $keys[$key] = $this->encodeValue($key);
                    $maxKeyLength = max($maxKeyLength, strlen($keys[$key]));
                }
            }

            foreach ($array as $key => $value) {
                if ($this->alignKeys) {
                    $pairs[] = str_pad($keys[$key], $maxKeyLength) .
                        ' => ' . $this->encodeValue($value);
                } elseif ($key === $nextNumber) {
                    $pairs[] = $this->encodeValue($value);
                } else {
                    $pairs[] =
                        $this->encodeValue($key) .
                        $this->space . '=>' . $this->space .
                        $this->encodeValue($value);
                }

                if (is_int($key) && $key >= $nextNumber) {
                    $nextNumber = $key + 1;
                }
            }
        }

        if ($indent === false) {
            return '[' . implode(',', $pairs) . ']';
        }

        return '[' . $this->eol .
            $indent . implode(",$this->eol$indent", $pairs) . ',' . $this->eol .
            $this->getIndent($this->depth - 1) . ']';
    }

    /**
     * Encodes object as a string
     * @param object $object Object to encode
     * @return string Object represented as string depending on object handling
     */
    private function encodeObject($object)
    {
        $this->depth--;

        if (method_exists($object, 'toPHP')) {
            $output = (string) $object->toPHP();
        } elseif (method_exists($object, 'toPHPValue')) {
            $output = $this->encodeValue($object->toPHPValue());
        } elseif ($this->objectFlags & self::OBJECT_STRING) {
            $output = $this->encodeValue((string) $object);
        } elseif ($this->objectFlags & self::OBJECT_SERIALIZE) {
            $output = 'unserialize(' . $this->encodeValue(serialize($object)) .')';
        } else {
            $output = $this->encodeValue($this->getObjectProperties($object));

            if ($this->objectFlags & self::OBJECT_CAST) {
                $output = '(object)' . $this->space . $output;
            }
        }

        $this->depth++;

        return $output;
    }

    private function getIndent($depth)
    {
        if ($this->indent === false) {
            return false;
        }

        $base = is_int($this->baseIndent) ? str_repeat(' ', $this->baseIndent) : $this->baseIndent;
        $indent = is_int($this->indent) ? str_repeat(' ', $this->indent) : $this->indent;
        return $base . str_repeat($indent, $depth);
    }

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
            foreach ((new \ReflectionClass($object))
                ->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                $array[$property->getName()] = $property->getValue($object);
            }
        } else {
            throw new \RuntimeException('Object encoding disabled, cannot encode objects');
        }

        return $array;
    }
}

<?php

namespace Riimu\Kit\PHPEncoder\Encoder;
use Riimu\Kit\PHPEncoder\RecursiveEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ObjectEncoder implements Encoder
{
    private static $defaultOptions = [
        'object.method' => true,
        'object.format' => 'vars',
        'object.cast' => true,
    ];

    public function getDefaultOptions()
    {
        return self::$defaultOptions;
    }

    public function supports($value)
    {
        return is_object($value);
    }

    public function encode($value, $depth, array $options, callable $encode)
    {
        if ($options['object.method']) {
            if (method_exists($value, 'toPHP')) {
                return (string)$value->toPHP();
            } elseif (method_exists($value, 'toPHPValue')) {
                return $encode($value->toPHPValue());
            }
        }

        return $this->encodeObject($value, $options, $encode);
    }

    public function encodeObject($object, array $options, callable $encode)
    {
        if ($options['object.format'] === 'string') {
            return $encode((string) $object);
        } elseif ($options['object.format'] === 'serialize') {
            return sprintf('unserialize(%s)', $encode(serialize($object)));
        } elseif ($options['object.format'] === 'export') {
            return sprintf('\\%s::__set_state(%s)', get_class($object), $encode($this->getObjectState($object)));
        }

        $output = $encode($this->getObjectArray($object, $options['object.format']));

        if ($options['object.cast']) {
            $output = '(object)' . ($options['whitespace'] ? ' ' : '') . $output;
        }

        return $output;
    }

    /**
     * Gets the properties of an object as an array.
     * @param object $object Object to convert to an array
     * @return array Array of properties and values
     * @throws \RuntimeException If object encoding is disabled
     */
    private function getObjectArray($object, $format)
    {
        if ($format === 'array') {
            return (array) $object;
        } elseif ($format === 'vars') {
            return get_object_vars($object);
        } elseif ($format === 'iterate') {
            $array = [];
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
            return $array;
        }

        throw new \RuntimeException('Invalid object encoding format: ' . $format);
    }

    /**
     * Returns array of all properties of the object and it's parents.
     * @param object $object Object to turn into array
     * @return array Properties of the object and it's parents
     */
    private function getObjectState($object)
    {
        $class = new \ReflectionClass($object);
        $visibility = \ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED;
        $values = [];

        do {
            foreach ($class->getProperties($visibility) as $property) {
                $property->setAccessible(true);
                $values[$property->getName()] = $property->getValue($object);
            }

            $class = $class->getParentClass();
            $visibility = \ReflectionProperty::IS_PRIVATE;
        } while ($class);

        return get_object_vars($object) + $values;
    }
}

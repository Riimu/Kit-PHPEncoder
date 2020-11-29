<?php

namespace Riimu\Kit\PHPEncoder;

/**
 * A highly customisable library for generating PHP code from variables.
 *
 * PHPEncoder provides a functionality similar to var_export(), but allows more
 * customisation and wider range of features. Better customisation options make
 * it easier to generate static PHP files when you need the file to formatted
 * in specific way.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2020 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class PHPEncoder
{
    /** @var Encoder\Encoder[] List of value encoders */
    private $encoders;

    /** @var array List of defined encoder option values. */
    private $options;

    /** @var array Default values for options in the encoder */
    private static $defaultOptions = [
        'whitespace' => true,
        'recursion.detect' => true,
        'recursion.ignore' => false,
        'recursion.max' => false,
        'hex.capitalize' => false,
    ];

    /**
     * Creates a new PHPEncoder instance.
     *
     * The constructor allows you to provide the list of default encoding options
     * used by the encoder. Note that if you are using custom value encoders, you
     * must provide them in the constructor if you are providing options for them
     * or otherwise the options will be considered invalid.
     *
     * Using the second parameter you can also provide a list of value encoders used
     * by the encoder. If null is provided, the list of default value encoders will
     * be used instead.
     *
     * @param array $options List of encoder options
     * @param Encoder\Encoder[]|null $encoders List of encoders to use or null for defaults
     * @throws InvalidOptionException If any of the encoder options are invalid
     */
    public function __construct(array $options = [], array $encoders = null)
    {
        $this->options = self::$defaultOptions;

        if ($encoders === null) {
            $this->encoders = [
                new Encoder\NullEncoder(),
                new Encoder\BooleanEncoder(),
                new Encoder\IntegerEncoder(),
                new Encoder\FloatEncoder(),
                new Encoder\StringEncoder(),
                new Encoder\ArrayEncoder(),
                new Encoder\GMPEncoder(),
                new Encoder\ObjectEncoder(),
            ];
        } else {
            $this->encoders = [];
            array_map([$this, 'addEncoder'], $encoders);
        }

        array_map([$this, 'setOption'], array_keys($options), $options);
    }

    /**
     * Adds a new encoder.
     *
     * Values are always encoded by the first encoder that supports encoding
     * that type of value. By setting the second optional parameter to true,
     * you can prepend the encoder to the list to ensure that it will be tested
     * first.
     *
     * @param Encoder\Encoder $encoder Encoder for encoding values
     * @param bool $prepend True to prepend the encoder to the list, false to add it as last
     */
    public function addEncoder(Encoder\Encoder $encoder, $prepend = false)
    {
        $prepend ? array_unshift($this->encoders, $encoder) : array_push($this->encoders, $encoder);
    }

    /**
     * Sets the value for an encoder option.
     * @param string $option Name of the option
     * @param mixed $value Value for the option
     * @throws InvalidOptionException If the provided encoder option is invalid
     */
    public function setOption($option, $value)
    {
        if (!$this->isValidOption($option)) {
            throw new InvalidOptionException(sprintf("Invalid encoder option '%s'", $option));
        }

        $this->options[$option] = $value;
    }

    /**
     * Tells if the given string is a valid option name.
     * @param string $option Option name to validate
     * @return bool True if the name is a valid option name, false if not
     */
    private function isValidOption($option)
    {
        if (\array_key_exists($option, $this->options)) {
            return true;
        }

        foreach ($this->encoders as $encoder) {
            if (\array_key_exists($option, $encoder->getDefaultOptions())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates the PHP code for the given value.
     * @param mixed $variable Value to encode as PHP
     * @param array $options List of encoder options
     * @return string The PHP code that represents the given value
     * @throws InvalidOptionException If any of the encoder options are invalid
     * @throws \InvalidArgumentException If the provided value contains an unsupported value type
     * @throws \RuntimeException If max depth is reached or a recursive value is detected
     */
    public function encode($variable, array $options = [])
    {
        return $this->generate($variable, 0, $this->getAllOptions($options));
    }

    /**
     * Returns a list of all encoder options.
     * @param array $overrides Options to override in the returned array
     * @return array List of encoder options
     * @throws InvalidOptionException If any of the encoder option overrides are invalid
     */
    public function getAllOptions(array $overrides = [])
    {
        $options = $this->options;

        foreach ($this->encoders as $encoder) {
            $options += $encoder->getDefaultOptions();
        }

        foreach ($overrides as $name => $value) {
            if (!\array_key_exists($name, $options)) {
                throw new InvalidOptionException(sprintf("Invalid encoder option '%s'", $name));
            }

            $options[$name] = $value;
        }

        ksort($options);

        return $options;
    }

    /**
     * Generates the code for the given value recursively.
     * @param mixed $value Value to encode
     * @param int $depth Current indentation depth of the output
     * @param array $options List of encoder options
     * @param array $recursion Previously encoded values for recursion detection
     * @return string The PHP code that represents the given value
     * @throws \RuntimeException If max depth is reached or a recursive value is detected
     */
    private function generate($value, $depth, array $options, array $recursion = [])
    {
        if ($this->detectRecursion($value, $options, $recursion)) {
            $recursion[] = $value;
        }

        if ($options['recursion.max'] !== false && $depth > (int) $options['recursion.max']) {
            throw new \RuntimeException('Maximum encoding depth reached');
        }

        $callback = function ($value, $level = 0, array $overrides = []) use ($depth, $options, $recursion) {
            return $this->generate($value, $depth + (int) $level, $overrides + $options, $recursion);
        };

        return $this->encodeValue($value, $depth, $options, $callback);
    }

    /**
     * Attempts to detect circular references in values.
     * @param mixed $value Value to try for circular reference
     * @param array $options List of encoder options
     * @param array $recursion Upper values in the encoding tree
     * @return bool True if values should be recorded, false if not
     * @throws \RuntimeException If a recursive value is detected
     */
    private function detectRecursion(&$value, array $options, array $recursion)
    {
        if ($options['recursion.detect']) {
            if (array_search($value, $recursion, true) !== false) {
                if ($options['recursion.ignore']) {
                    $value = null;
                } else {
                    throw new \RuntimeException('A recursive value was detected');
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Encodes the value using one of the encoders that supports the value type.
     * @param mixed $value Value to encode
     * @param int $depth Current indentation depth of the output
     * @param array $options List of encoder options
     * @param callable $encode Callback used to encode values
     * @return string The PHP code that represents the given value
     * @throws \InvalidArgumentException If the provided value contains an unsupported value type
     */
    private function encodeValue($value, $depth, array $options, callable $encode)
    {
        foreach ($this->encoders as $encoder) {
            if ($encoder->supports($value)) {
                return $encoder->encode($value, $depth, $options, $encode);
            }
        }

        throw new \InvalidArgumentException(sprintf("Unsupported value type '%s'", \gettype($value)));
    }
}

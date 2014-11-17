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
    private $encoders;
    private $options;

    /**
     * Creates a new PHPEncoder instance.
     */
    public function __construct(array $options = [], array $encoders = null)
    {
        $this->encoders = [];
        $this->options = [
            'whitespace' => true,
            'recursion.detect' => true,
            'recursion.ignore' => false,
            'recursion.max' => false,
        ];

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
            foreach ($encoders as $encoder) {
                $this->addEncoder($encoder);
            }
        }

        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }
    }

    public function addEncoder(Encoder\Encoder $encoder)
    {
        $this->encoders[] = $encoder;
    }

    public function setOption($option, $value)
    {
        if (!$this->isValidOption($option)) {
            throw new \InvalidArgumentException("Invalid option name: " . $option);
        }

        $this->options[$option] = $value;
    }

    private function isValidOption($option)
    {
        if (isset($this->options[$option])) {
            return true;
        }

        foreach ($this->encoders as $encoder) {
            if (isset($encoder->getDefaultOptions()[$option])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encodes the given variable as PHP code.
     * @param mixed $variable Value to encode as PHP
     * @return string The value encoded as PHP code
     */
    public function encode($variable, array $options = [])
    {
        return $this->recurse($variable, 0, $this->getAllOptions($options));
    }

    public function getAllOptions(array $overrides = [])
    {
        $options = $this->options;

        foreach ($this->encoders as $encoder) {
            $options += $encoder->getDefaultOptions();
        }

        foreach ($overrides as $name => $value) {
            if (!isset($options[$name])) {
                throw new \InvalidArgumentException("Invalid option name: " . $name);
            }

            $options[$name] = $value;
        }

        return $options;
    }

    private function recurse($value, $depth, array $options, & $recursion = [])
    {
        if ($options['recursion.detect']) {
            if (array_search($value, $recursion, true) !== false) {
                if ($options['recursion.ignore']) {
                    $value = null;
                } else {
                    throw new \RuntimeException('Recursion detected');
                }
            }

            $recursion[] = $value;
        }

        if ($options['recursion.max'] !== false && $depth > (int) $options['recursion.max']) {
            throw new \RuntimeException('Maximum encoding depth reached');
        }

        $callback = function ($value, $level = 0, array $overrides = []) use ($depth, $options, & $recursion) {
            return $this->recurse($value, $depth + (int) $level, $overrides + $options, $recursion);
        };

        foreach ($this->encoders as $encoder) {
            if ($encoder->supports($value)) {
                $output = $encoder->encode($value, $depth, $options, $callback);

                if ($options['recursion.detect']) {
                    array_pop($recursion);
                }

                return $output;
            }
        }

        throw new \InvalidArgumentException('Unable to encode value type: ' . gettype($value));
    }
}

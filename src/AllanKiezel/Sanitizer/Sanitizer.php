<?php namespace AllanKiezel\Sanitizer;

use AllanKiezel\Sanitizer\Exceptions\SanitizerAlreadyExistsException;
use AllanKiezel\Sanitizer\Exceptions\SanitizerNotCallableException;
use AllanKiezel\Sanitizer\Exceptions\SanitizerNotFoundException;

/**
 * Class Sanitizer
 *
 * @package AllanKiezel\Sanitizer
 * @author Allan Kiezel <allan.kiezel@gmail.com>
 */
abstract class Sanitizer {

    /**
     * Sanitizer rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * @var array
     */
    protected $sanitizers = [];

    /**
     * Sanitize a string by using rules
     *
     * Uses custom sanitizers or PHP functions as sanitizers. Custom
     * sanitizers should be registered beforehand with the register() method.
     *
     * @param array $fields
     * @param array|null $rules
     * @return array
     */
    public function sanitize(array $fields, array $rules = null)
    {
        $rules = ! empty($rules) ? $rules : $this->getRules();

        foreach ($fields as $field => $value) {

            // Skip if no rules exists for this field
            if ( ! isset($rules[$field])) continue;

            $fields[$field] = $this->applySanitizers($value, $rules[$field]);
        }

        return $fields;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Register a custom sanitizer
     *
     * @param string $name Name of the sanitizer
     * @param callable $callback
     * @param bool $override Override sanitizer if it already exists
     * @throws SanitizerAlreadyExistsException
     * @throws SanitizerNotCallableException
     */
    public function register($name, $callback, $override = false)
    {
        if ( ! is_callable($callback)) {
            throw new SanitizerNotCallableException('The $callback argument of Sanitizer::register() must be callable.');
        }

        if ($this->customSanitizerExists($name) and $override !== true) {
            throw new SanitizerAlreadyExistsException('Sanitizer with this name already exists.');
        }

        $this->sanitizers[$name] = $callback;
    }

    /**
     * Check if a sanitizer exists
     *
     * @param $sanitizer
     * @return bool
     */
    public function sanitizerExists($sanitizer)
    {
        return $this->customSanitizerExists($sanitizer) or function_exists($sanitizer);
    }

    /**
     * Check if a custom sanitizer exists
     *
     * @param $sanitizer
     * @return bool
     */
    public function customSanitizerExists($sanitizer)
    {
        return isset($this->sanitizers[$sanitizer]);
    }

    /**
     * Split pipe separated rules into an array if it's a string
     *
     * @param mixed $rules
     * @return array
     */
    private function splitSanitizers($rules)
    {
        if (is_array($rules)) {
            return $rules;
        }

        return explode('|', $rules);
    }

    /**
     * Apply a sanitizer to the value
     *
     * @param $value
     * @param $sanitizer
     * @return mixed
     */
    private function applySanitizer($value, $sanitizer)
    {
        if ( ! $this->sanitizerExists($sanitizer)) {
            throw new SanitizerNotFoundException("Sanitizer $sanitizer not found.");
        }

        if ($this->customSanitizerExists($sanitizer)) {
            $sanitizer = $this->sanitizers[$sanitizer];
        }

        return call_user_func($sanitizer, $value);
    }

    /**
     * Apply a set of sanitizers to a string
     *
     * @param $value
     * @param $rules
     * @return string
     */
    private function applySanitizers($value, $rules)
    {
        foreach ($this->splitSanitizers($rules) as $sanitizer) {
            $value = $this->applySanitizer($value, $sanitizer);
        }

        return $value;
    }

}

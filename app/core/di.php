<?php
namespace T\Core;

trait TDelayInitializer {

    /**
     * Delay Initializer
     * @var array
     */
    protected $di;

    public function __get(string $name) {

        if (isset($this->di[$name])) {

            return $this->{$name} = $this->di[$name]();
        }

        throw new \T\Msg\InvalidProperty("Property {$name} doesn't exist.");
    }

    public function __isset(string $name) {

        return isset($this->di[$name]);
    }

}
<?php

namespace C01l\PhpDecorator;

trait ProxyVariableAccessTrait
{
    public function __set(string $name, $value): void
    {
        $this->real->$name = $value;
    }

    public function __get(string $name)
    {
        return $this->real->$name;
    }

    public function __isset(string $name): bool
    {
        return isset($this->real->$name);
    }
}

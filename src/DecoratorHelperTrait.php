<?php

namespace Coil\PhpDecorator;

trait DecoratorHelperTrait {
    private array $wrappers = [];

    /**
     * @param Decorator[][] $wrappers
     */
    public function setWrappers(array $wrappers): void
    {
        $this->wrappers = $wrappers;
    }

    private function decoratorHelper(callable $wrappedMethod, array $args, string $wrappersKey): mixed {
        $wrappers = $this->wrappers[$wrappersKey] ?? [];
        $fn = $wrappedMethod;
        /** @var Decorator $wrap */
        foreach($wrappers as $wrap) {
            $fn = $wrap->wrap($fn);
        }
        return call_user_func_array($fn, $args);
    }
}

<?php

namespace Coil\PhpDecorator;

use Psr\Container\ContainerInterface;
use Reflection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

class ClassDecorator
{

    public function __construct(private ?ContainerInterface $container = null)
    {
    }


    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     * @throws ReflectionException in case the class does not exist
     * @throws DecoratorException in case a method could not be decorated
     */
    public function instantiateDecoratedObject(string $className): mixed
    {
        $rc = new ReflectionClass($className);
        if ($rc->isFinal()) {
            throw new DecoratorException("Cannot decorate final class: $className");
        }

        $trait = "\\" . DecoratorHelperTrait::class;

        $wrappers = [];
        $methods = $rc->getMethods();
        $overwrite_methods = "";
        foreach($methods as $method) {
            $overwrite_methods .= $this->handleMethod($method, $wrappers);
        }

        $classDef = 'return new class extends \\' . $rc->getName()
            . ' { use ' . $trait . '; ' . $overwrite_methods . '};';
        $obj = eval($classDef);
        $obj->setWrappers($wrappers);
        return $obj;
    }

    /**
     * @param ReflectionMethod $method
     * @param array $wrappers
     * @return string
     * @throws DecoratorException
     */
    private function handleMethod(ReflectionMethod $method, array &$wrappers): string
    {
        $attrs = $method->getAttributes(Decorator::class, ReflectionAttribute::IS_INSTANCEOF);

        if ($attrs === []) {
            return "";
        }

        if ($method->isPrivate()) {
            throw new DecoratorException("Cannot decorate private function '$method->name'");
        }

        $decorators = array_map(function (ReflectionAttribute $x) {
            /** @var Decorator $decoratorInstance */
            $decoratorInstance = $x->newInstance();
            if ($this->container !== null && method_exists($decoratorInstance, "setContainer")) {
                $decoratorInstance->setContainer($this->container);
            }
            return $decoratorInstance;
        }, $attrs);
        $wrappers[$method->name] = array_reverse($decorators);

        return $this->buildFunctionHead($method) . '{
            return $this->decoratorHelper([$this, "parent::' . $method->name . '"], func_get_args(), "' . $method->name . '");
        }';
    }

    private function buildFunctionHead(ReflectionMethod $method): string
    {
        $modifiers = implode(" ", Reflection::getModifierNames($method->getModifiers()));
        $paramList = implode(", ", array_map([$this, "buildFunctionParam"], $method->getParameters()));
        $type = $method->getReturnType()->getName();
        return $modifiers . " function " . $method->name . "($paramList): $type";
    }

    private function buildFunctionparam(ReflectionParameter $parameter): string
    {
        $default = "";
        if ($parameter->isDefaultValueAvailable()) {
            $value = $parameter->getDefaultValue();
            if (is_string($value)) {
                $value = json_encode($value);
            }
            $default = " = " . $value;
        };
        return $parameter->getType()->getName() . ' $' . $parameter->getName() . $default;
    }
}
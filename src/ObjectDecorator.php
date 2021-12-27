<?php

namespace Coil\PhpDecorator;

use Psr\Container\ContainerInterface;
use Reflection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

class ObjectDecorator
{

    public function __construct(private ?ContainerInterface $container = null)
    {
    }


    /**
     * @template T of object
     * @param T $real
     * @return T
     * @throws ReflectionException in case the class does not exist
     * @throws DecoratorException in case a method could not be decorated
     */
    public function decorate(object $real): object
    {
        $rc = new ReflectionClass($real::class);
        if ($rc->isFinal()) {
            throw new DecoratorException("Cannot decorate final class: " . $real::class);
        }

        $trait = "\\" . DecoratorHelperTrait::class;

        $wrappers = [];
        $methods = $rc->getMethods();
        $overwrite_methods = "";
        foreach($methods as $method) {
            $overwrite_methods .= $this->handleMethod($method, $wrappers);
        }

        $classDef = 'return new class($real) extends \\' . $rc->getName()
            . ' { use ' . $trait . '; public function __construct(private mixed $real) {} ' . $overwrite_methods . '};';
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
            return $this->decoratorHelper([$this->real, "' . $method->name . '"], func_get_args(), "' . $method->name . '");
        }';
    }

    private function buildFunctionHead(ReflectionMethod $method): string
    {
        $modifiers = implode(" ", Reflection::getModifierNames($method->getModifiers()));
        $paramList = implode(", ", array_map([$this, "buildFunctionParam"], $method->getParameters()));
        $type = $method->getReturnType()->getName();

        $attrs = $this->buildAttributeList($method->getAttributes());
        return "$attrs $modifiers function " . $method->name . "($paramList): $type";
    }

    private function buildFunctionParam(ReflectionParameter $parameter): string
    {
        $default = "";
        if ($parameter->isDefaultValueAvailable()) {
            $value = $parameter->getDefaultValue();
            if (is_string($value)) {
                $value = json_encode($value);
            }
            $default = " = " . $value;
        }

        $attrs = $this->buildAttributeList($parameter->getAttributes());
        return "$attrs {$parameter->getType()->getName()} \${$parameter->getName()}$default";
    }

    private function buildAttributeList(array $attrs): string {
        $attrs = array_filter($attrs, fn($a) => !($a->newInstance() instanceof Decorator));
        return implode(", ", array_map([$this, "buildAttributeParam"], $attrs));
    }

    private function buildAttributeParam(ReflectionAttribute $attr): string {
        $params = [];
        foreach($attr->getArguments() as $name => $value) {
            if (is_int($value) || is_float($value)) {
                $v = $value;
            } elseif (is_bool($value)) {
                $v = $value ? "true" : "false";
            } elseif(is_string($value)) {
                $v = json_encode($value);
            } else {
                throw new \InvalidArgumentException("Cannot encode argument parameter: $name (type: " . gettype($value) . ")");
            }
            $params[] = "$name: " . $v;
        }
        return "#[{$attr->getName()}(" . implode(", ", $params) . ")]";
    }
}
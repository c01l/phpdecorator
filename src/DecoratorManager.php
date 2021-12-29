<?php

namespace Coil\PhpDecorator;

use Psr\Container\ContainerInterface;
use Reflection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

class DecoratorManager
{
    public function __construct(
        private false|string $classCachePath = false,
        private ?ContainerInterface $container = null
    ) {
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
        $class = $this->loadFromCache($real::class);
        if ($class !== false) {
            $ret = new $class($real);
            $wrappers = [];
            $rc = new ReflectionClass($real::class);
            foreach ($rc->getMethods() as $m) {
                $wrappers[$m->name] = $this->buildDecoratorContainer($m);
            }
            $ret->setWrappers($wrappers);
            return $ret;
        }

        $rc = new ReflectionClass($real::class);
        if ($rc->isFinal()) {
            throw new DecoratorException("Cannot decorate final class: " . $real::class);
        }

        $trait = "\\" . DecoratorHelperTrait::class;

        $wrappers = [];
        $methods = $rc->getMethods();
        $overwrite_methods = "";
        foreach ($methods as $method) {
            $wrappers[$method->name] = $this->buildDecoratorContainer($method);
            $overwrite_methods .= $this->handleMethod(
                $method,
                fn($method) => 'return $this->decoratorHelper(
                    [$this->real, "' . $method->name . '"], 
                    func_get_args(), 
                    "' . $method->name . '"
                 );'
            );
        }

        $classBody = ' { use ' . $trait . '; public function __construct(private mixed $real) {} '
            . $overwrite_methods . '};';

        $this->storeInCache($real::class, $classBody);

        $classDef = 'return new class($real) extends \\' . $rc->getName() . $classBody;
        $obj = eval($classDef);
        $obj->setWrappers($wrappers);
        return $obj;
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     * @throws ReflectionException in case the class does not exist
     * @throws DecoratorException in case a method could not be decorated
     */
    public function instantiate(string $className): mixed
    {
        $class = $this->loadFromCache($className);
        if ($class !== false) {
            $ret = new $class();
            $wrappers = [];
            $rc = new ReflectionClass($className);
            foreach ($rc->getMethods() as $m) {
                $wrappers[$m->name] = $this->buildDecoratorContainer($m);
            }
            $ret->setWrappers($wrappers);
            return $ret;
        }

        $rc = new ReflectionClass($className);
        if ($rc->isFinal()) {
            throw new DecoratorException("Cannot decorate final class: $className");
        }

        $trait = "\\" . DecoratorHelperTrait::class;

        $wrappers = [];
        $methods = $rc->getMethods();
        $overwrite_methods = "";
        foreach ($methods as $method) {
            $wrappers[$method->name] = $this->buildDecoratorContainer($method);
            $overwrite_methods .= $this->handleMethod(
                $method,
                fn($method) => 'return $this->decoratorHelper(
                    [$this, "parent::' . $method->name . '"], 
                    func_get_args(), 
                    "' . $method->name . '"
                );'
            );
        }

        $classBody = '{ use ' . $trait . '; ' . $overwrite_methods . '};';
        $this->storeInCache($className, $classBody);

        $classDef = "return new class extends \\{$rc->getName()} $classBody";
        $obj = eval($classDef);
        $obj->setWrappers($wrappers);
        return $obj;
    }

    private function classToFilename(string $class): string
    {
        return str_replace("\\", "_", $class);
    }

    private function loadFromCache(string $class): string|false
    {
        $newName = $this->classToFilename($class);
        if (class_exists($newName)) {
            return $newName;
        }
        $filename = $this->classCachePath . "/$newName.php";
        if (!file_exists($filename)) {
            return false;
        }
        require_once $filename;
        return $newName;
    }

    private function storeInCache(string $className, string $classBody): void
    {
        if ($this->classCachePath === false) {
            return;
        }

        $newName = $this->classToFilename($className);

        file_put_contents(
            $this->classCachePath . "/$newName.php",
            "<?php class $newName extends $className $classBody"
        );
    }

    /**
     * @param ReflectionMethod $method
     * @param callable $functionBodyBuilder
     * @return string
     * @throws DecoratorException
     */
    private function handleMethod(ReflectionMethod $method, callable $functionBodyBuilder): string
    {
        $attrs = $method->getAttributes(Decorator::class, ReflectionAttribute::IS_INSTANCEOF);

        if ($attrs === []) {
            return "";
        }

        if ($method->isPrivate()) {
            throw new DecoratorException("Cannot decorate private function '$method->name'");
        }

        return $this->buildFunctionHead($method) . "{{$functionBodyBuilder($method)}}";
    }

    private function buildDecoratorContainer(ReflectionMethod $method): array
    {
        return array_reverse(
            array_map(function (ReflectionAttribute $x) {
                /** @var Decorator $decoratorInstance */
                $decoratorInstance = $x->newInstance();
                if ($this->container !== null && method_exists($decoratorInstance, "setContainer")) {
                    $decoratorInstance->setContainer($this->container);
                }
                return $decoratorInstance;
            }, $method->getAttributes(Decorator::class, ReflectionAttribute::IS_INSTANCEOF))
        );
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

    private function buildAttributeList(array $attrs): string
    {
        $attrs = array_filter($attrs, fn($a) => !($a->newInstance() instanceof Decorator));
        return implode(", ", array_map([$this, "buildAttributeParam"], $attrs));
    }

    private function buildAttributeParam(ReflectionAttribute $attr): string
    {
        $params = [];
        foreach ($attr->getArguments() as $name => $value) {
            if (is_int($value) || is_float($value)) {
                $v = $value;
            } elseif (is_bool($value)) {
                $v = $value ? "true" : "false";
            } elseif (is_string($value)) {
                $v = json_encode($value);
            } else {
                throw new \InvalidArgumentException(
                    "Cannot encode argument parameter: $name (type: " . gettype($value) . ")"
                );
            }
            $params[] = "$name: " . $v;
        }
        return "#[{$attr->getName()}(" . implode(", ", $params) . ")]";
    }
}

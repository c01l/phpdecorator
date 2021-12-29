# phpdecorator

The phpdecorator library can be used to wrap functions of objects and classes with additional functionality.
This is a feature that can be compared to Python decorators.

## How to create your own decorators?

1. Declare an attribute that can be put on methods extending the Decorator base-class provided by the library.
2. Implement the wrap function returning a function that will be called instead of the original function.
3. Use `call_user_func_array` together with `func_get_args` to call the original function.

```php
#[\Attribute(\Attribute::TARGET_METHOD)]
class LoggingDecorator extends \C01l\PhpDecorator\Decorator
{
    public function wrap(callable $func): callable
    {
        return function () use ($func) {
            /** @var Logger $logger */
            $logger = $this->getContainer()->get(Logger::class);
            $logger->log("Started");
            $ret = call_user_func_array($func, func_get_args());
            $logger->log("Ended");
            return $ret;
        };
    }
}

```

## Using decorators

Just annotate the relevant function on a class.

```php
class SomeClass
{
    #[LoggingDecorator]
    public function foo(int $bar): int
    {
        return $bar;
    }
}
```

The functionality will only be replaced if the object is passed through the library:

```php
$decoratorManager = new DecoratorManager();

$obj = $decoratorManager->instantiate(SomeClass::class); // only possible for classes with a parameter-less constructor!
// OR
$obj = new SomeClass();
$obj = $decoratorManager->decorate($obj); // creates a proxy object (do not use the original reference of the object!)
```

## Advanced Usage

### Passing a container for dependencies to the decorators

You can supply a container to the `DecoratorManager` which will passed on to each `Decorator` that will be instantiated.

```php
$container = /* use some PSR-11 container */
$decoratorManager = new DecoratorManager(container: $container)
```

In the decorator you can fetch the container with `$this->getContainer()`.

### Caching the generated classes

If you are using this library on a large amount of classes, it might be suitable to use the class cache.
Then classes are generated once and can be optimized by the runtime.

Just supply a path to a folder where your runtime is allowed to read and write files.

```php
$decoratorManager = new DecoratorManager(classCachePath: "/path/to/cache/");
```

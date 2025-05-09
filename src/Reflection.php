<?php

namespace Bermuda\Reflection;

use Closure;
use Generator;
use ReflectionClass;
use ReflectionConstant;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;

/**
 * Class Reflection
 *
 * This static helper class provides utility methods for retrieving reflection information and
 * metadata (attributes) from various reflection objects such as functions, classes, parameters,
 * constants, and properties. It also implements caching of created reflectors to improve performance.
 */
final class Reflection
{
    /**
     * @var array<string, Reflector> Cache mapping of unique identifiers to Reflector instances.
     */
    private static array $reflectors = [];

    /**
     * Adds a custom reflector to the static cache.
     *
     * @param string   $id        The unique identifier for caching the reflector.
     * @param Reflector $reflector The reflector instance to cache.
     */
    public static function addReflector(string $id, Reflector $reflector): void
    {
        self::$reflectors[$id] = $reflector;
    }

    /**
     * Retrieves all metadata attributes for the provided reflection object.
     *
     * If a name is supplied, only attributes matching the given class name are returned.
     * The metadata is yielded as a generator producing Attribute instances.
     *
     * @template T of object
     * @param ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant $reflector The reflection object to inspect.
     * @param class-string<T>|null $name Optional Attribute class name to filter by.
     * @return Generator<T>|null Yields instances of the Attribute, or null if none are found.
     */
    public static function getMetadata(ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant $reflector, ?string $name = null): ?Generator
    {
        if (empty($attributes = $reflector?->getAttributes($name) ?? [])) return null;
        foreach ($attributes as $attribute) yield $attribute->newInstance();
    }

    /**
     * Retrieves the first metadata Attribute instance for the provided reflection object that matches the given Attribute name.
     *
     * This method supports ReflectionFunctionAbstract, ReflectionClass, ReflectionParameter,
     * ReflectionConstant, and ReflectionProperty.
     *
     * @template T of object
     * @param ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant|ReflectionProperty $reflector The reflection object to inspect.
     * @param class-string<T> $name The Attribute class name to look for.
     * @return T|null Returns the Attribute instance if found, or null if not present.
     */
    public static function getFirstMetadata(ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant|ReflectionProperty $reflector, string $name): ?object
    {
        $attributes = $reflector?->getAttributes($name) ?? [];
        return isset($attributes[0]) ? $attributes[0]->newInstance() : null;
    }

    /**
     * Checks whether the provided reflection object has any metadata attributes matching the specified Attribute name.
     *
     * This method supports ReflectionFunctionAbstract, ReflectionClass, ReflectionParameter,
     * ReflectionConstant, and ReflectionProperty.
     *
     * @param ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant|ReflectionProperty $reflector The reflection object to check.
     * @param string $name The Attribute class name to look for.
     * @return bool Returns true if at least one matching Attribute is found; otherwise, false.
     */
    public static function hasMetadata(ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant|ReflectionProperty $reflector, string $name): bool
    {
        return !empty($reflector->getAttributes($name));
    }

    /**
     * Reflects a given variable and returns an appropriate reflection object.
     *
     * Supported types:
     * - Callables: Returns a reflection of the callable.
     * - Objects: Returns a ReflectionObject for the instance.
     * - Strings: Treated as a class name; returns a ReflectionClass if the class exists.
     *
     * @param mixed $var The variable to reflect.
     * @return null|ReflectionFunctionAbstract|ReflectionClass|ReflectionObject Returns a reflection object if supported, or null.
     */
    public static function reflect(mixed $var): null|ReflectionFunctionAbstract|ReflectionClass|ReflectionObject
    {
        return match (true) {
            is_callable($var) => self::callable($var),
            is_object($var) => self::object($var),
            is_string($var) => self::class($var),
            default => null
        };
    }

    /**
     * Returns a Reflection instance for the given callable.
     *
     * Depending on the type of callable:
     * - For closures, uses spl_object_hash() for caching.
     * - For string callables with "::" (e.g., "Class::method"), returns a ReflectionMethod.
     * - For simple function names, returns a ReflectionFunction.
     * - For array-style callables, returns a ReflectionMethod.
     *
     * @param callable $callable The callable to reflect.
     * @return ReflectionFunctionAbstract Returns the reflection instance corresponding to the callable.
     */
    public static function callable(callable $callable): ReflectionFunctionAbstract
    {
        if ($callable instanceof Closure) {
            if (isset(self::$reflectors[$cacheKey = spl_object_hash($callable)])) {
                return self::$reflectors[$cacheKey];
            }

            return self::$reflectors[$cacheKey] = new ReflectionFunction($callable);
        }

        if (is_string($callable)) {
            if (isset(self::$reflectors[$callable])) return self::$reflectors[$callable];
            if (str_contains($callable, '::')) {
                [$class, $method] = explode('::', $callable, 2);
                return self::$reflectors[$callable] = new ReflectionMethod($class, $method);
            } else return self::$reflectors[$callable] = new ReflectionFunction($callable);
        }

        if (is_array($callable)) {
            list ($objectOrClass, $method) = $callable;
            if (is_object($objectOrClass)) $objectOrClass = $objectOrClass::class;
            return self::$reflectors["$objectOrClass::$method"] = new ReflectionMethod($objectOrClass, $method);
        }

        $cacheKey = spl_object_hash($callable);
        if (isset(self::$reflectors[$cacheKey])) return self::$reflectors[$cacheKey];
        return self::$reflectors[$callable::class] = new ReflectionMethod($callable, '__invoke');
    }

    /**
     * Returns a ReflectionObject for the given object instance.
     *
     * The created ReflectionObject is cached based on the object’s spl_object_hash.
     *
     * @param object $object The object to reflect.
     * @return ReflectionObject Returns the ReflectionObject representing the given object.
     */
    public static function object(object $object): ReflectionObject
    {
        return self::$reflectors[$id = spl_object_hash($object)] ?? self::$reflectors[$id] = new ReflectionObject($object);
    }

    /**
     * Returns a ReflectionClass for the given class name.
     *
     * If the class exists, its reflection is cached and returned. If the class does not exist, null is returned.
     *
     * @param string $class The class name to reflect.
     * @return null|ReflectionClass Returns the ReflectionClass instance if the class exists; otherwise, null.
     */
    public static function class(string $class): ?ReflectionClass
    {
        if (isset(self::$reflectors[$class])) return self::$reflectors[$class];
        if (class_exists($class)) return self::$reflectors[$class] = new ReflectionClass($class);

        return null;
    }
}

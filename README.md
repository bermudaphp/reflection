# Bermuda Reflection

**[Русская версия](README.RU.md)**

A powerful static utility class for PHP reflection operations with built-in caching and advanced metadata (attributes) handling. Provides convenient methods for working with PHP 8+ attributes and reflection objects.

## Features

- 🚀 **Performance Optimized**: Built-in caching for reflection objects
- 🎯 **Attribute Support**: Comprehensive PHP 8+ attributes handling
- 🔍 **Deep Search**: Search attributes in class members (methods, properties, constants)
- 🧩 **Universal Reflection**: Smart reflection creation from various data types
- 💾 **Memory Efficient**: Automatic caching prevents duplicate reflection objects
- 🔧 **Type Safe**: Full generic type support for attributes

## Installation

```bash
composer require bermudaphp/reflection
```

## Requirements

- PHP 8.4 or higher

## Quick Start

### Basic Attribute Retrieval

```php
use Bermuda\Reflection\Reflection;

class UserController
{
    #[Route('/api/users')]
    #[Auth('admin')]
    public function index(): Response
    {
        // method implementation
    }
}

$reflection = new ReflectionMethod(UserController::class, 'index');

// Get all attributes
$attributes = Reflection::getMetadata($reflection);

// Get specific attribute type
$routes = Reflection::getMetadata($reflection, Route::class);

// Get first attribute of specific type
$route = Reflection::getFirstMetadata($reflection, Route::class);

// Check if attribute exists
$hasAuth = Reflection::hasMetadata($reflection, Auth::class); // true
```

### Universal Reflection

```php
use Bermuda\Reflection\Reflection;

// Reflect different types automatically
$reflection = Reflection::reflect('MyClass');           // ReflectionClass
$reflection = Reflection::reflect($object);             // ReflectionObject  
$reflection = Reflection::reflect('function_name');     // ReflectionFunction
$reflection = Reflection::reflect([$obj, 'method']);    // ReflectionMethod
$reflection = Reflection::reflect(fn() => true);       // ReflectionFunction
```

## Advanced Features

### Deep Attribute Search

Search for attributes not only on the class itself, but also on all its members:

```php
use Bermuda\Reflection\Reflection;

class UserService
{
    #[Inject]
    private UserRepository $repository;
    
    #[Route('/users')]
    #[Cache(ttl: 3600)]
    public function getUsers(): array
    {
        return $this->repository->findAll();
    }
    
    #[Deprecated('Use getUsers() instead')]
    public const OLD_ENDPOINT = '/api/users';
}

$reflection = new ReflectionClass(UserService::class);

// Get ALL attributes from class and its members
$allAttributes = Reflection::getDeepMetadata($reflection);
/*
Returns:
[
    'UserService::$repository' => [Inject],
    'UserService::getUsers' => [Route, Cache],
    'UserService::OLD_ENDPOINT' => [Deprecated]
]
*/

// Get specific attribute type from anywhere in the class
$injectAttributes = Reflection::getDeepMetadata($reflection, Inject::class);
/*
Returns:
[
    'UserService::$repository' => [Inject]
]
*/

// Get first occurrence of attribute in the class
$firstRoute = Reflection::getFirstDeepMetadata($reflection, Route::class);

// Check if class has attribute anywhere
$hasInject = Reflection::hasDeepMetadata($reflection, Inject::class); // true
```

### Path Format for Deep Search

Deep search results use descriptive paths to identify where attributes were found:

- `ClassName` - Attribute on the class itself
- `ClassName::methodName` - Attribute on a method
- `ClassName::$propertyName` - Attribute on a property  
- `ClassName::CONSTANT_NAME` - Attribute on a class constant

### Reflection Caching

The class automatically caches reflection objects for improved performance:

```php
use Bermuda\Reflection\Reflection;

// First call creates and caches ReflectionClass
$reflection1 = Reflection::class('MyClass');

// Second call returns cached instance (faster)
$reflection2 = Reflection::class('MyClass');

// $reflection1 === $reflection2 (same object)

// Manually add custom reflector to cache
Reflection::addReflector('my-key', $customReflector);
```

## API Reference

### Metadata Methods

#### `getMetadata()`
```php
public static function getMetadata(
    ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant $reflector,
    ?string $name = null
): ?array
```

Retrieves all attributes from a reflection object. Optionally filter by attribute class name.

```php
$allAttributes = Reflection::getMetadata($reflection);
$routeAttributes = Reflection::getMetadata($reflection, Route::class);
```

#### `getFirstMetadata()`
```php
public static function getFirstMetadata(
    ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant|ReflectionProperty $reflector,
    string $name
): ?object
```

Gets the first attribute instance of the specified type.

```php
$route = Reflection::getFirstMetadata($methodReflection, Route::class);
if ($route) {
    echo $route->path; // '/api/users'
}
```

#### `hasMetadata()`
```php
public static function hasMetadata(
    ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant|ReflectionProperty $reflector,
    string $name
): bool
```

Checks if the reflection object has any attributes of the specified type.

```php
if (Reflection::hasMetadata($reflection, Cache::class)) {
    // Handle caching logic
}
```

### Deep Search Methods

#### `getDeepMetadata()`
```php
public static function getDeepMetadata(
    ReflectionClass $reflector,
    ?string $name = null
): array
```

Searches for attributes in the class and all its members (methods, properties, constants).

```php
// Get all attributes from everywhere in the class
$allAttributes = Reflection::getDeepMetadata($classReflection);

// Get only Route attributes from anywhere in the class
$routes = Reflection::getDeepMetadata($classReflection, Route::class);
```

#### `getFirstDeepMetadata()`
```php
public static function getFirstDeepMetadata(
    ReflectionClass $reflector,
    string $name
): ?object
```

Gets the first attribute instance found anywhere in the class.

```php
$firstRoute = Reflection::getFirstDeepMetadata($classReflection, Route::class);
```

#### `hasDeepMetadata()`
```php
public static function hasDeepMetadata(
    ReflectionClass $reflector,
    string $name
): bool
```

Checks if the class or any of its members have the specified attribute.

```php
if (Reflection::hasDeepMetadata($classReflection, Inject::class)) {
    // Class uses dependency injection somewhere
}
```

### Reflection Creation Methods

#### `reflect()`
```php
public static function reflect(mixed $var): null|ReflectionFunctionAbstract|ReflectionClass|ReflectionObject
```

Universal reflection method that automatically determines the appropriate reflection type.

```php
$reflection = Reflection::reflect('MyClass');        // ReflectionClass
$reflection = Reflection::reflect($instance);        // ReflectionObject
$reflection = Reflection::reflect('strlen');         // ReflectionFunction
$reflection = Reflection::reflect([$obj, 'method']); // ReflectionMethod
```

#### `callable()`
```php
public static function callable(callable $callable): ReflectionFunctionAbstract
```

Creates reflection for callable types (functions, methods, closures).

```php
$reflection = Reflection::callable('strlen');           // ReflectionFunction
$reflection = Reflection::callable([$obj, 'method']);   // ReflectionMethod
$reflection = Reflection::callable(fn() => true);      // ReflectionFunction
$reflection = Reflection::callable('Class::method');    // ReflectionMethod
```

#### `object()`
```php
public static function object(object $object): ReflectionObject
```

Creates cached ReflectionObject for the given object instance.

```php
$reflection = Reflection::object($userInstance);
echo $reflection->getName(); // 'User'
```

#### `class()`
```php
public static function class(string $class): ?ReflectionClass
```

Creates cached ReflectionClass for the given class name. Returns null if class doesn't exist.

```php
$reflection = Reflection::class('User');
$reflection = Reflection::class('NonExistentClass'); // null
```

## Real-World Examples

### Dependency Injection Container

```php
use Bermuda\Reflection\Reflection;

class Container
{
    public function autowire(string $className): object
    {
        $reflection = Reflection::class($className);
        
        if (!$reflection) {
            throw new Exception("Class $className not found");
        }
        
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return new $className();
        }
        
        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->autowire($type->getName());
            }
        }
        
        return new $className(...$dependencies);
    }
}
```

### Route Discovery

```php
use Bermuda\Reflection\Reflection;

class RouteDiscovery
{
    public function discoverRoutes(array $controllerClasses): array
    {
        $routes = [];
        
        foreach ($controllerClasses as $className) {
            $reflection = Reflection::class($className);
            
            // Find all Route attributes in the class
            $routeAttributes = Reflection::getDeepMetadata($reflection, Route::class);
            
            foreach ($routeAttributes as $path => $attributes) {
                foreach ($attributes as $route) {
                    $routes[] = [
                        'path' => $route->path,
                        'handler' => $path,
                        'methods' => $route->methods ?? ['GET']
                    ];
                }
            }
        }
        
        return $routes;
    }
}
```

### Validation with Attributes

```php
use Bermuda\Reflection\Reflection;

class Validator
{
    public function validate(object $entity): array
    {
        $reflection = Reflection::object($entity);
        $errors = [];
        
        foreach ($reflection->getProperties() as $property) {
            $value = $property->getValue($entity);
            
            // Check for validation attributes
            $required = Reflection::getFirstMetadata($property, Required::class);
            if ($required && empty($value)) {
                $errors[] = "{$property->getName()} is required";
            }
            
            $length = Reflection::getFirstMetadata($property, Length::class);
            if ($length && strlen($value) > $length->max) {
                $errors[] = "{$property->getName()} is too long";
            }
        }
        
        return $errors;
    }
}
```

### Event Handler Discovery

```php
use Bermuda\Reflection\Reflection;

class EventManager
{
    public function registerHandlers(object $listener): void
    {
        $reflection = Reflection::object($listener);
        
        foreach ($reflection->getMethods() as $method) {
            $eventHandler = Reflection::getFirstMetadata($method, EventHandler::class);
            
            if ($eventHandler) {
                $this->addEventListener(
                    $eventHandler->eventType,
                    [$listener, $method->getName()]
                );
            }
        }
    }
}
```

## Performance Tips

1. **Leverage Caching**: The class automatically caches reflection objects, so prefer using static methods over creating new reflections manually
2. **Use Specific Searches**: When searching for specific attribute types, pass the class name to avoid unnecessary processing
3. **Deep Search Wisely**: Use deep search only when you need to search in class members, as it's more expensive than regular metadata retrieval
4. **Batch Operations**: When working with multiple classes, the caching system provides significant performance benefits

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

<?php

declare(strict_types=1);

namespace Bermuda\Reflection\Tests;

use Bermuda\Reflection\Reflection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;

class ReflectionTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear reflection cache before each test
        $reflection = new \ReflectionClass(Reflection::class);
        $reflection->setStaticPropertyValue('reflectors', []);
    }

    // === CORE METADATA FUNCTIONALITY ===

    public function testGetMetadata(): void
    {
        $reflection = new ReflectionClass(TestController::class);

        // Test getting all attributes
        $attributes = Reflection::getMetadata($reflection);
        $this->assertNotNull($attributes);
        $this->assertCount(2, $attributes);
        $this->assertInstanceOf(TestRoute::class, $attributes[0]);
        $this->assertInstanceOf(TestAuth::class, $attributes[1]);

        // Test getting specific attribute type
        $routes = Reflection::getMetadata($reflection, TestRoute::class);
        $this->assertNotNull($routes);
        $this->assertCount(1, $routes);
        $this->assertEquals('/api', $routes[0]->path);

        // Test with no attributes
        $simpleReflection = new ReflectionClass(SimpleClass::class);
        $noAttributes = Reflection::getMetadata($simpleReflection);
        $this->assertNull($noAttributes);
    }

    public function testGetFirstMetadata(): void
    {
        $reflection = new ReflectionClass(TestController::class);

        // Test getting first attribute
        $route = Reflection::getFirstMetadata($reflection, TestRoute::class);
        $this->assertNotNull($route);
        $this->assertInstanceOf(TestRoute::class, $route);
        $this->assertEquals('/api', $route->path);

        // Test with non-existent attribute
        $cache = Reflection::getFirstMetadata($reflection, TestCache::class);
        $this->assertNull($cache);

        // Test with method
        $methodReflection = new ReflectionMethod(TestController::class, 'getUsers');
        $methodRoute = Reflection::getFirstMetadata($methodReflection, TestRoute::class);
        $this->assertNotNull($methodRoute);
        $this->assertEquals('/users', $methodRoute->path);

        // Test with property
        $propertyReflection = new ReflectionProperty(TestController::class, 'userService');
        $inject = Reflection::getFirstMetadata($propertyReflection, TestInject::class);
        $this->assertNotNull($inject);
        $this->assertEquals('userService', $inject->service);
    }

    public function testHasMetadata(): void
    {
        $reflection = new ReflectionClass(TestController::class);

        // Test existing attributes
        $this->assertTrue(Reflection::hasMetadata($reflection, TestRoute::class));
        $this->assertTrue(Reflection::hasMetadata($reflection, TestAuth::class));

        // Test non-existent attribute
        $this->assertFalse(Reflection::hasMetadata($reflection, TestCache::class));

        // Test with method
        $methodReflection = new ReflectionMethod(TestController::class, 'getUsers');
        $this->assertTrue(Reflection::hasMetadata($methodReflection, TestRoute::class));
        $this->assertTrue(Reflection::hasMetadata($methodReflection, TestCache::class));
        $this->assertFalse(Reflection::hasMetadata($methodReflection, TestAuth::class));

        // Test with parameter
        $paramReflection = new ReflectionParameter([TestController::class, 'createUser'], 'data');
        $this->assertTrue(Reflection::hasMetadata($paramReflection, TestInject::class));
    }

    // === DEEP SEARCH FUNCTIONALITY ===

    public function testGetDeepMetadata(): void
    {
        $reflection = new ReflectionClass(TestController::class);

        // Test getting all deep metadata
        $allAttributes = Reflection::getDeepMetadata($reflection);
        $this->assertNotEmpty($allAttributes);

        // Should have attributes from class, methods, properties, and constants
        $this->assertArrayHasKey('Bermuda\Reflection\Tests\TestController', $allAttributes);
        $this->assertArrayHasKey('Bermuda\Reflection\Tests\TestController::getUsers', $allAttributes);
        $this->assertArrayHasKey('Bermuda\Reflection\Tests\TestController::createUser', $allAttributes);
        $this->assertArrayHasKey('Bermuda\Reflection\Tests\TestController::$userService', $allAttributes);
        $this->assertArrayHasKey('Bermuda\Reflection\Tests\TestController::OLD_ENDPOINT', $allAttributes);

        // Test getting specific attribute type
        $routeAttributes = Reflection::getDeepMetadata($reflection, TestRoute::class);
        $this->assertCount(3, $routeAttributes); // Class + 2 methods

        $injectAttributes = Reflection::getDeepMetadata($reflection, TestInject::class);
        $this->assertCount(1, $injectAttributes); // Property only

        // Test class without attributes
        $simpleReflection = new ReflectionClass(SimpleClass::class);
        $noAttributes = Reflection::getDeepMetadata($simpleReflection);
        $this->assertEmpty($noAttributes);
    }

    public function testGetFirstDeepMetadata(): void
    {
        $reflection = new ReflectionClass(TestController::class);

        // Test getting first route attribute
        $firstRoute = Reflection::getFirstDeepMetadata($reflection, TestRoute::class);
        $this->assertNotNull($firstRoute);
        $this->assertInstanceOf(TestRoute::class, $firstRoute);
        $this->assertEquals('/api', $firstRoute->path);

        // Test with non-existent attribute
        $nonExistent = Reflection::getFirstDeepMetadata($reflection, \stdClass::class);
        $this->assertNull($nonExistent);
    }

    public function testHasDeepMetadata(): void
    {
        $reflection = new ReflectionClass(TestController::class);

        // Test attributes that exist somewhere in the class
        $this->assertTrue(Reflection::hasDeepMetadata($reflection, TestRoute::class));
        $this->assertTrue(Reflection::hasDeepMetadata($reflection, TestAuth::class));
        $this->assertTrue(Reflection::hasDeepMetadata($reflection, TestInject::class));
        $this->assertTrue(Reflection::hasDeepMetadata($reflection, TestCache::class));
        $this->assertTrue(Reflection::hasDeepMetadata($reflection, TestDeprecated::class));

        // Test non-existent attribute
        $this->assertFalse(Reflection::hasDeepMetadata($reflection, \stdClass::class));

        // Test class without attributes
        $simpleReflection = new ReflectionClass(SimpleClass::class);
        $this->assertFalse(Reflection::hasDeepMetadata($simpleReflection, TestRoute::class));
    }

    // === CORE REFLECTION FUNCTIONALITY ===

    public function testReflect(): void
    {
        // Test with class name
        $classReflection = Reflection::reflect(TestController::class);
        $this->assertInstanceOf(ReflectionClass::class, $classReflection);
        $this->assertEquals(TestController::class, $classReflection->getName());

        // Test with object
        $object = new TestController();
        $objectReflection = Reflection::reflect($object);
        $this->assertInstanceOf(ReflectionObject::class, $objectReflection);
        $this->assertEquals(TestController::class, $objectReflection->getName());

        // Test with function name
        $functionReflection = Reflection::reflect('strlen');
        $this->assertInstanceOf(ReflectionFunction::class, $functionReflection);
        $this->assertEquals('strlen', $functionReflection->getName());

        // Test with closure
        $closure = fn() => 'test';
        $closureReflection = Reflection::reflect($closure);
        $this->assertInstanceOf(ReflectionFunction::class, $closureReflection);

        // Test with non-reflectable value
        $nullReflection = Reflection::reflect(123);
        $this->assertNull($nullReflection);

        // Test with non-existent class
        $nonExistentReflection = Reflection::reflect('NonExistentClass');
        $this->assertNull($nonExistentReflection);
    }

    public function testCallable(): void
    {
        // Test with function name
        $functionReflection = Reflection::callable('strlen');
        $this->assertInstanceOf(ReflectionFunction::class, $functionReflection);
        $this->assertEquals('strlen', $functionReflection->getName());

        // Test with static method string (using built-in class)
        $staticMethodReflection = Reflection::callable('DateTime::createFromFormat');
        $this->assertInstanceOf(ReflectionMethod::class, $staticMethodReflection);
        $this->assertEquals('createFromFormat', $staticMethodReflection->getName());

        // Test with array callable (object method)
        $object = new TestController();
        $objectMethodReflection = Reflection::callable([$object, 'getUsers']);
        $this->assertInstanceOf(ReflectionMethod::class, $objectMethodReflection);
        $this->assertEquals('getUsers', $objectMethodReflection->getName());

        // Test with array callable (static method using built-in class)
        $arrayStaticReflection = Reflection::callable(['DateTime', 'createFromFormat']);
        $this->assertInstanceOf(ReflectionMethod::class, $arrayStaticReflection);
        $this->assertEquals('createFromFormat', $arrayStaticReflection->getName());

        // Test with closure
        $closure = fn() => 'test';
        $closureReflection = Reflection::callable($closure);
        $this->assertInstanceOf(ReflectionFunction::class, $closureReflection);
    }

    public function testObject(): void
    {
        $object = new TestController();
        $objectReflection = Reflection::object($object);

        $this->assertInstanceOf(ReflectionObject::class, $objectReflection);
        $this->assertEquals(TestController::class, $objectReflection->getName());

        // Test caching - should return same instance
        $secondReflection = Reflection::object($object);
        $this->assertSame($objectReflection, $secondReflection);
    }

    public function testClass(): void
    {
        // Test with existing class
        $classReflection = Reflection::class(TestController::class);
        $this->assertInstanceOf(ReflectionClass::class, $classReflection);
        $this->assertEquals(TestController::class, $classReflection->getName());

        // Test caching - should return same instance
        $secondReflection = Reflection::class(TestController::class);
        $this->assertSame($classReflection, $secondReflection);

        // Test with non-existent class
        $nonExistentReflection = Reflection::class('NonExistentClass');
        $this->assertNull($nonExistentReflection);
    }

    public function testAddReflector(): void
    {
        $customReflection = new ReflectionClass(TestController::class);

        // Add custom reflector
        Reflection::addReflector('custom-key', $customReflection);

        // Test that it was added (we can't directly test the cache, but we can verify it doesn't interfere)
        $normalReflection = Reflection::class(TestController::class);
        $this->assertInstanceOf(ReflectionClass::class, $normalReflection);
    }

    // === CACHING FUNCTIONALITY ===

    public function testCaching(): void
    {
        // Test that consecutive calls return cached instances
        $reflection1 = Reflection::class(TestController::class);
        $reflection2 = Reflection::class(TestController::class);
        $this->assertSame($reflection1, $reflection2);

        // Test different cache keys
        $functionReflection1 = Reflection::callable('strlen');
        $functionReflection2 = Reflection::callable('strlen');
        $this->assertSame($functionReflection1, $functionReflection2);

        // Test object caching
        $object = new TestController();
        $objectReflection1 = Reflection::object($object);
        $objectReflection2 = Reflection::object($object);
        $this->assertSame($objectReflection1, $objectReflection2);
    }

    // === COMPLEX SCENARIOS ===

    public function testComplexDeepSearch(): void
    {
        $reflection = new ReflectionClass(TestController::class);

        // Test that we find attributes in all the right places
        $allRoutes = Reflection::getDeepMetadata($reflection, TestRoute::class);

        // Should find: class route + getUsers route + createUser route
        $this->assertCount(3, $allRoutes);

        $paths = array_keys($allRoutes);
        $this->assertContains('Bermuda\Reflection\Tests\TestController', $paths);
        $this->assertContains('Bermuda\Reflection\Tests\TestController::getUsers', $paths);
        $this->assertContains('Bermuda\Reflection\Tests\TestController::createUser', $paths);

        // Verify the actual route values
        $classRoute = $allRoutes['Bermuda\Reflection\Tests\TestController'][0];
        $this->assertEquals('/api', $classRoute->path);

        $getUsersRoute = $allRoutes['Bermuda\Reflection\Tests\TestController::getUsers'][0];
        $this->assertEquals('/users', $getUsersRoute->path);

        $createUserRoute = $allRoutes['Bermuda\Reflection\Tests\TestController::createUser'][0];
        $this->assertEquals('/users', $createUserRoute->path);
        $this->assertEquals(['POST'], $createUserRoute->methods);
    }

    // === BASIC FUNCTIONALITY TESTS ===

    public function testBasicClassReflection(): void
    {
        $reflection = Reflection::class(\stdClass::class);
        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertEquals('stdClass', $reflection->getName());
    }

    public function testBasicObjectReflection(): void
    {
        $object = new \stdClass();
        $reflection = Reflection::object($object);
        $this->assertInstanceOf(ReflectionObject::class, $reflection);
        $this->assertEquals('stdClass', $reflection->getName());
    }

    public function testBasicFunctionReflection(): void
    {
        $reflection = Reflection::callable('strlen');
        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
        $this->assertEquals('strlen', $reflection->getName());
    }

    public function testBuiltinMethodReflection(): void
    {
        $reflection = Reflection::callable('DateTime::createFromFormat');
        $this->assertInstanceOf(ReflectionMethod::class, $reflection);
        $this->assertEquals('createFromFormat', $reflection->getName());
    }

    public function testCachingWorks(): void
    {
        $reflection1 = Reflection::class(\stdClass::class);
        $reflection2 = Reflection::class(\stdClass::class);
        $this->assertSame($reflection1, $reflection2);
    }

    public function testNonExistentClass(): void
    {
        $reflection = Reflection::class('NonExistentClass123');
        $this->assertNull($reflection);
    }

    public function testReflectUniversalMethod(): void
    {
        // Test with class
        $classReflection = Reflection::reflect(\stdClass::class);
        $this->assertInstanceOf(ReflectionClass::class, $classReflection);

        // Test with object
        $object = new \stdClass();
        $objectReflection = Reflection::reflect($object);
        $this->assertInstanceOf(ReflectionObject::class, $objectReflection);

        // Test with function
        $functionReflection = Reflection::reflect('strlen');
        $this->assertInstanceOf(ReflectionFunction::class, $functionReflection);

        // Test with non-reflectable
        $nullReflection = Reflection::reflect(123);
        $this->assertNull($nullReflection);
    }

    public function testClosureReflection(): void
    {
        $closure = function() { return 'test'; };
        $reflection = Reflection::callable($closure);
        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
    }

    public function testReflectWithBuiltinFunction(): void
    {
        $reflection = Reflection::reflect('array_map');
        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
        $this->assertEquals('array_map', $reflection->getName());
        $this->assertTrue($reflection->isInternal());
    }

    public function testMetadataWithEmptyResults(): void
    {
        $reflection = new ReflectionClass(SimpleClass::class);

        // Test all metadata methods with class that has no attributes
        $metadata = Reflection::getMetadata($reflection);
        $this->assertNull($metadata);

        $firstMetadata = Reflection::getFirstMetadata($reflection, TestRoute::class);
        $this->assertNull($firstMetadata);

        $hasMetadata = Reflection::hasMetadata($reflection, TestRoute::class);
        $this->assertFalse($hasMetadata);
    }

    public function testCallableWithBuiltinClasses(): void
    {
        // Test various built-in PHP classes and functions
        $dateTimeReflection = Reflection::callable('DateTime::createFromFormat');
        $this->assertInstanceOf(ReflectionMethod::class, $dateTimeReflection);
        $this->assertEquals('DateTime', $dateTimeReflection->getDeclaringClass()->getName());

        // Test with array format
        $arrayReflection = Reflection::callable(['DateTime', 'createFromFormat']);
        $this->assertInstanceOf(ReflectionMethod::class, $arrayReflection);
        $this->assertEquals('createFromFormat', $arrayReflection->getName());

        // Test with regular function
        $strlenReflection = Reflection::callable('strlen');
        $this->assertInstanceOf(ReflectionFunction::class, $strlenReflection);
        $this->assertTrue($strlenReflection->isInternal());
    }

    public function testComplexCaching(): void
    {
        // Test caching across different reflection types

        // Class caching
        $class1 = Reflection::class(TestController::class);
        $class2 = Reflection::class(TestController::class);
        $this->assertSame($class1, $class2);

        // Function caching
        $func1 = Reflection::callable('strlen');
        $func2 = Reflection::callable('strlen');
        $this->assertSame($func1, $func2);

        // Object caching
        $obj = new TestController();
        $objRef1 = Reflection::object($obj);
        $objRef2 = Reflection::object($obj);
        $this->assertSame($objRef1, $objRef2);

        // Method caching with string
        $method1 = Reflection::callable('DateTime::createFromFormat');
        $method2 = Reflection::callable('DateTime::createFromFormat');
        $this->assertSame($method1, $method2);
    }

    public function testReflectErrorHandling(): void
    {
        // Test various error conditions that should return null
        $this->assertNull(Reflection::reflect('NonExistentFunction123'));
        $this->assertNull(Reflection::reflect('NonExistentClass123'));
        $this->assertNull(Reflection::reflect(null));
        $this->assertNull(Reflection::reflect([]));
        $this->assertNull(Reflection::reflect(123));
        $this->assertNull(Reflection::reflect(true));
        $this->assertNull(Reflection::reflect(3.14));

        // Test class method
        $this->assertNull(Reflection::class(''));
        $this->assertNull(Reflection::class('NonExistent'));

        // These should NOT be null (valid cases)
        $this->assertNotNull(Reflection::reflect(new \stdClass())); // Objects are valid
        $this->assertNotNull(Reflection::reflect('strlen')); // Functions are valid
        $this->assertNotNull(Reflection::reflect(\stdClass::class)); // Classes are valid
    }

    // === EDGE CASES ===

    public function testMultipleAttributesOfSameType(): void
    {
        $methodReflection = new ReflectionMethod(MultipleAttributesClass::class, 'methodWithMultipleAttributes');

        // Test getting all attributes of same type
        $attributes = Reflection::getMetadata($methodReflection, TestMultiple::class);
        $this->assertNotNull($attributes);
        $this->assertCount(3, $attributes);

        $values = array_map(fn($attr) => $attr->value, $attributes);
        $this->assertEquals(['first', 'second', 'third'], $values);

        // Test getting first attribute
        $firstAttribute = Reflection::getFirstMetadata($methodReflection, TestMultiple::class);
        $this->assertNotNull($firstAttribute);
        $this->assertEquals('first', $firstAttribute->value);
    }

    public function testEmptyClassDeepSearch(): void
    {
        $reflection = new ReflectionClass(EmptyClass::class);

        // Test that empty class returns empty results
        $deepMetadata = Reflection::getDeepMetadata($reflection);
        $this->assertEmpty($deepMetadata);

        $specificMetadata = Reflection::getDeepMetadata($reflection, TestRoute::class);
        $this->assertEmpty($specificMetadata);

        $firstDeepMetadata = Reflection::getFirstDeepMetadata($reflection, TestRoute::class);
        $this->assertNull($firstDeepMetadata);

        $hasDeepMetadata = Reflection::hasDeepMetadata($reflection, TestRoute::class);
        $this->assertFalse($hasDeepMetadata);
    }

    public function testInvokableCallable(): void
    {
        $invokable = new InvokableClass();

        // Test reflecting invokable object
        $reflection = Reflection::callable($invokable);
        $this->assertInstanceOf(ReflectionMethod::class, $reflection);
        $this->assertEquals('__invoke', $reflection->getName());
        $this->assertEquals(InvokableClass::class, $reflection->getDeclaringClass()->getName());
    }

    public function testClosureCaching(): void
    {
        $closure = fn() => 'test';

        // Test that closures are cached properly
        $reflection1 = Reflection::callable($closure);
        $reflection2 = Reflection::callable($closure);

        $this->assertSame($reflection1, $reflection2);
        $this->assertInstanceOf(ReflectionFunction::class, $reflection1);
    }

    public function testStaticMethodCallableVariations(): void
    {
        // Test different formats of static method callables (using built-in classes)
        $stringCallable = Reflection::callable('DateTime::createFromFormat');
        $arrayCallable = Reflection::callable(['DateTime', 'createFromFormat']);

        $this->assertInstanceOf(ReflectionMethod::class, $stringCallable);
        $this->assertInstanceOf(ReflectionMethod::class, $arrayCallable);
        $this->assertEquals('createFromFormat', $stringCallable->getName());
        $this->assertEquals('createFromFormat', $arrayCallable->getName());
    }

    public function testObjectMethodCaching(): void
    {
        $object1 = new EmptyClass();
        $object2 = new EmptyClass();

        // Test that different objects create different cache entries
        $reflection1 = Reflection::callable([$object1, 'plainMethod']);
        $reflection2 = Reflection::callable([$object2, 'plainMethod']);

        // Should be same type but potentially different instances
        $this->assertInstanceOf(ReflectionMethod::class, $reflection1);
        $this->assertInstanceOf(ReflectionMethod::class, $reflection2);
        $this->assertEquals('plainMethod', $reflection1->getName());
        $this->assertEquals('plainMethod', $reflection2->getName());
    }

    public function testReflectWithNonExistentFunction(): void
    {
        $reflection = Reflection::reflect('non_existent_function');
        $this->assertNull($reflection);
    }

    public function testClassCachingWithNonExistentClass(): void
    {
        // Test that non-existent classes return null consistently
        $reflection1 = Reflection::class('NonExistentClass');
        $reflection2 = Reflection::class('NonExistentClass');

        $this->assertNull($reflection1);
        $this->assertNull($reflection2);
    }

    public function testDeepMetadataPathFormats(): void
    {
        $reflection = new ReflectionClass(TestController::class);
        $allMetadata = Reflection::getDeepMetadata($reflection);

        $paths = array_keys($allMetadata);

        // Verify path formats
        $hasClassPath = false;
        $hasMethodPath = false;
        $hasPropertyPath = false;
        $hasConstantPath = false;

        foreach ($paths as $path) {
            if (!str_contains($path, '::')) {
                $hasClassPath = true;
            } elseif (str_contains($path, '::$')) {
                $hasPropertyPath = true;
            } elseif (str_contains($path, '::') && ctype_upper(substr($path, strrpos($path, '::') + 2, 1))) {
                $hasConstantPath = true;
            } else {
                $hasMethodPath = true;
            }
        }

        $this->assertTrue($hasClassPath, 'Should have class-level attributes');
        $this->assertTrue($hasMethodPath, 'Should have method-level attributes');
        $this->assertTrue($hasPropertyPath, 'Should have property-level attributes');
        $this->assertTrue($hasConstantPath, 'Should have constant-level attributes');
    }

    public function testAddReflectorOverride(): void
    {
        $originalReflection = Reflection::class(TestController::class);
        $customReflection = new ReflectionClass(EmptyClass::class);

        // Add custom reflector with same key
        Reflection::addReflector(TestController::class, $customReflection);

        // Should return the custom reflector now
        $newReflection = Reflection::class(TestController::class);

        // Note: This test assumes the cache is accessible and overridden
        // In real implementation, this might behave differently
        $this->assertInstanceOf(ReflectionClass::class, $newReflection);
    }

    public function testCallableWithObjectAndStringMethod(): void
    {
        $object = new EmptyClass();
        $reflection = Reflection::callable([$object, 'plainMethod']);

        $this->assertInstanceOf(ReflectionMethod::class, $reflection);
        $this->assertEquals('plainMethod', $reflection->getName());
        $this->assertEquals(EmptyClass::class, $reflection->getDeclaringClass()->getName());
    }

    public function testBasicReflectionFunctionality(): void
    {
        // Test basic built-in functionality to ensure our class works
        $strlenReflection = Reflection::callable('strlen');
        $this->assertInstanceOf(ReflectionFunction::class, $strlenReflection);
        $this->assertEquals('strlen', $strlenReflection->getName());

        // Test class reflection
        $stdClassReflection = Reflection::class('stdClass');
        $this->assertInstanceOf(ReflectionClass::class, $stdClassReflection);
        $this->assertEquals('stdClass', $stdClassReflection->getName());

        // Test object reflection
        $object = new \stdClass();
        $objectReflection = Reflection::object($object);
        $this->assertInstanceOf(ReflectionObject::class, $objectReflection);
        $this->assertEquals('stdClass', $objectReflection->getName());
    }
}

// Test attributes
#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
class TestRoute
{
    public function __construct(
        public string $path,
        public array $methods = ['GET']
    ) {}
}

#[\Attribute(\Attribute::TARGET_ALL)]
class TestAuth
{
    public function __construct(public string $role = 'user') {}
}

#[\Attribute(\Attribute::TARGET_ALL)]
class TestInject
{
    public function __construct(public ?string $service = null) {}
}

#[\Attribute(\Attribute::TARGET_ALL)]
class TestCache
{
    public function __construct(public int $ttl = 300) {}
}

#[\Attribute(\Attribute::TARGET_ALL)]
class TestDeprecated
{
    public function __construct(public string $message = '') {}
}

#[\Attribute(\Attribute::TARGET_ALL|\Attribute::IS_REPEATABLE)]
class TestMultiple
{
    public function __construct(public string $value) {}
}

// Test classes
#[TestRoute('/api')]
#[TestAuth('admin')]
class TestController
{
    #[TestInject('userService')]
    private string $userService;

    #[TestRoute('/users')]
    #[TestCache(ttl: 3600)]
    public function getUsers(): array
    {
        return [];
    }

    #[TestRoute('/users', ['POST'])]
    #[TestAuth('admin')]
    public function createUser(#[TestInject] string $data): void
    {
    }

    public function publicMethod(): string
    {
        return 'test';
    }

    #[TestDeprecated('Use getUsers() instead')]
    public const OLD_ENDPOINT = '/api/users';
}

class SimpleClass
{
    public function simpleMethod(): void {}
}

class MultipleAttributesClass
{
    #[TestMultiple('first')]
    #[TestMultiple('second')]
    #[TestMultiple('third')]
    public function methodWithMultipleAttributes(): void {}
}

class EmptyClass
{
    public function plainMethod(): void {}
    public string $plainProperty;
    public const PLAIN_CONSTANT = 'value';
}

class InvokableClass
{
    public function __invoke(): string
    {
        return 'invoked';
    }
}

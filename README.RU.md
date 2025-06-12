# Bermuda Reflection

**[English version](README.md)**

Мощный статический утилитарный класс для операций рефлексии PHP со встроенным кэшированием и продвинутой обработкой метаданных (атрибутов). Предоставляет удобные методы для работы с атрибутами PHP 8+ и объектами рефлексии.

## Возможности

- 🚀 **Оптимизация производительности**: Встроенное кэширование объектов рефлексии
- 🎯 **Поддержка атрибутов**: Комплексная обработка атрибутов PHP 8+
- 🔍 **Глубокий поиск**: Поиск атрибутов в членах класса (методы, свойства, константы)
- 🧩 **Универсальная рефлексия**: Умное создание рефлексии из различных типов данных
- 💾 **Эффективность памяти**: Автоматическое кэширование предотвращает дублирование объектов рефлексии
- 🔧 **Типобезопасность**: Полная поддержка дженерик типов для атрибутов

## Установка

```bash
composer require bermudaphp/reflection
```

## Требования

- PHP 8.4 или выше

## Быстрый старт

### Базовое получение атрибутов

```php
use Bermuda\Reflection\Reflection;

class UserController
{
    #[Route('/api/users')]
    #[Auth('admin')]
    public function index(): Response
    {
        // реализация метода
    }
}

$reflection = new ReflectionMethod(UserController::class, 'index');

// Получить все атрибуты
$attributes = Reflection::getMetadata($reflection);

// Получить атрибуты определённого типа
$routes = Reflection::getMetadata($reflection, Route::class);

// Получить первый атрибут определённого типа
$route = Reflection::getFirstMetadata($reflection, Route::class);

// Проверить существование атрибута
$hasAuth = Reflection::hasMetadata($reflection, Auth::class); // true
```

### Универсальная рефлексия

```php
use Bermuda\Reflection\Reflection;

// Автоматическое создание рефлексии для разных типов
$reflection = Reflection::reflect('MyClass');           // ReflectionClass
$reflection = Reflection::reflect($object);             // ReflectionObject  
$reflection = Reflection::reflect('function_name');     // ReflectionFunction
$reflection = Reflection::reflect([$obj, 'method']);    // ReflectionMethod
$reflection = Reflection::reflect(fn() => true);       // ReflectionFunction
```

## Продвинутые возможности

### Глубокий поиск атрибутов

Поиск атрибутов не только на самом классе, но и на всех его членах:

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
    
    #[Deprecated('Используйте getUsers() вместо этого')]
    public const OLD_ENDPOINT = '/api/users';
}

$reflection = new ReflectionClass(UserService::class);

// Получить ВСЕ атрибуты из класса и его членов
$allAttributes = Reflection::getDeepMetadata($reflection);
/*
Возвращает:
[
    'UserService::$repository' => [Inject],
    'UserService::getUsers' => [Route, Cache],
    'UserService::OLD_ENDPOINT' => [Deprecated]
]
*/

// Получить атрибуты определённого типа отовсюду в классе
$injectAttributes = Reflection::getDeepMetadata($reflection, Inject::class);
/*
Возвращает:
[
    'UserService::$repository' => [Inject]
]
*/

// Получить первое вхождение атрибута в классе
$firstRoute = Reflection::getFirstDeepMetadata($reflection, Route::class);

// Проверить, есть ли атрибут где-либо в классе
$hasInject = Reflection::hasDeepMetadata($reflection, Inject::class); // true
```

### Формат путей для глубокого поиска

Результаты глубокого поиска используют описательные пути для идентификации места обнаружения атрибутов:

- `ClassName` - Атрибут на самом классе
- `ClassName::methodName` - Атрибут на методе
- `ClassName::$propertyName` - Атрибут на свойстве
- `ClassName::CONSTANT_NAME` - Атрибут на константе класса

### Кэширование рефлексии

Класс автоматически кэширует объекты рефлексии для улучшения производительности:

```php
use Bermuda\Reflection\Reflection;

// Первый вызов создаёт и кэширует ReflectionClass
$reflection1 = Reflection::class('MyClass');

// Второй вызов возвращает кэшированный экземпляр (быстрее)
$reflection2 = Reflection::class('MyClass');

// $reflection1 === $reflection2 (тот же объект)

// Ручное добавление пользовательского рефлектора в кэш
Reflection::addReflector('my-key', $customReflector);
```

## Справочник по API

### Методы метаданных

#### `getMetadata()`
```php
public static function getMetadata(
    ReflectionFunctionAbstract|ReflectionClass|ReflectionParameter|ReflectionConstant $reflector,
    ?string $name = null
): ?array
```

Получает все атрибуты из объекта рефлексии. Опционально фильтрует по имени класса атрибута.

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

Получает первый экземпляр атрибута указанного типа.

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

Проверяет, есть ли у объекта рефлексии атрибуты указанного типа.

```php
if (Reflection::hasMetadata($reflection, Cache::class)) {
    // Обработать логику кэширования
}
```

### Методы глубокого поиска

#### `getDeepMetadata()`
```php
public static function getDeepMetadata(
    ReflectionClass $reflector,
    ?string $name = null
): array
```

Ищет атрибуты в классе и всех его членах (методы, свойства, константы).

```php
// Получить все атрибуты отовсюду в классе
$allAttributes = Reflection::getDeepMetadata($classReflection);

// Получить только Route атрибуты отовсюду в классе
$routes = Reflection::getDeepMetadata($classReflection, Route::class);
```

#### `getFirstDeepMetadata()`
```php
public static function getFirstDeepMetadata(
    ReflectionClass $reflector,
    string $name
): ?object
```

Получает первый экземпляр атрибута, найденный где-либо в классе.

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

Проверяет, есть ли у класса или любого из его членов указанный атрибут.

```php
if (Reflection::hasDeepMetadata($classReflection, Inject::class)) {
    // Класс где-то использует внедрение зависимостей
}
```

### Методы создания рефлексии

#### `reflect()`
```php
public static function reflect(mixed $var): null|ReflectionFunctionAbstract|ReflectionClass|ReflectionObject
```

Универсальный метод рефлексии, который автоматически определяет подходящий тип рефлексии.

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

Создаёт рефлексию для вызываемых типов (функции, методы, замыкания).

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

Создаёт кэшированный ReflectionObject для данного экземпляра объекта.

```php
$reflection = Reflection::object($userInstance);
echo $reflection->getName(); // 'User'
```

#### `class()`
```php
public static function class(string $class): ?ReflectionClass
```

Создаёт кэшированный ReflectionClass для данного имени класса. Возвращает null, если класс не существует.

```php
$reflection = Reflection::class('User');
$reflection = Reflection::class('NonExistentClass'); // null
```

## Примеры из реального мира

### Контейнер внедрения зависимостей

```php
use Bermuda\Reflection\Reflection;

class Container
{
    public function autowire(string $className): object
    {
        $reflection = Reflection::class($className);
        
        if (!$reflection) {
            throw new Exception("Класс $className не найден");
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

### Обнаружение маршрутов

```php
use Bermuda\Reflection\Reflection;

class RouteDiscovery
{
    public function discoverRoutes(array $controllerClasses): array
    {
        $routes = [];
        
        foreach ($controllerClasses as $className) {
            $reflection = Reflection::class($className);
            
            // Найти все Route атрибуты в классе
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

### Валидация с атрибутами

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
            
            // Проверить атрибуты валидации
            $required = Reflection::getFirstMetadata($property, Required::class);
            if ($required && empty($value)) {
                $errors[] = "{$property->getName()} обязательно для заполнения";
            }
            
            $length = Reflection::getFirstMetadata($property, Length::class);
            if ($length && strlen($value) > $length->max) {
                $errors[] = "{$property->getName()} слишком длинное";
            }
        }
        
        return $errors;
    }
}
```

### Обнаружение обработчиков событий

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

## Советы по производительности

1. **Используйте кэширование**: Класс автоматически кэширует объекты рефлексии, поэтому предпочитайте использование статических методов вместо ручного создания новых рефлексий
2. **Используйте конкретные поиски**: При поиске определённых типов атрибутов передавайте имя класса, чтобы избежать ненужной обработки
3. **Используйте глубокий поиск разумно**: Используйте глубокий поиск только когда нужно искать в членах класса, поскольку он более затратный чем обычное получение метаданных
4. **Групповые операции**: При работе с несколькими классами система кэширования обеспечивает значительные преимущества в производительности

## Лицензия

Этот проект лицензирован под лицензией MIT - смотрите файл [LICENSE](LICENSE) для подробностей.

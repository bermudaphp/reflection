<?php

namespace Bermuda\Reflection;

/**
 * @mixin ReflectionAttribute
 */
class ReflectionAttribute
{
    public function __construct(
        public readonly \ReflectionAttribute $attribute,
        public readonly \ReflectionProperty|ReflectionFunction|ReflectionClass|\ReflectionMethod $subject
    ) {
    }
    
    public function __call(string $name, array $arguments): mixed
    {
        return call_user_func_array([$this->attribute, $name], $arguments);
    }
}

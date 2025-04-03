<?php

namespace Bermuda\Reflection;

class ReflectionClass extends \ReflectionClass
{
    /**
     * @template T
     * @param string-class<T> $name
     */
    public function hasAttribute(string $name, bool $deep = false): bool
    {
        if ($deep) {
            if ($this->getPropetryAttribute($name) !== []) return true;
            if ($this->getMethodAttribute($name) !== []) return true;
        }

        return $this->getAttribute($name) !== null;
    }

    /**
     * @template T
     * @param string-class<T> $name
     * @return null|ReflectionAttribute[]|ReflectionAttribute
     */
    public function getAttribute(string $name, bool $deep = false): null|array|ReflectionAttribute
    {
        if ($deep) {
            return array_merge(
                $this->getPropetryAttribute($name),
                $this->getMethodAttribute($name),
                isset($this->getAttributes($name)[0]) ? [new ReflectionAttribute($this->getAttributes($name)[0], $this)] : []
            );
        }
        
        return isset($this->getAttributes($name)[0]) ? 
            new ReflectionAttribute($this->getAttributes($name)[0], $this) 
            : null;
    }

    public function isInvokable(): bool
    {
        return $this->hasMethod('__invoke');
    }

    /**
     * @param string $name
     * @return ReflectionAttribute[]
     */
    public function getPropetryAttribute(string $name): array
    {
        $attributes = [];

        foreach ($this->getProperties() as $property) {
            if (($attribute = $property->getAttributes($name)[0] ?? null) !== null) {
                $attributes[] = new ReflectionAttribute($attribute, $property);
            } 
        }

        return $attributes;
    }

    /**
     * @param string $name
     * @return ReflectionAttribute[]
     */
    public function getMethodAttribute(string $name): array
    {
        $attributes = [];

        foreach ($this->getMethods() as $method) {
            if (($attribute = $method->getAttributes($name)[0] ?? null) !== null) {
                $attributes[] = new ReflectionAttribute($attribute, $method);
            }
        }

        return $attributes;
    }
}

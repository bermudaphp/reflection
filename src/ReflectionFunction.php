<?php

namespace Bermuda\Reflection;

class ReflectionFunction extends \ReflectionFunction
{
    /**
     * @template T
     * @param string-class<T> $name
     * @return \ReflectionAttribute|null
     */
    public function getAttribute(string $name): ?ReflectionAttribute
    {
        $attribute = $this->getAttributes($name)[0] ?? null;
        return $attribute !== null ? new ReflectionAttribute($attribute, $this) : null;
    }

    public function hasAttributes(string $name): array
    {
        return $this->getAttributes($name)[0] !== null;
    }
}

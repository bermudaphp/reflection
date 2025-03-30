<?php

namespace Bermuda\Reflection;

class ReflectionClass extends \ReflectionClass
{
    public function hasAttribute(string $attribute): bool
    {
        return $this->getAttribute($attribute) !== null;
    }

    public function getAttribute(string $attribute): ?\ReflectionAttribute
    {
        return $this->getAttributes($attribute)[0] ?? null;
    }
}

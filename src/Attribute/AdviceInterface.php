<?php

declare(strict_types=1);

namespace Tourze\Symfony\Aop\Attribute;

interface AdviceInterface
{
    public function getStatement(): ?string;

    public function getClassAttribute(): ?string;

    public function getMethodAttribute(): ?string;

    /** @return array<string>|null */
    public function getServiceIds(): ?array;

    /** @return array<string>|null */
    public function getServiceTags(): ?array;

    /** @return array<string>|null */
    public function getParentClasses(): ?array;
}

<?php

namespace Tourze\Symfony\Aop\Attribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Aspect extends AutoconfigureTag
{
    const TAG_NAME = 'aop.aspect';

    public function __construct()
    {
        parent::__construct(self::TAG_NAME);
    }
}

<?php

namespace Tourze\Symfony\Aop\Attribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[\Attribute(flags: \Attribute::TARGET_CLASS)]
class Aspect extends AutoconfigureTag
{
    public const TAG_NAME = 'aop.aspect';

    public function __construct()
    {
        parent::__construct('aop.aspect');
    }
}

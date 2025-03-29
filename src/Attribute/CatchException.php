<?php

namespace Tourze\Symfony\Aop\Attribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[\Attribute(\Attribute::TARGET_METHOD)]
class CatchException extends AutoconfigureTag
{
    public function __construct()
    {
        parent::__construct('aop-catch-exception');
    }
}

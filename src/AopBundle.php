<?php

namespace AopBundle;

use AopBundle\DependencyInjection\AttributeCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AopBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AttributeCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}

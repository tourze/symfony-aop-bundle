<?php

namespace Tourze\Symfony\AOP;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\Symfony\AOP\DependencyInjection\Compiler\AopAttributeCompilerPass;

class AopBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AopAttributeCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}

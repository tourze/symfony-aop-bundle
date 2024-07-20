<?php

namespace AopBundle\DependencyInjection;

use AopBundle\Attribute\Advice;
use AopBundle\Attribute\Aspect;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ExpressionLanguage;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

class AttributeCompilerPass implements CompilerPassInterface
{
    const INTERNAL_PREFIX = 'sf-aop.';
    const INTERNAL_SUFFIX = '.internal-for-aop';

    public function process(ContainerBuilder $container): void
    {
        // 收集所有的 Aspect
        // 需要解析其中的所有 Method，判断是否存在切面
        $statements = [];
        foreach (array_keys($container->findTaggedServiceIds(Aspect::TAG_NAME)) as $serviceId) {
            $reflectionClass = $this->getReflectionClass($container, $serviceId);
            if (!$reflectionClass) {
                continue;
            }
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes() as $attribute) {
                    /** @var \ReflectionAttribute $attribute */
                    $name = $attribute->getName();
                    if (is_subclass_of($name, Advice::class)) {
                        $instance = $attribute->newInstance();
                        /** @var Advice $instance */
                        if (!isset($statements[$instance->statement])) {
                            $statements[$instance->statement] = [];
                        }
                        if (!isset($statements[$instance->statement][$name])) {
                            $statements[$instance->statement][$name] = [];
                        }
                        $statements[$instance->statement][$name][] = [$serviceId, $method->getName()];
                        // 强制修改为public，方便Interceptor去动态执行
                        $definition = $container->findDefinition($serviceId);
                        $definition->setPublic(true);
                    }
                }
            }
        }

        if (empty($statements)) {
            return;
        }

        // 开始切入
        // 这里为了快速实现，我们使用了表达式组件，跟Spring的实现有差异
        $expressionLanguage = new ExpressionLanguage(null, $container->getExpressionLanguageProviders());
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('count'));
        foreach ($container->getServiceIds() as $serviceId) {
            if ($this->isInternalService($serviceId)) {
                continue;
            }
            $reflectionClass = $this->getReflectionClass($container, $serviceId);
            if (!$reflectionClass) {
                continue;
            }
            // 为了减少循环依赖问题，这里我们暂时忽略Aspect
            if (!empty($reflectionClass->getAttributes(Aspect::class))) {
                continue;
            }
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $fullName = $reflectionClass->getName() . '::' . $method->getName();
                foreach ($statements as $statement => $config) {
                    // 满足条件，我们开始拦截方法
                    if ($statement !== $fullName && !$expressionLanguage->evaluate($statement, ['method' => $method])) {
                        continue;
                    }

                    // 创建一个新的service覆盖原service
                    $internalId = $this->updateInternalServiceId($container, $serviceId);
                    $aopNewService = $container->findDefinition($serviceId);

                    // 以 $method 为基准，创建一个 AopInterceptor 对象
                    $interceptorId = self::INTERNAL_PREFIX . "$serviceId.{$method->getModifiers()}.{$method->getName()}.interceptor";
                    if (!$container->hasDefinition($interceptorId)) {
                        $interceptorDefinition = clone $container->getDefinition('sf-aop.interceptor');
                        $interceptorDefinition->addMethodCall('setServiceId', [$serviceId]);
                        $interceptorDefinition->addMethodCall('setMethod', [$method->getName()]);
                        $container->setDefinition($interceptorId, $interceptorDefinition);

                        // 直接传一个服务会报错，要Closure才行
                        $closureDefinition = clone $container->getDefinition('sf-aop.closure');
                        $closureDefinition->setArguments([
                            new Reference($interceptorId),
                        ]);
                        $closureId = "{$interceptorId}.closure";
                        $container->setDefinition($closureId, $closureDefinition);

                        // 拦截这个方法
                        $aopNewService->addMethodCall('setMethodPrefixInterceptor', [
                            $method->getName(),
                            new Reference($closureId),
                        ]);
                    }

                    $interceptorDefinition = $container->findDefinition($interceptorId);
                    $interceptorDefinition->addMethodCall('addAttributes', [
                        $config,
                    ]);
                }
            }
        }
    }

    private function getReflectionClass(ContainerBuilder $container, string $serviceId): ?\ReflectionClass
    {
        // 别名的，我们都跳过
        if ($container->hasAlias($serviceId)) {
            return null;
        }

        $definition = $container->findDefinition($serviceId);
        if (empty($definition->getClass())) {
            return null;
        }
        try {
            if (!class_exists($definition->getClass())) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }
        if ($definition->isAbstract()) {
            return null;
        }

        return new \ReflectionClass($definition->getClass());
    }

    /**
     * 检查这个服务是否是内置服务，是的话就不要继续处理了
     */
    private function isInternalService(string $serviceId): bool
    {
        if (str_starts_with($serviceId, self::INTERNAL_PREFIX)) {
            return true;
        }
        return str_ends_with($serviceId, self::INTERNAL_SUFFIX);
    }

    /**
     * 隐藏旧服务，并返回隐藏后的服务ID
     */
    private function updateInternalServiceId(ContainerBuilder $container, string $serviceId): string
    {
        $internalId = $serviceId . AttributeCompilerPass::INTERNAL_SUFFIX;

        if (!$container->hasDefinition($internalId)) {
            $definition = $container->getDefinition($serviceId);
            // 声明为public，方便后面动态调用
            //$definition->setPublic(true);
            // 一定要lazy
            $definition->setLazy(true);
            // 之所以要清空tags，是因为我们要把这些tag归到代理对象去
            $existTags = $definition->getTags();
            $definition->clearTags();
            // 重新写入，隐藏原始服务
            $container->setDefinition($internalId, $definition);

            $aopNewService = (new Definition($definition->getClass()))
                ->setFactory([new Reference('sf-aop.proxy-manager'), 'createProxy'])
                ->setArguments([
                    new Reference($internalId),
                ])
                ->setPublic($definition->isPublic())
                ->setTags($existTags) // 这里重新补上原始服务的tag
                ->addTag('aop-proxy');
            // 覆盖旧的服务名
            $container->setDefinition($serviceId, $aopNewService);
        }

        return $internalId;
    }
}

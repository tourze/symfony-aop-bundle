<?php

namespace Tourze\Symfony\Aop\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ExpressionLanguage;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Tourze\Symfony\Aop\Attribute\AdviceInterface;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Service\AopInterceptor;

class AopAttributeCompilerPass implements CompilerPassInterface
{
    public const INTERNAL_PREFIX = 'sf-aop.';
    public const INTERNAL_SUFFIX = '.internal-for-aop';

    /**
     * 获取所有可能的类
     */
    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflectionClass
     * @return array<string>
     */
    protected static function getParentClasses(\ReflectionClass $reflectionClass): array
    {
        $rs = [
            $reflectionClass->getName(),
        ];
        $parentClass = $reflectionClass->getParentClass();
        if (false !== $parentClass) {
            $rs = array_merge($rs, static::getParentClasses($parentClass));
        }
        if ([] !== $reflectionClass->getInterfaceNames()) {
            $rs = array_merge($rs, $reflectionClass->getInterfaceNames());
        }

        return array_values(array_unique($rs));
    }

    public function process(ContainerBuilder $container): void
    {
        $statements = $this->collectAspectStatements($container);
        if ([] === $statements) {
            return;
        }

        $this->processServiceInterception($container, $statements);
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function collectAspectStatements(ContainerBuilder $container): array
    {
        $statements = [];

        // Use tagged iterator instead of findTaggedServiceIds for reliability
        foreach ($container->getServiceIds() as $serviceId) {
            if (!$container->hasDefinition($serviceId)) {
                continue;
            }

            $definition = $container->getDefinition($serviceId);
            if (!$definition->hasTag(Aspect::TAG_NAME)) {
                continue;
            }

            $reflectionClass = $this->getReflectionClass($container, $serviceId);
            if (null === $reflectionClass) {
                continue;
            }

            $statements = $this->processAspectMethods($container, $serviceId, $reflectionClass, $statements);
        }

        return $statements;
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflectionClass
     * @param array<string, array<mixed>> $statements
     * @return array<string, array<mixed>>
     */
    private function processAspectMethods(ContainerBuilder $container, string $serviceId, \ReflectionClass $reflectionClass, array $statements): array
    {
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes() as $attribute) {
                $statements = $this->processMethodAttribute($container, $serviceId, $method, $attribute, $statements);
            }
        }

        return $statements;
    }

    /**
     * @template T of object
     * @param \ReflectionAttribute<T> $attribute
     * @param array<string, array<mixed>> $statements
     * @return array<string, array<mixed>>
     */
    private function processMethodAttribute(ContainerBuilder $container, string $serviceId, \ReflectionMethod $method, \ReflectionAttribute $attribute, array $statements): array
    {
        $name = $attribute->getName();
        if (!is_subclass_of($name, AdviceInterface::class)) {
            return $statements;
        }

        $instance = $attribute->newInstance();
        if (!$instance instanceof AdviceInterface) {
            return $statements;
        }
        $statement = $instance->getStatement();

        if (!isset($statements[$statement])) {
            $statements[$statement] = [];
        }
        if (!isset($statements[$statement][$name])) {
            $statements[$statement][$name] = [];
        }

        $statements[$statement][$name][] = [$serviceId, $method->getName()];

        // 强制修改为public，方便Interceptor去动态执行
        $definition = $container->findDefinition($serviceId);
        $definition->setPublic(true);

        return $statements;
    }

    /**
     * @param array<string, array<mixed>> $statements
     */
    private function processServiceInterception(ContainerBuilder $container, array $statements): void
    {
        $expressionLanguage = $this->createExpressionLanguage($container);

        foreach ($container->getServiceIds() as $serviceId) {
            if ($this->shouldSkipService($container, $serviceId)) {
                continue;
            }

            $this->processService($container, $serviceId, $statements, $expressionLanguage);
        }
    }

    private function createExpressionLanguage(ContainerBuilder $container): ExpressionLanguage
    {
        $expressionLanguage = new ExpressionLanguage(null, $container->getExpressionLanguageProviders());
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('count'));
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('in_array'));

        return $expressionLanguage;
    }

    private function shouldSkipService(ContainerBuilder $container, string $serviceId): bool
    {
        if ($this->isInternalService($serviceId)) {
            return true;
        }

        $reflectionClass = $this->getReflectionClass($container, $serviceId);
        if (null === $reflectionClass) {
            return true;
        }

        // 为了减少循环依赖问题，这里我们暂时忽略Aspect
        return [] !== $reflectionClass->getAttributes(Aspect::class);
    }

    /**
     * @param array<string, array<mixed>> $statements
     */
    private function processService(ContainerBuilder $container, string $serviceId, array $statements, ExpressionLanguage $expressionLanguage): void
    {
        $reflectionClass = $this->getReflectionClass($container, $serviceId);
        if (null === $reflectionClass) {
            return;
        }

        $definition = $container->findDefinition($serviceId);
        $serviceTags = array_keys($definition->getTags());

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->shouldSkipMethod($method)) {
                continue;
            }

            $this->processServiceMethod($container, $serviceId, $reflectionClass, $method, $serviceTags, $statements, $expressionLanguage);
        }
    }

    private function shouldSkipMethod(\ReflectionMethod $method): bool
    {
        $skipMethods = ['__construct', '__destruct', '__unserialize'];

        return in_array($method->getName(), $skipMethods, true);
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflectionClass
     * @param array<string> $serviceTags
     * @param array<string, array<mixed>> $statements
     */
    private function processServiceMethod(ContainerBuilder $container, string $serviceId, \ReflectionClass $reflectionClass, \ReflectionMethod $method, array $serviceTags, array $statements, ExpressionLanguage $expressionLanguage): void
    {
        $fullName = $reflectionClass->getName() . '::' . $method->getName();

        foreach ($statements as $statement => $config) {
            if (!$this->shouldInterceptMethod($statement, $fullName, $expressionLanguage, $reflectionClass, $method, $serviceId, $serviceTags)) {
                continue;
            }

            $this->createMethodInterception($container, $serviceId, $method, $config);
        }
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflectionClass
     * @param array<string> $serviceTags
     */
    private function shouldInterceptMethod(string $statement, string $fullName, ExpressionLanguage $expressionLanguage, \ReflectionClass $reflectionClass, \ReflectionMethod $method, string $serviceId, array $serviceTags): bool
    {
        if ($statement === $fullName) {
            return true;
        }

        return (bool) $expressionLanguage->evaluate($statement, [
            'class' => $reflectionClass,
            'method' => $method,
            'serviceId' => $serviceId,
            'serviceTags' => $serviceTags,
            'parentClasses' => self::getParentClasses($reflectionClass),
        ]);
    }

    /**
     * @param array<mixed> $config
     */
    private function createMethodInterception(ContainerBuilder $container, string $serviceId, \ReflectionMethod $method, array $config): void
    {
        $internalId = $this->updateInternalServiceId($container, $serviceId);
        $aopNewService = $container->findDefinition($serviceId);

        $interceptorId = self::INTERNAL_PREFIX . "{$serviceId}.interceptor";
        $closureId = "{$interceptorId}.closure";

        if (!$container->hasDefinition($interceptorId)) {
            $this->createInterceptorDefinition($container, $interceptorId, $closureId, $internalId, $serviceId);
        }

        $this->configureMethodInterception($container, $aopNewService, $interceptorId, $method, $config, $closureId);
    }

    private function createInterceptorDefinition(ContainerBuilder $container, string $interceptorId, string $closureId, string $internalId, string $serviceId): void
    {
        $interceptorDefinition = clone $container->getDefinition(AopInterceptor::class);
        $interceptorDefinition->addMethodCall('setInternalServiceId', [$internalId]);
        $interceptorDefinition->addMethodCall('setProxyServiceId', [$serviceId]);

        $this->configureFactoryIfNeeded($container, $interceptorDefinition, $internalId);

        $interceptorDefinition->setPublic(false);
        $container->setDefinition($interceptorId, $interceptorDefinition);

        $this->createClosureDefinition($container, $closureId, $interceptorId);
    }

    private function configureFactoryIfNeeded(ContainerBuilder $container, Definition $interceptorDefinition, string $internalId): void
    {
        $internalDef = $container->getDefinition($internalId);
        if (!is_array($internalDef->getFactory())) {
            return;
        }

        $interceptorDefinition->addMethodCall('setFactoryInstance', [$internalDef->getFactory()[0]]);
        $interceptorDefinition->addMethodCall('setFactoryMethod', [$internalDef->getFactory()[1]]);
        $interceptorDefinition->addMethodCall('setFactoryArguments', [$internalDef->getArguments()]);
    }

    private function createClosureDefinition(ContainerBuilder $container, string $closureId, string $interceptorId): void
    {
        $closureDefinition = new Definition();
        $closureDefinition->setClass(\Closure::class);
        $closureDefinition->setFactory([\Closure::class, 'fromCallable']);
        $closureDefinition->setArguments([new Reference($interceptorId)]);
        $closureDefinition->setPublic(false);
        $container->setDefinition($closureId, $closureDefinition);
    }

    /**
     * @param array<mixed> $config
     */
    private function configureMethodInterception(ContainerBuilder $container, Definition $aopNewService, string $interceptorId, \ReflectionMethod $method, array $config, string $closureId): void
    {
        $tag = "aop-method:{$method->getName()}";
        if ([] === $aopNewService->getTag($tag)) {
            $aopNewService->addTag($tag, $config);
            $aopNewService->addMethodCall('setMethodPrefixInterceptor', [
                $method->getName(),
                new Reference($closureId),
            ]);
        }

        $this->addAttributeFunctions($container, $interceptorId, $method, $config);
    }

    /**
     * @param array<mixed> $config
     */
    private function addAttributeFunctions(ContainerBuilder $container, string $interceptorId, \ReflectionMethod $method, array $config): void
    {
        $interceptorDefinition = $container->findDefinition($interceptorId);
        $interceptorDefinition->addTag('aop-intercept', ['method' => $method->getName()]);

        foreach ($config as $_attribute => $_functions) {
            foreach ($_functions as [$aspectServiceId, $aspectMethod]) {
                $interceptorDefinition->addMethodCall('addAttributeFunction', [
                    $method->getName(),
                    $_attribute,
                    new ServiceClosureArgument(new Reference($aspectServiceId)),
                    $aspectMethod,
                ]);
            }
        }
    }

    /**
     * @return \ReflectionClass<object>|null
     */
    private function getReflectionClass(ContainerBuilder $container, string $serviceId): ?\ReflectionClass
    {
        // 别名的，我们都跳过
        if ($container->hasAlias($serviceId)) {
            return null;
        }

        $definition = $container->findDefinition($serviceId);
        if (null === $definition->getClass() || '' === $definition->getClass()) {
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

        return $container->getReflectionClass($definition->getClass());
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
        $internalId = $serviceId . AopAttributeCompilerPass::INTERNAL_SUFFIX;

        if (!$container->hasDefinition($internalId)) {
            $definition = $container->getDefinition($serviceId);
            // 保存原始的 public 状态
            $originalPublic = $definition->isPublic();
            if (!$definition->isAbstract() && 'session.abstract_handler' !== $serviceId) {
                // 声明为public，方便后面动态调用，目前主要在 ServiceCallHandler 中调用
                $definition->setPublic(true);
            }
            // 我们已经做了一层代理，如果再加上Lazy，感觉调用栈会太过复杂
            // 暂时改为强制lazy
            $definition->setLazy(false);
            // 之所以要清空tags，是因为我们要把这些tag归到代理对象去
            $existTags = $definition->getTags();
            $definition->clearTags();
            // 重新写入，隐藏原始服务
            $container->setDefinition($internalId, $definition);

            $aopNewService = (new Definition($definition->getClass()))
                ->setFactory([new Reference('sf-aop.value-holder-proxy-manager'), 'createProxy'])
                ->setArguments([
                    new Reference($internalId),
                ])
                ->setPublic($originalPublic)
                ->setTags($existTags) // 这里重新补上原始服务的tag
                // ->addTag('aop-proxy')
            ;
            // 覆盖旧的服务名
            $container->setDefinition($serviceId, $aopNewService);
        }

        return $internalId;
    }
}

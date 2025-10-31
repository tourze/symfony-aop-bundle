# AopBundle

[![Latest Version](https://img.shields.io/packagist/v/tourze/symfony-aop-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-aop-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/symfony-aop-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-aop-bundle)
[![License](https://img.shields.io/packagist/l/tourze/symfony-aop-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-aop-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

## 目录

- [概述](#概述)
- [系统要求](#系统要求)
- [安装方法](#安装方法)
- [快速开始](#快速开始)
- [主要功能](#主要功能)
- [基本用法](#基本用法)
- [高级用法](#高级用法)
- [性能监控](#性能监控)
- [最佳实践](#最佳实践)
- [配置说明](#配置说明)
- [贡献指南](#贡献指南)
- [许可证](#许可证)

## 概述

AopBundle 是一个基于 Symfony 的 AOP (面向切面编程) 实现包。它通过 PHP 8 的 Attribute 特性
提供了声明式的切面编程能力，可以在不修改原有代码的情况下为方法添加横切关注点(如日志、缓存、事务等)。

## 系统要求

- PHP 8.1+
- Symfony 7.3+

## 安装方法

```bash
composer require tourze/symfony-aop-bundle
```

## 快速开始

### 第一步：安装包

```bash
composer require tourze/symfony-aop-bundle
```

### 第二步：创建简单切面

在 `src/Aspect/LoggingAspect.php` 中创建日志切面：

```php
<?php

namespace App\Aspect;

use Psr\Log\LoggerInterface;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class LoggingAspect
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    #[Before(serviceTags: ['app.loggable'])]
    public function logMethodExecution(JoinPoint $joinPoint): void
    {
        $this->logger->info('方法已执行', [
            'class' => $joinPoint->getInstance()::class,
            'method' => $joinPoint->getMethod(),
            'params' => $joinPoint->getParams(),
        ]);
    }
}
```

### 第三步：标记你的服务

在 `config/services.yaml` 中为需要记录日志的服务添加标签：

```yaml
services:
    App\Service\UserService:
        tags:
            - { name: 'app.loggable' }
```

### 第四步：使用你的服务

现在当你调用 `UserService` 的方法时，它们会被自动记录日志！

```php
// 这将触发日志切面
$userService->createUser($userData);
```

## 主要功能

- 使用 PHP 8 属性定义切面
- 支持多种通知类型（Before、After、AfterReturning、AfterThrowing）
- 通过 JoinPoint 获取方法参数和返回值
- 强大的切点表达式，用于定位特定服务/方法
- 内置 Stopwatch 支持，用于性能监控
- 异常处理能力

## 基本用法

### 定义切面

使用 `#[Aspect]` 属性创建类并定义通知方法：

```php
<?php

namespace App\Aspect;

use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class LoggingAspect
{
    #[Before('class.getName() === "App\\Service\\UserService" && method.getName() === "createUser"')]
    public function logBefore(JoinPoint $joinPoint): void
    {
        // 方法执行前的日志逻辑
    }
}
```

### 可用的通知类型

- `#[Before]` - 目标方法执行前执行
- `#[After]` - 目标方法执行后执行（无论是否有异常）
- `#[AfterReturning]` - 方法成功返回后执行
- `#[AfterThrowing]` - 目标方法抛出异常时执行
- `#[Around]` - 环绕通知，完全控制方法执行
- `#[CatchException]` - 用于异常处理

### JoinPoint 上下文

`JoinPoint` 对象提供了被拦截方法的上下文信息：

```php
$joinPoint->getInstance();     // 服务实例
$joinPoint->getMethod();       // 正在执行的方法名
$joinPoint->getParams();       // 方法参数
$joinPoint->getReturnValue();  // 返回值（用于 after 通知）
$joinPoint->getException();    // 异常（用于异常通知）
$joinPoint->proceed();         // 手动执行原始方法
```

### 切点表达式

定义通知应该应用的位置：

```php
// 按服务 ID 匹配
#[Before(serviceIds: ['app.user_service'])]

// 按类属性匹配
#[AfterThrowing(classAttribute: CatchException::class)]

// 按方法属性匹配
#[After(methodAttribute: SomeAttribute::class)]

// 按服务标签匹配
#[Before(serviceTags: ['app.loggable'])]

// 按父类匹配
#[Before(parentClasses: [BaseRepository::class])]

// 自定义表达式
#[Before('class.getName() === "App\\Service\\UserService"')]
```

## 高级用法

### 异常处理

在切面中处理异常：

```php
#[Aspect]
class ExceptionHandlingAspect
{
    #[AfterThrowing(serviceTags: ['app.monitored'])]
    public function handleException(JoinPoint $joinPoint): void
    {
        $exception = $joinPoint->getException();
        
        // 记录异常
        $this->logger->error('方法执行失败', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        // 可选择转换或抑制异常
        if ($exception instanceof RecoverableException) {
            $joinPoint->setReturnEarly(true);
            $joinPoint->setReturnValue(null);
        }
    }
}
```

### 方法拦截

完全控制方法执行：

```php
#[Aspect]
class CacheAspect
{
    #[Before(methodAttribute: Cacheable::class)]
    public function cacheMethod(JoinPoint $joinPoint): void
    {
        $cacheKey = $this->generateCacheKey($joinPoint);
        
        if ($this->cache->has($cacheKey)) {
            $joinPoint->setReturnEarly(true);
            $joinPoint->setReturnValue($this->cache->get($cacheKey));
        }
    }
}
```

### Around 通知（新功能）

使用 Around 通知实现更灵活的控制：

```php
#[Aspect]
class TransactionAspect
{
    #[Around(methodAttribute: Transactional::class)]
    public function wrapInTransaction(JoinPoint $joinPoint): mixed
    {
        $this->entityManager->beginTransaction();
        
        try {
            // 执行目标方法
            $result = $joinPoint->proceed();
            $this->entityManager->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
```

## 性能监控

使用内置的 Stopwatch 属性进行性能监控：

```php
use Tourze\Symfony\Aop\Attribute\Stopwatch;

class UserService
{
    #[Stopwatch]
    public function complexOperation()
    {
        // 耗时操作
    }
}
```

## 最佳实践

### 保持切面专注

- 每个切面应该有单一职责
- 避免在通知方法中进行重量级操作
- 为切面和通知方法使用有意义的名称

### 通知执行顺序

- Before 通知按声明顺序执行
- After/AfterReturning/AfterThrowing 通知按相反顺序执行
- 定义多个切面时要考虑执行顺序

### 性能考虑

- 只在必要时使用 AOP
- 考虑在生产环境中的性能影响
- 使用 Stopwatch 监控方法执行时间
- 避免在热点路径中使用复杂的切点表达式

### 异常处理

- 在通知中小心处理异常
- 考虑使用 AfterThrowing 进行异常日志记录
- 使用 CatchException 进行受控异常处理
- 彻底测试异常场景

## 配置说明

无需额外配置。包会自动检测带有 `#[Aspect]` 属性的服务并将通知应用到匹配的服务。

### 调试命令

使用内置的调试命令来查看和分析 AOP 配置：

```bash
# 列出所有切面
bin/console aop:debug:aspects

# 查看特定切面的详情
bin/console aop:debug:aspects --aspect=App\Aspect\LoggingAspect

# 显示切面统计信息
bin/console aop:debug:aspects --stats
```

### 性能优化配置

本包内置了多项性能优化功能：

1. **JoinPoint 对象池** - 自动池化 JoinPoint 对象，减少内存分配
2. **切面匹配缓存** - 缓存切面匹配结果，提高执行效率
3. **性能指标收集** - 可选的性能监控，跟踪切面执行时间

## 贡献指南

欢迎贡献代码。请随时提交 Pull Request。

## 许可证

此包在 MIT 许可证下可用。有关更多信息，请参阅 [LICENSE](LICENSE) 文件。
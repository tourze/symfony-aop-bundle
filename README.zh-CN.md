# AopBundle

[![Latest Version](https://img.shields.io/packagist/v/tourze/symfony-aop-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-aop-bundle)

[English](README.md) | [中文](README.zh-CN.md)

## 概述

AopBundle 是一个基于 Symfony 的 AOP (面向切面编程) 实现包。它通过 PHP 8 的 Attribute 特性提供了声明式的切面编程能力，可以在不修改原有代码的情况下为方法添加横切关注点(如日志、缓存、事务等)。

## 系统要求

- PHP 8.1+
- Symfony 6.4+ 或 7.0+

## 安装方法

```bash
composer require tourze/symfony-aop-bundle
```

## 主要功能

- 使用 PHP 8 属性定义切面
- 支持多种通知类型（Before、After、AfterReturning、AfterThrowing）
- 通过 JoinPoint 获取方法参数和返回值
- 强大的切点表达式，用于定位特定服务/方法
- 内置 Stopwatch 支持，用于性能监控
- 异常处理能力

## 基本使用

### 1. 定义切面

创建一个带有 `#[Aspect]` 属性的类，并定义通知方法：

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

### 2. 可用的通知类型

- `#[Before]` - 在目标方法执行前执行
- `#[After]` - 在目标方法执行后执行（无论是否抛出异常）
- `#[AfterReturning]` - 在目标方法成功返回后执行
- `#[AfterThrowing]` - 在目标方法抛出异常时执行
- `#[CatchException]` - 用于异常处理

### 3. JoinPoint

`JoinPoint` 对象提供了被拦截方法的上下文：

```php
$joinPoint->getInstance();     // 服务实例
$joinPoint->getMethod();       // 正在执行的方法名
$joinPoint->getParams();       // 方法参数
$joinPoint->getReturnValue();  // 返回值（用于 After 通知）
$joinPoint->getException();    // 异常（用于异常通知）
$joinPoint->proceed();         // 手动执行原始方法
```

### 4. 切点表达式

定义通知应该应用的位置：

```php
// 通过服务 ID 匹配
#[Before(serviceIds: ['app.user_service'])]

// 通过类属性匹配
#[AfterThrowing(classAttribute: CatchException::class)]

// 通过方法属性匹配
#[After(methodAttribute: SomeAttribute::class)]

// 通过服务标签匹配
#[Before(serviceTags: ['app.loggable'])]

// 通过父类匹配
#[Before(parentClasses: [BaseRepository::class])]

// 自定义表达式
#[Before('class.getName() === "App\\Service\\UserService"')]
```

### 5. 性能监控

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

1. **保持切面专注**
   - 每个切面应该只有一个责任
   - 避免在通知方法中执行重量级操作

2. **通知执行顺序**
   - Before 通知按声明顺序执行
   - After/AfterReturning/AfterThrowing 通知按声明的逆序执行

3. **性能考虑**
   - 只在必要时使用 AOP
   - 考虑在生产环境中的性能影响
   - 使用 Stopwatch 监控方法执行时间

4. **异常处理**
   - 在通知中小心处理异常
   - 考虑使用 AfterThrowing 进行异常日志记录
   - 使用 CatchException 进行受控异常处理

## 配置

不需要额外配置。该包会自动检测带有 `#[Aspect]` 属性的服务，并将通知应用于匹配的服务。

## 贡献

欢迎贡献。请随时提交 Pull Request。

## 许可证

此包在 MIT 许可下可用。有关更多信息，请参阅 [LICENSE](LICENSE) 文件。

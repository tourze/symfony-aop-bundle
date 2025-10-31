# AopBundle

[![Latest Version](https://img.shields.io/packagist/v/tourze/symfony-aop-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-aop-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/symfony-aop-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-aop-bundle)
[![License](https://img.shields.io/packagist/l/tourze/symfony-aop-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-aop-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

## Table of Contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Features](#features)
- [Basic Usage](#basic-usage)
- [Advanced Usage](#advanced-usage)
- [Performance Monitoring](#performance-monitoring)
- [Best Practices](#best-practices)
- [Configuration](#configuration)
- [Contributing](#contributing)
- [License](#license)

## Overview

AopBundle is a Symfony bundle that implements Aspect-Oriented Programming (AOP) using PHP 8's 
Attribute features. It enables adding cross-cutting concerns (like logging, caching, transactions) 
to your code without modifying the core logic.

## Requirements

- PHP 8.1+
- Symfony 7.3+

## Installation

```bash
composer require tourze/symfony-aop-bundle
```

## Quick Start

### Step 1: Install the Bundle

```bash
composer require tourze/symfony-aop-bundle
```

### Step 2: Create a Simple Aspect

Create a logging aspect in `src/Aspect/LoggingAspect.php`:

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
        $this->logger->info('Method executed', [
            'class' => $joinPoint->getInstance()::class,
            'method' => $joinPoint->getMethod(),
            'params' => $joinPoint->getParams(),
        ]);
    }
}
```

### Step 3: Tag Your Services

Tag services you want to log in `config/services.yaml`:

```yaml
services:
    App\Service\UserService:
        tags:
            - { name: 'app.loggable' }
```

### Step 4: Use Your Service

Now when you call methods on `UserService`, they will be automatically logged!

```php
// This will trigger the logging aspect
$userService->createUser($userData);
```

## Features

- Define aspects using PHP 8 attributes
- Support for multiple advice types (Before, After, AfterReturning, AfterThrowing)
- Join point context with access to method parameters and return values
- Powerful pointcut expressions for targeting specific services/methods
- Built-in stopwatch support for performance monitoring
- Exception handling capabilities

## Basic Usage

### Define an Aspect

Create a class with the `#[Aspect]` attribute and define advice methods:

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
        // Logging logic before method execution
    }
}
```

### Available Advice Types

- `#[Before]` - Executed before the target method
- `#[After]` - Executed after the target method (regardless of exceptions)
- `#[AfterReturning]` - Executed after successful method return
- `#[AfterThrowing]` - Executed when the target method throws an exception
- `#[CatchException]` - Used for exception handling

### JoinPoint Context

The `JoinPoint` object provides context for the intercepted method:

```php
$joinPoint->getInstance();     // The service instance
$joinPoint->getMethod();       // Method name being executed
$joinPoint->getParams();       // Method parameters
$joinPoint->getReturnValue();  // Return value (for after advice)
$joinPoint->getException();    // Exception (for exception advice)
$joinPoint->proceed();         // Manually execute the original method
```

### Pointcut Expressions

Define where advice should be applied:

```php
// Match by service ID
#[Before(serviceIds: ['app.user_service'])]

// Match by class attribute
#[AfterThrowing(classAttribute: CatchException::class)]

// Match by method attribute
#[After(methodAttribute: SomeAttribute::class)]

// Match by service tags
#[Before(serviceTags: ['app.loggable'])]

// Match by parent class
#[Before(parentClasses: [BaseRepository::class])]

// Custom expression
#[Before('class.getName() === "App\\Service\\UserService"')]
```

## Advanced Usage

### Exception Handling

Handle exceptions in your aspects:

```php
#[Aspect]
class ExceptionHandlingAspect
{
    #[AfterThrowing(serviceTags: ['app.monitored'])]
    public function handleException(JoinPoint $joinPoint): void
    {
        $exception = $joinPoint->getException();
        
        // Log the exception
        $this->logger->error('Method failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        // Optionally transform or suppress the exception
        if ($exception instanceof RecoverableException) {
            $joinPoint->setReturnEarly(true);
            $joinPoint->setReturnValue(null);
        }
    }
}
```

### Method Interception

Completely control method execution:

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

## Performance Monitoring

Use the built-in Stopwatch attribute for performance monitoring:

```php
use Tourze\Symfony\Aop\Attribute\Stopwatch;

class UserService
{
    #[Stopwatch]
    public function complexOperation()
    {
        // Time-consuming operation
    }
}
```

## Best Practices

### Keep Aspects Focused

- Each aspect should have a single responsibility
- Avoid heavy operations in advice methods
- Use meaningful names for aspects and advice methods

### Advice Execution Order

- Before advice is executed in declaration order
- After/AfterReturning/AfterThrowing advice is executed in reverse order
- Consider the execution order when defining multiple aspects

### Performance Considerations

- Use AOP only when necessary
- Consider the performance impact in production
- Use Stopwatch to monitor method execution time
- Avoid complex pointcut expressions in hot paths

### Exception Handling

- Be careful with exception handling in advice
- Consider using AfterThrowing for exception logging
- Use CatchException for controlled exception handling
- Test exception scenarios thoroughly

## Configuration

No additional configuration is required. The bundle automatically detects services with the 
`#[Aspect]` attribute and applies advice to matching services.

## Contributing

Contributions are welcome. Please feel free to submit a Pull Request.

## License

This bundle is available under the MIT license. See the [LICENSE](LICENSE) file for more information.
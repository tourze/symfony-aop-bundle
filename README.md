# AopBundle

[![Latest Version](https://img.shields.io/packagist/v/tourze/symfony-aop-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-aop-bundle)

[English](README.md) | [中文](README.zh-CN.md)

## Overview

AopBundle is a Symfony bundle that implements Aspect-Oriented Programming (AOP) using PHP 8's Attribute features. It enables adding cross-cutting concerns (like logging, caching, transactions) to your code without modifying the core logic.

## Requirements

- PHP 8.1+
- Symfony 6.4+

## Installation

```bash
composer require tourze/symfony-aop-bundle
```

## Features

- Define aspects using PHP 8 attributes
- Support for multiple advice types (Before, After, AfterReturning, AfterThrowing)
- Join point context with access to method parameters and return values
- Powerful pointcut expressions for targeting specific services/methods
- Built-in stopwatch support for performance monitoring
- Exception handling capabilities

## Basic Usage

### 1. Define an Aspect

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

### 2. Available Advice Types

- `#[Before]` - Executed before the target method
- `#[After]` - Executed after the target method (regardless of exceptions)
- `#[AfterReturning]` - Executed after successful method return
- `#[AfterThrowing]` - Executed when the target method throws an exception
- `#[CatchException]` - Used for exception handling

### 3. JoinPoint

The `JoinPoint` object provides context for the intercepted method:

```php
$joinPoint->getInstance();     // The service instance
$joinPoint->getMethod();       // Method name being executed
$joinPoint->getParams();       // Method parameters
$joinPoint->getReturnValue();  // Return value (for after advice)
$joinPoint->getException();    // Exception (for exception advice)
$joinPoint->proceed();         // Manually execute the original method
```

### 4. Pointcut Expressions

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

### 5. Performance Monitoring

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

1. **Keep aspects focused**
   - Each aspect should have a single responsibility
   - Avoid heavy operations in advice methods

2. **Advice execution order**
   - Before advice is executed in declaration order
   - After/AfterReturning/AfterThrowing advice is executed in reverse order

3. **Performance considerations**
   - Use AOP only when necessary
   - Consider the performance impact in production
   - Use Stopwatch to monitor method execution time

4. **Exception handling**
   - Be careful with exception handling in advice
   - Consider using AfterThrowing for exception logging
   - Use CatchException for controlled exception handling

## Configuration

No additional configuration is required. The bundle automatically detects services with the `#[Aspect]` attribute and applies advice to matching services.

## Contributing

Contributions are welcome. Please feel free to submit a Pull Request.

## License

This bundle is available under the MIT license. See the [LICENSE](LICENSE) file for more information.

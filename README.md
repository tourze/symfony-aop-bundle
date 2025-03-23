# AopBundle

AopBundle 是一个基于 Symfony 的 AOP (面向切面编程) 实现包。它通过 PHP 8 的 Attribute 特性提供了声明式的切面编程能力,可以在不修改原有代码的情况下为方法添加横切关注点(如日志、缓存、事务等)。

## 依赖

- PHP 8.1+
- Symfony 6.0+

## 核心功能

### 1. 切面定义

使用 `#[Aspect]` 注解标记切面类:

```php
use AopBundle\Attribute\Aspect;

#[Aspect]
class LoggingAspect
{
    // 切面方法实现
}
```

### 2. 通知类型

支持以下通知类型:

- `#[Before]` - 在目标方法执行前执行
- `#[After]` - 在目标方法执行后执行(无论是否发生异常)
- `#[AfterReturning]` - 在目标方法成功返回后执行
- `#[AfterThrowing]` - 在目标方法抛出异常时执行
- `#[CatchException]` - 用于异常处理

### 3. 连接点管理

通过 `JoinPoint` 类提供对目标方法的上下文访问:

- 获取方法名称
- 获取方法参数
- 获取返回值
- 获取异常信息
- 获取服务 ID

### 4. 性能监控

内置 `Stopwatch` 支持,可以监控方法执行时间:

```php
use AopBundle\Attribute\Stopwatch;

#[Stopwatch]
public function someMethod()
{
    // 方法实现
}
```

## 使用方法

### 1. 定义切面

```php
use AopBundle\Attribute\Aspect;
use AopBundle\Attribute\Before;
use AopBundle\Model\JoinPoint;

#[Aspect]
class LoggingAspect
{
    #[Before('class.getName() === "App\\Service\\UserService" && method.getName() === "createUser"')]
    public function logBeforeUserCreation(JoinPoint $joinPoint): void
    {
        // 实现日志逻辑
    }
}
```

### 2. 异常处理

```php
use AopBundle\Attribute\Aspect;
use AopBundle\Attribute\AfterThrowing;
use AopBundle\Model\JoinPoint;

#[Aspect]
class ExceptionAspect
{
    #[AfterThrowing(classAttribute: CatchException::class)]
    public function handleException(JoinPoint $joinPoint): void
    {
        $exception = $joinPoint->getException();
        // 处理异常
    }
}
```

### 3. 性能监控

```php
use AopBundle\Attribute\Stopwatch;

class UserService
{
    #[Stopwatch]
    public function complexOperation()
    {
        // 耗时操作
    }
}
```

## 重要说明

1. **切面优先级**
    - 多个切面的执行顺序按照定义顺序进行
    - Before 通知按定义顺序执行
    - After/AfterReturning/AfterThrowing 通知按定义逆序执行

2. **性能考虑**
    - 切面会带来一定的性能开销
    - 建议只在必要的方法上使用切面
    - 可以使用 Stopwatch 监控性能影响

3. **调试建议**
    - 开发环境建议开启 Symfony Profiler
    - 使用 Stopwatch 监控方法执行时间
    - 检查日志以排查切面执行问题

4. **最佳实践**
    - 切面类应该只关注单一职责
    - 避免在切面中执行重量级操作
    - 合理使用切入点表达式以限制切面范围
    - 注意处理异常情况

## 扩展开发

1. **自定义通知类型**
    - 继承 `Advice` 类
    - 实现相应的处理逻辑
    - 注册到容器

2. **自定义切入点表达式**
    - 支持服务 ID 匹配
    - 支持标签匹配
    - 支持父类匹配

## 参考文档

- [Symfony 文档](https://symfony.com/doc/current/index.html)
- [AOP 概念](https://en.wikipedia.org/wiki/Aspect-oriented_programming)

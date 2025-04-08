<?php

namespace Tourze\Symfony\Aop\Service;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\Symfony\Aop\Model\ProcessContext;

/**
 * 上下文管理
 */
#[Autoconfigure(public: true)]
class ContextService implements ResetInterface
{
    private static int $maxId = 0;

    private string $id = '0';

    /**
     * @var array 延迟执行的所有信息
     */
    private array $deferCalls = [];

    /**
     * 检查当前是否在协程运行环境
     */
    public function isCoroutineRuntime(): bool
    {
        if (class_exists(\Swoole\Coroutine::class) && \Swoole\Coroutine::getCid() > 0) {
            return true;
        }
        return false;
    }

    /**
     * 判断是否运行在Workerman环境中
     */
    public function isWorkerman(): bool
    {
        return class_exists(\Workerman\Worker::class) && \Workerman\Worker::$pidFile !== '';
    }

    public function isSwoole(): bool
    {
        return class_exists(\Swoole\Coroutine::class) && \Swoole\Coroutine::getCid() > 0;
    }

    public function getId(): string
    {
        if (class_exists(\Swoole\Coroutine::class) && \Swoole\Coroutine::getCid() > 0) {
            return \Swoole\Coroutine::getCid();
        }

        // 默认是获取进程id来作为上下文id
        // 失败的话再自己生成
        $id = getmypid();
        if ($id === false) {
            // 在构造函数中生成 ID
            $this->generateId();
            return $this->id;
        }
        return "pid-$id";
    }

    /**
     * 延迟执行
     */
    public function defer(callable $callback): void
    {
        if (class_exists(\Swoole\Coroutine::class) && \Swoole\Coroutine::getCid() > 0) {
            defer($callback);
            return;
        }

        if ($this->isWorkerman()) {
            \Workerman\Timer::add(0.001, $callback, persistent: false);
            return;
        }

        // 默认情况，可能是FPM，此时我们不要马上执行咯
        $this->deferCalls[] = $callback;
    }

    public function getContext(): object
    {
        // Swoole环境特殊处理
        if (class_exists(\Swoole\Coroutine::class) && \Swoole\Coroutine::getCid() > 0) {
            return \Swoole\Coroutine::getContext();
        }
        return ProcessContext::instance(getmypid());
    }

    private function generateId(): void
    {
        $this->id = $this->generateUniqueId();
    }

    private function generateUniqueId(): string
    {
        // 使用时间戳和自增的唯一标识符
        self::$maxId++;
        return uniqid('', true) . '-' . self::$maxId;
    }

    /**
     * 在各个时机，尝试执行逻辑
     */
    #[AsEventListener(event: KernelEvents::FINISH_REQUEST, priority: -1)]
    #[AsEventListener(event: KernelEvents::TERMINATE, priority: -1)]
    #[AsEventListener(event: KernelEvents::EXCEPTION, priority: -1)]
    #[AsEventListener(event: ConsoleEvents::TERMINATE, priority: -1)]
    #[AsEventListener(event: ConsoleEvents::ERROR, priority: -1)]
    public function executeDeferCalls(): void
    {
        while (!empty($this->deferCalls)) {
            $func = array_shift($this->deferCalls);
            try {
                call_user_func($func);
            } catch (\Throwable $exception) {
                // 这里抛出异常是不对的，我们不处理
            }
        }
    }

    /**
     * Web 请求中生成 ID
     */
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 11000)]
    public function onKernelRequest(): void
    {
        $this->generateId();
    }

    /**
     * Console 命令中生成 ID
     */
    #[AsEventListener(event: ConsoleEvents::COMMAND, priority: 100)]
    public function onConsoleCommand(): void
    {
        $this->generateId();
    }

    #[AsEventListener(event: KernelEvents::TERMINATE, priority: -11000)]
    public function reset(): void
    {
        $this->id = '0';
    }
}

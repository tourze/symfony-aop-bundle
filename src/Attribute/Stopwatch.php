<?php

namespace Tourze\Symfony\AOP\Attribute;

/**
 * 更加智能地添加stopwatch
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Stopwatch
{
}

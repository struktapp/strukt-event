<?php

namespace Strukt;

use Closure;
use ReflectionFunction;

/**
 * Wraps a callable so it can be prepared once and invoked repeatedly.
 *
 * The callable is normalised to a Closure and its ReflectionFunction at
 * construction time, so repeated execution performs no reflection work.
 */
final class Event
{
    /** Callable retained without repeated wrapping. */
    private Closure $callback;

    /** @var array<int|string, mixed>|null Arguments applied on execution. */
    private ?array $args = null;

    /** Reflection object retained for callers that need callable metadata. */
    private ReflectionFunction $reflection;

    /**
     * Creates an event from a callable.
     *
     * @param callable $event Callable to execute.
     */
    public function __construct(callable $event)
    {
        $this->callback = $event instanceof Closure ? $event : Closure::fromCallable($event);
        $this->reflection = new ReflectionFunction($this->callback);
    }

    /**
     * Creates an event through late static binding.
     *
     * @param callable $event Callable to execute.
     * @return static New event.
     */
    public static function create(callable $event): static
    {
        return new static($event);
    }

    /**
     * Applies positional or named arguments.
     *
     * Associative arrays are expanded by PHP as named arguments, which avoids
     * reflecting and rebuilding the parameter list on every execution.
     *
     * @param mixed ...$args Arguments to apply.
     * @return static This event.
     */
    public function apply(mixed ...$args): static
    {
        $this->args = $args;

        return $this;
    }

    /**
     * Applies an argument array.
     *
     * @param array<int|string, mixed> $args Positional or named arguments.
     * @return static This event.
     */
    public function applyArgs(array $args): static
    {
        $this->args = $args;

        return $this;
    }

    /**
     * Clears previously applied arguments.
     *
     * @return static This event.
     */
    public function clear(): static
    {
        $this->args = null;

        return $this;
    }

    /**
     * Returns the callable reflection object.
     *
     * @return ReflectionFunction Callable reflection.
     */
    public function ref(): ReflectionFunction
    {
        return $this->reflection;
    }

    /**
     * Executes the callable.
     *
     * @return mixed Callable result.
     */
    public function exec(): mixed
    {
        if ($this->args === null) {
            return ($this->callback)();
        }

        return ($this->callback)(...$this->args);
    }

    /**
     * Executes the callable.
     *
     * @return mixed Callable result.
     */
    public function __invoke(): mixed
    {
        return $this->exec();
    }
}

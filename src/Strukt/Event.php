<?php

namespace Strukt;

use Closure;

/** Executes a callable with positional or named arguments. */
final class Event
{
    /** Callable retained without repeated reflection or wrapper creation. */
    private Closure $callback;

    /** @var array<int|string, mixed>|null Arguments to apply on execution. */
    private ?array $args = null;

    /** Reflection object retained for callers that need callable metadata. */
    private \ReflectionFunction $reflection;

    /**
     * Creates an event from a callable.
     *
     * @param callable $event Callable to execute.
     */
    public function __construct(callable $event)
    {
        $this->callback = $event instanceof Closure ? $event : Closure::fromCallable($event);
        $this->reflection = new \ReflectionFunction($this->callback);
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
     * Associative arrays are expanded as PHP named arguments, which avoids
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
     * Returns the callable reflection object.
     *
     * @return \ReflectionFunction Callable reflection.
     */
    public function getRef(): \ReflectionFunction
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
}

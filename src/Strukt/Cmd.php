<?php

namespace Strukt;

use Closure;

/** Stores named callables and optional default arguments. */
class Cmd
{
    /** @var array<string, callable> Registered command callbacks. */
    protected static array $callbacks = [];

    /** @var array<string, array<int|string, mixed>|null> Default command arguments. */
    protected static array $args = [];

    /** @var list<string> Insertion-ordered command names. */
    protected static array $names = [];

    /**
     * Registers or replaces a named command.
     *
     * The optional first definition value may be an argument array; the
     * callback can then be the first or second variadic value.
     *
     * @param string $name Command name.
     * @param mixed ...$definition Optional argument array and callback.
     * @return void
     * @throws \InvalidArgumentException When the name or callback is invalid.
     */
    public static function add(string $name, mixed ...$definition): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Command names must not be empty.');
        }

        $expected = $definition[0] ?? null;
        $callback = is_callable($expected) ? $expected : ($definition[1] ?? null);
        $args = is_array($expected) ? $expected : null;

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(sprintf('Command [%s] requires a callable.', $name));
        }

        if (!array_key_exists($name, static::$callbacks)) {
            static::$names[] = $name;
        }

        static::$callbacks[$name] = $callback;
        static::$args[$name] = $args;
    }

    /**
     * Reads a command callback.
     *
     * @param string $name Command name.
     * @return callable|null Callback, or `null` when absent.
     */
    public static function get(string $name): ?callable
    {
        return static::$callbacks[$name] ?? null;
    }

    /**
     * Lists command names, optionally using a regular-expression filter.
     *
     * @param string|null $filter Regular expression body.
     * @return list<string> Matching command names.
     * @throws \InvalidArgumentException When the filter is invalid.
     */
    public static function ls(?string $filter = null): array
    {
        if ($filter === null) {
            return static::$names;
        }

        $pattern = sprintf('/%s/', $filter);
        $names = [];
        foreach (static::$names as $name) {
            $matched = preg_match($pattern, $name);
            if ($matched === false) {
                throw new \InvalidArgumentException('The command filter is not a valid regular expression.');
            }
            if ($matched === 1) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Checks whether a command exists.
     *
     * @param string $name Command name.
     * @return bool True when registered.
     */
    public static function exists(string $name): bool
    {
        return array_key_exists($name, static::$callbacks);
    }

    /**
     * Returns a compact command description from its doc comment.
     *
     * @param string $name Command name.
     * @return string Description line.
     */
    public static function doc(string $name): string
    {
        $callback = static::get($name);
        if ($callback === null) {
            return $name;
        }

        $reflection = new \ReflectionFunction(Closure::fromCallable($callback));
        $doc = $reflection->getDocComment();
        if ($doc === false) {
            return $name;
        }

        $doc = preg_replace('/^\/\*\*|\*\/$/', '', $doc) ?? $doc;
        $doc = preg_replace('/^\s*\* ?/m', '', $doc) ?? $doc;

        return sprintf(' %s %s', str_pad($name, 15), trim($doc));
    }

    /**
     * Executes a named command.
     *
     * @param string $name Command name.
     * @param array<int|string, mixed>|null $args Optional invocation arguments.
     * @return mixed Command result.
     * @throws \InvalidArgumentException When the command does not exist.
     */
    public static function exec(string $name, ?array $args = null): mixed
    {
        $callback = static::get($name);
        if ($callback === null) {
            throw new \InvalidArgumentException(sprintf('Command [%s] was not registered.', $name));
        }

        $args ??= static::$args[$name] ?? null;

        return $args === null ? $callback() : $callback(...$args);
    }

    /**
     * Clears all commands.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$callbacks = [];
        static::$args = [];
        static::$names = [];
    }

    /**
     * Removes a command from the registry.
     *
     * @param string $name Command name.
     * @return void
     */
    protected static function forget(string $name): void
    {
        unset(static::$callbacks[$name], static::$args[$name]);
        static::$names = array_values(array_filter(
            static::$names,
            static fn (string $registered): bool => $registered !== $name,
        ));
    }

    /**
     * Returns a snapshot of command names.
     *
     * @return list<string> Command names.
     */
    protected static function names(): array
    {
        return static::$names;
    }

    /**
     * Returns a command's stored default arguments.
     *
     * @param string $name Command name.
     * @return array<int|string, mixed>|null Stored arguments.
     */
    protected static function defaultArgs(string $name): ?array
    {
        return static::$args[$name] ?? null;
    }
}

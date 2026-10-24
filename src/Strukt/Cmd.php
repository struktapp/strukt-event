<?php

namespace Strukt;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;

/**
 * Process-local registry of named callables with optional default arguments.
 *
 * Commands keep insertion order, so a caller can register work once and then
 * enumerate or execute it by name. Callbacks are invoked directly.
 */
class Cmd
{
    /** @var array<string, callable> Registered command callbacks. */
    protected static array $callbacks = [];

    /** @var array<string, array<int|string, mixed>|null> Default command arguments. */
    protected static array $args = [];

    /** @var list<string> Insertion-ordered command names. */
    protected static array $names = [];

    /** @var array<string, string> Cached descriptions keyed by command name. */
    private static array $docs = [];

    /**
     * Registers or replaces a named command.
     *
     * The callback may follow the name directly, or an argument array may sit
     * between them. Both forms are accepted:
     *
     *     Cmd::add('greet', $callback);
     *     Cmd::add('greet', ['name' => 'Ada'], $callback);
     *
     * Re-adding an existing name replaces its callback and defaults while
     * preserving its position in the command order.
     *
     * @param string $name Command name.
     * @param mixed ...$definition Optional argument array and callback.
     * @return void
     * @throws InvalidArgumentException When the name or callback is invalid.
     */
    public static function add(string $name, mixed ...$definition): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Command name must not be empty.');
        }

        [$callback, $args] = self::parse($name, $definition);

        if (!array_key_exists($name, static::$callbacks)) {
            static::$names[] = $name;
        }

        static::$callbacks[$name] = $callback;
        static::$args[$name] = $args;
        unset(self::$docs[$name]);
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
     * @throws InvalidArgumentException When the filter is invalid.
     */
    public static function ls(?string $filter = null): array
    {
        if ($filter === null) {
            return static::$names;
        }

        $pattern = sprintf('/%s/', $filter);
        if (!self::isValidPattern($pattern)) {
            throw new InvalidArgumentException('The command filter is not a valid regular expression.');
        }

        return array_values(array_filter(
            static::$names,
            static fn (string $name): bool => preg_match($pattern, $name) === 1,
        ));
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
     * Descriptions are cached until the command is replaced, removed, or the
     * registry is reset.
     *
     * @param string $name Command name.
     * @return string Description line.
     */
    public static function doc(string $name): string
    {
        if (array_key_exists($name, self::$docs)) {
            return self::$docs[$name];
        }

        $callback = static::get($name);
        if ($callback === null) {
            return self::$docs[$name] = $name;
        }

        $doc = (new ReflectionFunction(Closure::fromCallable($callback)))->getDocComment();
        if ($doc === false) {
            return self::$docs[$name] = $name;
        }

        $doc = preg_replace('/^\/\*\*|\*\/$/', '', $doc) ?? $doc;
        $doc = preg_replace('/^\s*\* ?/m', '', $doc) ?? $doc;

        return self::$docs[$name] = sprintf(' %s %s', str_pad($name, 15), trim($doc));
    }

    /**
     * Executes a named command.
     *
     * Explicit arguments win; otherwise the defaults stored by add() are used.
     *
     * @param string $name Command name.
     * @param array<int|string, mixed>|null $args Optional invocation arguments.
     * @return mixed Command result.
     * @throws InvalidArgumentException When the command does not exist.
     */
    public static function exec(string $name, ?array $args = null): mixed
    {
        $callback = static::get($name);
        if ($callback === null) {
            throw new InvalidArgumentException(sprintf('Command [%s] was not registered.', $name));
        }

        $args ??= static::$args[$name] ?? null;

        return $args === null ? $callback() : $callback(...$args);
    }

    /**
     * Clears all commands and cached descriptions.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$callbacks = [];
        static::$args = [];
        static::$names = [];
        self::$docs = [];
    }

    /**
     * Removes a command from the registry.
     *
     * @param string $name Command name.
     * @return void
     */
    protected static function forget(string $name): void
    {
        unset(static::$callbacks[$name], static::$args[$name], self::$docs[$name]);
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

    /**
     * Resolves an add() definition list into a callback and default arguments.
     *
     * Both `(args, callback)` and `(callback, args)` are accepted. Care is
     * taken to tell an argument array apart from an array callable such as
     * `[$object, 'method']`.
     *
     * @param string $name Command name, used in error messages.
     * @param list<mixed> $definition Values passed after the name.
     * @return array{0: callable, 1: array<int|string, mixed>|null} Pair of callback and defaults.
     * @throws InvalidArgumentException When no callable can be found.
     */
    private static function parse(string $name, array $definition): array
    {
        $first = $definition[0] ?? null;
        $second = $definition[1] ?? null;

        if (is_callable($second)) {
            if ($first !== null && !is_array($first)) {
                throw new InvalidArgumentException(sprintf(
                    'Command [%s] must declare arguments as an array when a callback follows.',
                    $name,
                ));
            }

            return [$second, is_array($first) ? $first : null];
        }

        if (is_callable($first)) {
            return [$first, is_array($second) ? $second : null];
        }

        throw new InvalidArgumentException(sprintf('Command [%s] requires a callable.', $name));
    }

    /**
     * Checks a delimited regular expression without emitting warnings.
     *
     * @param string $pattern Delimited pattern.
     * @return bool True when the pattern compiles.
     */
    private static function isValidPattern(string $pattern): bool
    {
        set_error_handler(static fn (): bool => true);

        try {
            return preg_match($pattern, '') !== false;
        } finally {
            restore_error_handler();
        }
    }
}

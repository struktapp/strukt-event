<?php

namespace Strukt;

/**
 * Runs registered commands in insertion order with a repeatable halt hook.
 *
 * The loop is the REPL primitive of the package: register a hook with halt(),
 * then call run(). The hook is re-invoked on every iteration while the loop
 * stays halted, so it can read input and drive further commands.
 */
final class Loop extends Cmd
{
    /** Whether execution is currently paused. */
    private static bool $halted = false;

    /** Name of the currently registered terminal halt command. */
    private static ?string $haltedAt = null;

    /** Monotonic suffix used to name halt commands without randomness. */
    private static int $sequence = 0;

    /**
     * Registers the terminal callback and requests a halt after queued commands.
     *
     * Calling halt() again replaces the previous terminal callback, so stale
     * halt commands never linger in the queue.
     *
     * @param callable $callback Callback invoked while the loop is halted.
     * @return void
     */
    public static function halt(callable $callback): void
    {
        if (self::$haltedAt !== null) {
            static::forget(self::$haltedAt);
        }

        self::$halted = true;
        self::$haltedAt = sprintf('__halt_%d', ++self::$sequence);
        static::add(self::$haltedAt, $callback);
    }

    /**
     * Pauses or resumes loop execution.
     *
     * @param bool $halt Whether the loop should remain halted.
     * @return void
     */
    public static function pause(bool $halt = true): void
    {
        self::$halted = $halt;
    }

    /**
     * Checks whether the loop is paused.
     *
     * @return bool True when execution is paused.
     */
    public static function isHalted(): bool
    {
        return self::$halted;
    }

    /**
     * Executes the command queue, then loops the halt hook while halted.
     *
     * Queued commands run once in insertion order and are removed after they
     * run. The hook registered through halt() is then re-invoked on every
     * iteration while isHalted() stays true; call pause(false) to stop it.
     * Registry cleanup runs even when a command throws, so a failed run never
     * leaves the queue inconsistent.
     *
     * @param array{alias?: string, limit?: int}|array<string, mixed> $options Run options.
     * @return void
     */
    public static function run(array $options = []): void
    {
        $names = static::names();
        if (isset($options['alias']) && function_exists('alias')) {
            $aliases = alias(sprintf('%s*', trim((string) $options['alias'], '*')));
            $names = is_array($aliases)
                ? array_values(array_unique(array_map('strval', $aliases)))
                : [];
        }

        $terminal = self::$haltedAt;
        $limit = isset($options['limit']) ? max(0, (int) $options['limit']) : null;

        try {
            foreach ($names as $name) {
                if ($terminal !== null && $name === $terminal) {
                    continue;
                }

                if (!static::exists($name)) {
                    continue;
                }

                static::exec($name, static::defaultArgs($name));
                static::forget($name);
            }

            $iterations = 0;
            while ($terminal !== null && static::exists($terminal) && self::$halted) {
                static::exec($terminal, static::defaultArgs($terminal));

                if ($limit !== null && ++$iterations >= $limit) {
                    break;
                }
            }
        } finally {
            if ($terminal !== null) {
                static::forget($terminal);

                if (self::$haltedAt === $terminal) {
                    self::$haltedAt = null;
                }
            }
        }
    }

    /**
     * Clears commands and loop state.
     *
     * @return void
     */
    public static function reset(): void
    {
        parent::reset();
        self::$halted = false;
        self::$haltedAt = null;
        self::$sequence = 0;
    }
}

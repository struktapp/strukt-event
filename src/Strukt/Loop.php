<?php

namespace Strukt;

/** Runs registered commands in insertion order with a bounded halt hook. */
final class Loop extends Cmd
{
    /** Whether execution is currently paused. */
    private static bool $halted = false;

    /** Name of the optional terminal halt command. */
    private static ?string $haltedAt = null;

    /** Monotonic suffix used to avoid random-name generation. */
    private static int $sequence = 0;

    /**
     * Registers a terminal callback and requests a halt after queued commands.
     *
     * @param callable $callback Callback invoked at the end of the current run.
     * @return void
     */
    public static function halt(callable $callback): void
    {
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
     * Executes a snapshot of the command queue once.
     *
     * Alias filtering uses the base package's wildcard alias map. A halt hook
     * is always executed once; if it leaves the loop halted, execution stops
     * without spinning on the same command.
     *
     * @param array{alias?: string}|array<string, mixed> $options Run options.
     * @return void
     */
    public static function run(array $options = []): void
    {
        $names = static::names();
        if (isset($options['alias']) && function_exists('alias')) {
            $aliases = alias(sprintf('%s*', trim((string) $options['alias'], '*')));
            $names = is_array($aliases) ? array_values($aliases) : [];
        }

        $terminal = self::$haltedAt;
        if ($terminal !== null && !in_array($terminal, $names, true)) {
            $names[] = $terminal;
        }

        foreach ($names as $name) {
            if (!static::exists($name)) {
                continue;
            }

            static::exec($name, static::defaultArgs($name));
            static::forget($name);

            if ($name === $terminal) {
                if (!self::$halted) {
                    self::$haltedAt = null;
                }
                break;
            }

            if (self::$halted && $terminal === null) {
                break;
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

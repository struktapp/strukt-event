<?php

use Strukt\Cmd;
use Strukt\Event;

if (function_exists('helper')) {
    helper('event');
}

if (!function_exists('event') && helper_add('event')) {
    /**
     * Registers or retrieves a named event.
     *
     * @param string $name Event name.
     * @param callable|null $function Optional callback to register.
     * @return Event|null Event wrapper, or `null` when absent.
     */
    function event(string $name, ?callable $function = null): ?Event
    {
        if ($function !== null) {
            Cmd::add($name, $function);
        }

        $callback = Cmd::get($name);

        return $callback === null ? null : new Event($callback);
    }
}

if (!function_exists('cmd') && helper_add('cmd')) {
    /**
     * Executes a registered command.
     *
     * @param string $name Command name.
     * @param array<int|string, mixed>|null $args Optional arguments.
     * @return mixed Command result, or `null` when absent.
     */
    function cmd(string $name, ?array $args = null): mixed
    {
        if (!Cmd::exists($name)) {
            return null;
        }

        return Cmd::exec($name, $args);
    }
}

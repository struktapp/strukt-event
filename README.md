Strukt Event
============

[![Latest Stable Version](https://poser.pugx.org/strukt/event/v)](https://packagist.org/packages/strukt/event)
[![Total Downloads](https://poser.pugx.org/strukt/event/downloads)](https://packagist.org/packages/strukt/event)
[![License](https://poser.pugx.org/strukt/event/license)](https://packagist.org/packages/strukt/event)

Fast callable events and named command queues for Strukt. This package is
named event (singular) and depends on strukt/base.

This is the `strukt5` line. The public API is a drop-in match for
`strukt4/event`; the internals fix the halt-hook leak in `Loop`, make `run()`
exception-safe, and stop treating array callables such as
`[$object, 'method']` as argument arrays.

Requirements
------------

- PHP 8.2 or newer
- strukt/base

Installation
------------

```sh
composer require strukt/event
```

Composer loads src/helpers.php automatically. The package installs the global
event() and cmd() helpers after the normal Composer autoloader is required.

Global helpers
--------------

### event()

```php
event(string $name, ?callable $function = null): ?Strukt\Event
```

Registers a callable under a name when function is supplied, then returns an
Event wrapper for the registered command. It returns null when the name is not
registered.

```php
$login = event(
    'login',
    static fn (string $user, string $password): bool =>
        $user === 'admin' && $password === 'secret',
);

$accepted = $login?->apply('admin', 'secret')->exec();
```

Calling event() again with the same name replaces the command callback and
preserves its position in the command order.

### cmd()

```php
cmd(string $name, ?array $args = null): mixed
```

Executes a registered command. The optional argument array may be positional
or associative. It returns null when no command exists.

```php
cmd('login', ['admin', 'secret']);
cmd('greet', ['name' => 'Ada']);
```

Helper inventory
---------------

The event package registers these global helper names:

```text
event, cmd
```

Event API
---------

Strukt\Event retains a Closure and its ReflectionFunction. Use it when a
callable needs to be prepared once and invoked repeatedly.

```php
new Event(callable $event)
Event::create(callable $event): Event
$event->apply(mixed ...$args): Event
$event->applyArgs(array $args): Event
$event->clear(): Event
$event->ref(): ReflectionFunction
$event->exec(): mixed
$event(): mixed
```

apply() stores positional arguments. applyArgs() accepts either positional
integer keys or named string keys. Named keys are expanded with PHP named
argument invocation:

```php
$event = Event::create(
    static fn (string $name, int $age): string => "$name:$age",
);

$event->applyArgs(['name' => 'Ada', 'age' => 37])->exec();
// Ada:37
```

Calling apply() or applyArgs() replaces arguments stored by an earlier call.
clear() drops them again. Calling exec() without prepared arguments invokes the
callback with no arguments. An Event is invokable, so `$event()` is shorthand
for `$event->exec()`.

Command API
-----------

Strukt\Cmd is the process-local named command registry.

### add()

```php
Cmd::add(string $name, callable $callback): void
Cmd::add(string $name, array $defaultArgs, callable $callback): void
```

The first form registers a callback without defaults. The second stores
default positional or named arguments that exec() uses when no explicit
arguments are supplied. Arguments may also follow the callback
(`Cmd::add($name, $callback, $defaultArgs)`). Empty names, argument lists
without a callback, and non-callable definitions throw
InvalidArgumentException. Array callables such as `[$object, 'method']` are
recognised as callbacks, not as argument arrays.

```php
Cmd::add(
    'greet',
    ['name' => 'Ada'],
    static fn (string $name): string => "Hello $name",
);
```

### get()

```php
Cmd::get(string $name): ?callable
```

Returns a registered callback or null.

### ls()

```php
Cmd::ls(?string $filter = null): array
```

Returns command names in insertion order. When filter is supplied, it is used
as a regular-expression body and wrapped in slash delimiters. An invalid
regular expression throws InvalidArgumentException.

```php
Cmd::ls();          // ['greet', 'login']
Cmd::ls('^g');      // ['greet']
```

### exists(), doc(), exec(), and reset()

```php
Cmd::exists(string $name): bool
Cmd::doc(string $name): string
Cmd::exec(string $name, ?array $args = null): mixed
Cmd::reset(): void
```

doc() extracts the callback's doc comment and returns a compact description,
caching it until the command changes; for an undocumented or unknown command it
returns the command name. exec() uses explicit arguments when provided,
otherwise the defaults from add(). Executing an unknown command throws
InvalidArgumentException. reset() clears callbacks, defaults, cached
descriptions, and insertion order.

Loop API
--------

Strukt\Loop extends Cmd with an insertion-ordered queue and a repeatable halt
hook.

```php
Loop::halt(callable $callback): void
Loop::pause(bool $halt = true): void
Loop::isHalted(): bool
Loop::run(array $options = []): void
Loop::reset(): void
```

Register work, then run it:

```php
Loop::add('prepare', static function (): void {
    // work
});

Loop::halt(static function (): void {
    // invoked while paused; call pause(false) to continue
});

Loop::run();
```

run() executes commands in insertion order and removes them after they run.
While paused (Loop::pause(true) or after halt()), the halt callback registered
via halt() is re-invoked on each iteration until pause(false) is called, which
makes the loop a read-eval-print loop. Calling halt() again replaces the
previous terminal callback rather than leaving a stale command behind.

run() accepts two options:

- `alias`: filters the queue through the base package's wildcard alias map:

  ```php
  Loop::run(['alias' => 'worker']);
  ```

- `limit`: caps the number of halt iterations, useful as an escape hatch for
  non-interactive runs. It is unbounded by default so REPLs keep working.

  ```php
  Loop::run(['limit' => 1000]);
  ```

If a command or hook throws, run() still removes the terminal command before
letting the exception escape, so a failed run never leaves a dangling halt
command in the registry.

Performance notes
-----------------

- Event converts a callable to Closure and ReflectionFunction once.
- Event execution invokes the retained Closure directly.
- Cmd stores callbacks, defaults, and insertion order in dedicated arrays.
- Cmd caches doc() descriptions and invokes callbacks directly, without
  creating an Event wrapper.
- Loop re-invokes the halt hook while paused, enabling REPL-style loops.

Testing
-------

```sh
composer test
```

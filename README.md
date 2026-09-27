Strukt Event
============

[![Latest Stable Version](https://poser.pugx.org/strukt/event/v)](https://packagist.org/packages/strukt/event)
[![Total Downloads](https://poser.pugx.org/strukt/event/downloads)](https://packagist.org/packages/strukt/event)
[![License](https://poser.pugx.org/strukt/event/license)](https://packagist.org/packages/strukt/event)

Fast callable events and named command queues for Strukt. This package is
named event (singular) and depends on strukt/base.

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
$event->getRef(): ReflectionFunction
$event->exec(): mixed
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
Calling exec() without prepared arguments invokes the callback with no
arguments.

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
arguments are supplied. Empty names and non-callable definitions throw
InvalidArgumentException.

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

doc() extracts the callback's doc comment and returns a compact description;
for an undocumented or unknown command it returns the command name. exec()
uses explicit arguments when provided, otherwise the defaults from add().
Executing an unknown command throws InvalidArgumentException. reset() clears
callbacks, defaults, and insertion order.

Loop API
--------

Strukt\Loop extends Cmd with an insertion-ordered one-shot queue and a
terminal halt callback.

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
    // runs once at the end of this run
});

Loop::run();
```

run() executes a snapshot of the queue once and removes commands after they
run. A halt callback is executed once; if it leaves the loop halted, a later
run() will not spin on the same callback. Use pause(false) to resume a halted
loop. The optional alias option filters the queue through the base package's
wildcard alias map:

```php
Loop::run(['alias' => 'worker']);
```

Performance notes
-----------------

- Event converts a callable to Closure and ReflectionFunction once.
- Event execution invokes the retained Closure directly.
- Cmd stores callbacks, defaults, and insertion order in dedicated arrays.
- Cmd execution invokes callbacks directly without creating an Event wrapper.
- Loop uses a bounded terminal callback so a halted queue cannot spin forever.

Testing
-------

```sh
composer test
```

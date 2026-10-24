<?php

use Strukt\Alias;
use Strukt\Cmd;
use Strukt\Event;
use Strukt\Loop;

beforeEach(function (): void {
    Cmd::reset();
    Loop::reset();
    Alias::reset();
});

final class DocTarget
{
    /** Greets the world. */
    public function greet(): string
    {
        return 'hi';
    }

    public function bump(): int
    {
        return ++$this->count;
    }

    public int $count = 0;
}

test('events execute without reflection work on every call', function (): void {
    $event = Event::create(static fn (int $left, int $right): int => $left + $right);

    expect($event->apply(2, 3)->exec())->toBe(5)
        ->and($event->applyArgs(['left' => 4, 'right' => 5])->exec())->toBe(9);
});

test('event clear() drops prepared arguments', function (): void {
    $event = Event::create(static fn (int $value = 42): int => $value);

    expect($event->apply(7)->exec())->toBe(7)
        ->and($event->clear()->exec())->toBe(42);
});

test('event is invokable', function (): void {
    $event = Event::create(static fn (): string => 'called');

    expect($event())->toBe('called');
});

test('event exposes its reflection', function (): void {
    $event = Event::create(static fn (int $a): int => $a);

    expect($event->ref())->toBeInstanceOf(ReflectionFunction::class)
        ->and($event->ref()->getNumberOfParameters())->toBe(1);
});

test('commands preserve defaults and insertion order', function (): void {
    Cmd::add('greet', ['name' => 'Ada'], static fn (string $name): string => "Hello {$name}");
    Cmd::add('sum', static fn (int $left, int $right): int => $left + $right);

    expect(cmd('greet'))->toBe('Hello Ada')
        ->and(cmd('sum', [2, 3]))->toBe(5)
        ->and(Cmd::ls())->toBe(['greet', 'sum']);
});

test('explicit arguments override stored defaults', function (): void {
    Cmd::add('greet', ['name' => 'Ada'], static fn (string $name): string => "Hello {$name}");

    expect(Cmd::exec('greet'))->toBe('Hello Ada')
        ->and(Cmd::exec('greet', ['name' => 'Bob']))->toBe('Hello Bob');
});

test('callback-first definitions accept trailing arguments', function (): void {
    Cmd::add('greet', static fn (string $name): string => "Hi {$name}", ['name' => 'Ada']);

    expect(Cmd::exec('greet'))->toBe('Hi Ada');
});

test('array callables are not mistaken for argument arrays', function (): void {
    $target = new DocTarget();
    Cmd::add('bump', [$target, 'bump']);

    expect(Cmd::exec('bump'))->toBe(1);
});

test('re-adding a command replaces it and keeps its position', function (): void {
    Cmd::add('first', static fn (): string => 'one');
    Cmd::add('second', static fn (): string => 'two');
    Cmd::add('first', static fn (): string => 'updated');

    expect(Cmd::ls())->toBe(['first', 'second'])
        ->and(Cmd::exec('first'))->toBe('updated');
});

test('invalid command definitions are rejected', function (): void {
    expect(fn () => Cmd::add(''))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Cmd::add('   '))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Cmd::add('broken', ['name' => 'Ada']))->toThrow(InvalidArgumentException::class);
});

test('listing supports a regular-expression filter', function (): void {
    Cmd::add('greet', static function (): void {});
    Cmd::add('login', static function (): void {});

    expect(Cmd::ls('^g'))->toBe(['greet'])
        ->and(Cmd::ls('n$'))->toBe(['login'])
        ->and(fn () => Cmd::ls('('))->toThrow(InvalidArgumentException::class);
});

test('doc() reads a doc comment and caches it', function (): void {
    $target = new DocTarget();
    Cmd::add('doc.greet', [$target, 'greet']);

    $first = Cmd::doc('doc.greet');
    Cmd::exec('doc.greet');

    expect($first)->toContain('Greets the world.')
        ->and(Cmd::doc('doc.greet'))->toBe($first)
        ->and(Cmd::doc('unknown'))->toBe('unknown');
});

test('executing an unknown command throws', function (): void {
    expect(fn () => Cmd::exec('missing'))->toThrow(InvalidArgumentException::class);
});

test('reset clears commands', function (): void {
    Cmd::add('greet', static function (): void {});
    Cmd::reset();

    expect(Cmd::ls())->toBe([])
        ->and(Cmd::exists('greet'))->toBeFalse();
});

test('loop runs queued commands once in order and removes them', function (): void {
    $calls = [];
    Loop::add('one', function () use (&$calls): void {
        $calls[] = 'one';
    });
    Loop::add('two', function () use (&$calls): void {
        $calls[] = 'two';
    });

    Loop::run();

    expect($calls)->toBe(['one', 'two'])
        ->and(Cmd::ls())->toBe([]);
});

test('loop runs a halt callback while halted', function (): void {
    $calls = [];
    Loop::add('first', static function () use (&$calls): void {
        $calls[] = 'first';
        Loop::pause(true);
    });
    Loop::halt(static function () use (&$calls): void {
        $calls[] = 'halt';
        Loop::pause(false);
    });

    Loop::run();

    expect($calls)->toBe(['first', 'halt'])
        ->and(Loop::isHalted())->toBeFalse();
});

test('re-registering halt replaces the previous hook without leaking it', function (): void {
    $calls = [];
    Loop::halt(function () use (&$calls): void {
        $calls[] = 'first';
    });
    Loop::halt(function () use (&$calls): void {
        $calls[] = 'second';
        Loop::pause(false);
    });

    Loop::run();

    expect($calls)->toBe(['second'])
        ->and(Cmd::ls())->toBe([]);
});

test('a halt hook may run the loop more than once', function (): void {
    $calls = [];
    $iterations = 0;
    Loop::halt(function () use (&$calls, &$iterations): void {
        $calls[] = 'tick';
        if (++$iterations === 3) {
            Loop::pause(false);
        }
    });

    Loop::run();

    expect($calls)->toBe(['tick', 'tick', 'tick']);
});

test('a limit caps the halt loop', function (): void {
    $count = 0;
    Loop::halt(function () use (&$count): void {
        $count++;
    });

    Loop::run(['limit' => 3]);

    expect($count)->toBe(3);
});

test('a throwing halt hook still cleans up its terminal command', function (): void {
    Loop::halt(static function (): void {
        throw new RuntimeException('boom');
    });

    expect(fn () => Loop::run())->toThrow(RuntimeException::class)
        ->and(Cmd::exists('__halt_1'))->toBeFalse();
});

test('reset clears loop state', function (): void {
    Loop::halt(static function (): void {});
    Loop::reset();

    expect(Loop::isHalted())->toBeFalse()
        ->and(Cmd::ls())->toBe([]);
});

test('alias option filters the queue through the wildcard map', function (): void {
    alias('worker.one', 'work.one');

    $ran = [];
    Cmd::add('work.one', function () use (&$ran): void {
        $ran[] = 'one';
    });
    Cmd::add('other', function () use (&$ran): void {
        $ran[] = 'other';
    });

    Loop::run(['alias' => 'worker']);

    expect($ran)->toBe(['one']);
});

test('helpers register and execute events', function (): void {
    $event = event('sum', static fn (int $left, int $right): int => $left + $right);

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event?->apply(2, 3)->exec())->toBe(5)
        ->and(cmd('sum', [4, 5]))->toBe(9)
        ->and(event('missing'))->toBeNull()
        ->and(cmd('missing'))->toBeNull();
});

test('event helper registry includes every event helper', function (): void {
    expect(helper('event'))->toContain('event', 'cmd');
});

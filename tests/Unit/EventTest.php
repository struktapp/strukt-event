<?php

use Strukt\Cmd;
use Strukt\Event;
use Strukt\Loop;

beforeEach(function (): void {
    Cmd::reset();
    Loop::reset();
});

test('events execute without reflection work on every call', function (): void {
    $event = Event::create(static fn (int $left, int $right): int => $left + $right);

    expect($event->apply(2, 3)->exec())->toBe(5)
        ->and($event->applyArgs(['left' => 4, 'right' => 5])->exec())->toBe(9);
});

test('commands preserve defaults and insertion order', function (): void {
    Cmd::add('greet', ['name' => 'Ada'], static fn (string $name): string => "Hello {$name}");
    Cmd::add('sum', static fn (int $left, int $right): int => $left + $right);

    expect(cmd('greet'))->toBe('Hello Ada')
        ->and(cmd('sum', [2, 3]))->toBe(5)
        ->and(Cmd::ls())->toBe(['greet', 'sum']);
});

test('loop runs a halt callback once without spinning', function (): void {
    $calls = [];
    Loop::add('first', static function () use (&$calls): void {
        $calls[] = 'first';
    });
    Loop::halt(static function () use (&$calls): void {
        $calls[] = 'halt';
    });

    Loop::run();

    expect($calls)->toBe(['first', 'halt'])
        ->and(Loop::isHalted())->toBeTrue();
});

test('event helper registry includes every event helper', function (): void {
    expect(helper('event'))->toContain('event', 'cmd');
});

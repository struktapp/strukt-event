<?php

use Strukt\Cmd;
use Strukt\Loop;

require __DIR__ . '/../vendor/autoload.php';

Loop::halt(function (): void {

    Cmd::add('ls', static function (): string {
        return 'list dir';
    });

    Cmd::add('exit', static function (): string {
        Loop::pause(false);
        return 'Bye bye!';
    });

    $line = trim((string) readline('test>> '));
    readline_add_history($line);

    if ($line === '' || !Cmd::exists($line)) {
        return;
    }

    echo sprintf("%s\n", Cmd::exec($line));
});

Loop::run();

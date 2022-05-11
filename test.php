<?php

use Strukt\Loop;
use Strukt\Cmd;

require "bootstrap.php";

Loop::halt(function(){

	Cmd::add("ls", function(){

		return "list dir";
	});

	Cmd::add("exit", function(){

		Loop::pause(false);

		return "Bye bye!";
	});

	$line = trim(readline("test>> ")); 
	readline_add_history($line); 
	
	$found = false;
	if(!empty($line))
		$found = Cmd::exists($line);

	if($found)
		echo(sprintf("%s\n", Cmd::exec($line)));
});

Loop::run();
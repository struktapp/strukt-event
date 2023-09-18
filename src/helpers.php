<?php

use Strukt\Event;
use Strukt\Cmd;

if(!function_exists("event")){

	function event(string $name, callable $func = null){

		if(!is_null($func))
			Cmd::add($name, $func);

		if(Cmd::exists($name))
			return new Event(Cmd::get($name));
	}
}
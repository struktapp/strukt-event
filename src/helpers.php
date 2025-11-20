<?php

use Strukt\Event;
use Strukt\Cmd;

if(helper_add("cmd")){

	/**
	 * @param string $name
	 * @param callable $func
	 * 
	 * @return \Strukt\Event
	 */
	function event(string $name, ?callable $func = null):?Event{

		if(!is_null($func))
			Cmd::add($name, $func);

		if(Cmd::exists($name))
			return new Event(Cmd::get($name));

		return null;
	}
}

if(helper_add("cmd")){

	/**
	 * @param string $name
	 * @param array $args
	 * 
	 * @return mixed
	 */
	function cmd(string $name, ?array $args = null):mixed{

		if(Cmd::exists($name)){

			if(!is_null($args))
				return Cmd::exec($name, $args);

			return Cmd::exec($name);
		}

		return null;
	}
}
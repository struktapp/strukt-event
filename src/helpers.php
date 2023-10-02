<?php

use Strukt\Event;
use Strukt\Cmd;
use Strukt\Alias;

if(!function_exists("event")){

	function event(string $name, callable $func = null){

		if(!is_null($func))
			Cmd::add($name, $func);

		if(Cmd::exists($name))
			return new Event(Cmd::get($name));
	} 
}

if(!function_exists("alias")){

	function alias(string $alias = null, string $long_name = null){

		if((!is_null($alias) || !empty($alias)) && is_null($long_name))
			return Alias::get($alias);

		if(!is_null($alias) && !is_null($long_name))
			return Alias::set($alias, $long_name);

		return Alias::ls();
	}
}
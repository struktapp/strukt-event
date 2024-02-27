<?php

use Strukt\Event;
use Strukt\Cmd;
use Strukt\Alias;

helper("events");

if(helper_add("event")){

	function event(string $name, callable $func = null){

		if(!is_null($func))
			Cmd::add($name, $func);

		if(Cmd::exists($name))
			return new Event(Cmd::get($name));
	} 
}

if(helper_add("cmd")){

	function cmd(string $name, array $args = null){

		if(Cmd::exists($name)){

			if(!is_null($args))
				return Cmd::exec($name, $args);

			return Cmd::exec($name);
		}
	}
}

if(helper_add("alias")){

	function alias(string $alias = null, string $long_name = null){

		if(!is_null($alias))
			if(!str_ends_with($alias, "*")){

				if(!empty($alias) && is_null($long_name))
					return Alias::get($alias);

				if(!is_null($long_name))
					return Alias::set($alias, $long_name);
			}

		if(!is_null($alias))
			return Alias::ls(trim($alias,"*"));

		return Alias::ls();
	}
}

if(helper_add("path")){

	function path(string $path){

		return $path;
	}
}

if(helper_add("ref")){

	function ref(string $class){

		return new Strukt\Ref($class);
	}
}
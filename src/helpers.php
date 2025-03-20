<?php

use Strukt\Event;
use Strukt\Cmd;
use Strukt\Alias;

helper("events");

if(helper_add("event")){

	function event(string $name, ?callable $func){

		if(!is_null($func))
			Cmd::add($name, $func);

		if(Cmd::exists($name))
			return new Event(Cmd::get($name));
	} 
}

if(helper_add("cmd")){

	function cmd(string $name, ?array $args){

		if(Cmd::exists($name)){

			if(!is_null($args))
				return Cmd::exec($name, $args);

			return Cmd::exec($name);
		}
	}
}

if(helper_add("alias")){

	function alias(?string $alias, ?string $long_name){

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

	function ref(mixed $class){

		if(is_string($class))
			if(class_exists($class))
				return Strukt\Ref::create($class);

		if(is_object($class))
			if(class_exists(@array_shift(array_filter([get_class($class)], fn($name)=>$name!=Closure::class))??""))
				return Strukt\Ref::createFrom($class);

		if(is_callable($class))
			return Strukt\Ref::func($class);
	}
}
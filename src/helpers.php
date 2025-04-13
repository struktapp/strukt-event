<?php

use Strukt\Event;
use Strukt\Cmd;
use Strukt\Alias;
use Strukt\Ref;

helper("events");

if(helper_add("event")){

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

if(helper_add("alias")){

	/**
	 * @param string $alias
	 * @param string $long_name
	 * 
	 * @return array|string|null
	 */
	function alias(?string $alias = null, ?string $long_name = null):array|string|null{

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

	/**
	 * Just a function to highlight a path on code
	 * 
	 * @param string $path
	 * 
	 * @return string
	 */
	function path(string $path):string{

		return $path;
	}
}

if(helper_add("ref")){

	/**
	 * @param string|object|callable $class
	 * 
	 * @return mixed
	 */
	function ref(string|object|callable $class):mixed{

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
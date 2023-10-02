<?php

namespace Strukt;

class Alias{

	protected static $aliases = []; 

	public static function set(string $alias, string $long_name){

		if(self::exists($alias))
			throw new \Exception(sprintf("Alias %s already exists!", $alias));

		return static::$aliases[$alias] = $long_name;
	}

	public static function get(string $alias){

		if(self::exists($alias))
			return static::$aliases[$alias];

		return null;
	}

	public static function exists(string $alias){

		return array_key_exists($alias, static::$aliases);
	}

	public static function ls(){

		return array_keys(static::$aliases);
	}
}
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

	public static function ls(string $starts_with = null){

		$names = array_keys(static::$aliases);
		if(is_null($starts_with))
			return $names;

		$ls = [];
		foreach($names as $name)
			if(str_starts_with($name, $starts_with))
				$ls[] = $name;

		return $ls;
	}
}
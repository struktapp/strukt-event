<?php

namespace Strukt;

class Loop extends Cmd{

	private static $halted = false;
	private static $halted_at = null;

	private static function noExit(){

		return !empty(static::$callbacks);
	}

	public static function halt(callable $callback){

		static::$halted = true;
		static::$halted_at = sprintf("init:halted@%s", sha1(rand()));

		static::add(static::$halted_at, $callback);
	}

	public static function pause(bool $halt = true){

		static::$halted = $halt;		
	}

	public static function isHalted(){

		return static::$halted;
	}

	public static function run(array $options = []){

		$names = static::$names;
		if(array_key_exists("alias", $options)){

			$aliases = alias(sprintf("%s*", trim($options["alias"],"*")));
			$names = arr($aliases)->each(fn($k, $alias)=>alias($alias))->yield();
			$names[] = static::$halted_at;
		}

		$idx = 0;
	    while(static::noExit()){

	    	$name = trim($names[$idx]);
	    	$args = static::$args[$name];

	    	static::exec($name, $args);

	    	if(!static::isHalted()){

		        unset(static::$callbacks[$name]);

		        ++$idx;
		    }
	    }
	}
}
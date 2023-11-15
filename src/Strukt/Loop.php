<?php

namespace Strukt;

class Loop extends Cmd{

	private static $halted = false;

	private static function noExit(){

		return !empty(static::$callbacks);
	}

	public static function halt(callable $callback){

		static::$halted = true;

		static::add(sprintf("init:halted@%s", sha1(rand())), $callback);
	}

	public static function pause(bool $halt = true){

		static::$halted = $halt;		
	}

	public static function isHalted(){

		return static::$halted;
	}

	public static function run(array $options = []){

		if(array_key_exists("alias", $options))
			static::$names = alias(sprintf("%s*", trim($options["alias"],"*")));

		$idx = 0;
	    while(static::noExit()){

	    	$name = static::$names[$idx];
	    	$args = static::$args[$name];

	    	static::exec($name, $args);

	    	if(!static::isHalted()){

		        unset(static::$callbacks[$name]);

		        ++$idx;
		    }
	    }
	}
}
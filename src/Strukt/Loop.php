<?php

namespace Strukt;

class Loop extends Cmd{

	private static $halted = false;
	private static $halted_at = null;

	/**
	 * @return boolean
	 */
	private static function noExit():bool{

		return !empty(static::$callbacks);
	}

	/**
	 * @param callable $callback
	 * 
	 * @return void
	 */
	public static function halt(callable $callback):void{

		static::$halted = true;
		static::$halted_at = sprintf("init:halted@%s", sha1(rand()));

		static::add(static::$halted_at, $callback);
	}

	/**
	 * Halt loop
	 * 
	 * @param bool $halt
	 */
	public static function pause(bool $halt = true):void{

		static::$halted = $halt;		
	}

	/**
	 * Check if loop is halted
	 * 
	 * @return boolean
	 */
	public static function isHalted(){

		return static::$halted;
	}

	/**
	 * Run loop
	 * 
	 * @param array $options
	 * 
	 * @return void
	 */
	public static function run(array $options = []):void{

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
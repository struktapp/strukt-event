<?php

namespace Strukt;

class Loop extends Cmd{

	public static function run(){

		$idx = 0;

	    while(true){

	    	if(empty(static::$callbacks))
	    		return;

	    	$name = static::$names[$idx];
	    	$args = static::$args[$name];

	    	static::exec($name, $args);

	        unset(static::$callbacks[$name]);

	        ++$idx;
	    }
	}
}
<?php

namespace Strukt;

class Cmd{

	protected static $callbacks = []; 
	protected static $args = [];
	protected static $names = [];

	public static function add(...$argv){

		$name = array_shift($argv);
		if(!is_string($name))
			new \Exception("Strukt\Loop::create(arg1...) must be string!");

		$expected = array_shift($argv);

		$args = null;
		$callback = $expected;
		if(is_array($expected))
			$args = $expected;

		if(!is_callable($callback))
			$callback = array_shift($argv);

		if(!is_callable($callback))
			new \Exception("Strukt\Loop::create(...arg2|arg3...) either must be callable!");

		static::$callbacks[$name] = $callback;
		static::$args[$name] = $args;
		static::$names[] = $name;
	}

	public static function get($name){

		return static::$callbacks[$name];
	}

	public static function ls(){

		return array_keys(static::$callbacks);
	}

	public static function exists($name){

		return array_key_exists($name, static::$callbacks);
	}

	public static function doc($name){

		$rfunc = Ref::func(static::get($name))->getRef();

        $doc = $rfunc->getDocComment();

        if(!empty($doc)){

            $doc = str_replace(["/**","* ", "*/"], "", strval($doc));

            return sprintf(" %s %s", str_pad($name, 15), trim($doc));
        }

        return sprintf(" %s", $name);
	}

	public static function exec(string $name, array $args = null){

		$callback = static::get($name);

		$event =  Event::create($callback);
		if(!is_null($args))
			$event = $event->applyArgs($args);

		return $event->exec();
	}
}
<?php

namespace Strukt;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class Cmd{

	protected static $callbacks = []; 
	protected static $args = [];
	protected static $names = [];

	/**
	 * @return void
	 */
	public static function add(...$argv):void{

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

	/**
	 * @param string $name
	 * 
	 * @return callable
	 */
	public static function get(string $name):?callable{

		return static::$callbacks[$name];
	}

	/**
	 * @param string $filter
	 * 
	 * @return array
	 */
	public static function ls(?string $filter = null):array{

		if(!is_null($filter))
			return array_keys(array_filter(static::$callbacks, 
											fn($k)=>preg_match(sprintf("/%s/", $filter), $k), 
											ARRAY_FILTER_USE_KEY));

		return array_keys(static::$callbacks);
	}

	/**
	 * @param string $name
	 * 
	 * @return boolean
	 */
	public static function exists(string $name):bool{

		return array_key_exists($name, static::$callbacks);
	}

	/**
	 * @param string $name
	 * 
	 * @return string
	 */
	public static function doc(string $name):string{

		$rfunc = Ref::func(static::get($name))->getRef();

        $doc = $rfunc->getDocComment();

        if(!empty($doc)){

            $doc = str_replace(["/**","* ", "*/"], "", strval($doc));

            return sprintf(" %s %s", str_pad($name, 15), trim($doc));
        }

        return sprintf(" %s", $name);
	}

	/**
	 * @param string $name
	 * @param array $args
	 * 
	 * @return mixed
	 */
	public static function exec(string $name, ?array $args = null):mixed{

		$callback = static::get($name);

		$event =  Event::create($callback);
		if(!is_null($args))
			$event = $event->applyArgs($args);

		return $event->exec();
	}
}
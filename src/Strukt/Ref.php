<?php

namespace Strukt;

class Ref{

	private $class;
	private $instance;

	public function __construct(\Reflector $class){

		$this->class = $class;
	}

	public static function func($name_or_func){

		$rFunc = new \ReflectionFunction($name_or_func);

		return new class($rFunc){

			private $oFunc;

			public function __construct($func){

				$this->oFunc = $func;
			}

			public function getRef(){

				return $this->oFunc;
			}

			public function invoke(...$args){

				if(is_null($args))
					$result = $this->oFunc->invoke();
				else
					$result = $this->oFunc->invokeArgs($args);

				return $result;
			}
		};
	}

	/**
	* Create reflection from object instance
	*/
	public static function createFrom(object $instance){

		$class = new \ReflectionObject($instance);

		$ref = new self($class);

		$ref->readyMade($instance);

		return $ref;
	}

	public static function create(string $classname){		

		$class = new \ReflectionClass($classname);

		return new self($class);
	}

	public function method(string $name){

		$rMethod = $this->class->getMethod($name);

		return new class($rMethod, $this->instance){

			private $oMethod;
			private $oInstance;

			public function __construct($method, $instance){

				$this->oMethod = $method;
				$this->oInstance = $instance;
			}

			public function getRef(){

				return $this->oMethod;
			}

			public function getClosure(){

				return $this->oMethod->getClosure($this->oInstance);
			}

			public function invoke(...$args){

				if(is_null($args))
					$result = $this->oMethod->invoke($this->oInstance);
				else
					$result = $this->oMethod->invokeArgs($this->oInstance, $args);

				return $result;
			}
		};
	}

	public function prop(string $name){

		$rProp = $this->class->getProperty($name);

		return new class($rProp, $this->instance){

			private $rProp;
			private $oInstance;

			public function __construct($prop, $instance){

				$this->rProp = $prop;
				$this->oInstance = $instance;
			}

			public function getRef(){

				return $this->rProp;
			}

			public function set($value){

				$this->rProp->setValue($this->oInstance, $value);
			}

			public function get(){

				return $this->rProp->getValue($this->oInstance);
			}
		};
	}

	/**
	* \ReflectionClass
	*/
	public function getRef(){

		return $this->class;
	}

	public function getInstance(){

		return $this->instance;
	}

	public function readyMade($instance){

		return $this->instance = $instance;
	}

	/**
	* newInstance
	*/
	public function make(...$args){

		$this->instance = $this->class->newInstance($args);

		return $this;
	}

	/**
	* newInstanceArgs
	*/
	public function makeArgs($args){

		$this->instance = $this->class->newInstanceArgs($args);

		return $this;
	}

	/**
	* newInstanceWithoutConstructor
	*/
	public function noMake(){

		$this->instance = $this->class->newInstanceWithoutConstructor();

		return $this;
	}
}
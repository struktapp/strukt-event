<?php

namespace Strukt;

use Strukt\Ref;

/**
* Event Executor class
*
* @author Moderator <pitsolu@gmail.com>
*/
class Event{

	/**
	* Event arguments
	*
	* @var Array
	*/
	private $args = null;

	/**
	* Reflected Event
	*
	* @var \Strukt\Ref
	*/
	private $ref = null;

	/**
	* List of reflected parameters
	*
	* @var array
	*/
	private $rParams = null;

	/**
	* Constructor
	*
	* @param $event callable
	*/
	public function __construct(callable $event){

		$this->ref = Ref::func($event);
		$this->rParams = $this->ref->getRef()->getParameters();
	}

	/**
	* Static constructor
	*
	* @param $event callable
	* 
	* @return static
	*/
	public static function create(callable $event):static{

		return new self($event);
	}

	/**
	* Apply arguments to event
	*
	* @param mixed ...
	*
	* @return static
	*/
	public function apply(...$args):static{

		$this->args = $args;

		return $this;
	}

	/**
	* Apply arguments to event
	*
	* @param array $args
	*
	* @return static
	*/
	public function applyArgs(array $args):static{

		$this->args = $args;

		return $this;
	}

	/**
	* Get reflector
	*
	* @return \ReflectionFunction
	*/
	public function getRef():\ReflectionFunction{

		return $this->ref->getRef();
	}

	/**
	* Execute event
	*
	* @return mixed
	*/
	public function exec():mixed{

		if(is_null($this->args))
			return $this->ref->invoke();

		$args=null;
		if(is_map($this->args))
			foreach($this->rParams as $rParam)
				$args[] = $this->args[$rParam->getName()];

		$this->args = array_values($this->args);
		if(!is_null($args))
			$this->args = $args;

		return $this->ref->invoke(...$this->args);
	}
}
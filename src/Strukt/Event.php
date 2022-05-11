<?php

namespace Strukt;

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
	* Event
	*
	* @var Array
	*/
	// private $event = null;

	/**
	* Reflected Event
	*
	* @var Strukt\Ref
	*/
	private $ref = null;

	/**
	* List of reflected parameters
	*
	* @var Array<ReflectionParameters>
	*/
	private $rParams = null;

	/**
	* Constructor
	*
	* @param $event callable
	*/
	public function __construct(callable $event){

		// $this->event = $event;

		$this->ref = Ref::func($event);

		$this->rParams = $this->ref->getRef()->getParameters();
	}

	/**
	* Static constructor
	*
	* @param $event callable
	*/
	public static function create(callable $event){

		return new self($event);
	}

	/**
	* Apply arguments to event
	*
	* @param mixed ...
	*
	* @return \Strukt\Event
	*/
	public function apply(...$args){

		// $this->args = func_get_args();
		$this->args = $args;

		return $this;
	}

	/**
	* Apply arguments to event
	*
	* @param $args Array
	*
	* @return \Strukt\Event
	*/
	public function applyArgs(Array $args){

		$this->args = $args;

		return $this;
	}

	/**
	* Get list of reflected parameters
	*
	* @return Array
	*/
	public function getParams(){

		$params = [];
		foreach($this->rParams as $rParam){

			$name = (string)$rParam->getName();
			$type = (string)$rParam->getType();
			$params[$name] = $type;
		}

		return $params;
	}

	public function expects($type){

		foreach($this->rParams as $rParam)
			if($rParam->hasType())
				if($type == $rParam->getType())
					return true;

		return false;
	}

	/**
	* Execute event
	*
	* @return mixed
	*/
	public function exec(){

		if(is_null($this->args))
			return $this->ref->invoke();
			// return call_user_func($this->event);

		// if(!is_null($this->ref)){
			
			// $isNotAssoc = is_numeric(key($this->args));

		$args=null;
		if(Contract\AbstractArr::isMap($this->args))
			foreach($this->rParams as $rParam)
				$args[] = $this->args[$rParam->getName()];

		if(!is_null($args))
			$this->args = $args;

		return $this->ref->invoke(...$this->args);
		// }

		// return call_user_func_array($this->event, $this->args);				
	}
}
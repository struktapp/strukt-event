<?php

namespace Strukt;

/**
* Event Executor class
*
* @author Moderator <pitsolu@gmail.com>
*/
class Event{

	use Traits\Arr;

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
	*/
	public static function create(callable $event):Event{

		return new self($event);
	}

	/**
	* Apply arguments to event
	*
	* @param mixed ...
	*
	* @return \Strukt\Event
	*/
	public function apply(...$args):Event{

		$this->args = $args;

		return $this;
	}

	/**
	* Apply arguments to event
	*
	* @param array $args
	*
	* @return \Strukt\Event
	*/
	public function applyArgs(array $args):Event{

		$this->args = $args;

		return $this;
	}

	/**
	* Get list of reflected parameters
	*
	* @return array
	*/
	public function getParams():array{

		$params = [];
		foreach($this->rParams as $rParam){

			$name = (string)$rParam->getName();
			$type = (string)$rParam->getType();
			$params[$name] = $type;
		}

		return $params;
	}

	/**
	 * @param string $type
	 * 
	 * @return boolean
	 */
	public function expects(string $type):bool{

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
	public function exec():mixed{

		if(is_null($this->args))
			return $this->ref->invoke();

		$args=null;
		if($this->isMap($this->args))
			foreach($this->rParams as $rParam)
				$args[] = $this->args[$rParam->getName()];

		$this->args = array_values($this->args);
		if(!is_null($args))
			$this->args = $args;

		return $this->ref->invoke(...$this->args);
	}
}
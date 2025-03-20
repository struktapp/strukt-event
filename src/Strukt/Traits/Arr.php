<?php

namespace Strukt\Traits;

trait Arr{

	/**
	* Is array fully associative
	* 
	* @param array arr
	* 
	* @return bool
	*/
	public function isMap(array $arr):bool{

		return !empty($arr) && 
				array_keys($arr) !== range(0, count($arr) - 1) && 
				empty(array_filter(array_keys($arr), "is_numeric"));
	}
}
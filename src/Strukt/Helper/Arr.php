<?php

namespace Strukt\Helper;

trait Arr{

	/**
	* Is array fully associative
	*/
	public function isMap(array $arr){

		return !empty($arr) && 
				array_keys($arr) !== range(0, count($arr) - 1) && 
				empty(array_filter(array_keys($arr), "is_numeric"));
	}
}
<?php

use Strukt\Ref;

class RefTest extends PHPUnit\Framework\TestCase{

	public function setUp():void{

		//
	}

	public function testRefClass(){

		$ref = Ref::create(Fixture\User::class);
		$ref->makeArgs(["pitsolu"]);
		$ref->method("setPassword")->invoke("p@55w0rd");
		$this->assertEquals("pitsolu", $ref->method("getUsername")->invoke());
		$this->assertEquals(sha1("p@55w0rd"), $ref->method("getPassword")->invoke());
	}

	public function testRefObj(){

		$user = new Fixture\User("admin");
		$user->setPassword("p@55w0rd!!");

		$ref = Ref::createFrom($user);
		$this->assertEquals("admin", $ref->method("getUsername")->invoke());
		$this->assertEquals(sha1("p@55w0rd!!"), $ref->method("getPassword")->invoke());
	}

	public function testRefProp(){

		$this->markTestSkipped("Problem asserting exception on setting non-public member!");

		$this->expectExceptionMessage('Cannot access non-public member Fixture\User::$username');

		$ref = Ref::create(Fixture\User::class);
		$ref->noMake();
		$ref->prop("username")->set("pitsolu");

		/**
		* asserts will not be executed because of exception in the line above
		*/
		$this->assertInstanceOf(Fixture\User::class, $ref->getInstance());
		$this->assertEquals("pitsolu", $ref->prop("username")->get());
	}

	public function testRefFunc(){

		$ref = Ref::func(function(int $a, int $b){

			return $a + $b;
		});

		$this->assertEquals(5, $ref->invoke(3,2));
	}
}
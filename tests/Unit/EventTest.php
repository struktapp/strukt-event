<?php

use Strukt\Event;

$credentials = array("admin", "p@55w0rd");
$login = Event::create(function($username, $password)use($credentials){

	return $username == reset($credentials) && $password == end($credentials);
});

test("event.exec", function(){

	$adeleSays = Event::create(function(){

		return "Hello? Are you there?";
	});

	expect($adeleSays->exec())->toBe("Hello? Are you there?");
});

test("event.apply", function()use($login){

	$credentials = array("admin", "p@55w0rd");

	list($username, $password) = $credentials;

	expect($login->apply($username, $password)->exec())->toBeTrue();
});

test("event.applyArgs", function()use($login){

	$credentials = array("admin", "p@55w0rd");

	expect($login->applyArgs($credentials)->exec())->toBeTrue();
});

test("event.applyArgs[map]", function()use($login){

	$credentials = array("password"=>"p@55w0rd", "username"=>"admin");

	expect($login->applyArgs($credentials)->exec())->toBeTrue();
});

test("event[reflect.method]", function(){

	$r = new ReflectionClass(Fixture\Person::class);
	$m = $r->getMethod("getId");
	$c = $m->getClosure($r->newInstance());

	$e = new Event($c);
	expect($e->exec())->toBeInt();
});
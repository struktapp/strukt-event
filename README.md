Strukt Event
===

## Events

```php
//use Strukt\Event
$credentials = array("username"=>"admin", "password"=>"p@55w0rd");

$login = Event::create(function($username, $password) use($credentials){

    return $username == $credentials["username"] && $password == $credentials["password"];
});

$isLoggedIn = $login->apply("admin","p@55w0rd")->exec();
// $isLoggedIn = $login->applyArgs($credentials)->exec();
```

## Reflector

```php
// $r = Strukt\Ref::createFrom(new Payroll\User);
$r = Strukt\Ref::create(Payroll\User::class);
$r->getRef();//ReflectionClass
//$r->noMake();//newInstanceWithoutConstructor
$r->make("pitsolu");//newInstanceArgs
$r->getInstance();//InstanceOf Payroll\AuthModule\Model\User
$r->prop("id")->getRef();//ReflectionProperty
$r->prop("id")->set(1);
$r->prop("id")->get();//1
$r->method("getUsername")->invoke();//pitsolu
$r->method("getUsername")->getRef(); //ReflectionMethod
$r->method("getUsername")->getClosure();//Closure
Strukt\Ref::func("array_sum")->invoke([1,2]);//3
Strukt\Ref::func("array_sum")->getRef();//ReflectionFunction
```

## Loop

```php
use Strukt\Loop;
use Strukt\Cmd;

Loop::add("auth", ["admin", "p@55w0rd"], function($username, $password){

	echo sprintf("username:%s|password:%s\n", $username, $password);
});

Loop::add("help", function(){

	echo "Docs\n";
});

Loop::add("hello", function($name){

	echo sprintf("Hello %s", $name);
});

Loop::run();
// Cmd::exec("help");
// Cmd::exec("auth", ["peter", "pazzw0rd"]);
// Cmd::exec("hello", ["World"]);
```
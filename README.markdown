# RocketSled - a micro framework for PHP 5.3+

by iain@workingsoftware.com.au

## Goals:

* Provide a simple way to organise code from anywhere

* Provide a default and extensible autoloading implementation

* Provide a simple and extensible way to execute classes from the web or command line

* Do as little as possible and be as close to "raw PHP" as possible

* Don't provide any "directory structure"

* Don't provide the main application entry point (eg. index.php)

## Hello World

The simplest possible RocketSled application using the default autoloader and 
default routing (see below for details) is:

require('../RocketSled/rocket_sled.class.php');
RocketSled::run();

## Using and extending the default router

Any class which implements the rocketsled\Runnable interface can be executed
from the command line:

```
php index.php RunnableClass
```

or from the browser:

```
http://localhost/?r=RunnableClass
```

By default, RocketSled will look for the first argument on the command line
or a parameter in the query string "r" (for runnable).

You can override this behaviour with your own implementation by passing a new
Closure into the RocketSled::runnable() method:

```php
RocketSled::runnable(function()
{
    return MyDespatcher::runnable();
});
```

The function you pass in should return an instantiated object that implements
the Runnable interface.

## Using and extending the default autoloader

The default autoloader implementation will load any class in any directory
being scanned by RocketSled.

By default, RocketSled will scan its own parent directory, however you can
change this by passing in an array of directory paths to the RocketSled::scan()
method:

```php
RocketSled::scan(array('../','/some/shared/path/');
```

The class names are mapped to files where:

```
ClassName
```

is expected to be located in a file called:

```
class_name.class.php
```

The default autoloader implementation also supports namespaces. It expects the
namespace path to match the directory structure so the class:

```php
my\NameSpace\ClassName
```

is expected to be located in:

```
my/NameSpace/class_name.class.php
```

In any directory being scanned by RocketSled

NB: The capitaliasation used in the namesapce is preserved in the path,
however the way that class names map to class file name is enforced.

Namespaced classes can also be executed from the command line:

```
php index.php "my\\NameSpace\\ClassName"
```
NB: You will need to use double backslashes on the command line

or from the browser:

```
http://localhost/?r=my\NameSpace\ClassName
```

You can also override the autoload, configs and runnable functions
by passing in closures:

```php
RocketSled::autoload(function()
{
    require('all_my_classes.php');
});
```


## Something more sophisticated

For a suggested setup using a cached autoloader and the RocketPack package management system,
setup to allow for easy deployment on multiple servers with shared packages, see the repo:

https://github.com/iaindooley/RocketSledBoilerPlate

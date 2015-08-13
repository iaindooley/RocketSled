# RocketSled - a the microest framework for PHP 5.3+

by iain@workingsoftware.com.au

###NB: This release of RocketSled is a new, non-monolithic style RocketSled. In this release, RocketSled no longer has the main "entry point" to the application, ie. index.php. This represents a significant change in the code. If you are already using RocketSled and don't wish to update, use the release tagged 1.3 or below and fork from there.

## Goals:

* Provide a simple way to organise code from anywhere

* Provide a default and extensible autoloading implementation

* Provide a simple and extensible way to execute classes from the web or command line

* Do as little as possible and be as close to "raw PHP" as possible

* Don't provide any "directory structure"

* Don't provide the main application entry point (eg. index.php)

* Don't be magic: work with PHP and use PHPs features

## Hello World

RocketSled does not aim to provide the main application entry point and
encourages all your code to be outside of your web accessible directory
by default.

To create your first hello world, start by creating a directory
called MyProject and make it accessible via a web browser.

Install RocketSled in the same parent directory so your directory looks
like this:

```
site/
├── MyProject
└── RocketSled
```

Now in MyProject put a file called index.php that looks like this:

```php
<?php
require('../RocketSled/autoload.php');
RocketSled::run();
```

You should then put all your actual code into classes inside other packages
for example if you wanted to create code to say "Hello World" create a directory
called HelloWorld as a sibling of the other two:

```
site/
├── MyProject
├── RocketSled
└── HelloWorld
```

Now inside HelloWorld put a file called say_hello.class.php that looks like
this:

```php
<?php
    class SayHello implements RocketSled\Runnable
    {
        public function run()
        {
            echo "Hello World!".PHP_EOL;
        }
    }
```

Now change to MyProject directory:

```
cd /path/to/MyProject/
```

Now you can run your class from the command line like this:

```
php index.php SayHello
```

or from a web browser like this:

```
http://localhost/MyProject/?r=SayHello
```

You can organise your code/classes however you like. See below for further
details about the default autoloading and routing implementations.


## Using and extending the default router

Any class which implements the RocketSled\Runnable interface can be executed
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

NB: The capitalisation used in the namesapce is preserved in the path,
however the way that class names map to class file name is enforced.

Namespaced classes can also be executed from the command line:

```
php index.php "my\\NameSpace\\ClassName"
```
NB: You will need to use double backslashes on the command line.

They can also be executed from the browser:

```
http://localhost/?r=my\NameSpace\ClassName
```

You can override the default autoloader by passing in a new Closure
to the RocketSled::autoload() method:

```php
RocketSled::autoload(function()
{
    require('all_my_classes.php');
});
```

Note that if you want to use a class to implement a new autoloader, for
example to cache class paths and prevent directory scanning, then
you will have to include that class before using it because otherwise
RocketSled's default autoloader will always scan the directory anyway.

So you can do something like:

```php
include_once('../MyCachingAutoLoader/autoload.php');
RocketSled::autoload(MyCachingAutoLoader::autoloadClosure());
```

For an example of a caching autoloader implementation see:

https://github.com/iaindooley/DataBank

## Something more sophisticated

For a suggested setup using a cached autoloader and the RocketPack package management system
setup to allow for easy deployment on multiple servers with shared packages, see the repo:

https://github.com/iaindooley/RocketSledBoilerPlate

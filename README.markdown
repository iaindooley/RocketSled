# RocketSled - a front controller micro framework for PHP 5.3

by iain@workingsoftware.com.au

## Goals:

* Provide a front controller and default autoload implementation

* Provide a way of managing packages and executing config files (eg. to add custom autoload implementations)

* Ability to use any class, code or package from any framework with the absolute minimum effort

* Ability to create and distribute packages that are independently useful and do not depend on RocketSled

* Ability to execute classes easily from the command line

* Do as little as possible, leaving everything else (eg. things like URLs, templating and database access) up to packages

## Hello World

NB: You can use RocketSled on the command line with or without a web server but if you want to load pages in your browser then you'll need to put the package somewhere web accessible.

1. In the packages directory of your RocketSled install, create a file called:

```
hello_world.class.php
```

2. Add the following code to it:

```php
<?php
   class HelloWorld implements rocketsled\Runnable
   {
       public function run()
       {
           echo 'Hello World'.PHP_EOL;
       }
   }
```

3. Run it from the command line with:

```
php index.php HelloWorld
```

4. Run it from the web browser by simply pointing a browser to your web root as follows:

```
http://localhost/?r=HelloWorld
```

## Architecture and configuration

RocketSled consists of a single file (index.php) and a packages directory where you
put your classes.

The default location for the packages directory is just a directory called packages
in the web root.

You can modify this by creating a file called packages.config.php which defines 
the constant PACKAGES_DIR using php.net/define and putting it in your webroot.

NB: it's important that you use php.net/define here so that PACKAGES_DIR is a 
globally namespaced constant, eg:

```php
<?php define('PACKAGES_DIR','/usr/local/share/packages/');
```

Any class which implements the rocketsled\Runnable interface can be executed
from the command line:

```php
php index.php RunnableClass
```

or from the browser:

```
http://localhost/?r=RunnableClass
```

The default autoloader implementation will load any class anywhere in 
your packages directory. The class names are mapped where:

```
ClassName
```

is expected to be located in a file anywhere in your packages directory called:

```
class_name.class.php
```

The default autoloader implementation also supports namespaces. It expects the
namespace path to match the directory structure so the class:

```php
my\namespace\ClassName
```

is expected to be located in:

```
PACKAGES_DIR/my/namespace/class_name.class.php
```

Namespaced classes can also be executed from the command line:

```
php index.php 'my\namespace\ClassName'
```

or from the browser:

```
http://localhost/?r=my\namespace\ClassName
```

If you have some package specific configuration or a custom autoloader 
implementation you can include it in a file in your package called:

```
rs.config.php
```

It is a good idea to namespace your config files so as not to risk clashes in
constant or global variable names.

You should avoid using php.net/define in package specific config files As
constants defined using php.net/define are not namespaced.

Instead, use the const keyword to define constants so that they will be namespaced, eg:

```php
<?php
    namespace my_package;
    const SOME_CONFIG = 'something';
```

You can later access this constant with my_package\SOME_CONFIG.

For example if you wanted to use a package where all classes were defined in
a single file (ie. not supported by the default autoloader), you could include
the following in rs.config.php in your package directory:

```php
<?php
    namespace my_package;
    
    const SOME_CONSTANT = 'value';

    spl_autoload_register(function($class)
    {
        $my_classes = array('FirstClass','SecondClass','ThirdClass');
        
        if(in_array($class,$my_classes))
            require_once(PACKAGES_DIR.'/my_package/classes.php');
    });
```

This means that you can easily integrate packages or classes from any other framework or source, even if that package or class does provide a good autoloading implementation.

Lastly, in order to set which class will be executed by default, just add a file anywhere in your packages directory tree called:

```
runnable.default.php
```

and have it return the name of the class that should be executed by default, eg.:

```php
<?php return 'ClassName';
```

this can also work with namespaced classes:

```php
<?php return 'my\namespace\ClassName';
```

You can then execute that class from the command line:

```
php index.php
```

or the browser:

```
http://localhost/
```

## A word on namespaces

The default autoload implementation supports namespaces. In general I'm of the 
opinion that the way most frameworks have chosen to use namespaces is 
cumbersome (ie. that each an every class should be namespaced).

I personally think it's reasonable to not use namespaces for "top level" classes in a re-usable package, however if you create a class called "User" in a package, then it would be a good idea to add a namespace to it. Basically I think you should use namespaces where required but not as a rule for everything.

Requiring that you declare every single class before you use it at the top of your file basically sucks and ruins one of the most awesome things about autoloading. There is a good article on the subject here: http://propel.posterous.com/the-end-of-autoloading

Constants that you define in your config.php files, though, should always be namespaced
because there are fewer of them and its not such a cumbersome task to always prefix
constants with a namespace when using them. To namespace constants you should define
them using the const keyword, rather than php.net/define as described above.

-----------------------------------------------------------------------------------

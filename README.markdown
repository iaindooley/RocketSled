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

## Using and extending the default autoloader

You can also override the autoload, configs and runnable functions
by passing in closures:

```php
RocketSled::autoload(function()
{
    require('all_my_classes.php');
});
```

## Using and extending the default router

RocketSled::runnable(function()
{
    return MyDespatcher::runnable();
});

## Something more sophisticated

For a suggested setup using a cached autoloader and the RocketPack package management system,
setup to allow for easy deployment on multiple servers with shared packages, see the repo:

https://github.com/iaindooley/RocketSledBoilerPlate

# RocketSled - a front controller micro framework for PHP 5.3

## Goals:

* Provide a front controller and default autoload implementation

* Provide a way of managing packages and executing config files (eg. to add custom autoload implementations)

* Ability to use any class, code or package from any framework with the absolute minimum effort

* Ability to create and distribute packages that are independently useful and do not depend on RocketSled

* Ability to execute classes easily from the command line

* Do as little as possible, leaving everything else (eg. things like URLs, templating and database access) up to packages

## Hello World

* Create a file in your packages directory called:

    hello_world.class.php

2. Add the following code to it:

    <?php
       use rocketsled\Runnable;
    
       class HelloWorld implements Runnable
       {
           public function run()
           {
               echo 'Hello World'.PHP_EOL;
           }
       }

3. Run it from the command line with:

php index.php HelloWorld

4. Run it from the web browser by simply pointing a browser to your web root as follows:

    http://localhost/?r=HelloWorld

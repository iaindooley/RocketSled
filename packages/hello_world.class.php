<?php
   use rocketsled\Runnable;

   class HelloWorld implements Runnable
   {
       public function run()
       {
           echo 'Hello World'.PHP_EOL;
       }
   }

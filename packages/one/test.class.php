<?php
    use rocketsled\Runnable;

    class Test implements Runnable
    {
        public function doSomething()
        {
            echo 'one here'.PHP_EOL;
        }
        
        public function run()
        {
            $test = new SomeClass();
            $test->printSomething();
        }
    }

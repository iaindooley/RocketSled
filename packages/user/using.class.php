<?php
    //use two\Test;
    use rocketsled\Runnable;

    class Using implements Runnable
    {
        public function run()
        {
            $test = new Test();
            $test->doSomething();
            $one = new StumpyOne();
            $two = new StumpyTwo();
            $three = new StumpyThree();
            echo get_class($one).PHP_EOL;
            echo get_class($two).PHP_EOL;
            echo get_class($three).PHP_EOL;
        }
    }
    

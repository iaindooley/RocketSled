<?php
    namespace two;
    use rocketsled\Runnable;

    class Test implements Runnable
    {
        public function run()
        {
            echo $this->doSomething();
        }

        public function doSomething()
        {
            echo 'two here'.PHP_EOL;
        }
    }

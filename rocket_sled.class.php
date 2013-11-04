<?php
    require_once('runnable.interface.php');

    class RocketSled
    {
        private static $scan             = NULL;
        private static $instance         = NULL;
        private static $runnable         = NULL;
        private static $autoload         = NULL;
        private static $packages         = NULL;

        private function __construct()
        {
            self::$runnable  = self::defaultRunnable();
            self::$autoload  = self::defaultAutoload();
            self::$scan      = array(__DIR__.'/../');
            spl_autoload_register(self::$autoload);
        }
        
        public static function run()
        {
            self::instance();
            $runnable = self::$runnable;
            $runnable_object = $runnable();
            $runnable_object->run();
        }
        
        public static function defaultRunnable()
        {
            return function()
            {
                global $argv;
                /**
                * Get the class to run whether we're on the command line or in
                * the browser
                */
                if(isset($argv))
                    $runnable_class = isset($argv[1]) ? $argv[1]:require_once('runnable.default.php');
                else
                    $runnable_class = isset($_GET['r']) ? $_GET['r']:require_once('runnable.default.php');
            
                //Make sure no-one's trying to haxor us by running a class that's not runnable
                $refl = new ReflectionClass($runnable_class);
                
                if(!$refl->implementsInterface('RocketSled\\Runnable'))
                    die('Running a class that does not implement interface Runnable is not allowed');
            
                $runnable = new $runnable_class();
                return $runnable;
            };
        }

        public static function defaultAutoload()
        {
            return function($class)
            {
                $ret = FALSE;
                $namespaced = explode('\\',$class);
                
                if(count($namespaced) > 1)
                {
                    $class_part = strtolower(preg_replace('/^_/','',preg_replace('/([A-Z])/','_\1',array_pop($namespaced)))).'.class.php';
                    
                    foreach(RocketSled::scan() as $dir)
                    {
                        $fname = $dir.'/'.implode('/',$namespaced).'/'.$class_part;
                            
                        if(file_exists($fname))
                        {
                            require_once($fname);
                            $ret = $fname;
                        }
                    }
                }
                
                else
                {
                    $classes = RocketSled::filteredPackages(function($fname) use ($class,&$ret)
                    {
                        $ending = '.class.php';
            
                        if(RocketSled::endsWith($fname,$ending))
                        {
                            if(str_replace(' ','',ucwords(str_replace('_',' ',str_replace($ending,'',basename($fname))))) === $class)
                            {
                                require_once($fname);
                                $ret = $fname;
                            }
                        }
                    });
                }

                return $ret;
            };
        }

        public static function instance()
        {
            if(self::$instance === NULL)
                self::$instance = new RocketSled();
            
            return self::$instance;
        }

        /**
        * Pass in an array of directories to scan
        */
        public static function scan($dirs = NULL)
        {
            self::instance();
            if($dirs !== NULL)
            {
                self::$scan = array_filter(array_unique($dirs),function($path)
                {
                    return realpath($path);
                });
            }

            else
                return self::$scan;
        }

        public static function autoload(Closure $autoload = NULL)
        {
            self::instance();
            
            if($autoload !== NULL)
            {
                if(self::$autoload !== NULL)
                    spl_autoload_unregister(self::$autoload);

                self::$autoload = $autoload;
                spl_autoload_register(self::$autoload);
            }

            else
                return self::$autoload;
        }

        public static function runnable(Closure $runnable = NULL)
        {
            self::instance();
            
            if($runnable !== NULL)
                self::$runnable = $runnable;
            else
                return self::$runnable;
        }
            
        public static function filteredPackages($callback)
        {
            return array_filter(self::packages(),$callback);
        }
    
        private static function packages()
        {
            if(self::$packages === NULL)
            {
                self::$packages = array();
                foreach(self::$scan as $dir)
                {
                    $list = self::directoryList($dir);
                    
                    if(is_array($list) && count($list))
                        self::$packages = array_merge(self::$packages,$list);
                }
            }

            return self::$packages;
        }
    
        /**
        * Courtesy of donovan dot pp at gmail dot com on http://au2.php.net/scandir
        */
        private static function directoryList($dir)
        {
           $path = '';
           $stack[] = $dir;
           
           while ($stack)
           {
               $thisdir = array_pop($stack);
               
               if($dircont = scandir($thisdir))
               {
                   $i=0;
                   
                   while(isset($dircont[$i]))
                   {
                       if($dircont[$i] !== '.' && $dircont[$i] !== '..')
                       {
                           $current_file = "{$thisdir}/{$dircont[$i]}";
                           
                           if (is_file($current_file))
                               $path[] = "{$thisdir}/{$dircont[$i]}";
                           else if(is_dir($current_file))
                           {
                               $path[] = "{$thisdir}/{$dircont[$i]}";
                               $stack[] = $current_file;
                           }
                       }
                       
                       $i++;
                   }
               }
           }
           
           return $path;
        }
        
        public static function endsWith($str,$test)
        {
            return (substr($str, -strlen($test)) == $test);
        }
    }

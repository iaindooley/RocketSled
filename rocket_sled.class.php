<?php
    require_once('runnable.interface.php');

    class RocketSled
    {
        private static $rs_dir           = NULL;
        private static $userland_dir     = NULL;
        private static $lib_dir          = NULL;
        private static $root_dir         = NULL;
        private static $instance         = NULL;
        private static $runnable         = NULL;
        private static $autoload         = NULL;
        private static $configs          = NULL;
        private static $packages         = NULL;

        private function __construct()
        {
            self::$runnable     = self::defaultRunnable();
            self::$autoload     = self::defaultAutoload();
            spl_autoload_register(self::$autoload);
            self::$configs      = self::defaultConfigs();
            self::$rs_dir       = realpath(__DIR__.'/..');
            self::$lib_dir      = self::$rs_dir;
            self::$userland_dir = self::getParentDirectoryOfFileThatIncludedMe();
            self::$root_dir     = self::getDirectoryOfFileThatIncludedMe();
        }
        
        public static function run()
        {
            self::instance();
            $configs  = self::$configs;
            $runnable = self::$runnable;
            $configs();
            $runnable_object = $runnable();
            $runnable_object->run();
        }
        
        /**
        * Courtesy of danorton from stackoverflow: http://stackoverflow.com/questions/1318608/php-get-parent-script-name
        */
        private static function getParentDirectoryOfFileThatIncludedMe()
        {
            return realpath(dirname(self::getDirectoryOfFileThatIncludedMe()));
        }

        private static function getDirectoryOfFileThatIncludedMe()
        {
            $backtrace = debug_backtrace(defined("DEBUG_BACKTRACE_IGNORE_ARGS") ? DEBUG_BACKTRACE_IGNORE_ARGS : FALSE);
            $top_frame = array_pop($backtrace);
            return dirname($top_frame['file']);
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
                $namespaced = explode('\\',$class);
                
                if(count($namespaced) > 1)
                {
                    $class_part = strtolower(preg_replace('/^_/','',preg_replace('/([A-Z])/','_\1',array_pop($namespaced)))).'.class.php';
                    $fname_rs       = RocketSled::$rs_dir.'/'.implode('/',$namespaced).'/'.$class_part;
                    $fname_userland = RocketSled::$userland_dir.'/'.implode('/',$namespaced).'/'.$class_part;
                    
                    if(file_exists($fname_rs))
                        require_once($fname_rs);
                    else if(file_exists($fname_userland))
                        require_once($fname_userland);
                }
                
                else
                {
                    $classes = RocketSled::filteredPackages(function($fname) use ($class)
                    {
                        $ending = '.class.php';
            
                        if(RocketSled::endsWith($fname,$ending))
                        {
                            if(str_replace(' ','',ucwords(str_replace('_',' ',str_replace($ending,'',basename($fname))))) === $class)
                                require_once($fname);
                        }
                    });
                }
            };
        }

        public static function defaultConfigs()
        {
            return function()
            {
                $ret = array();
                
                /**
                * Recursively scan the package tree for files called config.php
                * with package configuration or custom autoload implementations
                */
                RocketSled::filteredPackages(function($fname) use(&$ret)
                {
                    if(basename($fname) == 'rs.config.php')
                    {
                        require_once($fname);
                        $ret[] = $fname;
                    }
                });
                
                return $ret;
            };
        }
        
        public static function instance()
        {
            if(self::$instance === NULL)
                self::$instance = new RocketSled();
            
            return self::$instance;
        }

        public static function __callStatic($name,$args)
        {
            //Make sure we're initialised
            //directories and instance
            self::instance();
            
            switch($name)
            {
                case 'rs_dir':
                    $ret = self::setRsDir($args);
                break;
                
                case 'lib_dir':
                    $ret = self::setLibDir($args);
                break;

                case 'userland_dir':
                    $ret = self::setUserlandDir($args);
                break;

                case 'root_dir':
                    $ret = self::setRootDir($args);
                break;

                case 'autoload':
                    $ret = self::setAutoload($args);
                break;

                case 'runnable':
                    $ret = self::setRunnable($args);
                break;

                case 'configs':
                    $ret = self::setConfigs($args);
                break;
                
                default:
                    throw new Exception('Tried to call non-existent static method: RocketSled::'.$name);
                break;
            }
            
            return $ret;
        }
        
        private static function setRsDir($args)
        {
            if(count($args))
                self::$rs_dir = realpath($args[0]);
            else
                return self::$rs_dir;
        }

        private static function setLibDir($args)
        {
            if(count($args))
                self::$lib_dir = realpath($args[0]);
            else
                return self::$lib_dir;
        }

        private static function setUserlandDir($args)
        {
            if(count($args))
                self::$userland_dir = realpath($args[0]);
            else
                return self::$userland_dir;
        }

        private static function setRootDir($args)
        {
            if(count($args))
                self::$root_dir = realpath($args[0]);
            else
                return self::$root_dir;
        }

        private static function setAutoload($args)
        {
            if(count($args))
            {
                if(self::$autoload !== NULL)
                    spl_autoload_unregister(self::$autoload);

                self::$autoload = $args[0];
                spl_autoload_register(self::$autoload);
            }

            else
                return self::$autoload;
        }

        private static function setConfigs($args)
        {
            if(count($args))
                self::$configs = $args[0];
            else
                return self::$configs;
        }

        private static function setRunnable($args)
        {
            if(count($args))
                self::$runnable = $args[0];
            else
                return self::$runnable;
        }

        public static function filteredPackages($callback)
        {
            return array_filter(self::packages(),$callback);
        }
    
        public static function packages()
        {
            if(self::$packages === NULL)
            {
                self::$packages = self::directoryList(self::$rs_dir);
                
                if(self::$rs_dir !== self::$userland_dir)
                    self::$packages = array_merge(self::$packages,self::directoryList(self::$userland_dir));
            }

            return self::$packages;
        }
    
        /**
        * Courtesy of donovan dot pp at gmail dot com on http://au2.php.net/scandir
        */
        public static function directoryList($dir)
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

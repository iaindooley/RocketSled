<?php

RocketSled knows about:

 rs_dir
 lib_dir
 userland_dir

The default autoload and config functions will scan these
for classes and package specific config files.

Other packages may also use these, for example Murphy
or RocketPack to scan for config files.

You can override lib dir or userland dir:

RocketSled::lib_dir('/usr/share/php/');
//USEFUL IF YOU'RE INCLUDING RS FROM A SUB DIR
RocketSled::userland_dir('.');


the simplest rocketsled application just does:

require('../RocketSled/rocket_sled.class.php');
RocketSled::run();

You can also override the autoload, configs and runnable functions
by passing in closures:

RocketSled::autoload(function()
{
    require('all_my_classes.php');
});

RocketSled::configs(function()
{
    require('all_my_configs.php');
});

RocketSled::runnable(function()
{
    return MyDespatcher::runnable();
});

For example if you wanted to create a package
to do pretty URL routing, called MyUrlRouter,
or if you wanted to use Symfony's routing 
framework, you could put this in your
bootstrap.php file:

$hosts = array(
    'host1' => 'local',
    'host1' => 'live',
);

$rs_dirs = array(
    'local' => '..',
);

$lib_dirs = array(
    'local' => '..',
);

require($rs_dirs[$hosts[gethostname()]].'/RocketSled/rocket_sled.class.php');
//IN ORDER TO ENSURE THAT THE STATIC MEMBER VARS ARE SETUP 
//PRIOR TO THIS METHOD EXECUTING WE CAN USE __callStatic
//FOR ALL OF THESE TO DISPATCH THE ACTUAL METHOD CALLS
RocketSled::runnable(function()
{
    $default_runnable = RocketSled::$default_runnable;
    if(!$ret = MyUrlRouter::runnable())
        $ret = $default_runnable();
    
    return $ret;
});

---

NOW RocketPack ... by default, packages are installed
in the same directory as the rocketpack.config.php file
appears.

However, you can override this behaviour by passing
in a directory to place the packages into the verify()
method eg.

    RocketPack\Dependencies::register(function()
    {
        //INSTALL ROCKETSLED DEPENDENCIES IN THE SAME DIR AS RS ITSELF
        RocketPack\Dependency::forPackage('https://github.com/iaindooley/RocketPack')
        ->add('https://github.com/iaindooley/Args',array(0,1,0))
        ->add('https://github.com/iaindooley/Murphy',array(0,1,1))
        ->verify(RocketSled::$rs_dir);

        //INSTALL EXTERNAL PACKAGES IN THE LIB DIR
        RocketPack\Dependency::forPackage('https://github.com/iaindooley/RocketPack')
        ->add('https://github.com/zend/ZDF','v1.1')
        ->verify(RocketSled::$lib_dir);
    });

NOW .. you can pass in a call back to the RocketPack add() method which will
require a package specific autoload implementation or bootstrap file.
RocketPack will pass in the directory path into which the package was installed
(which will either be the parent directory in which the package itself was
installed, or the directory you pass into the verify() method):

        RocketPack\Dependency::forPackage('https://github.com/iaindooley/RocketPack')
        ->add('https://github.com/zend/ZDF','v1.1',function($install_directory)
        {
            require_once($install_directory.'/boostrap.php');
        })
        ->verify(RocketSled::$lib_dir);

If you have passed a directory into the verify() method, and a version string,
then the version string will be appended to the name of the directory that 
the package installs as ONLY IF the directory passed in differs from 
RocketSled::$userland_dir

This will be included in the $install_directory method passed into the callback
function provided as the third argument.

RocketPack\Dependency::forPackage('https://github.com/iaindooley/RocketPack')
->add('https://github.com/zend/ZDF','v1.1')
->verify(RocketSled::$lib_dir);

OF COURSE THIS WON'T ACTUALLY WORK UNLESS YOU EXECUTE ROCKETPACK ON EVERY
SCRIPT EXECUTION WHICH WE DON'T WANT TO DO ... HMM BACK TO THE DRAWING
BOARD

OR ACTUALLY MAYBE RATHER THAN PASSING A CLOSURE AS THE THIRD ARGUMENT TO
RocketPack ADD YOU JUST PASS THE PATH OF THE BOOTSTRAP FILE FOR THAT PACKAGE
AND THEN THESE ARE SAVED IN A CONFIGURATION FILE WHICH IS PARSED BY
RocketPack if it exists. If it doesn't exist, RocketPack will execute,
so you can either run RocketPack by just running your application and
using the RocketPack config operation or by running it explicitly

For Murphy, it will need to scan rs_dir and userland_dir for murphy tests
and the exclude/include need to be changed to work with this method.

Something like Plusql can be installed either in a lib dir or a common 
dir, indeed the goal of most Rocketsled packages is to be externally
useful and not depend on RocketSled (save for an rs.config.php OR
rocketpack.config.php file).


    /**
    * RocketSled is the microest framework. It's only goal
    * is to simplify the organisation and execution of
    * classes from a web browser or the command line.
    */

    class RocketSled
    {
        public static $default_runnable;
        private $runnable;
        
        public static $default_configs;
        private $configs;
        
        /**
        * Default auto loader implementation, expects:
        *
        * ClassName
        *
        * should map to:
        *
        * class_name.php
        *
        * Also supports namespaces and expects that:
        *
        * My\NameSpace\ClassName
        *
        * is going to be located at:
        *
        * My/NameSpace/class_name.class.php
        *
        * this means that package *directories* are served as they are namespaced
        * but that ClassName maps to a file called class_name.class.php
        */
        public static $default_autoload;
        private $autoload;
        private $locations;
        
        public function __construct()
        {
            $this->setLocations();
            self::$default_autoload = function($class)
            {
                $namespaced = explode('\\',$class);
                
                if(count($namespaced) > 1)
                {
                    $class_part = strtolower(preg_replace('/^_/','',preg_replace('/([A-Z])/','_\1',array_pop($namespaced)))).'.class.php';
                    $fname = PACKAGES_DIR.'/'.implode('/',$namespaced).'/'.$class_part;
                    
                    if(file_exists($fname))
                        require_once($fname);
                }
                
                else
                {
                    $classes = filteredPackages(function($fname) use ($class)
                    {
                        $ending = '.class.php';
            
                        if(endsWith($fname,$ending))
                        {
                            if(str_replace(' ','',ucwords(str_replace('_',' ',str_replace($ending,'',basename($fname))))) === $class)
                                require_once($fname);
                        }
                    });
                }
            }
            
            $this->autoload = self::$default_autoload;
        }
        
        /**
        * RocketSled is aware of 3 locations by default:
        *
        * - Userland: where the code for the application you're creating is
        * - RsCommon: where RocketSled and related packages are
        * - Libs: where some external packages are
        *
        * In the simplest case, where you have all your packages in a 
        * single directory and just do:
        *
        * require('../RocketSled/rocket_sled.class.php');
        *
        * Then RocketSled will determine that all three directories
        * are the same (which is the purpose of this function).
        *
        * If you have RocketSled located in some shared location
        * and do:
        *
        * require('/path/to/rs-common/RocketSled/rocket_sled.class.php');
        * 
        * Then RocketSled will add both the parent directory of the file
        * from which you included it (using debug_backtrace()) as well
        * as the parent directory of RocketSled itself, calling them
        * userland and rs-common respectively.
        * 
        * If you then use the RocketSled::libs('/path/to/libs') method
        * RocketSled will have a third location, called libs, which can 
        * be useful when installing stuff that has it's own autoload
        * implementation that needs to be included, for example something
        * like SwiftMailer or Zend Framework.
        *
        * By using these locations, you ensure that it's simple to deploy
        * your RocketSled application simply and locally, as well as 
        * on a live server with a shared codebase without too many changes.
        */
        private function setLocations()
    
    /**
    * Recursively scan the package tree for files called config.php
    * with package configuration or custom autoload implementations
    */
    filteredPackages(function($fname)
    {
        if(basename($fname) == 'rs.config.php')
            require_once($fname);
    });
    
    /**
    * Get the class to run whether we're on the command line or in
    * the browser
    */
    if(isset($argv))
        $runnable_class = isset($argv[1]) ? $argv[1]:defaultRunnable();
    else
        $runnable_class = isset($_GET['r']) ? $_GET['r']:defaultRunnable();

    //Make sure no-one's trying to haxor us by running a class that's not runnable
    $refl = new ReflectionClass($runnable_class);
    
    if(!$refl->implementsInterface('rocketsled\\Runnable'))
        die('Running a class that does not implement interface Runnable is not allowed');

    //Run that shit!
    $runnable = new $runnable_class();
    $runnable->run();
    
    //Some functions
    function endsWith($str,$test)
    {
        return (substr($str, -strlen($test)) == $test);
    }

    function defaultRunnable()
    {
        $runnable = filteredPackages(function($input)
        {
            return endsWith($input,'runnable.default.php');
        });

        if(!count($runnable))
            die('No default runnable. Try adding a file somewhere in your package tree called runnable.default.php or setting runnable in the query string, or calling this script on the command line where the first argument is a class name to run');
        
        if(count($runnable) > 1)
            die('More than one default runnable found: '.implode(' ; ',$runnable));
            
        return require_once(current($runnable));
    }

    $packages = NULL;

    function filteredPackages($callback)
    {
        return array_filter(packages(),$callback);
    }

    function packages()
    {
        global $packages;
        
        if($packages === NULL)
            $packages = directoryList(PACKAGES_DIR);

        return $packages;
    }

    /**
    * Courtesy of donovan dot pp at gmail dot com on http://au2.php.net/scandir
    */
    function directoryList($dir)
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

    interface Runnable
    {
        public function run();
    }

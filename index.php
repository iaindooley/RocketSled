<?php
    namespace rocketsled;
    use ReflectionClass;
    use Exception;

    //allow implementor to override the packages dir
    if(file_exists('packages.config.php'))
        require_once('packages.config.php');
    else
        define('PACKAGES_DIR','packages');

    /**
    * Default auto loader implementation, expects:
    *
    * ClassName
    *
    * should map to:
    *
    * class_name.php
    *
    * Recursively scans PACKAGES_DIR to find classes. Also 
    * supports namespaces and expects that:
    *
    * my\namespace\ClassName
    *
    * is going to be located at:
    *
    * PACKAGES_DIR/my/namespace/class_name.class.php
    */
    spl_autoload_register(function($class)
    {
        $namespaced = explode('\\',$class);
        
        if(count($namespaced) > 1)
        {
            $class_part = strtolower(preg_replace('/^_/','',preg_replace('/([A-Z])/','_\1',array_pop($namespaced)))).'.class.php';
            $fname = 'packages/'.implode('/',$namespaced).'/'.$class_part;
            
            if(!file_exists($fname))
                die('The default auto loader expects: '.$class.' to map to: '.$fname.'. You should register your own autoloader if your package is setup differently');

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
    });
    
    /**
    * Recursively scan the package tree for files called autoload.php
    * with custom autoload implementations, or files called config.php
    * with some package configuration directives
    */
    filteredPackages(function($fname)
    {
        if((basename($fname) == 'autoload.php')||
           (basename($fname) == 'config.php'))
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

    //Some classes
    interface Runnable
    {
        public function run();
    }

    class AutoloadSuccessful extends Exception {}

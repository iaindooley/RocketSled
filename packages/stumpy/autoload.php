<?php
    spl_autoload_register(function($class)
    {
        require_once('packages/stumpy/all.php');
    });

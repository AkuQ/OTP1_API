<?php

require_once __DIR__ . '/functions.php';

date_default_timezone_set('Europe/Helsinki');

spl_autoload_register(function($name) {
    if(substr($name,0,9) == 'StormChat') {
        $name = substr($name, 10);
        $file = str_replace('\\', '/', __DIR__."/$name.php" );
        if (file_exists($file)) /** @noinspection PhpIncludeInspection */
            include $file;
    }
}, true);
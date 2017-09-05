<?php


date_default_timezone_set('Europe/Helsinki');

spl_autoload_register(function($name) {
    if(substr($name,0,9) == 'StormChat') {
        $file = str_replace('\\', '/', __DIR__."/$name.php" );
        if (file_exists($file)) /** @noinspection PhpIncludeInspection */
            include $file;
    }
}, true);
<?php

/*
 * regist autoloader
 */
spl_autoload_register(function ($class)
{
    if ($class && !class_exists($class))
    {
        $file = str_replace('\\', '/', $class);
        $file .= '.php';

        if (file_exists($file))
        {
            include $file;
        }
        else
        {
            $file = __APP_DIR__ . '/' . $file;
            if (file_exists($file))
            {
                include $file;
            }
        }
    }
});

<?php

/*
 * Gogal config
 */
error_reporting(11);
defined('__HAMMER_DIR__') or define('__HAMMER_DIR__', __DIR__);
defined('__APP_DIR__') or define('__APP_DIR__', __DIR__ . '/app');

require 'autoloader.php';
require 'vendor/autoload.php';
/*
 * process manger
 */
$console = new \hammer\cli\CliApp($argv);


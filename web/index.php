<?php

require_once __DIR__.'/../app/bootstrap.php';

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

ErrorHandler::register();
ExceptionHandler::register((('APPLICATION_ENV' !== 'production')? true : false));

// Define application environment
defined('APPLICATION_ENV') ||
define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

if (APPLICATION_ENV === 'production') { ini_set('display_errors', 0); }
else { ini_set('display_errors', 1); }

$app = require __DIR__.'/../app/app.php';

require __DIR__.'/../app/controller.php';
$app->run();

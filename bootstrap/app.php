<?php

use Monolog\Logger;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
	realpath(__DIR__ . '/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
	Illuminate\Contracts\Http\Kernel::class,
	App\Http\Kernel::class
);

$app->singleton(
	Illuminate\Contracts\Console\Kernel::class,
	App\Console\Kernel::class
);

$app->singleton(
	Illuminate\Contracts\Debug\ExceptionHandler::class,
	App\Exceptions\Handler::class
);

$app->configureMonologUsing(function ($monolog) {
	$handler = new Monolog\Handler\RotatingFileHandler(storage_path() . '/logs/debug.log', 3, Logger::DEBUG, false);
	$monolog->pushHandler($handler->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true, true)));

	$handler = new Monolog\Handler\RotatingFileHandler(storage_path() . '/logs/info.log', 3, Logger::INFO, false);
	$monolog->pushHandler($handler->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true, true)));

	$handler = new Monolog\Handler\RotatingFileHandler(storage_path() . '/logs/error.log', 3, Logger::ERROR, false);
	$monolog->pushHandler($handler->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true, true)));
});

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;

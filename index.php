<?php

require __DIR__ . '/vendor/autoload.php';
//start session
session_start();
// Instantiate the settings
$settings = require __DIR__ . '/src/settings.php';
// Instantiate the app
$app = new \Slim\App($settings);
// Set up dependencies
require __DIR__ . '/src/dependencies.php';
// Register middleware
require __DIR__ . '/src/middleware.php';
// Register routes
require __DIR__ . '/src/routes.php';
// Run app
$app->run();

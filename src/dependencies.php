<?php

require 'Model/Mapp.php';
require 'Model/Salesforce.php';
require 'Model/Mapping.php';
require 'Model/Setting.php';
require 'Model/CepUser.php';

// Configuration for Slim Dependency Injection Container
$container = $app->getContainer();

// Csrf
$container['csrf'] = function ($c) {
    return new \Slim\Csrf\Guard();
};

// Monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], Monolog\Logger::DEBUG));
    return $logger;
};

// Eloquent Database
$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container->get('settings')['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Mapp Platform Executor
$container['mappCep'] = function ($c) {
  return new \Mapp\CustomerEngagementPlatformApi(); //
};

// Mapp Platform Contact
$container['mappContact'] = function ($c) {
    return new \Mapp\Contact();
};

// Salesforce Executor
$container['Sforce'] = function ($c) {
	$client = new SforcePartnerClient();
	$wsdl = $c->get('settings')['sfdc']['wsdl'];
    $sforce = new SforceExecutor($client,$wsdl);
    return $sforce;
};

// Salesforce Client
$container['SforceClient'] = function ($c) {
    return new SforcePartnerClient();
};

// Twig
$container['view'] = function ($c) {
	//tell slim where the templates are
    $view = new \Slim\Views\Twig(__DIR__.'/../templates', [
        'cache' => false,
		'auto_reload' => true
    ]);
	//adapt the base url
    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));
	//pass it to the view as a variable
    $view['base_url'] = $c['request']->getUri()->getBaseUrl();

    return $view;
};

// Mapping Controller
$container['MappIntegrator\Controller\AdminController'] = function ($c) {
    return new MappIntegrator\Controller\AdminController($c);
};

// Settings Controller
$container['MappIntegrator\Controller\SettingController'] = function ($c) {
    return new MappIntegrator\Controller\SettingController($c);
};

// API Controller
$container['MappIntegrator\Controller\ApiController'] = function ($c) {
    return new MappIntegrator\Controller\ApiController($c);
};

<?php

//login and logout routes
$app->get('/', 'MappIntegrator\Controller\AdminController:goLogin');
$app->get('/login', 'MappIntegrator\Controller\AdminController:showLogin')->setName('showLogin')->add($container->get('csrf'));
$app->post('/login', 'MappIntegrator\Controller\AdminController:login')->setName('login')->add($container->get('csrf'));
$app->get('/logout', 'MappIntegrator\Controller\AdminController:logout')->setName('logout')->add($container->get('csrf'));

//route admin group requests and validate by session
$app->group('/admin', function() {

	///////////////////////////////////////////////////////////
	// HOME
	///////////////////////////////////////////////////////////
	$this->get('/', 'MappIntegrator\Controller\AdminController:home')->setName('home');

	///////////////////////////////////////////////////////////
	// GETTING STARTED
	///////////////////////////////////////////////////////////

	$this->get('/getting-started', 'MappIntegrator\Controller\AdminController:gettingStarted');

	///////////////////////////////////////////////////////////
	// SETTINGS
	///////////////////////////////////////////////////////////

	$this->get('/settings', 'MappIntegrator\Controller\SettingController:get')->setName('getSetting');
	$this->post('/settings/create', 'MappIntegrator\Controller\SettingController:create')->setName('createSetting');
	$this->get('/settings/connection', 'MappIntegrator\Controller\SettingController:testConnection')->setName('testConnection');
	$this->get('/settings/oauth', 'MappIntegrator\Controller\SettingController:oauth')->setName('oauth');
	$this->get('/settings/authorize', 'MappIntegrator\Controller\SettingController:authorizeApp')->setName('authorize');
	
	///////////////////////////////////////////////////////////
	// MAPPING
	///////////////////////////////////////////////////////////

	$this->get('/mapping', 'MappIntegrator\Controller\AdminController:getMapping')->setName('getMapping');
	$this->get('/mapping/map', 'MappIntegrator\Controller\AdminController:createJsonMap')->setName('createJsonMap');
	$this->post('/mapping/create', 'MappIntegrator\Controller\AdminController:createMapping')->setName('createMapping');


})->add( new SessionAuthenticator($container))->add($container->get('csrf'));


///////////////////////////////////////////////////////////
// API
///////////////////////////////////////////////////////////

//route api requests to the api group and validate by ApiAuthenticator
$app->group('/api', function() {
	
	//root node
	$this->get('', 'MappIntegrator\Controller\ApiController:root');
	$this->get('/', 'MappIntegrator\Controller\ApiController:root');
	//mapping
	$this->get('/mapping', 'MappIntegrator\Controller\ApiController:mappingGetAll');
	///////////////////////////////////////////////////////////
	// CEP GROUP
	///////////////////////////////////////////////////////////
	$this->group('/cep', function() {
		$this->get('', 'MappIntegrator\Controller\ApiController:cepRoot');
		$this->get('/', 'MappIntegrator\Controller\ApiController:cepRoot');
		$this->get('/contact/get','MappIntegrator\Controller\ApiController:cepGetContact');
		$this->post('/contact/upsert','MappIntegrator\Controller\ApiController:cepUpsertContact');
	});
	
	///////////////////////////////////////////////////////////
	// SFDC GROUP
	///////////////////////////////////////////////////////////
	$this->group('/sfdc', function() {
		//identity
		$this->get('/id', 'MappIntegrator\Controller\ApiController:sfdcIdentity');
		//api server
		$this->get('/server', 'MappIntegrator\Controller\ApiController:sfdcApiEndpoint');
		//describe the current user
		$this->get('/user', 'MappIntegrator\Controller\ApiController:sfdcUser');
		//run a query
		$this->get('/query', 'MappIntegrator\Controller\ApiController:sfdcQuery');
		//run a search
		$this->get('/search', 'MappIntegrator\Controller\ApiController:sfdcSearch');
		//transfer an object using a query
		$this->post('/{object}/transfer', 'MappIntegrator\Controller\ApiController:sfdcTransferQuery');
		//describe the defined object fields
		$this->get('/{object}/fields', 'MappIntegrator\Controller\ApiController:sfdcObjectFields');
		//map
		$this->post('/{object}/map', 'MappIntegrator\Controller\ApiController:sfdcMap');
		//upsert generic
		$this->post('/upsert', 'MappIntegrator\Controller\ApiController:sfdcUpsert');
		//generic upsert by method
		$this->post('/{object}/upsertby', 'MappIntegrator\Controller\ApiController:sfdcUpsertBy');
		//create a defined object
		$this->post('/{object}/create', 'MappIntegrator\Controller\ApiController:sfdcCreate');
	});

})->add( new MappApiAuthenticator($container));
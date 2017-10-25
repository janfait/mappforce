<?php

//login and logout routes
$app->get('/', 'MappIntegrator\Controller\AdminController:goLogin');
$app->get('/login', 'MappIntegrator\Controller\AdminController:showLogin')->setName('showLogin');
$app->post('/login', 'MappIntegrator\Controller\AdminController:login')->setName('login');
$app->get('/logout', 'MappIntegrator\Controller\AdminController:logout')->setName('logout');

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
	$this->post('/settings/delete', 'MappIntegrator\Controller\SettingController:delete')->setName('deleteSetting');
	$this->get('/settings/connection', 'MappIntegrator\Controller\SettingController:testConnection')->setName('testConnection');
	$this->get('/settings/oauth', 'MappIntegrator\Controller\SettingController:oauth')->setName('oauth');
	$this->get('/settings/authorize', 'MappIntegrator\Controller\SettingController:authorize')->setName('authorize');
	
	///////////////////////////////////////////////////////////
	// MAPPING
	///////////////////////////////////////////////////////////

	$this->get('/mapping', 'MappIntegrator\Controller\AdminController:getMapping')->setName('getMapping');
	$this->get('/mapping/map', 'MappIntegrator\Controller\AdminController:createJsonMap')->setName('createJsonMap');
	$this->post('/mapping/create', 'MappIntegrator\Controller\AdminController:createMapping')->setName('createMapping');
	$this->post('/mapping/delete', 'MappIntegrator\Controller\AdminController:deleteMapping')->setName('deleteMapping');


})->add( new SessionAuthenticator($container));


///////////////////////////////////////////////////////////
// API
///////////////////////////////////////////////////////////

//route api requests to the api group and validate by ApiAuthenticator
$app->group('/api', function() {
	
	//status check
	$this->get('/status', 'MappIntegrator\Controller\ApiController:status');
	//root node
	$this->get('', 'MappIntegrator\Controller\ApiController:root');
	$this->get('/', 'MappIntegrator\Controller\ApiController:root');
	//mapping
	$this->get('/mapping', 'MappIntegrator\Controller\ApiController:mappingGetAll');
	//settings
	$this->get('/settings', 'MappIntegrator\Controller\ApiController:settingGetAll');
	
	///////////////////////////////////////////////////////////
	// CEP GROUP
	///////////////////////////////////////////////////////////
	$this->group('/cep', function() {
		$this->get('', 'MappIntegrator\Controller\ApiController:cepRoot');
		$this->get('/', 'MappIntegrator\Controller\ApiController:cepRoot');
	});
	
	///////////////////////////////////////////////////////////
	// SFDC GROUP
	///////////////////////////////////////////////////////////
	$this->group('/sfdc', function() {
		//identity
		$this->get('/id', 'MappIntegrator\Controller\ApiController:sfdcIdentity');
		//describe the current user
		$this->get('/user', 'MappIntegrator\Controller\ApiController:sfdcUser');
		//run a query
		$this->get('/query', 'MappIntegrator\Controller\ApiController:sfdcQuery');
		//run a query
		$this->get('/search', 'MappIntegrator\Controller\ApiController:sfdcSearch');
		//test
		$this->get('/test', 'MappIntegrator\Controller\ApiController:sfdcTest');
		//describe the defined object
		$this->get('/{object}/describe', 'MappIntegrator\Controller\ApiController:sfdcObject');
		//describe the defined object fields
		$this->get('/{object}/fields', 'MappIntegrator\Controller\ApiController:sfdcObjectFields');
		//map
		$this->post('/{object}/map', 'MappIntegrator\Controller\ApiController:sfdcMap');
		//upsert the defined object
		$this->post('/{object}/upsert', 'MappIntegrator\Controller\ApiController:sfdcUpsert');
		//create a defined object
		$this->post('/{object}/create', 'MappIntegrator\Controller\ApiController:sfdcCreate');
	});

})->add( new MappApiAuthenticator($container));
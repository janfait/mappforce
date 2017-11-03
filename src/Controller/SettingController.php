<?php

namespace MappIntegrator\Controller;

use \MappIntegrator\Mapping as Mapping;
use \MappIntegrator\Setting as Setting;
use Slim\Http\Request;
use Slim\Http\Response;
use Stevenmaguire\OAuth2\Client\Provider\Salesforce
as SalesforceOauth;

/**
 * Class SettingController
 * @package MappIntegrator\Controller
 */
class SettingController extends Controller
{
	
	public function testConnection(Request $request, Response $response, $args){
		//login
		$this->sfdc_login();
		//collect user info
		$sfdc_user = $this->sfdc_client->getUserInfo();
		//return
		return $response->withJson($sfdc_user);
	}

	public function authorizeApp(Request $request, Response $response, $args){
		//revert if oauth settings are not initialized
		$this->_sfdc_collect_settings();
		//check presence of authorization settings
		if(!$this->_sfdc_check_authorization_settings()){
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['success'=>0,'error_message'=>'missing_settings']));
		}
		//if oauth client has not been initialized, pass back
		if(is_null($this->sfdc_oauth_client)){
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['success'=>0,'error_message'=>'no_oauth_client']));
		}
		//define scope, has to be identical to the connected app scope
		$options = [
			'scope' => ['api','id','refresh_token']
		];
		//get auth url
		$authorization_url = $this->sfdc_oauth_client->getAuthorizationUrl();
		//redirect to authorization url
		return $response->withRedirect($authorization_url);
		
	}
	
	public function oauth(Request $request, Response $response, $args){
		//collect query parameters
		$query = $request->getQueryParams();
		//collect authorization code arriving from the authorization url
		$authorization_code = $query['code'];
		//collect the request state arriving from the authorization url
		$state = $query['state'];
		if(!isset($state)){
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['success'=>0,'error_message'=>'missing_state']));
		}
		//check state
		if(empty($authorization_code)){
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['success'=>0,'error_message'=>'missing_authorization_code']));
		}
		//pass it to the oauth token collector
		$oauth_token = $this->_sfdc_collect_oauth_token($authorization_code);
		if($outh_token['error']){
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['success'=>0,'error_message'=>'failed_oauth_request']));
		}
		//store the token elements
		$storage_result = $this->_sfdc_store_oauth_token($oauth_token);
		//go back to settings with message
		return $response->withRedirect($this->container->router->pathFor('getSetting',[],['success'=>$storage_result]));
	}
	

	public function get(Request $request, Response $response, $args){

		$status = $this->default_ui_status;
		//collect query
		$query = $request->getQueryParams();
	
		//collect settings
		$cep_settings = Setting::where([['realm','cep'],['editable',true]])->get();
		$sfdc_settings = Setting::where([['realm','sfdc'],['editable',true]])->get();
		$global_settings = Setting::where([['realm','global'],['editable',true]])->get();
		
		//collect settings indicating whether SFDC credentials have been supplied and app has been authorized
		$connected_app = Setting::where(['category','connected_app'])->get();
		$authorized = Setting::where('name','sfdc_refresh_token')->count();
		
		//loop through the settings and decrypt passwords
		foreach($sfdc_settings as $setting){
			if($setting->type=='password' && !empty($setting->value)){
				$setting->value = $this->_decrypt($setting->value,$this->settings['secret']);
			}
		}
		
		//collect success messages from oauth attempt
		if(isset($query['success'])){
			$status['error'] = boolval($query['success']);
			$status['message'] = $query['error_message'];
			$status['success'] = !$status['error'];
		}

		//render
		$body = $this->view->fetch('admin/pages/settings.twig', [
			'authorized'=> $authorized,
			'cep_settings'=> $cep_settings,
			'sfdc_settings'=> $sfdc_settings,
			'global_settings'=> $global_settings,
			'user' =>  $request->getAttribute('user'),
			'error' => $status['error'],
			'message'=>$status['message'],
			'success' => $status['success'],
			'debug' => $this->container->get('settings')['debug']
		]);
		
		return $response->write($body);

	}
	
	public function create(Request $request, Response $response, $args){
		
		$data = $request->getParsedBody();

		foreach($data as $item_key => $value){
			
			$setting = Setting::where('name',$item_key)->first();
			if($setting){
				if($setting->type=='password'){
					$setting->value = $this->_encrypt($value,$this->settings['secret']);
				}else{
					$setting->value = $value;
				}
				$setting->save();
			}
		}
		
		return $response->withRedirect($this->container->router->pathFor('getSetting'));

	}

	

	
}




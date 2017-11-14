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
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['error'=>true,'message'=>'MISSING_SETTINGS']));
		}
		//if oauth client has not been initialized, pass back
		if(is_null($this->sfdc_oauth_client)){
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['error'=>true,'message'=>'NO_OAUTH_CLIENT']));
		}
		//define scope, has to be identical to the connected app scope
		$options = [
			'scope' => ['api','id','refresh_token']
		];
		//get auth url
		$authorization_url = $this->sfdc_oauth_client->getAuthorizationUrl();
		//store the state in session
		$_SESSION['STATE'] = $this->sfdc_oauth_client->getState();
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
		//check valid state
		if(empty($state) || (isset($_SESSION['STATE']) && $state !== $_SESSION['STATE'])){
				//unset state for next attempt
			    if (isset($_SESSION['STATE'])) {
					unset($_SESSION['STATE']);
				}
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['error'=>true,'message'=>'INVALID_STATE']));
		}
		//check state
		if(empty($authorization_code)){
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['error'=>true,'message'=>'MISSING_AUTH_CODE']));;
		}
		//pass it to the oauth token collector
		$oauth_token = $this->_sfdc_collect_oauth_token($authorization_code);
		if($outh_token['error']){
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['error'=>true,'message'=>'FAILED_OAUTH_REQUEST']));
		}
		//store the token elements
		$storage_result = $this->_sfdc_store_oauth_token($oauth_token);
		//go back to settings with message
		return $response->withRedirect($this->container->router->pathFor('getSetting',[],['success'=>$storage_result,'message'=>'AUTHORIZATION_SUCCESS']));
	}
	

	public function get(Request $request, Response $response, $args){

		//csrf
		$csrf = array();
		$csrf['namekey'] = $this->container->csrf->getTokenNameKey();
		$csrf['valuekey'] = $this->container->csrf->getTokenValueKey();
		$csrf['name'] = $request->getAttribute($csrf['namekey']);
		$csrf['value'] = $request->getAttribute($csrf['valuekey']);
		//default outputs
		$status = $this->default_ui_status;
		//collect query
		$query = $request->getQueryParams();
		//collect settings
		$cep_settings = Setting::where([['realm','cep'],['editable',true]])->get();
		$sfdc_settings = Setting::where([['realm','sfdc'],['editable',true]])->get();
		
		//collect settings indicating whether SFDC credentials have been supplied and app has been authorized
		$connected_app = Setting::where(['category','connected_app'])->get();
		//collect refresh token to tell whether it was authorized
		$has_token = Setting::where('name','sfdc_refresh_token')->first()->value;
		$authorized = false;
		if(!empty($has_token)){
			$authorized = true;
		}
		
		//loop through the settings and decrypt passwords
		foreach($sfdc_settings as $setting){
			if($setting->type=='password' && !empty($setting->value)){
				$setting->value = $this->_decrypt($setting->value,$this->settings['secret']);
			}
		}
		//loop through the settings and decrypt passwords
		foreach($cep_settings as $setting){
			if($setting->type=='password' && !empty($setting->value)){
				$setting->value = $this->_decrypt($setting->value,$this->settings['secret']);
			}
		}
		
		//collect success messages from oauth attempt
		if(isset($query['error'])){
			$status['error'] = boolval($query['error']);
			$status['message'] = $this->messages[$query['message']];
			$status['success'] = !$status['error'];
		}
		
		//render
		$body = $this->view->fetch('admin/pages/settings.twig', [
			'csrf'=>$csrf,
			'connected_app'=>$connected_app,
			'authorized'=> $authorized,
			'cep_settings'=> $cep_settings,
			'sfdc_settings'=> $sfdc_settings,
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

		
		return $response->withJson($data);
		
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




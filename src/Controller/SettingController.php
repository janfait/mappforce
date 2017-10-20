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
		if(empty($this->sfdc_session)){
			$this->sfdc_login();
		}
		$sfdc_user = $this->sfdc_client->getUserInfo();
		return $response->withJson($sfdc_user);
	}

	public function authorize(Request $request, Response $response, $args){
		//revert if oauth settings are not initialized
		if(is_null($this->sfdc_oauth_client)){
			return $response->withRedirect($this->container->router->pathFor('getSetting',[],['success'=>false]));
		}
		//go to authorization url
		$authorization_url = $this->sfdc_oauth_client->getAuthorizationUrl();
		//redirect to authorization
		return $response->withRedirect($authorization_url);
		
	}
	
	public function oauth(Request $request, Response $response, $args){
		//collect query parameters
		$query = $request->getQueryParams();
		//collect authorization code arriving from the authorization_url
		$authorization_code = $query['code'];
		//collect the request state
		$state = $query['state'];
		//check state
		
		//pass it to the oauth token collector
		$oauth_token = $this->_sfdc_collect_oauth_token($authorization_code);
		//check if it has expired and refresh

		//save the setting
		if(isset($oauth_token['access_token'])){
			$value = $oauth_token['access_token'];
			$setting = Setting::where('name','sfdc_access_token')->first();
			if($setting){
				if($setting->type=='password'){
					$setting->value = $this->_encrypt($value,$this->settings['secret']);
				}else{
					$setting->value = $value;
				}
				$setting->save();
			}
		}
		if(isset($oauth_token['id'])){
			$value = $oauth_token['id'];
			$setting = Setting::where('name','sfdc_server_url')->first();
			if($setting){
				if($setting->type=='password'){
					$setting->value = $this->_encrypt($value,$this->settings['secret']);
				}else{
					$setting->value = $value;
				}
				$setting->save();
			}
		}
		if(isset($oauth_token['refresh_token'])){
			$value = $oauth_token['refresh_token'];
			$setting = Setting::where('name','sfdc_refresh_token')->first();
			if($setting){
				if($setting->type=='password'){
					$setting->value = $this->_encrypt($value,$this->settings['secret']);
				}else{
					$setting->value = $value;
				}
				$setting->save();
			}
		}
		
		//go back to settings
		return $response->withRedirect($this->container->router->pathFor('getSetting',[],['success'=>true]));
	}
	

	public function get(Request $request, Response $response, $args){

		//collect settings
		$cep_settings = Setting::where([['realm','cep'],['editable',true]])->get();
		$sfdc_settings = Setting::where([['realm','sfdc'],['editable',true]])->get();
		$global_settings = Setting::where([['realm','global'],['editable',true]])->get();
		
		//loop through the settings and decrypt passwords
		foreach($sfdc_settings as $setting){
			if($setting->type=='password' && !empty($setting->value)){
				$setting->value = $this->_decrypt($setting->value,$this->settings['secret']);
			}
		}
		
		$body = $this->view->fetch('admin/pages/settings.twig', [
			'cep_settings'=> $cep_settings,
			'sfdc_settings'=> $sfdc_settings,
			'global_settings'=> $global_settings,
			'user' =>  $request->getAttribute('user'),
			'error' => false,
			'error_message'=>'',
			'success' => false,
			'success_message'=>'',
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




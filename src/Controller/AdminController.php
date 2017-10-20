<?php

namespace MappIntegrator\Controller;

use \MappIntegrator\Mapping as Mapping;
use \MappIntegrator\Setting as Setting;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class AdminController
 * @package MappIntegrator\Controller
 */
class AdminController extends Controller
{
	
	public $mapp_user;

/////////////////////////////////////////////////////////////////////////////////////	
	public function logout(Request $request, Response $response, $args)
	{
		
		session_start();
		session_unset();
		session_destroy();

		$body = $this->view->fetch('admin/pages/login.twig', [
			'error' => false,
			'error_message'=>'',
			'success' => true,
			'success_message'=>'You have logged out',
			'debug' => $this->container->get('settings')['debug']
        ]);
		
		return $response->write($body)->withRedirect($this->container->router->pathFor('showLogin'));
		
	}	
	
/////////////////////////////////////////////////////////////////////////////////////	
	public function goLogin(Request $request, Response $response, $args)
	{
		return $response->withRedirect($this->container->router->pathFor('showLogin'));
	}
/////////////////////////////////////////////////////////////////////////////////////	
	public function showLogin(Request $request, Response $response, $args)
    {

		$body = $this->view->fetch('admin/pages/login.twig', [
			'error' => false,
			'debug' => $this->container->get('settings')['debug']
        ]);

        return $response->write($body);
    }
/////////////////////////////////////////////////////////////////////////////////////
	public function login(Request $request, Response $response, $args)
	{

		$data = $request->getParsedBody();
		$this->mapp_client->setInstance($data['sysname']);
		$this->mapp_client->setAuthentication($data['username'],$data['password']);
		$this->mapp_user = $this->mapp_client->getSystemUser();

		if(!isset($this->mapp_user) | true === $this->mapp_user['error']){
			
			$body = $this->view->fetch('admin/pages/login.twig', [
				'error' => true,
				'error_message'=>'Incorrect credentials. Please try again. Make sure your Mapp API user is active and this IP whitelisted',
				'success' => false,
				'success_message'=>'',
				'debug' => $this->container->get('settings')['debug']
			]);
			
			return $response->withStatus(401)->write($body);

		}else{
			
			$_SESSION['LOGGEDIN'] = true;
			$_SESSION['INST'] = $this->_encrypt($data['sysname'],$this->settings['secret']);
			$_SESSION['USER'] = $this->mapp_user['data'];
			$_SESSION['USERNAME'] = $this->_encrypt($data['username'],$this->settings['secret']);
			$_SESSION['PWD'] = $this->_encrypt($data['password'],$this->settings['secret']);

			return $response->withRedirect($this->container->router->pathFor('home'));
		
		}

	}
/////////////////////////////////////////////////////////////////////////////////////	
	public function home(Request $request, Response $response, $args)
    {

		$status = array(
			'error'=>false,
			'error_message'=>null,
			'success'=>false,
			'success_message'=>null
		);
		
		$tiles = array(
			'lead_mapping'=>0,
			'contact_mapping'=>0,
			'campaign_mapping'=>0,
			'settings'=>false,
		);
		//collect the number of stored mappings
		$lead_standard_mapping = Mapping::where(['sfdc_object'=>'lead','cep_attr_type'=>'standard'])->count();
		$lead_custom_mapping = Mapping::where(['sfdc_object'=>'lead','cep_attr_type'=>'custom'])->count();
		$contact_standard_mapping = Mapping::where(['sfdc_object'=>'contact','cep_attr_type'=>'standard'])->count();
		$contact_custom_mapping = Mapping::where(['sfdc_object'=>'contact','cep_attr_type'=>'custom'])->count();
		$campaign_mapping = Mapping::where('sfdc_object','campaign')->count();
		
		$settings = Setting::where('realm','sfdc')->count();
		
		$tiles = array(
			'lead'=>array('standard'=>$lead_standard_mapping,'custom'=>$lead_custom_mapping),
			'contact'=>array('standard'=>$contact_standard_mapping,'custom'=>$contact_custom_mapping),
			'campaign'=>$campaign_mapping,
			'settings'=>false,
		);
		
		
		//check number of settings
		if($settings==3){
			$tiles['settings'] = true;
		}
		

		$body = $this->view->fetch('admin/pages/homepage.twig', [
			'user' =>  $request->getAttribute('user'),
			'tiles' => $tiles,
			'debug' => $this->container->get('settings')['debug']
        ]);

        return $response->write($body);
    }
/////////////////////////////////////////////////////////////////////////////////////
	public function gettingStarted(Request $request, Response $response, $args)
	{

		$status = array(
			'error'=>false,
			'error_message'=>null,
			'success'=>false,
			'success_message'=>null
		);
	
		$body = $this->view->fetch('admin/pages/getting_started.twig', [
			'user' =>  $request->getAttribute('user'),
			'error' => false,
			'error_message'=>'',
			'success' => false,
			'success_message'=>'',
			'debug' => $this->container->get('settings')['debug']
		]);
		
		return $response->write($body);

	}
/////////////////////////////////////////////////////////////////////////////////////
	public function createMapping(Request $request, Response $response, $args)
	{
		
		$data = $request->getParsedBody();
		
		foreach($data as $row){
			$mapping = Mapping::updateOrCreate([
				'cep'=> $row['cep'],
				'sfdc_object'=>$row['sfdc_object'],
				'cep_attr_type'=>$row['cep_attr_type']
			],$row);
			$mapping->save();
		}

		return $response->withRedirect($this->container->router->pathFor('getMapping'));
		
	}
/////////////////////////////////////////////////////////////////////////////////////	
	public function getMapping(Request $request, Response $response, $args)
	{
		
		$status = array(
			'error'=>false,
			'error_message'=>null,
			'success'=>false,
			'success_message'=>null
		);

		//populate mapp client from session
		$this->mapp_client->setInstance(
			$this->_decrypt($_SESSION['INST'],$this->settings['secret'])
		);
		$this->mapp_client->setAuthentication(
			$this->_decrypt($_SESSION['USERNAME'],$this->settings['secret']),
			$this->_decrypt($_SESSION['PWD'],$this->settings['secret'])
		);
		
		//collect data from Mapp CEP system
		$cep_response = $this->mapp_client->getAttributeDefinitions();
		$cep_custom_attributes = $cep_response['data'];
		$cep_standard_attributes = $this->mapp_client->getStandardAttributeDefinitions();
		$cep_group_attributes = $this->mapp_client->getGroupAttributeDefinitions();

		//collect fields from Salesforce
		if(!empty($this->sfdc_session)){
			$sfdc_campaign = $this->sfdc_client->describeSObject('Campaign');
			$sfdc_campaign_fields = $this->container->Sforce->getObjectFields($sfdc_campaign);
			$sfdc_lead = $this->sfdc_client->describeSObject('Lead');
			$sfdc_lead_fields = $this->container->Sforce->getObjectFields($sfdc_lead);
			$sfdc_contact = $this->sfdc_client->describeSObject('Contact');
			$sfdc_contact_fields = $this->container->Sforce->getObjectFields($sfdc_contact);
		}else{
			$status['error'] = true;
			$status['error_message'] = 'Failed to connect to Salesforce. Please set or review your credentials in Settings section.';
			$sfdc_campaign_fields = array();
			$sfdc_lead_fields = array();
			$sfdc_contact_fields = array();
		}
		//collect current mapping from database
		$lead_mapping = Mapping::where('sfdc_object','lead')->get()->keyBy('cep')->toArray();
		$contact_mapping = Mapping::where('sfdc_object','contact')->get()->toArray();
		$campaign_mapping = Mapping::where('sfdc_object','campaign')->get()->toArray();

		//combine database and cep values for standard attributes
		foreach($cep_standard_attributes as $key=>$value){
			if(array_key_exists($value['name'],$lead_mapping)){
				$cep_standard_attributes[$key]['lead'] = $lead_mapping[$value['name']]['sfdc_name'];
				$cep_standard_attributes[$key]['lead_active'] = $lead_mapping[$value['name']]['active'];
				$cep_standard_attributes[$key]['lead_function'] = $lead_mapping[$value['name']]['sfdc_function'];
			}
			if(array_key_exists($value['name'],$contact_mapping)){
				$cep_standard_attributes[$key]['contact'] = $contact_mapping[$value['name']]['sfdc_name'];
				$cep_standard_attributes[$key]['contact_active'] = $contact_mapping[$value['name']]['active'];
				$cep_standard_attributes[$key]['contact_function'] = $contact_mapping[$value['name']]['sfdc_function'];
			}
		}
		
		//combine database and cep values for custom attributes
		foreach($cep_custom_attributes as $key=>$value){
			if(array_key_exists($value['name'],$lead_mapping)){
				$cep_custom_attributes[$key]['lead'] = $lead_mapping[$value['name']]['sfdc_name'];
				$cep_custom_attributes[$key]['lead_active'] = $lead_mapping[$value['name']]['active'];
				$cep_custom_attributes[$key]['lead_function'] = $lead_mapping[$value['name']]['sfdc_function'];
			}
			if(array_key_exists($value['name'],$contact_mapping)){
				$cep_custom_attributes[$key]['contact'] = $contact_mapping[$value['name']]['sfdc_name'];
				$cep_custom_attributes[$key]['contact_active'] = $contact_mapping[$value['name']]['active'];
				$cep_custom_attributes[$key]['contact_function'] = $contact_mapping[$value['name']]['sfdc_function'];
			}
		}
		
		
		//combine database and cep values for group attributes
		foreach($cep_group_attributes as $key=>$value){
			if(array_key_exists($value['name'],$campaign_mapping)){
				$cep_group_attributes[$key]['campaign'] = $campaign_mapping[$value['name']]['sfdc_name'];
				$cep_custom_attributes[$key]['campaign_active'] = $lead_mapping[$value['name']]['active'];
				$cep_custom_attributes[$key]['campaign_function'] = $lead_mapping[$value['name']]['sfdc_function'];
			}
		}
		
		//render mapping view with CEP, SFDC and settings data
		$body = $this->view->fetch('admin/pages/mapping.twig', [
			'cep_group_attributes' => $cep_group_attributes,
			'cep_standard_attributes' => $cep_standard_attributes,
			'cep_custom_attributes' => $cep_custom_attributes,
			'sfdc_campaign_fields' => $sfdc_campaign_fields,
			'sfdc_lead_fields' => $sfdc_lead_fields,
			'sfdc_contact_fields' => $sfdc_contact_fields,
			'user' =>  $request->getAttribute('user'),
			'error' => $status['error'],
			'error_message'=>$status['error_message'],
			'success' => $status['success'],
			'success_message'=>$status['success_message'],
			'debug' => $this->container->get('settings')['debug']
        ]);

        return $response->write($body);

	}

	public function createJsonMap(Request $request, Response $response, $args){
		
		//collect request context
		$query = $request->getQueryParams();
		//get saved mapping
		$mapping = Mapping::where([
			['active','true'],
			['sfdc_name','<>',''],
			['sfdc_object','<>','campaign']
		])
		->get(['cep', 'cep_api_name','cep_attr_type','sfdc_name'])
		->keyBy('cep')
		->toArray();
		
		//prepare array
		$json_map = [];
		
		//for each mapping
		foreach($mapping as $item=>$values){
			//define a placeholder depending on the cep_attr_type (standard,custom,member)
			if($values['cep_attr_type'] == 'standard'){
				$json_map[$values['cep_api_name']] = "\${user.".$item."}";
			}else if($values['cep_attr_type']== 'custom'){
				$json_map[$values['cep_api_name']] = "\${user.CustomAttribute['".$item."']}";
			}else{
				$json_map[$values['cep_api_name']] = '${user.MemberAttribute["'.$item.'"]}';
			}	
		}

		//collect campaign mapping
		$campaign_mapping = Mapping::where([
			['sfdc_name','<>',''],
			['sfdc_object','=','campaign']
		])
		->get(['cep', 'cep_api_name','cep_attr_type','sfdc_name'])
		->keyBy('cep')
		->toArray();
		
		//if there is any campaign mapping defined
		if(count($campaign_mapping)>0){
			//prepare array
			$json_map_campaign = [];
			//for each mapping
			foreach($campaign_mapping as $item=>$values){
				//define a placeholder depending on the cep_attr_type (standard,custom)
				if($values['cep_attr_type'] == 'standard'){
					$json_map_campaign[$values['cep_api_name']] = "\${group.".$item."}";
				}else{
					$json_map_campaign[$values['cep_api_name']] = "\${group.CustomAttribute['".$item."']}";
				}
			}
			//assign the campaign mapping to user mapping
			$json_map['campaign'] = $json_map_campaign;
		}

		return $response->withJson($json_map);
		
	}
	
/////////////////////////////////////////////////////////////////////////////////////	
	public function deleteMapping(Request $request, Response $response, $args)
	{
		
		$data = $request->getParsedBody();
		$mapping_id = $data['cep'];
		$mapping = Mapping::where('cep', '=', $mapping_id);
		Mapping::find($mapping_id)->delete();

		$body = $this->view->fetch('admin/pages/mapping.twig', [
			'user' =>  $request->getAttribute('user'),
			'error' => false,
			'error_message'=>'',
			'success' => false,
			'success_message'=>'You have logged out',
			'debug' => $this->container->get('settings')['debug']
		]);
        return $response->write($body);

	}
/////////////////////////////////////////////////////////////////////////////////////


	
	
}
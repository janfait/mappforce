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
	
	
	/**
     * Renderer for output adding debug data, csrf keys and logged-in user data
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     * @param array $body                  	  array of attributes passed to the Twig template
	 * @param string $template                path to the Twig template to be used to render body
	 * @param numeric $status                 HTTP Status code for the Response
	 * @param string $redirect                name of the pathName to redirect to (optional)
     *
     * @return ResponseInterface
     */
	public function renderAdminUI(Request $request, Response $response, $template, $body = null, $status = 200, $redirect = null){
		
		//assign default body if not supplied
		if(empty($body)){
			$body = $this->default_ui_status;
		}
		
		$csrf = array();
		$csrf['namekey'] = $this->container->csrf->getTokenNameKey();
		$csrf['valuekey'] = $this->container->csrf->getTokenValueKey();
		$csrf['name'] = $request->getAttribute($csrf['namekey']);
		$csrf['value'] = $request->getAttribute($csrf['valuekey']);
		
		//add standard body elements
		$body['debug'] = $this->container->get('settings')['debug'];
		$body['user'] = $request->getAttribute('user');
		$body['csrf'] = $csrf;
		
		//redirect to supplied pathName
		if(empty($redirect)){
			$ui = $this->view->fetch($template,$body);
			//write body directly
			return $response->write($ui)->withStatus($status);
		}else{
			$ui = $body;
			//redirect the response
			return $response->withRedirect($this->container->router->pathFor($redirect));	
		}
			
	}
	
	/**
     * Logout destoys all session data and kills the SFDC client and session
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */	
	public function logout(Request $request, Response $response, $args)
	{
		//$this->sfdc_client->invalidateSessions();
		$this->sfdc_client->setSessionHeader(null);
		$this->sfdc_client = null;

		session_start();
		session_unset();
		session_destroy();

		$body = $this->default_ui_status;
		$body['success'] = true;
		$body['message'] = 'You have logged out';
		
		return $this->renderAdminUI($request,$response,'admin/pages/login.twig',$body,200,'showLogin');
	}	
	
	/**
     * Redirect to login used after logout or session timeout
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */	
	public function goLogin(Request $request, Response $response, $args)
	{
		return $this->renderAdminUI($request,$response,null,null,200,'showLogin');
	}
	/**
     * Shows login screen
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */		
	public function showLogin(Request $request, Response $response, $args)
    {
		$body = $this->default_ui_status;
		$error = $request->getParam('error');
		
		if(!empty($error)){
			
			$body['error'] = true;
			$message_code = $request->getParam('message');
			$body['message'] = $this->messages[$message_code];
		}
		
		return $this->renderAdminUI($request,$response,'admin/pages/login.twig',$body,200);

    }
	/**
     * Performs login, stores encrypted user data into SESSION superglobal
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */	
	public function login(Request $request, Response $response, $args)
	{
		$data = $request->getParsedBody();
		$this->mapp_client->setInstance($data['sysname']);
		$this->mapp_client->setAuthentication($data['username'],$data['password']);
		$this->mapp_user = $this->mapp_client->getSystemUser();

		if(!isset($this->mapp_user) | true === $this->mapp_user['error']){
			
			$body = $this->default_ui_status;
			$body['error'] = true;
			$body['message'] = 'Incorrect credentials. Please try again. Make sure your Mapp API user is active and this IP whitelisted';

			return $this->renderAdminUI($request,$response,'admin/pages/login.twig',$body,401);

		}else{
			
			session_regenerate_id();
			$_SESSION['LOGGEDIN'] = true;
			$_SESSION['INST'] = $this->_encrypt($data['sysname'],$this->settings['secret']);
			$_SESSION['USER'] = $this->mapp_user['data'];
			$_SESSION['USERNAME'] = $this->_encrypt($data['username'],$this->settings['secret']);
			$_SESSION['PWD'] = $this->_encrypt($data['password'],$this->settings['secret']);

			return $this->renderAdminUI($request,$response,null,null,200,'home');
		
		}

	}
	/**
     * Path for the HomePage
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */		
	public function home(Request $request, Response $response, $args)
    {
		//collect the number of stored mappings
		$lead_standard_mapping = Mapping::where(['sfdc_object'=>'lead','cep_attr_type'=>'standard'])->count();
		$lead_custom_mapping = Mapping::where(['sfdc_object'=>'lead','cep_attr_type'=>'custom'])->count();
		$contact_standard_mapping = Mapping::where(['sfdc_object'=>'contact','cep_attr_type'=>'standard'])->count();
		$contact_custom_mapping = Mapping::where(['sfdc_object'=>'contact','cep_attr_type'=>'custom'])->count();
		$campaign_mapping = Mapping::where('sfdc_object','campaign')->count();
		
		$settings = Setting::where('realm','sfdc')->count();
		
		$body = array(
			'lead'=>array('standard'=>$lead_standard_mapping,'custom'=>$lead_custom_mapping),
			'contact'=>array('standard'=>$contact_standard_mapping,'custom'=>$contact_custom_mapping),
			'campaign'=>$campaign_mapping,
			'settings'=>false,
		);

		return $this->renderAdminUI($request,$response,'admin/pages/homepage.twig',$body,200);

    }
	/**
     * Path for the Getting Started page
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */	
	public function gettingStarted(Request $request, Response $response, $args)
	{
		return $this->renderAdminUI($request,$response,'admin/pages/getting_started.twig',null,200);

	}
	/**
     * Accepts POST request from the Mapping page and stores attributes given in message body in the mapping table
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */	
	public function createMapping(Request $request, Response $response, $args)
	{
		$data = $request->getParsedBody();

		foreach($data as $row){
			if(isset($row['sfdc_name'])){
				$mapping = Mapping::updateOrCreate([
					'cep'=> $row['cep_name'],
					'sfdc_object'=>$row['sfdc_object'],
					'cep_attr_type'=>$row['cep_attr_type']
				],$row);
				$mapping->save();
			}
		}

		return $response->withRedirect($this->container->router->pathFor('getMapping'));
		
	}
	/**
     * Collects mapping and prepares it for rendering in the Mapping pages
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */	
	public function getMapping(Request $request, Response $response, $args)
	{
		//populate mapp client from session todo: remove from controller methods to middleware/make common for all methods
		$this->mapp_client->setInstance(
			$this->_decrypt($_SESSION['INST'],$this->settings['secret'])
		);
		$this->mapp_client->setAuthentication(
			$this->_decrypt($_SESSION['USERNAME'],$this->settings['secret']),
			$this->_decrypt($_SESSION['PWD'],$this->settings['secret'])
		);
		$this->mapp_contact->setExecutor(
			$this->mapp_client
		);
		
		$status = $this->default_ui_status;

		//collect data from Mapp CEP system
		$cep_response = $this->mapp_client->getAttributeDefinitions();
		$cep_custom_attributes = $cep_response['data'];
		$cep_standard_attributes = $this->mapp_client->getStandardAttributeDefinitions();
		$cep_member_attributes = Mapping::where('cep_attr_type','member')->get()->keyBy('cep')->toArray();
		$cep_group_attributes = $this->mapp_client->getGroupAttributeDefinitions();

		//collect fields from Salesforce
		if($this->sfdc_session){
			$sfdc_campaign = $this->sfdc_client->describeSObject('Campaign');
			$sfdc_campaign_fields = $this->container->Sforce->getObjectFields($sfdc_campaign);
			$sfdc_lead = $this->sfdc_client->describeSObject('Lead');
			$sfdc_lead_fields = $this->container->Sforce->getObjectFields($sfdc_lead);
			$sfdc_contact = $this->sfdc_client->describeSObject('Contact');
			$sfdc_contact_fields = $this->container->Sforce->getObjectFields($sfdc_contact);
		}else{
			$status['error'] = true;
			$status['message'] = $this->messages['NO_CONNECTION_SFDC'];
			$status['success'] = false;
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
		
		//combine database and cep values for member attributes
		foreach($cep_member_attributes as $key=>$value){
			if(array_key_exists($value['cep'],$lead_mapping)){
				$cep_member_attributes[$key]['lead'] = $lead_mapping[$value['cep']]['sfdc_name'];
				$cep_member_attributes[$key]['lead_active'] = $lead_mapping[$value['cep']]['active'];
				$cep_member_attributes[$key]['lead_function'] = $lead_mapping[$value['cep']]['sfdc_function'];
			}
			if(array_key_exists($value['cep'],$contact_mapping)){
				$cep_member_attributes[$key]['contact'] = $contact_mapping[$value['cep']]['sfdc_name'];
				$cep_member_attributes[$key]['contact_active'] = $contact_mapping[$value['cep']]['active'];
				$cep_member_attributes[$key]['contact_function'] = $contact_mapping[$value['cep']]['sfdc_function'];
			}
			if(array_key_exists($value['cep'],$campaign_mapping)){
				$cep_member_attributes[$key]['campaign'] = $campaign_mapping[$value['cep']]['sfdc_name'];
				$cep_member_attributes[$key]['campaign_active'] = $campaign_mapping[$value['cep']]['active'];
				$cep_member_attributes[$key]['campaign_function'] = $campaign_mapping[$value['cep']]['sfdc_function'];
			}
		} 
		
		//combine database and cep values for group attributes
		foreach($cep_group_attributes as $key=>$value){
			if(array_key_exists($value['name'],$campaign_mapping)){
				$cep_group_attributes[$key]['campaign'] = $campaign_mapping[$value['name']]['sfdc_name'];
			}
		}
		
		//render mapping view with CEP, SFDC and settings data
		$body = array(
			'cep_group_attributes' => $cep_group_attributes,
			'cep_standard_attributes' => $cep_standard_attributes,
			'cep_custom_attributes' => $cep_custom_attributes,
			'cep_member_attributes' => $cep_member_attributes,
			'sfdc_campaign_fields' => $sfdc_campaign_fields,
			'sfdc_lead_fields' => $sfdc_lead_fields,
			'sfdc_contact_fields' => $sfdc_contact_fields,
			'error' => $status['error'],
			'message'=>$status['message'],
			'success' => $status['success']
        );

        return $this->renderAdminUI($request,$response,'admin/pages/mapping.twig',$body,200);

	}
	/**
     * Collects existing mapping from database and transforms it into a JSON map to be used in CEP automations
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */	
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
			$json_map_campaign['status'] = Setting::where('name','campaign_member_status_default')->first()->value;
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
	
	/**
     * Deletes mapping based on request body data
	 * This function is currently unused
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */	
	public function deleteMapping(Request $request, Response $response, $args)
	{
		
		$data = $request->getParsedBody();
		$mapping_id = $data['cep'];
		$mapping = Mapping::where('cep', '=', $mapping_id);
		Mapping::find($mapping_id)->delete();

        return $this->renderAdminUI($request,$response,'admin/pages/mapping.twig',$body,200);

	}


	
	
}
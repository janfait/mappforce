<?php
namespace MappIntegrator\Controller;
use Slim\Container as Container;
use \MappIntegrator\Setting as Setting;
use Slim\Views\Twig as TwigViews;
use SalesforceSoapClient;
use Stevenmaguire\OAuth2\Client\Provider\Salesforce as SalesforceOauth;
use Slim\Http\Request;
use Slim\Http\Response; 
/**
 * Class Controller
 * @package MappIntegrator\Controller
 *
 *
 * Base Controller handles Authentication and Authorization of MappForce against a given Salesforce instance
 * Handles all tokens and their storage
 * Most functions have side-effects acessing and storing values in class members.
 */
class Controller
{
    protected $view;
	public $container;
	public $call_stack;
	public $response_stack;
	public $sfdc_client;
	public $sfdc_connection;
	public $sfdc_session;
	public $sfdc_settings;
	public $sfdc_oauth_client;
	public $oauth;
	protected $mapp_client;
	public $mapp_contact;
	public $identifiers;
	public $default_output;
    /**
     * Controller constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
		//hand over dependencies from container
		$this->container = $container;
        $this->view = $container->view;
		$this->settings = $container->settings;
		//populate the mapp client and subclasses
		$this->mapp_client = $container->mappCep;
		$this->mapp_contact = $container->mappContact;
		
		//initialize settings and constants
		$this->identifiers = array("email"=>"Email","identifier"=>"Id");
		$this->valid_objects = array('contact','lead','campaign','any');
		$this->messages = array(
			'MISSING_REQUIRED_PARAMETER' => 'Your request query string is missing a required parameter',
			'OBJECT_NOT_ALLOWED' => 'Your request uses an invalid object attribute. The allowed objects are:'.implode(",",$this->valid_objects),
			'IDENTIFIER_NOT_ALLOWED' => 'Your request uses an invalid parameter. The allowed identifiers are:'.implode(",",$this->identifiers),
			'MISSING_REQUIRED_FIELD' => 'Your request body is missing a required field.',
			'MISSING_SETTINGS' => 'Some required settings are not populated.',
			'INVALID_STATE' => 'The response state parameter does not match the request. A security problem has occured.',
			'NO_OAUTH_CLIENT' => 'The Salesforce Oauth Client has failed to load.',
			'FAILED_OAUTH_REQUEST' => 'The request to the OAuth endpoint of Salesforce has failed.',
			'AUTHORIZATION_SUCCESS' => 'MappForce app is now succesfully authorized against Salesforce',
			'NO_CONNECTION_SFDC' => 'Failed to connect to Salesforce. Please set or review your connection in Settings section.',
			'SESSION_EXPIRED' => 'Your session has expired, please login again.',
			'STORAGE_SUCCESS' => 'Your settings have been stored successfuly.',
			'LOGIN_FAILED_SERVER' => 'Failed to connect to Salesforce. This is a server error. Please try again later'
		);
		$this->oauth = $this->settings['sfdc']['oauth'];
		$this->call_stack = array();
		$this->response_stack = array();
		$this->default_output = array('error'=>false,'error_message'=>'','payload'=>null);
		$this->default_ui_status = array('error'=>false,'message'=>null,'success'=>false);
		$this->mapping_exceptions = array('LeadId','ContactId','Status','Id');
		
		//register a logger
		if($this->settings['debug']){
			$this->logger = $this->container->devlogger;
		}else{
			$this->logger = $this->container->prodlogger;
		}
		//attempt sfdc login
		$this->sfdcLogin();

		if(!$this->sfdc_session){
			
			$data = $this->default_output;
			$data['error'] = true;
			$data['message'] = 'Connection to Salesforce instance failed. Please review your Salesforce credentials and try again';
			
			$response = new Response();
			return $response->withJson($data,403);
		}
		
    }

	
    /**
     * Builds an array of settings collected from the database depending on oauth property
     *
     * @param none
     * @return none
     */
	public function _sfdcCollectSettings()
	{
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//collect data
		$settings = Setting::where('realm','sfdc')->get();
		$_settings = array();
		//decrypt
		foreach($settings as $setting){
			//decrypt all non-empty password types with the supplied secret
			if($setting->type=='password' && !empty($setting->value)){
				$_settings[$setting->name] =  $this->_decrypt($setting->value,$this->settings['secret']);
			}else{
				$_settings[$setting->name] =  $setting->value;
			}
		}
		//store into property
		$this->sfdc_settings = $_settings;
		//define provider
		$this->sfdc_oauth_client = new SalesforceOauth([
			'clientId'=> $this->sfdc_settings['sfdc_consumer_key'],
			'clientSecret'=> $this->sfdc_settings['sfdc_consumer_secret'],
			'redirectUri'=> $this->sfdc_settings['sfdc_redirect_uri']
		]);
			
	}
	
	/**
     * Checks whether all required credentials are set before attempting a login
     *
     * @param none
     * @return boolean
     */
	public function _sfdcCheckSettings()
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//pass the sfdc settings property
		$s = $this->sfdc_settings;
		//if using the oauth flow
		if($this->oauth){
			//check presence of access token and its validity
			if(!empty($s['sfdc_access_token']) && !empty($s['sfdc_refresh_token']) && !empty($s['sfdc_access_token_expires_at'])){
				return true;
			}
			return false;
			
		}else{
			//check presence of username, password and security token
			if(!empty($s['sfdc_username']) && !empty($s['sfdc_password']) && !empty($s['sfdc_security_token'])){
				return true;
			}
			return false;
		}
		
	}
	/**
     * Checks whether all required credentials are set before attempting an app authorization
     *
     * @param none
     * @return boolean
     */	
	public function _sfdcCheckAuthSettings()
	{
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//pass the sfdc settings property
		$s = $this->sfdc_settings;
		//check that authorization settings are defined
		if(!empty($s['sfdc_consumer_key']) && !empty($s['sfdc_consumer_secret']) && !empty($s['sfdc_redirect_uri'])){
			return true;
		}
		return false;
	}
	/**
     * Checks whether a currently available access token has expired and if so, requests and stores a new one using the refresh token
     *
     * @param none
     * @return boolean
     */	
	private function _sfdcValidateAccessToken()
	{
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//check token expiry
		$expiry = $this->sfdc_settings['sfdc_access_token_expires_at'];
		$token_expired = time() > intval($expiry);
		if ($token_expired){
			//use refresh token to obtain a new access token
			$refresh_token = $this->_sfdcRefreshToken();
			//if request fails
			if(isset($refresh_token['error'])){
				return false;
			}
			$this->response_stack[] = array(__FUNCTION__=>$this->_sfdcStoreOauthToken($refresh_token));
		}
		return true;
		
	}
	/**
     * Stores all required nodes of the arriving OAuth token by mapping them to database Settings, returns true (success) and false (error)
     *
     * @param array $token 			An array received from the OAuth endpoint, should include access_token, refresh_token, issued_at and id nodes
     * @return boolean
     */	
	public function _sfdcStoreOauthToken($token)
	{
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//define mapping between what is supplied by SFDC and how it enters database
		$map = array(
			'access_token'=>'sfdc_access_token',
			'refresh_token'=>'sfdc_refresh_token',
			'issued_at'=>'sfdc_access_token_expires_at',
			'id'=>'sfdc_server_url'
		);
		//if token contains an error node
		if(array_key_exists('error',$token)){
			return false;
		}
		//loop throught the map 
		foreach($map as $key=>$v){
			//if key exists in supplied token
			if(array_key_exists($key,$token)){
				//look up a db setting of the same name
				$setting = Setting::where('name',$v)->first();
				//transformation for expiry
				if($key=='issued_at'){
					$value = strtotime('+25 minutes',time());
				}else{
					$value = $token[$key];
				}
				if($setting->type == 'password'){
					$setting->value = $this->_encrypt($value,$this->settings['secret']);	
				}else{
					$setting->value = $value;
				}
				//store to database
				$setting->save();
				//store to existing session settings
				$this->sfdc_settings[$v] = $value;
			}
		}
		$this->response_stack[] = array(__FUNCTION__=>true);
		return true;
		
	}
	/**
     * Requests OAuth token from SFDC OAuth endpoint
     *
     * @param string $code 			An array received from the OAuth endpoint, should include access_token, refresh_token, issued_at and id nodes
     * @return array $response
     */	
	public function _sfdcCollectOauthToken($code)
	{
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//collect settings due to a redirect between MappForce and authorization url
		$this->_sfdcCollectSettings();
		//define the POST parameters
		$params = "code=" . $code
		   . "&grant_type=authorization_code"
		   . "&client_id=" . $this->sfdc_settings['sfdc_consumer_key']
		   . "&client_secret=" . $this->sfdc_settings['sfdc_consumer_secret']
		   . "&redirect_uri=" . urlencode($this->sfdc_settings['sfdc_redirect_uri']);
		$curl = curl_init($this->container->Sforce->oauth_token_url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		if($this->settings['debug']){
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		if ( $status != 200 ) {
		  $error = json_decode($json_response, true);
		  $response = array('error'=>true,'http_code'=>$status,'error_message'=>$error['error_description']); 
		}else{
		  $response = json_decode($json_response, true);
		}
		$this->response_stack[] = array(__FUNCTION__=>$response);
		return $response;
			
	}
	/**
     * Requests Refresh token from SFDC OAuth endpoint
     *
     * @param none			
     * @return array $response
     */	
	private function _sfdcRefreshToken()
	{
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//define the POST parameters
		$params = "grant_type=refresh_token"
		   . "&client_id=" . $this->sfdc_settings['sfdc_consumer_key']
		   . "&client_secret=" . $this->sfdc_settings['sfdc_consumer_secret']
		   . "&refresh_token=" . $this->sfdc_settings['sfdc_refresh_token'];
		$curl = curl_init($this->container->Sforce->oauth_token_url);
		
		curl_setopt($curl, CURLOPT_HEADER, false);
		if($this->settings['debug']){
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		if ( $status != 200 ) {
		  $error = json_decode($json_response, true);
		  $response = array('error'=>true,'http_code'=>$status,'error_message'=>$error['error_description']); 
		}else{
		  $response = json_decode($json_response, true);
		}
		$this->response_stack[] = array(__FUNCTION__=>$response);
		return $response;
	}
	/**
     * Calls the SFDC Identity URL to obtain a valid endpoint for API calls
     *
     * @param none			
     * @return array $response
     */	
	public function _sfdcCollectIdentity($id)
	{
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//id parameter is a identity url
		$curl = curl_init($id);
		//place the oauth access token with to the authentication header
		$h = array();
		$h[] = 'Content-length: 0';
		$h[] = 'Content-type: application/json';
		$h[] = 'Authorization: OAuth '.$this->sfdc_settings['sfdc_access_token'];
		
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER,$h);
		if($this->settings['debug']){
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		if ( $status != 200 ) {
		  $error = json_decode($json_response, true);
		  $response = array('error'=>true,'http_code'=>$status,'error_message'=>$error['error_description']);
		}else{
		  $response = json_decode($json_response, true);	
		}
		$this->response_stack[] = array(__FUNCTION__=>$response);
		return $response;
	}
	/**
     * Collects the SFDC API Endpoint address, returns its value (success) or false (error)
     *
     * @param none			
     * @return string
     */	
	public function _sfdcCollectApiServer(){
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);

		$sfdc_api_server = Setting::where('name','sfdc_api_server')->first();
		
		if(!empty($sfdc_api_server->value)){
			$sfdc_api_server = $this->_decrypt($sfdc_api_server->value,$this->settings['secret']);
		}else{
			$sfdc_identity = $this->_sfdcCollectIdentity($this->sfdc_settings['sfdc_server_url']);
			if(isset($sfdc_identity['urls'])){
				//extract the api server url
				$sfdc_api_server = $sfdc_identity['urls']['partner'];
				//replace the version with "latest" keywords
				$sfdc_api_server = str_replace("{version}","latest",$sfdc_api_server);
				//store it
				$sfdc_api_server = $this->_sfdcStoreApiServer($sfdc_api_server);
			}else{
				$sfdc_api_server = false;
			}

		}
		$this->response_stack[] = array(__FUNCTION__=>$sfdc_api_server);
		return $sfdc_api_server;

	}
	/**
     * Stores the SFDC API Endpoint address, returns its value 
     *
     * @param string $value			
     * @return string $value
     */	
	private function _sfdcStoreApiServer($value){
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);

		$setting = Setting::where('name','sfdc_api_server')->first();
		if($setting->type == 'password'){
			$setting->value = $this->_encrypt($value,$this->settings['secret']);	
		}else{
			$setting->value = $value;
		}
		//store to database
		$setting->save();
		
		return $value;
	}
	/**
     * Attempts a login to SFDC, populates persistent session header with access token obtain in the OAuth procedure
     *
     * @param none		
     * @return string $value
     */	
	public function sfdcLogin()
	{
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//create default session flag
		$this->sfdc_session = false;
		//initialize client
		$this->sfdc_client = $this->container->SforceClient;
		//create wsdl connection
		$this->sfdc_connection = $this->sfdc_client->createConnection(
			$this->container->Sforce->wsdl,null,array('exceptions'=>true,'trace'=>false)
		);
		//collect the settings from database
		$this->_sfdcCollectSettings();
		//if oauth method not applies
		if(!$this->oauth){
			//try to login in create sfdc session
			$this->sfdc_session = $this->sfdc_client->login(
				$this->sfdc_settings['sfdc_username'],
				$this->sfdc_settings['sfdc_password'].$this->sfdc_settings['sfdc_security_token']
			);
		}else{
			//if required sfdc settings are not populated
			if($this->_sfdcCheckSettings()){				
				//if access token expired, request new one through a refresh token
				$this->_sfdcValidateAccessToken();
				//collect the returned api server url
				$sfdc_api_server = $this->_sfdcCollectApiServer();
				//set connection headers
				/*$sfdc_assignment_header = new \AssignmentRuleHeader("", false);
				$this->sfdc_connection->setAssignmentRuleHeader($sfdc_assignment_header);*/
				try{
					//attach session ID and endpoint to the client directly, bypassing the login method
					$this->sfdc_client->setEndpoint($sfdc_api_server);
					$this->sfdc_client->setSessionHeader($this->sfdc_settings['sfdc_access_token']);
					$this->sfdc_session = true;
				}catch(\Exception $e){
					$this->sfdc_session = false;
					$this->response_stack[] = array(__FUNCTION__=>array('session'=>$this->sfdc_session,'error'=>$e->faultstring,'connection'=>$this->sfdc_conection));
					var_dump($this->response_stack);
					die();
				}
			}
				
		}
		$this->response_stack[] = array(__FUNCTION__=>$this->sfdc_session);
		return $this->sfdc_session;

	}
	
	
	public function _generateKey(){
		return openssl_random_pseudo_bytes(40);
	}
	
	public function _collectKey(){

	}
	/**
     * Helper function for encryption given a key
     *
     * @param string $data
	 * @params string $key
	 *
     * @return string $data
     */	
	public function _encrypt($data, $key)
	{
		if(empty($data)){
			return null;
		}
		$encryption_key = base64_decode($key);
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		$encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
		return base64_encode($encrypted . '::' . $iv);
	}
	/**
     * Helper function for decryption given a key
     *
     * @param string $data
	 * @params string $key
	 *
     * @return string $data
     */	
	public function _decrypt($data, $key)
	{
		if(empty($data)){
			return null;
		}
		$encryption_key = base64_decode($key);
		list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
		return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
	}
	
}
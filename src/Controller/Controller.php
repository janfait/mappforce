<?php
namespace MappIntegrator\Controller;
use Slim\Container as Container;
use \MappIntegrator\Setting as Setting;
use Slim\Views\Twig as TwigViews;
use SalesforceSoapClient;
use Stevenmaguire\OAuth2\Client\Provider\Salesforce
as SalesforceOauth;
/**
 * Class Controller
 * @package MappIntegrator\Controller
 */
abstract class Controller
{
    /** @var TwigViews view */
    protected $view;
	public $container;
	public $sfdc_client;
	public $sfdc_connection;
	public $sfdc_session;
	public $sfdc_settings;
	public $sfdc_oauth_client;
	protected $mapp_client;
	public $identifiers;
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
		$this->mapp_client = $container->mappCep;
		$this->identifiers = array("email"=>"Email","identifier"=>"Id");
		$this->sfdc_collect_oauth_settings();
		$this->sfdc_login();	
    }
	
	
	public function sfdc_collect_oauth_settings()
	{
		//collect sfdc settings from database
		$sfdc_settings = array();
		$sfdc_settings['consumer_key'] = Setting::where('name','sfdc_consumer_key')->first()->value;
		$sfdc_settings['consumer_secret'] = Setting::where('name','sfdc_consumer_secret')->first()->value;
		$sfdc_settings['redirect_uri'] = Setting::where('name','sfdc_redirect_uri')->first()->value;
		
		if(empty($sfdc_settings['consumer_key']) | empty($sfdc_settings['consumer_secret']) | empty($sfdc_settings['redirect_uri'])){
			$this->sfdc_settings = null;
			$this->sfdc_oauth_client = null;
		}else{
			$sfdc_settings['consumer_secret'] = $this->_decrypt($sfdc_settings['consumer_secret'],$this->settings['secret']);
			$sfdc_settings['consumer_key'] = $this->_decrypt($sfdc_settings['consumer_key'],$this->settings['secret']);
			$this->sfdc_settings = $sfdc_settings;
			//define provider
			$this->sfdc_oauth_client = new SalesforceOauth([
				'clientId'=> $this->sfdc_settings['consumer_key'],
				'clientSecret'=> $this->sfdc_settings['consumer_secret'],
				'redirectUri'=> $this->sfdc_settings['redirect_uri']
			]);
			
		}	
	}
	
	public function _sfdc_collect_oauth_token($code){
		
		$this->sfdc_collect_oauth_settings();
		
		$token_url = "https://login.salesforce.com/services/oauth2/token";
		$params = "code=" . $code
		   . "&grant_type=authorization_code"
		   . "&client_id=" . $this->sfdc_settings['consumer_key']
		   . "&client_secret=" . $this->sfdc_settings['consumer_secret']
		   . "&redirect_uri=" . urlencode($this->sfdc_settings['redirect_uri']);
		$curl = curl_init($token_url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ( $status != 200 ) {
		   die("Error: call to token URL $token_url failed with status $status, request $params and response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}
		curl_close($curl);
		$response = json_decode($json_response, true);
		$access_token = $response['access_token'];
		$instance_url = $response['instance_url'];
		if (!isset($access_token) || $access_token == "") {
		   die("Error - access token missing from response!");
		}
		if (!isset($instance_url) || $instance_url == "") {
		   die("Error - instance URL missing from response!");
		}
		
		return $response;
		
		
	}
	
	public function sfdc_collect_oauth_token($code){
		try {
			// Try to get an access token using the authorization code grant.
			$accessToken = $this->sfdc_oauth_client->getAccessToken('authorization_code', [
				'code' => $code
			]);
			$oauth_token = array();
			$oauth_token['access_token'] = $accessToken->getToken();
			$oauth_token['refresh_token'] = $accessToken->getRefreshToken();
			$oauth_token['expiration_date'] = $accessToken->getExpires();
			$oauth_token['expired'] = $accessToken->hasExpired();
			
			return $oauth_token;
			
		} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
			return null;
		}
	
	}
	
	public function sfdc_collect_identity($id){
		
		$curl = curl_init($id);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ( $status != 200 ) {
		   die("Error: call to token URL $id failed with status $status and response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}
		curl_close($curl);
		$response = json_decode($json_response, true);
		return $response;
	}
	
	public function sfdc_collect_authentication($oauth=false)
	{
		//collect sfdc settings from database
		$sfdc_settings = array();
		
		if(!$oauth){
			
			$sfdc_settings['username'] = Setting::where('name','sfdc_username')->first()->value;
			$sfdc_settings['token'] = Setting::where('name','sfdc_security_token')->first()->value;
			$sfdc_settings['password'] = Setting::where('name','sfdc_password')->first()->value;
			if(empty($sfdc_settings['username']) | empty($sfdc_settings['password']) | empty($sfdc_settings['token'])){
				$sfdc_settings = null;
			}else{
				$sfdc_settings['password'] = $this->_decrypt($sfdc_settings['password'],$this->settings['secret']);
				$sfdc_settings['token'] = $this->_decrypt($sfdc_settings['token'],$this->settings['secret']);	
			}
		}else{
			
			$sfdc_settings['access_token'] = Setting::where('name','sfdc_access_token')->first()->value;
			$sfdc_settings['server_url'] = Setting::where('name','sfdc_server_url')->first()->value;
			if(empty($sfdc_settings['access_token']) | empty($sfdc_settings['server_url'])){
				$sfdc_settings = null;
			}else{
				
				//refresh if expired
				
				//store into settings
				$sfdc_settings['access_token'] = $this->_decrypt($sfdc_settings['access_token'],$this->settings['secret']);
				$sfdc_settings['server_url'] = $this->_decrypt($sfdc_settings['server_url'],$this->settings['secret']);	
			}
			
		}
		//add to property
		$this->sfdc_settings = $sfdc_settings;
		

		
	}
	
	public function sfdc_login($oauth=false)
	{
		//create sfdc connection
		$this->sfdc_client = $this->container->SforceClient;
		$this->sfdc_connection = $this->sfdc_client->createConnection(
			$this->container->Sforce->wsdl,null,array('exceptions'=>true,'trace'=>false)
		);
		
		//collect settings from database depending on the oauth parameter
		$this->sfdc_collect_authentication($oauth);
		if(is_null($this->sfdc_settings)){
			$this->sfdc_session = null;	
		}
		//if oauth method not applies
		if(!$oauth){

			//use the password flow
			try{
				//try to login in create sfdc session
				$this->sfdc_session = $this->sfdc_client->login(
					$this->sfdc_settings['username'],
					$this->sfdc_settings['password'].$this->sfdc_settings['token']
				);
			}
			catch(\Exception $e){
				$this->sfdc_session = null;
			}
			
		}else{
			//use the oauth flow
			try{
				
				$sfdc_identity = $this->sfdc_collect_identity($this->sfdc_settings['server_url']);
				$api_server = $sfdc_identity['urls']['partner'];
				//attach session ID and endpoint to the client, bypassing the login method
				$this->sfdc_client->setEndpoint();
				$this->sfdc_client->setSessionHeader($this->sfdc_settings['access_token']);
				
			}
			catch(\Exception $e){
				$this->sfdc_session = null;
			}
			
		}

	}
	
	public function _encrypt($data, $key)
	{
		$encryption_key = base64_decode($key);
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		$encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
		return base64_encode($encrypted . '::' . $iv);
	}
	 
	public function _decrypt($data, $key)
	{
		$encryption_key = base64_decode($key);
		list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
		return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
	}
	
}
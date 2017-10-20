<?php 

namespace Mapp;

   class CustomerEngagementPlatformApi {
      
      private $debug;
      private $apiversion = "v8";
      private $protocol = "https://";
      private $mirror = "amundsen.shortest-route.com";
      public $instance;
      public $username;
      public $password;
      private $headers = array(
      	"Content-Type"=>"Content-Type:application/json",
      	"Accept"=>"Accept:application/json"
      );
      public $errors = array(
      	"PARAMS_NOT_SUPPLIED"=>"Parameters have to be either an array with name=>value pairs or null"
      );
	  public $standardAttributes = array(
		array("name"=>"Email","type"=>"STRING"),
		array("name"=>"MobileNumber","type"=>"NUMBER"),
		array("name"=>"Identifier","type"=>"STRING"),
		array("name"=>"Title","type"=>"NUMBER"),
		array("name"=>"FirstName","type"=>"STRING"),
		array("name"=>"LastName","type"=>"STRING"),
		array("name"=>"ISOCountryCode","type"=>"STRING"),
		array("name"=>"ISOLanguageCode","type"=>"STRING"),
		array("name"=>"TimeZone","type"=>"STRING"),
		array("name"=>"DateOfBirth","type"=>"DATE")
	  );
	  public $groupAttributes = array(
		array("name"=>"Email","type"=>"STRING"),
		array("name"=>"Name","type"=>"STRING"),
		array("name"=>"Identifier","type"=>"STRING"),
		array("name"=>"Title","type"=>"NUMBER")
	  );
	  
      //constructor
	  function __construct($instance=null,$username=null,$password=null,$apiversion="v6",$debug=false){
	  	   $this->debugPrint("Initializing with debug=true");
		   $this->instance = $instance;
		   $this->username = $username;
		   $this->password = $password;
		   $this->debug = $debug;
		   $this->version = $apiversion;
	  }

      //debug printing function
      public function debugPrint($object){
      	if($this->debug){
      		var_dump($object);
      		echo "</br>";
      	}
      }
      //get authentication details for calls
      private function getAuthentication(){
		if(empty($this->username)){
			throw new \RuntimeException("Authentication details are not set, either initialize the object with username and password or set with setAuthentication method");	
		}
        return $this->username.":".$this->password;
      }
	  //set Authentication credentials after initialization
	  public function setAuthentication($username, $password) {
		$this->username = $username;
		$this->password = $password; 
	  }
	  //set Instance from outside
	  public function setInstance($instance){
		 $this->instance = $instance; 
	  }
	  //get instance
	  public function getInstance(){
		 if(empty($this->instance)){
			throw new \RuntimeException("Instance field is missing, either initialize the object with an instance parameter or set with setInstance method");	
		 }
		 return $this->instance; 
	  }
      //construct the api root for the particular instance
      private function buildRoot(){
      	return $this->protocol.$this->mirror."/".$this->getInstance()."/api/rest/".$this->apiversion."/";
      }
      //construct the current url for the query
      public function buildUrl($domain,$method,$params){
      	//append method and domain
      	$u = $this->buildRoot();
      	$u .= $domain."/".$method;
      	if(is_array($params) & count($params)>0){
      		$u .= "?".http_build_query($params);
      		$this->debugPrint($u);
      		return $u;
      	}else if(is_null($params)){
      		return $u;
      	}else{
      		return $this->errors->PARAMS_ERROR;
      	}
      }
      //serialize the body
      private function serializeBody($body){
		$out = array();
		foreach($body as $item => $key){
			$a = array();
			$a['name'] = $item;
			$a['value']= $key;
			$out[] = $a;
		}
		return json_encode($out);
	  }
	  private function respond($info,$body){
		
		$response = array("error"=>false,"httpCode"=>null);
		$response['httpCode'] = intval($info['http_code']);
		if($response['httpCode']>=400){
			$response['error'] = true;	
		}
		$response['data'] = $body;
		$this->debugPrint($response);
		return $response;
	  }
      //call the api
      public function call($domain,$method,$params,$body=null){
		//initialize CURL
		$c = curl_init();
		//construct the url
		$u = $this->buildUrl($domain,$method,$params);
		$h = array($this->headers['Content-Type'],$this->headers['Accept']);
		//define CURL settings
		curl_setopt($c, CURLOPT_URL, $u);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($c, CURLOPT_USERPWD, $this->getAuthentication());
		curl_setopt($c, CURLOPT_HEADER, true);
		curl_setopt($c, CURLOPT_HTTPHEADER, $h);
		curl_setopt($c, CURLINFO_HEADER_OUT, true);
		//decide over GET and POST methods
		if(is_array($body) & count($body)>0){
			$body = $this->serializeBody($body);
			curl_setopt($c, CURLOPT_POST, true);
			curl_setopt($c, CURLOPT_POSTFIELDS, $body);
		}
		//execute
		$cResponse = curl_exec($c);
		$cInfo = curl_getinfo($c);
		$cHeaderSize = curl_getinfo($c, CURLINFO_HEADER_SIZE);
		$cHeader = substr($cResponse, 0, $cHeaderSize);
		$cBody = json_decode(substr($cResponse, $cHeaderSize),true);
		//close
		curl_close($c);
		$response = $this->respond($cInfo,$cBody);
		return $response;
	  }
	  //get the user info of the current user
	  public function getSystemUser($email=null){
		if(empty($email)){
			$email = $this->username;
		}  
		return $this->call(
			$domain="systemuser",
			$method="get",
			$params= array("email"=>$email)
		);
	  }
	  public function getApiVersion(){
		 return $this->call(
			$domain="system",
			$method="getApiVersion",
			$params= null
		);  
	  }
	  public function getStandardAttributeDefinitions(){
		  return $this->standardAttributes;
	  }
	  public function getGroupAttributeDefinitions(){
		  return $this->groupAttributes;
	  }
	  //get attribute definitions
	  public function getAttributeDefinitions(){
		return $this->call(
			$domain="meta",
			$method="getAttributeDefinitions",
			$params= null
		);   
	  }

   }

   class Contact extends CustomerEngagementPlatformApi {
		//private members
		private $executor;
		public $attributes = array(
			"Standard" => array(
				"user.Email","user.FirstName","user.LastName","user.ISOCountryCode","user.MobileNumber"
			),
			"Custom" => array(
				"user.CustomAttribute.Job","user.CustomAttribute.Company"	
			)
		);
		public $errorCodes = array("OBJECT_ALREADY_EXISTS");
		//constructor
		function __construct(CustomerEngagementPlatformApi $system){
			$this->executor = $system;
		}
		private function filterAttributeName($a){
			return in_array($a,array_merge($this->attributes['Standard'],$this->attributes['Custom']));
		}
		//filter for attributes
		public function validateAttributes(array $attributes){
			$matchedKeys = array_filter(array_keys($attributes), array($this,'filterAttributeName'));
			return array_intersect_key($attributes, array_flip($matchedKeys));
		}
		//get by id
		public function get($params){
			return $this->executor->call(
				$domain="user",
				$method="get",
				$params= $params
			);
		}
		//get by email
		public function getByEmail($params){
			return $this->executor->call(
				$domain="user",
				$method="getByEmail",
				$params= $params
			);
		}
		//create
		public function create($params,$attributes){
			$attributes = $this->validateAttributes($attributes);
			return $this->executor->call(
				$domain="user",
				$method="create",
				$params=$params,
				$body=$attributes
			);
		}
		//update
		public function updateProfile($params,$attributes){
			$attributes = $this->validateAttributes($attributes);
			return $this->executor->call(
				$domain="user",
				$method="update",
				$params=$params,
				$body=$attributes
			);
		}
		//update
		public function updateProfileByEmail($params,$attributes){
			$attributes = $this->validateAttributes($attributes);
			return $this->executor->call(
				$domain="user",
				$method="updateProfileByEmail",
				$params=$params,
				$body=$attributes
			);
		}
		//upsert by email
		public function upsertByEmail($params,$attributes){
			//try to create
			$create = $this->create($params,$attributes);
			//if creation fails
			if($create['error']==true){
				//try to update
				$update = $this->updateProfileByEmail($params,$attributes);
				//if update fails
				if($update['error']==true){
					return $update;
				}else{
					$get = $this->getByEmail($params);
					return $get;
				}
			}else{
				return $create;
			}
		}
   }
   
   
?>
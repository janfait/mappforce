<?php 

class SforceExecutor {
  
  public $client;
  private $connection;
  public $session;
  private $username;
  private $password;
  private $token;
  public $wsdl;
  private $assignment_rule_id;
  private $email_header_id;
  private $oauth_endpoint;
  private $consumer_key;
  private $consumer_secret;
  private $redirect_uri;


  function __construct(SforcePartnerClient $client = null,$wsdl = null){
	$this->client = $client;
	$this->wsdl = $wsdl;
	$this->oauth_authorize_url = "https://login.salesforce.com/services/oauth2/authorize?response_type=code";
	$this->oauth_token_url = "https://login.salesforce.com/services/oauth2/token";
  }

  public function setClient(SforcePartnerClient $client){
  	$this->client = $client;
  }
  
  public function setCredentials($username,$password,$token){
  	$this->username = $username;
	$this->password = $password;
	$this->token = $token;
  }
  
  public function setConsumerKeys($consumer_key,$consumer_secret,$redirect_uri){
	$this->consumer_key = $consumer_key;
	$this->consumer_secret = $consumer_secret;
	$this->redirect_uri = $redirect_uri; 
  }
  
  
  public function login(){
	 
	if(empty($this->username)){
		throw new \RuntimeException("SforceExecutor: username is not set or empty");
	}
	if(empty($this->password)){
		throw new \RuntimeException("SforceExecutor: password is not set or empty");
	}
	if(empty($this->token)){
		throw new \RuntimeException("SforceExecutor: token is not set or empty");
	}
	$this->connection = $this->client->createConnection($this->wsdl);
	$this->session = $this->client->login($this->username, $this->password.$this->token);
	
	if(isset($this->assingment_rule_id)){
		$assignment_header = new AssignmentRuleHeader($this->assignment_rule_id, false);
		$this->client->setAssignmentRuleHeader($assignment_header);
	}
	
	if(isset($this->email_header_id)){
		$email_header = new EmailHeader(false,false,true);
		$this->client->setEmailHeader($email_header);
	}
	  
  }

  public function getObjectFields($sfdc_object){
	
	$sfdc_fields_object = $sfdc_object->fields;
	$sfdc_fields = array();
	foreach($sfdc_fields_object as $field){
		
		if($field->updateable){
			$reduced_field = array(
				'custom'=>$field->custom,
				'label'=>$field->label,
				'name'=>$field->name,
				'type'=>$field->type,
				'restricted'=>$field->restrictedPicklist,
				'required'=>!$field->nillable
			);
			array_push($sfdc_fields,$reduced_field);
		}

	}
	return $sfdc_fields;
  }

  public function executeQuery($fields,$object,$where){
	
	if(is_array($fields)){
		$fields = array_implode(",",$fields);
	}
	
	$q = 'SELECT '.$fields.' FROM '.$object.' WHERE '.$where;
	$req = $this->client->query($q);
	$res = new QueryResult($req);

	try {
		$results = array();
		for ($res->rewind(); $res->pointer < $res->size; $res->next()) {
			array_push($results,(array) $res->current());
		}
		return $results;
	} catch (Exception $e) {
	  return $e->faultstring;
	}

  }
  
  public function findObjectByIdentifier($object,$identifier,$identifierValue){
  	
  }

  	
  

}


   
   
?>
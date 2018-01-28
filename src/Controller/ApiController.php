<?php
namespace MappIntegrator\Controller;


use \MappIntegrator\Mapping as Mapping;
use \MappIntegrator\Setting as Setting;
use Slim\Http\Request;
use Slim\Http\Response; 

/**
 * Class ApiController
 * @package MappIntegrator\Controller
 *
 *
 * The package functions are typically divided into two categories.
 *
 * a) Wrapper function - this function consumes the PSR7 request, collects and validates query parameters and message body which are then passed to the executor function, returns a PSR7 response 
 * b) Executor function - typically starts with _sfdc which accepts input from the Wrapper function and wraps around the Salesforce native SOAP API Toolkit functions, passing the resposne back to the Wrapper
 *
 */
class ApiController extends Controller
{
	
	
	
	 /**
     * Renderer for output adding debug data is query debug parameter and debug settings are true
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     * @param array $data                  	  Data to be rendered as JSON to the client
	 * @param numeric $status                 HTTP Status code for the Response
     *
     * @return ResponseInterface
     */
	
	private function renderOutput(Request $request, Response $response, $data, $status = 200){
		
		//allow debug only for applications in development
		if($request->getParam('debug') && $this->settings['debug']){
			$data['debug'] = array(
				'cep_user'=>$request->getAttribute('user'),
				'call_stack' => $this->call_stack,
				'response_stack' => $this->response_stack
			);
		}
		//log event
		$this->logger->info(json_encode($data));
		
		return $response->withJson($data,$status);
	}
	
	/**
     * Renderer for error outputs, matches error code with corresponding message
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 * @param string $error_code              Must be one of array keys in Contoller->messages
     * @param string $error_message           Message sent to the client with error details, if valid error_code given, overwritten with Controller->messages[$error_code]	 
     * @param array $data                  	  Data to be rendered as JSON to the client
	 * @param numeric $status                 HTTP Status code for the Response
     *
     * @return ResponseInterface
     */
	
	private function renderError(Request $request, Response $response, $error_code = null, $error_message = null, $function = null, $status = 400){
		
		$data = $this->default_output;
		$data['error'] = true;
		if(in_array($error_code,$this->messages)){
			$data['error_message'] = $this->messages[$error_code];
		}else{
			$data['error_message'] = $error_message;
		}
		
		if($request->getParam('debug')){
			$data['debug'] = array(
				'cep_user'=>$request->getAttribute('user'),
				'call_stack' => $this->call_stack,
				'response_stack' => $this->response_stack
			);
		}
		//log event
		$this->logger->error(json_encode($data));
		
		return $response->withJson($data,$status);
		
	}
	
	/**
     * Helper function validating that object attribute in the requests is one of valid objects
     *
     * @param string $object		Any of $this->valid_objects
     *
     * @return boolean
     */
	private function _validateObject($object){
		if(in_array($object, $this->valid_objects)){
			return true;
		}else{
			return false;
		}
	}
	/**
     * Helper function validating presence of query parameters on a particular route
     *
     * @param string $query			Query string as aresult of $request->getQueryParams()
	 * @param array $required		An array of required parameters for a route
     *
     * @return boolean
     */
	private function _validateQuery($query,$required){
		foreach($required as $r){
			if(!array_key_exists($r,$query)){
				return false;
			}
		}
		return true;
	}

	/**
     * Mapping function for country variables. Assumes the presence of the CountryMap object in Container, see ../dependencies.php
     *
     * @param string $string		A string to be mapped, typically a ISO-3166 alpha-2 or a ISO-3166 country name
	 * @param string $iso			A designation of the ISO standard to be returned. If alpha-2, the function returns a country code, else a country name
     *
     * @return string
     */
	private function _countryMap($string,$iso){
		
		//retrieve country map
		$country_map = $this->container->CountryMap;
		
		//if not found, return input
		if(empty($country_map)){
			return $string;
		}
		//if iso is alpha-2 flip the map
		if($iso=='alpha-2'){
			$country_map = array_flip($country_map);
		}
		//return a corresponding item or if not found the input
		if(array_key_exists($string,$country_map)){
			return $country_map[$string];
		}else{
			return $string;
		}
		
	}
	
	////////////////////////////////////////////////
	// Root
	////////////////////////////////////////////////
	
	 /**
     * Root endpoint handler for the API. Welcomes user and lists all endpoitns
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */
    public function root(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$router = $this->container->router->getRoutes();
		$routes = array("Welcome to MappForce, these are the supported API endpoints");
		foreach($router as $r){
			if(strpos($r->getPattern(), '/api/') !== false){
				$routes[] = array('method'=>$r->getMethods()[0],'path'=>$r->getPattern());	
			}
			
		}
		$output['payload'] = $routes;
		
        return $this->renderOutput($request,$response,$output);
    }

	////////////////////////////////////////////////
	// Mapp CEP specific endpoints
	////////////////////////////////////////////////
	
	/**
     * Root endpoint for CEP group, returns the currently approached API version of Mapp CEP
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
     *
     * @return ResponseInterface
     */
	public function cepRoot(Request $request, Response $response, $args)
	{
		$output = $this->container->mappCep->getApiVersion();
        return $this->renderOutput($request,$response,$output);
    }
	
	/**
     * Retrieves a CEP contact based on email
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 * Query parameters:
	 * @param string email     email address of the contact to be retrieve
     *
     * @return ResponseInterface
     */	
	public function cepGetContact(Request $request, Response $response, $args)
	{
		$query = $request -> getQueryParams();
		$output = $this->default_output;
		
		
		if(!isset($query['email'])){
			return $this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}else{
			$this->mapp_contact->setExecutor($this->mapp_client);
			$cep_response = $this->mapp_contact->getByEmail(array('email'=>$query['email']));
			if(isset($cep_response['error'])){
				return $this->renderError($request,$response,null,'CEP ERROR');
			}
			$output['payload'] = $cep_response['data'];
		}

        return $this->renderOutput($request,$response,$output);
    }
	
	/**
     * Upserts a CEP contact based on email
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 * Query parameters:
	 * @param string email     email address of the contact to be upserted
	 * Message Body:
	 * @param array     array of attribute name=>value pairs
     *
     * @return ResponseInterface
     */		
	public function cepUpsertContact(Request $request, Response $response, $args)
	{
		$query = $request -> getQueryParams();
		$body = $request -> getParsedBody();
		$output = $this->default_output;
		
		if(!isset($query['email'])){
			return $this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}else{
			$this->mapp_contact->setExecutor($this->mapp_client);
			$cep_response = $this->mapp_contact->upsertByEmail(array('email'=>$query['email']),$body);
			if(isset($cep_response['error'])){
				return $this->renderError($request,$response,null,'CEP ERROR');
			}
			$output['payload'] = $cep_response['data'];
		}

        return $this->renderOutput($request,$response,$output);
    }
	
	////////////////////////////////////////////////
	// SFDC specific endpoints
	////////////////////////////////////////////////
	
	/**
     * Makes a call to the SFDC identity service and returns all output
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
     *
     * @return ResponseInterface
     */	
	public function sfdcIdentity(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$id = Setting::where('name','sfdc_server_url')->first();
		$id = $this->_decrypt($id->value,$this->settings['secret']);
		$output['payload'] = $this->_sfdcCollectIdentity($id);
        return $this->renderOutput($request,$response,$output);
    }
	/**
     * Makes a call to the SFDC identity service and returns API endpoint for MappForce (urls->partner)
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
     *
     * @return ResponseInterface
     */
	public function sfdcApiEndpoint(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$output['payload'] = $this->_sfdcCollectApiServer();
        return $this->renderOutput($request,$response,$output);
    }
	/**
     * Makes a call to the SFDC getUserInfo() and returns the data of the user on who's behalf MappForce has authenticated to SFDC
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
     *
     * @return ResponseInterface
     */
	public function sfdcUser(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$output['payload'] = $this->sfdc_client->getUserInfo();
        return $this->renderOutput($request,$response,$output);
    }
	/**
     * Describes the SFDC object given in the route attribute placeholder
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
     *
     * @return ResponseInterface
     */
	public function sfdcObject(Request $request, Response $response, $args)
	{	
		//collect object
		$object = $request->getAttribute('object');
		//validate object
		if(!$this->_validateObject($object)){
			return $this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		$sfdc_response = $this->sfdc_client->describeSObject($object);
        return $response->withStatus(200);
	}
	/**
     * Returns an array of fields for an SFDC object given in the route attribute placeholder
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
     *
     * @return ResponseInterface
     */
	public function sfdcObjectFields(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		//collect object
		$object = $request->getAttribute('object');
		//validate object
		if(!$this->_validateObject($object)){
			$this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		$sfdc_response = $this->sfdc_client->describeSObject($object);
		$sfdc_fields = $this->container->Sforce->getObjectFields($sfdc_response);
		$output['payload'] = $sfdc_fields;
        return $this->renderOutput($request,$response,$output);
	}
	/**
     * Supplies the default campaign member status setting from the settings table
     *
     * @return string
     */
	private function sfdcGetDefaultStatus(){
		return Setting::where('name','campaign_member_status_default')->first()->value;
	}
	/**
     * Returns a mapping of request body attribtues for a particular SFDC object
     * to their corresponding SFDC attributes based on mapping table
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
     *
     * @return ResponseInterface
     */
	public function sfdcMap(Request $request, Response $response, $args)
	{	
		//get body of request and request params
		$body = $request->getParsedBody();
		//collect the output
		$object = $request->getAttribute('object');
		//validate object
		if(!$this->_validateObject($object)){
			return $this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		//prepare default output
		$output = $this->default_output;
		//attempt an upsert on the selected object
		$map = $this->_sfdcMap($object,$body);
		//define output
		$output['payload'] = $map;
		
        return $this->renderOutput($request,$response,$output);
	}
	/**
     * Returns a mapping of a supplied array of SFDC attribute keys to 
     * their corresponding CEP API attribute names based on mapping table
     *
     * @param string $object				Valid name of a SFDC object
     * @param array $body					Array of attribute name=>value pairs
	 *
     *
     * @return array
     */
	private function _cepMap($object,$body)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__,'params'=>func_get_args());
		//get mapping from database for the particular object, key by cep_api_name
		$mapping = Mapping::where([['sfdc_object',$object],['sfdc_name','<>','']])->get()->keyBy('sfdc_name')->toArray();
		//create fields array
		$fields = [];
		//collect the fields from request body and map them according to existing mapping
		foreach($body as $item=>$value){
			if(array_key_exists($item,$mapping)){
				//collect the corresponding sfdc field key
				$field_key = $mapping[$item]['cep_api_name'];
				//add value
				$fields[$field_key]=$value;
			}
		}

		if(isset($fields['user.ISOCountryCode'])){
			$fields['user.ISOCountryCode'] = $this->_countryMap($fields['user.ISOCountryCode'],'alpha-2');
		}
		
		return $fields;
	}
	/**
     * Returns a mapping of a supplied array of CEP attribute keys to 
     * their corresponding SFDC API attribute names based on mapping table
     *
     * @param string $object				Valid name of a SFDC object
     * @param array $body					Array of attribute name=>value pairs
	 *
     *
     * @return array
     */
	private function _sfdcMap($object,$body)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__,'params'=>func_get_args());
		//get mapping from database for the particular object, key by cep_api_name
		$mapping = Mapping::where([['sfdc_object',$object],['sfdc_name','<>','']])->get()->keyBy('cep_api_name')->toArray();
		//create fields array
		$fields = [];
		//collect the fields from request body and map them according to existing mapping
		foreach($body as $item=>$value){
			if(array_key_exists($item,$mapping)){
				//collect the mapping function
				$field_function = $mapping[$item]['sfdc_function'];
				//collect the corresponding sfdc field key
				$field_key = $mapping[$item]['sfdc_name'];
				//apply mapping function
				switch ($field_function) {
					case 'insert':
						$fields[$field_key]=$value;
						break;
					case 'concat_semi':
						$fields[$field_key]+=";".$value;
						break;
					case 'concat_comma':
						$fields[$field_key]+=",".$value;
						break;
					case 'add':
						$fields[$field_key]= intval($fields[$field_key])+intval($value);
						break;
					case 'subtract':
						$fields[$field_key]= intval($fields[$field_key])-intval($value);
						break;
				}
				
			}else if(in_array($item,$this->mapping_exceptions) | $object=='CampaignMember'){
				$fields[$item] = $value;
			}
			if(isset($fields['Country'])){
				$fields['Country'] = $this->_countryMap($fields['Country'],'alpha-2');
			}
		}
		//add mapping to response stack
		$this->response_stack[] = array(__FUNCTION__=>$fields);
		
		return $fields;
	}
	/**
     * Returns the results of a SOQL query supplied as a query parameter
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 * Query parameters:
	 * @param string q     a valid query in SOQL language
	 *
     * @return ResponseInterface
     */
	public function sfdcQuery(Request $request, Response $response,$args)
	{
		//get body of request and request params
		$query = $request->getQueryParams();
		//get default output
		$output = $this->default_output;

		if(isset($query['q'])){
			$search_query = $query['q'];
			$output = $this->_sfdcQuery($search_query);
		}else{
			return $this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}
		
		return $this->renderOutput($request,$response,$output);
	}
	/**
     * Returns the results of a SOQL query
     *
	 * @param string query     a valid query in SOQL language
	 *
     * @return array
     */
	private function _sfdcQuery($query)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__,'params'=>func_get_args());
		$results = $this->default_output;
		
		$results['query_result_size'] = 0;
		$results['payload'] = array();
		
		try {
		  if(strpos($query,'LIMIT') == false){
			 $query = $query." LIMIT ".$this->container->settings['sfdc']['query_limit']; 
		  }
		  $results['query'] = $query;
		  $req = $this->sfdc_client->query($query);
		  $res = new \QueryResult($req);
		  $results['query_result_size'] = $res->size;

		  for($res->rewind();$res->pointer < $res->size; $res->next()){
			$set = $res->current();
			$fields = (array)$set->fields;
			$fields['Id'] = $set->Id;
			array_push($results['payload'],$fields);
		  }

		} catch (\Exception $e) {

		  $results['error'] =  true;
		  $results['error_message'] =  $e->faultstring;
		}
		
		return $results;
	}
	/**
     * Combines the _sfdcQuery and _sfdcTransferRecord to search and transfer objects from SFDC to CEP using a defined mapping
     *
     * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 * Message body:
	 * @param string q     a valid query in SOQL language
	 *
     * @return ResponseInterface
     */
	public function sfdcTransferQuery(Request $request, Response $response,$args)
	{
		//get body of request and request params
		$body = $request->getParsedBody();
		//get default output
		$output = $this->default_output;
		//get object
		$object = $request->getAttribute('object');
		//validate object
		if(!$this->_validateObject($object)){
			return $this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		if(isset($body['q'])){
			$search_query = $body['q'];
			$results = $this->_sfdcQuery($search_query);
			//initialize empty array for results
			$transfer_results = [];
			//loop through query results and transfer them
			foreach($results['payload'] as $record){
				$transfer_result = $this->_sfdcTransferRecord($record,$object);
				$transfer_results[] = $transfer_result;
			}
			//pass result to payload
			$output['payload'] = $transfer_results;
			
		}else{
			$this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}

		
		return $this->renderOutput($request,$response,$output);
	}
	/**
     * Transfers a given SFDC record object to CEP as a contact
     *
     * @param stdClass $record				 SFDC record
     * @param string $object     			 a valid name of SFDC object
	 *
	 * Message body:
	 * @param string q     					 a valid query in SOQL language
	 *
     * @return array
     */
	private function _sfdcTransferRecord($record,$object){
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__,'params'=>func_get_args());
		//map the record to CEP
		$mapped_body = $this->_cepMap($object,$record);
		//use mapp client to upsert the record
		$this->mapp_contact->setExecutor($this->mapp_client);
		$cep_response = $this->mapp_contact->upsertByEmail(array('email'=>$mapped_body['user.Email']),$mapped_body);
		//assign result
		$transfer_result = array('email'=>$mapped_body['user.Email'],'cep_response'=>$cep_response);
		//return $transfer_result;
		return $transfer_result;
		
	}
	/**
     * Adds a supplied lead or contact record to a campaign as a campaign member with a given status
     *
	 * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 * Query Parameters:
	 * @param string object_id     			an 18-Digit ID of the SFDC object
	 * @param string campaign_id     		an 18-Digit ID of the SFDC campaign
	 * @param string status (optional)		a valid Campaign Member Status
	 *
     * @return ResponseInterface
     */
	public function sfdcAddToCampaign(Request $request, Response $response, $args)
	{		
		//get body of request and request params
		$query = $request->getQueryParams();
		//prepare default output
		$output = $this->default_output;
		//get object
		$object = $request->getAttribute('object');
		//validate query
		if(!$this->_validateQuery(array('object_id','campaign_id'),$query)){
			return $this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}
		//validate object
		if(!$this->_validateObject($object)){
			return $this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		if(!isset($query['status'])){
			$status = $this->sfdcGetDefaultStatus();
		}
		//attempt an upsert on the selected object
		try {
			$record = $this->_sfdcAddToCampaign($object,$object_id,$campaign_id,$status);
		} catch (\Exception $e) {
			$record =  $e->faultstring;
		}
		//define output
		$output['payload'] = (array) $record;
		
        return $this->renderOutput($request,$response,$output);
	}

	/**
     * Adds a supplied lead or contact record to a campaign as a campaign member with a given status
     *
     * @param string $object     			a valid name of SFDC object
	 * @param string object_id     			an 18-Digit ID of the SFDC object
	 * @param string campaign_id     		an 18-Digit ID of the SFDC campaign
	 * @param string status (optional)		a valid Campaign Member Status
	 *
     * @return stdClass
     */
	private function _sfdcAddToCampaign($object,$object_id,$campaign_id,$status= null)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__,'params'=>func_get_args());
		//define the membership object
		$record = new \stdClass();
		//add status
		$record->Status = $status;
		//define search field for query
		$search_field = ucfirst($object)."Id";
		//check if the campaign membership exists
		$search_query = "SELECT Id,Status FROM CampaignMember WHERE ".$search_field."='".$object_id."' AND CampaignId='".$campaign_id."' LIMIT 1";
		//run the search
		$search_result = $this->_sfdcQuery($search_query);
		
		if($search_result['query_result_size']>0){
			$record_id = $search_result['payload'][0]['Id'];
			$campaign_member = $this->_sfdcUpdate($record_id,'CampaignMember',$record);
		}else{
			//check the record for Contact or Lead Id
			if($object=='contact'){
				$record -> ContactId = $object_id;
			}else{
				$record-> LeadId = $object_id;	
			}
			$record->CampaignId = $campaign_id;
			$campaign_member = $this->_sfdcCreate('CampaignMember',$record);
		}
		
		return $campaign_member;
	}
	/**
     * Creates a SFDC object specified in attribute using attributes supplied in message body
     *
	 * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 * Message Body:
	 * @param array     			an array of name:value attribute pairs
	 *
	 *
     * @return ResponseInterface
     */
	public function sfdcCreate(Request $request, Response $response, $args)
	{		
		//get body of request and request params
		$body = $request->getParsedBody();
		//prepare default output
		$output = $this->default_output;
		//collect the output
		$object = $request->getAttribute('object');
		//validate object
		if(!$this->_validateObject($object)){
			return $this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		//attempt an upsert on the selected object
		try {
			$record = $this->_sfdcCreate($object,$body);
		} catch (\Exception $e) {
			$record =  $e->faultstring;
		}
		//define output
		$output['payload'] = (array) $record;
		
        return $this->renderOutput($request,$response,$output);
	}
	
	/**
     * Creates a SFDC object specified in attribute using attributes supplied in message body
     *
	 * @param string $object				a valid name of a SFDC object in lower case
     * @param array	 $body     				an array with name:value attribute pairs
	 *
	 * Query Parameters:
	 * @param string object_id     			an 18-Digit ID of the SFDC object
	 * @param string campaign_id     		an 18-Digit ID of the SFDC campaign
	 * @param string status (optional)		a valid Campaign Member Status
	 *
     * @return array
     */
	private function _sfdcCreate($object,$body)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__,'params'=>func_get_args());
		//create the sfdc record object
		$record = new \stdClass();
		//map fields
		$fields = $this->_sfdcMap($object,$body);
		//assign the fields to the record
		$record->fields = $fields;
		//assign type to the record
		$record->type = ucfirst($object);
		//upsert to sfdc
		$sfdc_response = $this->sfdc_client->create(array ($record));
		//return
		return get_object_vars($sfdc_response[0]);
	}
	
	/**
     * Upserts a SFDC object specified in attribute using attributes supplied in message body
	 * Creates campaign membership object if "campaign" node provided in the message body
	 * Unlike the latter sfdcUpsertBy method, this uses a sfdcSearch function to locate the objects to be updated
     *
	 * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 *
	 * Query Parameters:
	 * @param string identifier    			any of $this->identifiers
	 *
	 * Message Body:
	 * @param array     			an array of name:value attribute pairs
	 *
	 *
     * @return ResponseInterface
     */
	public function sfdcUpsert(Request $request, Response $response, $args)
	{
		//get body of request and request params
		$body = $request->getParsedBody();
		$query = $request->getQueryParams();
		//prepare default output
		$output = $this->default_output;
		//if identifier isn't set
		if(!isset($query['identifier'])){
			return $this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		//if identifier is outside of the alloweed value range
		}else if(!array_key_exists($query['identifier'],$this->identifiers)){
			return $this->renderError($request,$response,'IDENTIFIER_NOT_ALLOWED');
		}else{
			$identifier = $query['identifier'];
			//construct the identifier key
			$identifier_key = "user.".ucfirst($identifier);
			//collect the actual identifier variable name for sfdc
			$identifier = $this->identifiers[$identifier];
		}
		//check if identifier key node is present in the supplied JSON object
		if(!isset($body[$identifier_key])){
			return $this->renderError($request,$response,'MISSING_REQUIRED_FIELD');
		}
		//collect existing records using SOSL
		$records = $this->_sfdcSearch($body[$identifier_key]);
			
		//determine which record type applies
		if(count($records['contacts'])>0){
			$object = 'contact';
			$object_id = $records['contacts'][0];
			$record = $this->_sfdcUpdate($object_id,$object,$body);
		}else if(count($records['leads'])>0){
			$object = 'lead';
			$object_id = $records['leads'][0];
			$record = $this->_sfdcUpdate($object_id,$object,$body);
		}else{
			$record = $this->_sfdcCreate('Lead',$body);	
		}
		//add to campaign if campaign object supplied
		if(isset($body["campaign"])){
			
			//if status empty, collect default status from database
			if(!isset($body['campaign']['status'])){
				$status = $this->sfdcGetDefaultStatus();
			}else{
				$status = $body['campaign']['status'];
			}
			//upsert the campaign member
			$campaign = $this->_sfdcUpsertBy('Name','campaign',$body['campaign']);
			
			if(isset($campaign['id']) && isset($record['id'])){
				$campaign_member = $this->_sfdcAddToCampaign($object,$record['id'],$campaign['id'],$status);
			}else{
				$campaign_member = null;
			}

		}else{
			$campaign = null;
			$campaign_member = null;
		}
		
		$output['payload'] = array('record'=>$record,'campaign'=>$campaign,'campaign_member'=>$campaign_member);

        return $this->renderOutput($request,$response,$output);
	}
	
	/**
     * Upserts a SFDC object specified in attribute using attributes supplied in message body
     *
	 * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 *
	 * Query Parameters:
	 * @param string identifier    			any of $this->identifiers
	 *
	 * Message Body:
	 * @param array     			an array of name:value attribute pairs
	 *
	 *
     * @return ResponseInterface
     */
	public function sfdcUpsertBy(Request $request, Response $response, $args)
	{
		//get body of request and request params
		$body = $request->getParsedBody();
		$query = $request->getQueryParams();
		//prepare default output
		$output = $this->default_output;
		//collect the output
		$object = $request->getAttribute('object');
		//validate object
		if(!$this->_validateObject($object)){
			return $this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		//validate identifier
		if(!isset($query['identifier'])){
			return $this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}else{
			$record = $this->_sfdcUpsertBy(ucfirst($query['identifier']),$object,$body);
			$output['payload'] = (array) $record;
			return $this->renderOutput($request,$response,$output);
		}
		
	}
	
	/**
     * Upserts a SFDC object specified in attribute using attributes supplied in message body
     * Unlike the sfdcUpsert function, a the sfdcQuery function is used to locate the object to be updated
	 * Also, this function does not create the Campaign and CampaignMembership objects.
	 *
	 * @param string $field					  valid field to upsert by
     * @param string $object				  name of the object
	 * @param array $body					  array of name=>value attribute pairs
	 *
	 *
     * @return stdClass $record
     */
	private function _sfdcUpsertBy($field,$object,$body){
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__,'params'=>func_get_args());
		//map
		$fields = $this->_sfdcMap($object,$body);
		//check that field is supplied in the body
		if(isset($fields[$field])){
			$search_query = "SELECT Id,".$field." FROM ".ucfirst($object)." WHERE ".$field." = '".$fields[$field]."' LIMIT 1";
			$this->call_stack[] = $search_query;
			$search_result = $this->_sfdcQuery($search_query);
		}else{
			return false;
		}

		if($search_result['query_result_size']>0){
			$object_id = $search_result['payload'][0]['Id'];
			try{
				$record = $this->_sfdcUpdate($object_id,$object,$body);
			}catch(\Exception $e){
				return false;
			}
		}else{
			try{
				$record = $this->_sfdcCreate($object,$body);
			}catch(\Exception $e){
				return false;
			}
		}
		return $record;
		
	}
	
	/**
     * Updates a SFDC object specified in attribute using attributes supplied in message body
     *
	 * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 *
	 * Query Parameters:
	 * @param string id    			18-Digit Id of the object to be updated
	 *
	 * Message Body:
	 * @param array     			an array of name:value attribute pairs
	 *
	 *
     * @return ResponseInterface
     */
	public function sfdcUpdate(Request $request, Response $response, $args)
	{	
		//get body of request and request params
		$body = $request->getParsedBody();
		//prepare default output
		$output = $this->default_output;
		//collect the output
		$object = $request->getAttribute('object');
		//validate object
		if(!$this->_validateObject($object)){
			return $this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		//
		if(!isset($query['id'])){
			return $this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER',__FUNCTION__);
		}else{
			//attempt an upsert on the selected object
			try {
				$record = $this->_sfdcUpdate($query['id'],$object,$body);
			} catch (\Exception $e) {
				$record =  $e->faultstring;
			}
			//define output
			$output['payload'] = (array) $record;
		}
        return $this->renderOutput($request,$response,$output);
	}
	
	
	/**
     * Updates a SFDC object specified in attribute using attributes supplied in message body
     *
	 * @param string $object_id				  18-Digit Id of the object to be updated
     * @param string $object				  name of the object
	 * @param array $body					  array of name=>value attribute pairs
	 *
	 *
     * @return array $record
     */
	private function _sfdcUpdate($object_id,$object,$body)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__,'params'=>func_get_args());
		//create the sfdc record object
		$record = new \stdClass();
		//map fields
		$fields = $this->_sfdcMap($object,$body);
		$fields['Id'] = $object_id;
		//assign the fields to the record
		$record->fields = $fields;
		//assign type to the record
		$record->type = ucfirst($object);
		//upsert to sfdc
		$sfdc_response = $this->sfdc_client->update(array($record));
		//return
		return  get_object_vars($sfdc_response[0]);
	}
	
	/**
     * Searches the Lead and Contact objects for ones that match the supplied string
     *
	 * @param ServerRequestInterface $request PSR7 request
     * @param ResponseInterface $response     PSR7 response
	 *
	 *
	 * Query Parameters:
	 * @param string q    			a character string to search for
	 * @param string field    		lowercase name of a field to search in, defaults to email
	 *
	 *
	 *
     * @return ResponseInterface
     */
	public function sfdcSearch(Request $request, Response $response, $args)
	{
		$query = $request->getQueryParams();
		//prepare default output
		$output = $this->default_output;
		//check arriving email in query
		if(isset($query['q'])){
			
			$field = 'email';
			if(isset($query['field'])){
				$field = $query['field'];
			}
			$output['payload'] = $this->_sfdcSearch($query['q'],$field);
		}else{
			return $this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}
		return $this->renderOutput($request,$response,$output);
	}	
	/**
     * Searches the Lead and Contact objects for ones that match the supplied string
     *
	 * @param string q    			a character string to search for
	 * @param string field    		lowercase name of a field to search in, defaults to email
	 *
	 *
     * @return array
     */
	private function _sfdcSearch($q,$field='email')
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__,'params'=>func_get_args());
		if(isset($q)){
			$search_query = 'FIND {'.$q.'} IN '.strtoupper($field).' FIELDS RETURNING CONTACT(ID),LEAD(ID)';
			//do the serach
			try{
				$search_result = $this->sfdc_client->search($search_query);	
				//collect search results
				$records = $search_result->searchRecords;
				//create arrays for each object 
				$contacts = array();
				$leads = array();
				//loop over the returned object
				foreach($records as $key=>$record){
					if($record->type=='Contact'){
						$contacts[] = $record->Id;
					}else{
						$leads[] = $record->Id;
					}
				}
				
			} catch (\Exception $e) {
				return array('contacts'=>null,'leads'=>null,'query'=>$search_query,'query_result'=>$e->faultstring);	
			}
			return array('contacts'=>$contacts,'leads'=>$leads,'query'=>$search_query,'query_result'=>$records);
		}else{
			return false;
		}
	}

	

	////////////////////////////////////////////////
	// MappForce Admin endpoints
	////////////////////////////////////////////////		
	
	public function mappingGetAll(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$output['payload'] = Mapping::all();
		return $this->renderOutput($request,$response,$output);
	}
	
	
}


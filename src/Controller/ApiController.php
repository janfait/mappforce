<?php
namespace MappIntegrator\Controller;


use \MappIntegrator\Mapping as Mapping;
use \MappIntegrator\Setting as Setting;
use Slim\Http\Request;
use Slim\Http\Response; 

/**
 * Class ApiController
 * @package MappIntegrator\Controller
 */
class ApiController extends Controller
{
	
	
	private function renderOutput(Request $request, Response $response, $data, $status = 200){
		
		if($request->getParam('debug')){
			$data['debug'] = array(
				'cep_user'=>$request->getAttribute('user'),
				'call_stack' => $this->call_stack
			);
		}
		
		$csfr = array();
		$csfr['namekey'] = $this->container->csrf->getTokenNameKey();
		$csfr['valuekey'] = $this->container->csrf->getTokenValueKey();
		$csfr['name'] = $request->getAttribute($csfr['namekey']);
		$csfr['value'] = $request->getAttribute($csfr['valuekey']);
		
		//add standard body elements
		$body['debug'] = $this->container->get('settings')['debug'];
		$body['user'] = $request->getAttribute('user');
		$body['csfr'] = $csfr;

		$this->container->logger->info(json_encode($data));
		
		return $response->withJson($data,$status);
	}
	
	private function renderError(Request $request, Response $response, $error_code = null, $error_message = null, $function = null, $status = 400){
		
		$data = $this->default_output;
		$data['error'] = true;
		if(in_array($error_code,$this->error_messages)){
			$data['error_message'] = $this->error_messages[$error_code];
		}else{
			$data['error_message'] = $error_message;
		}
		
		if($request->getParam('debug')){
			$data['debug'] = array(
				'cep_user'=>$request->getAttribute('user'),
				'call_stack' => $this->call_stack
			);
		}
		
		$this->container->logger->error(json_encode($data));
		
		return $response->withJson($data,$status);
		
	}
	
	private function _validateObject($object){
		if(in_array($object, $this->valid_objects)){
			return true;
		}else{
			return false;
		}
	}
	
	private function _validateQuery($query,$required,$function=null){
		foreach($required as $r){
			if(!array_key_exists($r,$query)){
				return false;
			}
		}
		return true;
	}
	
	////////////////////////////////////////////////
	// Root
	////////////////////////////////////////////////

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
	
	public function cepRoot(Request $request, Response $response, $args)
	{
		$output = $this->container->mappCep->getApiVersion();
        return $this->renderOutput($request,$response,$output);
    }
	
	
	public function cepGetContact(Request $request, Response $response, $args)
	{
		$query = $request -> getQueryParams();
		$output = $this->default_output;
		
		
		if(!isset($query['email'])){
			$this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}else{
			$this->mapp_contact->setExecutor($this->mapp_client);
			$cep_response = $this->mapp_contact->getByEmail(array('email'=>$query['email']));
			if(isset($cep_response['error'])){
				$this->renderError($request,$response,null,'CEP ERROR');
			}
			$output['payload'] = $cep_response['data'];
		}

        return $this->renderOutput($request,$response,$output);
    }
	
	
		
	public function cepUpsertContact(Request $request, Response $response, $args)
	{
		$query = $request -> getQueryParams();
		$body = $request -> getParsedBody();
		$output = $this->default_output;
		
		if(!isset($query['email'])){
			$this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}else{
			$this->mapp_contact->setExecutor($this->mapp_client);
			$cep_response = $this->mapp_contact->upsertByEmail(array('email'=>$query['email']),$body);
			if(isset($cep_response['error'])){
				$this->renderError($request,$response,null,'CEP ERROR');
			}
			$output['payload'] = $cep_response['data'];
		}

        return $this->renderOutput($request,$response,$output);
    }
	////////////////////////////////////////////////
	// SFDC specific endpoints
	////////////////////////////////////////////////
	
	public function sfdcIdentity(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$id = Setting::where('name','sfdc_server_url')->first();
		$id = $this->_decrypt($id->value,$this->settings['secret']);
		$output['payload'] = $this->_sfdc_collect_identity($id);
        return $this->renderOutput($request,$response,$output);
    }
	
	public function sfdcUser(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$output['payload'] = $this->sfdc_client->getUserInfo();
        return $this->renderOutput($request,$response,$output);
    }
	
	public function sfdcObject(Request $request, Response $response, $args)
	{	
		//collect object
		$object = $request->getAttribute('object');
		//validate object
		if(!$this->_validateObject($object)){
			$this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		$sfdc_response = $this->sfdc_client->describeSObject($object);
        return $response->withStatus(200);
	}
	
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
	
	private function sfdcGetDefaultStatus(){
		return Setting::where('name','campaign_member_status_default')->first()->value;
	}

	public function sfdcMap(Request $request, Response $response, $args)
	{	
		//get body of request and request params
		$body = $request->getParsedBody();
		//collect the output
		$object = $request->getAttribute('object');
		//validate object
		if(!$this->_validateObject($object)){
			$this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		//prepare default output
		$output = $this->default_output;
		//attempt an upsert on the selected object
		$map = $this->_sfdcMap($object,$body);
		//define output
		$output['payload'] = $map;
		
        return $this->renderOutput($request,$response,$output);
	}
	
	private function _sfdcMap($object,$body)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		//get mapping from database for the particular object, key by cep_api_name
		$mapping = Mapping::where('sfdc_object',$object)->get()->keyBy('cep_api_name')->toArray();
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
		}
		
		return $fields;
	}
	
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
			$this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}
		
		return $this->renderOutput($request,$response,$output);
	}
	
		private function _sfdcQuery($query)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
		$results = $this->default_output;
		$results['query'] = $query;
		$results['query_result_size'] = 0;
		$results['payload'] = array();
		
		try {
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
			$this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}
		//validate object
		if(!$this->_validateObject($object)){
			$this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
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

	
	private function _sfdcAddToCampaign($object,$object_id,$campaign_id,$status= null)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
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
			$this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
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
	
	
	private function _sfdcCreate($object,$body)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
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
			$this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		//validate identifier
		if(!isset($query['identifier'])){
			$this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}else{
			$record = $this->_sfdcUpsertBy(ucfirst($query['identifier']),$object,$body);
			$output['payload'] = (array) $record;
			return $this->renderOutput($request,$response,$output);
		}
		
	}
	
	private function _sfdcUpsertBy($field,$object,$body){
		
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
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
			$this->renderError($request,$response,'OBJECT_NOT_ALLOWED');
		}
		//
		if(!isset($query['id'])){
			$this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER',__FUNCTION__);
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
	
	private function _sfdcUpdate($object_id,$object,$body)
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
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
	

	public function sfdcSearch(Request $request, Response $response, $args)
	{
		$query = $request->getQueryParams();
		//prepare default output
		$output = $this->default_output;
		//check arriving email in query
		if(isset($query['q'])){
			$output['payload'] = $this->_sfdcSearch($query['q']);
		}else{
			$this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		}
		return $this->renderOutput($request,$response,$output);
	}	

	private function _sfdcSearch($q,$field='email')
	{
		$this->call_stack[] = array('time'=>microtime(),'function'=>__FUNCTION__);
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

	
	public function sfdcUpsert(Request $request, Response $response, $args)
	{
		//get body of request and request params
		$body = $request->getParsedBody();
		$query = $request->getQueryParams();
		//prepare default output
		$output = $this->default_output;
		//if identifier isn't set
		if(!isset($query['identifier'])){
			$this->renderError($request,$response,'MISSING_REQUIRED_PARAMETER');
		//if identifier is outside of the alloweed value range
		}else if(!array_key_exists($query['identifier'],$this->identifiers)){
			$this->renderError($request,$response,'IDENTIFIER_NOT_ALLOWED');
		}else{
			$identifier = $query['identifier'];
			//construct the identifier key
			$identifier_key = "user.".ucfirst($identifier);
			//collect the actual identifier variable name for sfdc
			$identifier = $this->identifiers[$identifier];
		}
		//check if identifier key node is present in the supplied JSON object
		if(!isset($body[$identifier_key])){
			$this->renderError($request,$response,'MISSING_REQUIRED_FIELD');
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
	////////////////////////////////////////////////
	// MappForce Admin endpoints
	////////////////////////////////////////////////		
	
	public function mappingGetAll(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$output['payload'] = Mapping::all();
		return $this->renderOutput($request,$response,$output);
	}
	
	public function settingGetAll(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$settings = Setting::all();
		$output['payload'] = $settings;
		return $this->renderOutput($request,$response,$output);
	}
	
}


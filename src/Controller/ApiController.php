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
	
	
	private function renderJson(Request $request, Response $response, $data, $status = 200){
		
		if($request->getParam('debug')){
			$data['debug'] = array(
				'time'=>microtime() - $request->getAttribute('time'),
				'cep_user'=>$request->getAttribute('user'),
				'call_stack' => $this->call_stack
			);
		}
		return $response->withJson($data,$status);
	}
	
	////////////////////////////////////////////////
	// Root
	////////////////////////////////////////////////

    public function root(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		
		$router = $this->container->router->getRoutes();
		$routes = array();
		foreach($router as $r){
			if(strpos($r->getPattern(), '/api/') !== false){
				$routes[] = array('method'=>$r->getMethods()[0],'path'=>$r->getPattern());	
			}
			
		}
		$output['payload'] = $routes;
		
		
        return $this->renderJson($request,$response,$output);
    }
	
	////////////////////////////////////////////////
	// Mapp CEP specific endpoints
	////////////////////////////////////////////////
	
	public function cepRoot(Request $request, Response $response, $args)
	{
		$output = $this->container->mappCep->getApiVersion();
        return $this->renderJson($request,$response,$output);
    }
	
	public function cepGetContact(Request $request, Response $response, $args)
	{
		$output = $this->container->mappCep->getApiVersion();
        return $this->renderJson($request,$response,$output);
    }
	
	////////////////////////////////////////////////
	// SFDC specific endpoints
	////////////////////////////////////////////////
	
	public function sfdcIdentity(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$id = Setting::where('name','sfdc_server_url')->first();
		$id = $this->_decrypt($id->value,$this->settings['secret']);
		$id = $this->_sfdc_collect_identity($id);
		$output['payload'] = $id;
        return $this->renderJson($request,$response,$output);
    }
	
	public function sfdcUser(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$output['payload'] = $this->sfdc_client->getUserInfo();
        return $this->renderJson($request,$response,$output);
    }
	
	public function sfdcObject(Request $request, Response $response, $args)
	{
		$sfdc_response = $this->sfdc_client->describeSObject($request->getAttribute('object'));
        return $response->withStatus(200);
	}
	
	public function sfdcObjectFields(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$sfdc_response = $this->sfdc_client->describeSObject($request->getAttribute('object'));
		$sfdc_fields = $this->container->Sforce->getObjectFields($sfdc_response);
		$output['payload'] = $sfdc_fields;
        return $this->renderJson($request,$response,$output);
	}
	
	private function _sfdcMap($object,$body)
	{
		$this->call_stack[] = __FUNCTION__;
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
			$output['payload'] = $this->_sfdcQuery($search_query);
		}else{
			$output['error'] = true;
			$output['error_message'] = 'Required parameter "q" is missing';
		}
		
		return $this->renderJson($request,$response,$output);

	}

	
	private function _sfdcAddToCampaign($object,$object_id,$campaign_id,$status)
	{
		$this->call_stack[] = __FUNCTION__;
		//define the membership object
		$record = new stdClass();
		//check the record for Contact or Lead Id
		if($object=='contact'){
			$record -> ContactId = $object_id;
		}else{
			$record-> LeadId = $object_id;	
		}
		//define search field for query
		$search_field = ucfirst($object)."Id";
		//check if the campaign membership exists
		$search_query = "SELECT Id,Status FROM CampaignMember WHERE ".$search_field."='".$object_id."' AND CampaignId='".$campaign_id."' LIMIT 1";
		//run the search
		$search_result = $this->_sfdcQuery($search_query);
		
		if($search_result['query_result_size']>0){
			$record_id = $search_result['payload'][0]['Id'];
			$record->Status = $status;
			$campaign_member = $this->_sfdcUpdate($record_id,'CampaignMember',$record);
		}else{
			$campaign_member = $this->_sfdcCreate('CampaignMember',$record);
		}
		
		return $campaign_member;
		
	}
	
	private function _sfdcQuery($query)
	{
		$this->call_stack[] = __FUNCTION__;
		$results = array();
		$results['error'] =  false;
		$results['error_message'] =  null;
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
	
	private function _sfdcCreate($object,$body){
		
		$this->call_stack[] = __FUNCTION__;
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
		return $sfdc_response;
		
	}
	
	private function _sfdcUpsert($identifier,$object,$body)
	{
		$this->call_stack[] = __FUNCTION__;
		//create the sfdc record object
		$record = new \stdClass();
		//map fields
		$fields = $this->_sfdcMap($object,$body);
		//assign type to the record
		$record->type = ucfirst($object);
		//assign the fields to the record
		$record->fields = $fields;
		//upsert to sfdc
		$sfdc_response = $this->sfdc_client->upsert("Email", array($record));
		//return
		return $sfdc_response;
	}
	
	private function _sfdcUpdate($object_id,$object,$body)
	{
		$this->call_stack[] = __FUNCTION__;
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
		return $sfdc_response;
	}
	
	public function sfdcMap(Request $request, Response $response, $args){	
		//get body of request and request params
		$body = $request->getParsedBody();
		//collect the output
		$object = $request->getAttribute('object');
		//prepare default output
		$output = $this->default_output;
		//attempt an upsert on the selected object
		$map = $this->_sfdcMap($object,$body);
		//define output
		$output['payload'] = $map;
		
        return $this->renderJson($request,$response,$output);
	}
	
	public function sfdcCreate(Request $request, Response $response, $args){
				
		//get body of request and request params
		$body = $request->getParsedBody();
		//prepare default output
		$output = $this->default_output;
		//collect the output
		$object = $request->getAttribute('object');
		//attempt an upsert on the selected object
		try {
			$record = $this->_sfdcCreate($object,$body);
		} catch (\Exception $e) {
			$record =  $e->faultstring;
		}
		//define output
		$output['payload'] = $record;
		
        return $this->renderJson($request,$response,$output);
	}
	
	private function _sfdcSearch($q,$field='email'){
		
		$this->call_stack[] = __FUNCTION__;
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
				return array('contacts'=>null,'leads'=>null);	
			}
			return array('contacts'=>$contacts,'leads'=>$leads);
		}else{
			return false;
		}
	}
	
	public function sfdcSearch(Request $request, Response $response, $args){
		
		$query = $request->getQueryParams();
		//prepare default output
		$output = $this->default_output;
		//check arriving email in query
		if(isset($query['q'])){
			$output['payload'] = $this->_sfdcSearch($query['q']);
		}else{
			$output['error'] = true;
			$output['error_message'] = "Your request is missing the mandatory parameter 'q' with the search string";
		}
		return $this->renderJson($request,$response,$output);
	}
	
	
	public function sfdcUpsert(Request $request, Response $response, $args)
	{
		
		//get body of request and request params
		$body = $request->getParsedBody();
		$query = $request->getQueryParams();
		
		//prepare default output
		$output = $this->default_output;
		//collect the output
		$object = $request->getAttribute('object');
		//if identifier isn't set
		if(!isset($query['identifier'])){
			$output['error'] = true;
			$output['error_message'] = "Your request is missing the mandatory parameter 'identifier'.";
			return $response->withStatus(400)->withJson($output);
		//if identifier is outside of the alloweed value range
		}else if($query['identifier']!="email" && $query['identifier']!="identifier"){
			$output['error'] = true;
			$output['error_message'] = "Incorrect value for 'identifier' parameter. Allowed values for the identifier parameter are 'email' or 'identifier'";
			return $response->withStatus(400)->withJson($output);
		}else{
			$identifier = $query['identifier'];
			//construct the identifier key
			$identifier_key = "user.".ucfirst($identifier);
			//collect the actual identifier variable name for sfdc
			$identifier = $this->identifiers[$identifier];
		}

		//check if identifier key node is present in the supplied JSON object
		if(!isset($body[$identifier_key])){
			$output['error'] = true;
			$output['error_message'] = "The required identifier '".$identifier_key."' was not found in your JSON object. The upsert operation cannot proceed";
			return $response->withStatus(400)->withJson($output);
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
			if(!isset($query['status'])){
				$status = Setting::where('name','campaign_member_status_default')->first()->value;
			}else{
				$status = $query['status'];
			}
			//upsert the campaign by Name
			try{
				$campaign = $this->_sfdcCreate('Campaign',$body['campaign']);
			}catch(\Exception $e){
				$campaign = $e->faultstring();
			}
			//upsert the campaign member
			$campaign_member = $this->_sfdcAddToCampaign($object,$record['id'],$campaign['id'],$status);
			
		}else{
			$campaign = null;
			$campaign_member = null;
		}
		
		$output['payload'] = array('record'=>$record,'campaign'=>$campaign,'campaign_member'=>$campaign_member);

        return $this->renderJson($request,$response,$output);
	}
	
	////////////////////////////////////////////////
	// MappForce Admin endpoints
	////////////////////////////////////////////////		
	
	public function mappingGetAll(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$output['payload'] = Mapping::all();
		return $this->renderJson($request,$response,$output);
	}
	
	public function settingGetAll(Request $request, Response $response, $args)
	{
		$output = $this->default_output;
		$settings = Setting::all();
		$output['payload'] = $settings;
		return $this->renderJson($request,$response,$output);
	}
	
}


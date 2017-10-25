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
	
	////////////////////////////////////////////////
	// Root
	////////////////////////////////////////////////

    public function root(Request $request, Response $response, $args)
	{
		$output = array('error'=>false,'error_message'=>'','payload'=>'This is the API root','user'=>$request->getAttribute('user'));
        return $response->withJson($output);
    }
	
	public function status(Request $request, Response $response, $args)
	{
		$output = array('error'=>false,'error_message'=>'','payload'=>'This is the API root','user'=>$request->getAttribute('user'));
        return $response->withJson($output);
    }
	
	////////////////////////////////////////////////
	// Mapp CEP specific endpoints
	////////////////////////////////////////////////
	
	public function cepRoot(Request $request, Response $response, $args)
	{
		$cep_response = $this->container->mappCep->getApiVersion();
        return $response->withStatus($cep_response['httpCode'])->withJson($cep_response);
    }
	
	////////////////////////////////////////////////
	// SFDC specific endpoints
	////////////////////////////////////////////////
	
	public function sfdcLogin(Request $request, Response $response, $args)
	{
		$output = array('error'=>false,'error_message'=>'','payload'=>'','user'=>$request->getAttribute('user'));
		$output['payload'] = array('call_stack'=>$this->call_stack,'response_stack'=>$this->response_stack);
        return $response->withJson($output);
    }
	
	public function sfdcIdentity(Request $request, Response $response, $args)
	{
		$output = array('error'=>false,'error_message'=>'','payload'=>'','user'=>$request->getAttribute('user'));
		$id = Setting::where('name','sfdc_server_url')->first();
		$id = $this->_decrypt($id->value,$this->settings['secret']);
		$id = $this->_sfdc_collect_identity($id);
		$output['payload'] = $id;
        return $response->withJson($output);
    }
	
	public function sfdcUser(Request $request, Response $response, $args)
	{
		$output = array('error'=>false,'error_message'=>'','payload'=>null,'user'=>$request->getAttribute('user'));
		$sfdc_response = $this->sfdc_client->getUserInfo();
		$output['payload'] = $sfdc_response;
        return $response->withStatus(200)->withJson($output);
    }
	
	public function sfdcObject(Request $request, Response $response, $args)
	{
		$sfdc_response = $this->sfdc_client->describeSObject($request->getAttribute('object'));
		var_dump($sfdc_response);
        return $response->withStatus(200);
	}
	
	public function sfdcObjectFields(Request $request, Response $response, $args)
	{
		$output = array('error'=>false,'error_message'=>'','payload'=>null,'user'=>$request->getAttribute('user'));
		$sfdc_response = $this->sfdc_client->describeSObject($request->getAttribute('object'));
		$sfdc_fields = $this->container->Sforce->getObjectFields($sfdc_response);
		$output['payload'] = $sfdc_fields;
        return $response->withStatus(200)->withJson($output);
	}
	
	private function _sfdcMap($object,$body,$mapping)
	{
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
	
	
	public function sfdcTest(Request $request, Response $response,$args){
			//create the sfdc record object

			$createFields = array (
				'FirstName' => 'test',
				'LastName' => 'test',
				'Phone' => '510-555-5555',
				'Email' => 'test3213@test-domain.com',
				'Company'=> 'test'
			);
			
			$output = array();

			$sObject = new \stdClass();
			$sObject->type = 'Lead';
			$sObject->fields = $createFields;

			$sObject->fields['FirstName'] = 'test3213';
			$sObject->fields['LastName'] = 'test3213';

			$output[] = "Upserting Lead (existing)";
			try{
				$upsertResponse = $this->sfdc_client->upsert("Email", array ($sObject));
				$output[] = $upsertResponse;
			} catch (\Exception $e) {
				
				$output = $this->sfdc_client->getLastRequest();
				//$output = $e->faultstring;
				
			}


			return $response->withStatus(200)->withJson($output);
		
	}
	
	public function sfdcQuery(Request $request, Response $response,$args)
	{
		
		//get body of request and request params
		$query = $request->getQueryParams();

		if(!isset($query['q'])){
			$search_query = "SELECT Id,Email,FirstName,LastName FROM Lead LIMIT 1";
		}else{
			$search_query = $query['q'];;
		}

		$query_results = $this->_sfdcQuery($search_query);
		$output = array('error'=>$query_results['error'],'error_message'=>$query_results['error_message'],'payload'=>$query_results['payload'],'user'=>$request->getAttribute('user')); 
		
		return $response->withStatus(200)->withJson($output);

	}

	
	private function _sfdcAddToCampaign($object,$object_id,$campaign_id,$status)
	{
		
		//define the membership object
		$record = new stdClass();
		
		//check the record for Contact or Lead Id
		if($object=='contact'){
			$search_field = ucfirst($object)."Id";
			$record -> ContactId = $object_id;
		}else{
			$search_field = ucfirst($object)."Id";
			$record-> LeadId = $object_id;	
		}
		
		//check if the campaign membership exists
		$search_query = "SELECT Id FROM CampaignMember WHERE ".$search_field."='".$object_id."' AND CampaignId='".$campaign_id."'";
		//run the search
		$search_result_cm = $this->_sfdcQuery($search_query);
		
		return $search_result_cm;
		
	}
	
	private function _sfdcQuery($query)
	{
		
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
		  $results['sfdc_request'] = $this->sfdc_client->getLastRequest();
		  $results['error_message'] =  $e->faultstring;
		}
		
		return $results;
	}
	
	private function _sfdcCreate($object,$body){
		
		//create the sfdc record object
		$record = new \stdClass();
		//get mapping from database for the particular object, key by cep_api_name
		$mapping = Mapping::where('sfdc_object',$object)->get()->keyBy('cep_api_name')->toArray();
		//map fields
		$fields = $this->_sfdcMap($object,$body,$mapping);
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
		//create the sfdc record object
		$record = new \stdClass();
		//get mapping from database for the particular object, key by cep_api_name
		$mapping = Mapping::where('sfdc_object',$object)->get()->keyBy('cep_api_name')->toArray();
		//map fields
		$fields = $this->_sfdcMap($object,$body,$mapping);
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
		//create the sfdc record object
		$record = new \stdClass();
		//get mapping from database for the particular object, key by cep_api_name
		$mapping = Mapping::where('sfdc_object',$object)->get()->keyBy('cep_api_name')->toArray();
		//map fields
		$fields = $this->_sfdcMap($object,$body,$mapping);
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
		//collect mapping
		$mapping = Mapping::where('sfdc_object',$object)->get()->keyBy('cep_api_name')->toArray();
		//prepare default output
		$output = array('error'=>false,'error_message'=>'','payload'=>null,'user'=>$request->getAttribute('user'));
		//attempt an upsert on the selected object
		$map = $this->_sfdcMap($object,$body,$mapping);
		//define output
		$output['payload'] = $map;
		
        return $response->withStatus(200)->withJson($output);
	}
	
	public function sfdcCreate(Request $request, Response $response, $args){
				
		//get body of request and request params
		$body = $request->getParsedBody();
		//prepare default output
		$output = array('error'=>false,'error_message'=>'','payload'=>null,'user'=>$request->getAttribute('user'));
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
		
        return $response->withStatus(200)->withJson($output);
	}
	
	private function _sfdcSearch($q,$field='email'){
		
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
		$output = array('error'=>false,'error_message'=>'','payload'=>null,'user'=>$request->getAttribute('user'));
		//check arriving email in query
		if(isset($query['q'])){
			$output['payload'] = $this->_sfdcSearch($query['q']);
		}else{
			$output['error'] = true;
			$output['error_message'] = "Your request is missing the mandatory parameter 'q' with the search string";
		}
		return $response->withStatus(200)->withJson($output);
	}
	
	
	public function sfdcUpsert(Request $request, Response $response, $args)
	{
		
		//get body of request and request params
		$body = $request->getParsedBody();
		$query = $request->getQueryParams();
		
		//prepare default output
		$output = array('error'=>false,'error_message'=>'','payload'=>null,'user'=>$request->getAttribute('user'));
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
			
			$status = '';
			//if status empty, collect default status from database
			if(empty($status)){
				$status = Setting::where('name','campaign_member_status_default')->first()->value;
			}
			//upsert the campaign by Name
			$campaign = $this->_sfdcCreate('Campaign',$body['campaign']);
			
			//upsert the campaign member
			$campaign_member = $this->_sfdcAddToCampaign($object,$record['id'],$campaign['id'],$status);
			
		}else{
			$campaign = null;
			$campaign_member = null;
		}
		
		$output['payload'] = array('record'=>$record,'campaign'=>$campaign,'campaign_member'=>$campaign_member);

        return $response->withStatus(200)->withJson($output);
	}
	
	////////////////////////////////////////////////
	// MappForce Admin endpoints
	////////////////////////////////////////////////		
	
	public function mappingGetAll(Request $request, Response $response, $args)
	{
		$output = array('error'=>false,'error_message'=>'','payload'=>null,'user'=>$request->getAttribute('user'));
		$output['payload'] = Mapping::all();
		return $response->withJson($output);
	}
	
	public function settingGetAll(Request $request, Response $response, $args)
	{
		$output = array('error'=>false,'error_message'=>'','payload'=>null,'user'=>$request->getAttribute('user'));
		$settings = Setting::all();
		$output['payload'] = $settings;
		return $response->withJson($output);
	}
	
}


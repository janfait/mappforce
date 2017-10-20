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
	
	public function syncLastEdited(Request $request, Response $response, $args){
		
		$output = array('error'=>false,'error_message'=>'','payload'=>null,'user'=>$request->getAttribute('user'));
		$object = $request->getAttribute('object');
		$object = ucfirst(strtolower($object));
	    //collect query parameters
	    $query = $request->getQueryParams();

		//check for the presence of the lookback query
		if (array_key_exists('lookback',$query)){
			//collect the lookback parameter
			$lookback = $query['lookback'];
			//collect the lookback unit
			$lookbackUnits = $query['lookback_units'];
			if(($lookbackMap[$lookbackUnits]*$lookback)>$lookbackMap['limit']){
				$out['error'] = true;
				$out['httpCode'] = 400;
		    	$out['error_message'] = 'You are trying to query too much data';
				return $response->withStatus($out['httpCode'])->withJson($out);	
			}
			//get current time
		  	$currentTime = time();
		  	//lookback by the number of seconds defined by lookback and lookback units
		  	$startTime = $currentTime-($lookbackMap[$lookbackUnits]*$lookback);
		  	//translate to the SOQL query format
			$startTime = date("Y-m-d\TH:i:s", $startTime)."-07:00";
		  	//define query
		  	$out['searchQuery'] = "SELECT ".implode(",",$objectFields)." FROM ".$object." WHERE LastModifiedDate  >= ".$startTime;
		  	//execute query
		  	$searchQueryResult = $connection->query($out['searchQuery']);
			//respond based on the number of records
		  	if(count($searchQueryResult->records)>0 && count($searchQueryResult->records)<$lookbackMap['recordLimit']){
		    	$searchQueryArray = array();
		    	//create the records container
		    	$searchQueryArray['records'] = array();
		    	//loop through all records and collect allowed values
		    	foreach ($searchQueryResult->records as $record) {
		    		$searchQueryRecord = array();
		    		foreach($record as $recordKey => $recordValue){
		    			//place all allowed values to the searchQueryRecord object
						if(in_array($recordKey,$objectFields)){
							$searchQueryRecord[$recordKey]=$recordValue;
						}
						//if the record is present in mapping
						if(!is_null($mapping[$object][$recordKey])){
							//map according to supplied mapping
							$newRecordKey = $mapping[$object][$recordKey];
							//delete those that are mapped
							unset($searchQueryRecord[$recordKey]);
							//replace with new mapping
							$searchQueryRecord[$newRecordKey]=$recordValue;
						}else{
							//delete those that are not mapped
							unset($searchQueryRecord[$recordKey]);
						}
					}
					//sync to dmc if it has email
					if(!empty($searchQueryRecord['user.Email'])){
						//sync data to dmc via DMC API 2.0
						$serializedBody = dmcSerializeBody($searchQueryRecord);
						$createCall = dmcCreateUser($searchQueryRecord['user.Email'],$serializedBody);
					    if(dmcReturnsError($createCall)){
					    	$updateCall = dmcUpdateUser($searchQueryRecord['user.Email'],$serializedBody);
					    	if(dmcReturnsError($updateCall)){
					    		$searchQueryRecord['syncSuccess'] = false;
					    		$searchQueryRecord['syncResponse'] = $updateCall;
					    	}else{
					    		$searchQueryRecord['syncSuccess'] = true;
					    		$searchQueryRecord['syncResponse'] = $updateCall;
					    	}
					    }else{
					    	$searchQueryRecord['syncSuccess'] = true;
					    	$searchQueryRecord['syncResponse'] = $createCall;
					    }
					}else{
						$searchQueryRecord['syncSuccess'] = false;
					}
					//append the record as an array to the existing records and identify it via Id
					$searchQueryArray['records'][count($searchQueryArray['records'])]=$searchQueryRecord;
		    	}
		    	//merge the standard output and the records array
		    	$out = array_merge($out,$searchQueryArray);
				return $response->withStatus($out['httpCode'])->withJson($out);	
	    	}else{
	    		$out['error'] = true;
				$out['httpCode'] = 400;
		    	$out['error_message'] = 'Your search -'.$out['searchQuery'].'- has returned '.count($searchQueryResult->records).' results.';
				return $response->withStatus($out['httpCode'])->withJson($out);		
	    	}

		}else{
			$out['error'] = true;
			$out['httpCode'] = 400;
    		$out['error_message'] = 'To get '.$object.' provide lookback(int) parameter in query string ';		
		}
		
		
		return $response->withStatus(200)->withJson($output);
	}
	
	
	public function _sfdcMap($object,$body,$mapping)
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
	
	public function sfdcQuery(Request $request, Response $response,$args)
	{
		
		//get body of request and request params
		$query = $request->getQueryParams();

		if(!isset($query['q'])){
			$search_query = "SELECT Id,Email,FirstName,LastName FROM Lead WHERE Email='jan.fait@mapp.com' LIMIT 1";
		}else{
			$search_query = $query['q'];;
		}

		$query_results = $this->_sfdcQuery($search_query);
		$output = array('error'=>$query_results['error'],'error_message'=>$query_results['error_message'],'payload'=>$query_results['payload'],'user'=>$request->getAttribute('user')); 
		
		return $response->withStatus(200)->withJson($output);

	}

	
	private function _sfdcAddToCampaign($object_id,$campaign_id,$status)
	{
		

		//define the membership object
		$record = new stdClass();
		
		//check the record for Contact or Lead Id
		if($syncObject=='ContactId'){
			$record -> ContactId = $syncId;
		}else{
			$record-> LeadId = $syncId;	
		}
		
		//

		//check if the campaign membership exists
		$search_query = "SELECT Id FROM CampaignMember WHERE ".$syncObject."='".$syncId."' AND CampaignId='".$campaignId."'";
		
		
		$campaignMemberQueryResult = $connection->query($out['campaignMemberSearchQuery']);
		
		
		if(count($campaignMemberQueryResult->records)>0){
			$campaignMemberId = $campaignMemberQueryResult->records[0]->Id;	
			$recordToUpdate = new stdClass();
			$recordToUpdate -> Id = $campaignMemberId;
			$recordToUpdate -> Status = $parsedBody['campaignMemberStatus'];
			try {
				$updateCampaignMembershipResult = $connection->update(array($recordToUpdate),'CampaignMember');
			}catch (Exception $e){
				$connection->getLastRequest();
				$updateCampaignMembershipResult = false;
				$out['errorMessage'] = $e->faultstring;
			}
		}else{
			$recordToCreate -> CampaignId = $campaignId;
			$recordToCreate -> Status = $parsedBody['campaignMemberStatus'];
			//create the campaign membership
			try {
				$createCampaignMembershipResult = $connection->create(array($recordToCreate),'CampaignMember');
			}catch (Exception $e){
				$connection->getLastRequest();
				$createCampaignMembershipResult = false;
				$out['errorMessage'] = $e->faultstring;
			}

			//collet the campaign member id
			$campaignMemberId = $createCampaignMembershipResult[0]->id;	
		}

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

		} catch (Exception $e) {

		  $results['error'] =  true;
		  $results['sfdc_request'] = $this->sfdc_client->getLastRequest();
		  $results['error_message'] =  $e->faultstring;
		}
		
		return $results;
	}
	
	
	private function _sfdcUpsert($identifier,$object,$body)
	{
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
		$sfdc_response = $this->sfdc_client->upsert($identifier, array ($record));
		//return
		return $sfdc_response;
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
		//collect the identifier
		$identifier = $query['identifier'];
		
		
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
		
		//if object is set to any, we must decide over preference by searching
		if($object=='any'){
			//attempt to search for a contact first
			$search_query = "SELECT Id,Email FROM Contact WHERE ".$identifier."='".$body[$identifier_key]."' LIMIT 1";
			//collect results
			$search_results = $this->_sfdcQuery($search_query);
			//if the result set for contact search has zero length
			if($search_results['query_result_size'] == 0){
				//then we will upsert a lead
				$object = 'lead';
			}else{
				//else a contact
				$object = 'contact';
			}
		}
		//attempt an upsert on the selected object
		$sfdc_response = $this->_sfdcUpsert($identifer,$object,$body);
		//assign to output
		$output['payload'] = $sfdc_response;
		
		//add to campaign if campaign object supplied
		if(isset($body["campaign"])){
			
			//upsert the campaign by Name
			$campaign = $this->_sfdcUpsert('Name','campaign',$body['campaign']);
			//collect the campaign Id
			return $response->withStatus(200)->withJson($campaign);
	
		}

        return $response->withStatus(200)->withJson($output);
	}
	
	////////////////////////////////////////////////
	// Mapp Integrator endpoints
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
		$output['payload'] = Setting::all();
		return $response->withJson($output);
	}
	
}


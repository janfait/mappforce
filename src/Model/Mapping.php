<?php 

namespace MappIntegrator;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Mapping extends Eloquent {

	protected $table = 'mapping';
	protected $fillable = ['active','cep', 'cep_name','cep_api_name','cep_attr_type','cep_object', 'sfdc_function', 'sfdc_object','sfdc_name', 'created_at', 'updated_at'];
	
	private $active;
	private $cep;
	private $cep_name;
	private $cep_api_name;
	private $cep_attr_type;
	private $cep_object;
	private $sfdc_function;
	private $sfdc_object;
	private $sfdc_name;


	public function add($input){
		
		$this->active = $input['active'];
		$this->cep = $input['cep'];
		$this->cep_name = $input['cep_name'];
		$this->cep_api_name = $input['cep_api_name'];
		$this->cep_attr_type = $input['cep_attr_type'];
		$this->cep_object = $input['cep_object'];
		$this->sfdc_object = $input['sfdc_object'];
		$this->sfdc_function = $input['sfdc_function'];
		$this->sfdc_name = $input['sfdc_name'];

	}

} 
   
?>
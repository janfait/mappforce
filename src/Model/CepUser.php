<?php 

namespace MappIntegrator;

use Illuminate\Database\Eloquent\Model as Eloquent;

class CepUser extends Eloquent {

	protected $table = 'cep_users';
	protected $fillable = ['active','cep_id','instance','username','password','cep_role','app_role','p_oauth','p_admin','created_at','updated_at'];
	
	private $active;
	private $cep_id;
	private $instance;
	private $username;
	private $password;
	private $cep_role;
	private $app_role;
	private $p_oauth;
	private $p_admin;

} 
   
?>
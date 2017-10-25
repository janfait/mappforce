<?php 

namespace MappIntegrator;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Setting extends Eloquent {

	protected $table = 'settings';
	protected $fillable = ['realm','category','type','name','required','label','icon','description','value','picklist','created_at', 'updated_at'];
	
	private $realm;
	private $cateogry;
	private $type;
	private $name;
	private $required;
	private $label;
	private $icon;
	private $description;
	private $value;
	private $picklist;

} 

class SettingsDictionary {

	public $dictionary = array(
		'cep_instance'=>array(
			'realm'=>'cep',
			'category'=>'main',
			'name'=>'cep_instance',
			'required'=>false,
			'value'=>'',
			'label'=>'Mapp CEP Instance',
			'icon'=>'domain',
			'description'=>'Instance of your Mapp CEP system (example: mapp_marketing)',
			'type'=>'text',
			'editable'=>false,
			'picklist'=>null,
		),
		'cep_username'=>array(
			'realm'=>'cep',
			'category'=>'main',
			'name'=>'cep_username',
			'required'=>false,
			'value'=>'',
			'label'=>'Mapp CEP Username',
			'icon'=>'verified_user',
			'description'=>'Username for your Mapp CEP system',
			'type'=>'text',
			'editable'=>false,
			'picklist'=>null
		),
		'cep_password'=>array(
			'realm'=>'cep',
			'category'=>'main',
			'name'=>'cep_password',
			'required'=>false,
			'value'=>'',
			'label'=>'Mapp CEP Password',
			'icon'=>'verified_user',
			'description'=>'Password for your Mapp CEP system',
			'type'=>'password',
			'editable'=>false,
			'picklist'=>null
		),
		'cep_group_template_id'=>array(
			'realm'=>'cep',
			'category'=>'main',
			'name'=>'cep_template_group_id',
			'required'=>false,
			'value'=>0,
			'label'=>'Default Group Configuration Templates',
			'icon'=>'verified_user',
			'description'=>'ID of a configuration template which should be applied to newly cloned groups',
			'type'=>'number',
			'editable'=>true,
			'picklist'=>null
		),
		'cep_clone_group_id'=>array(
			'realm'=>'cep',
			'category'=>'main',
			'name'=>'cep_clone_group_id',
			'required'=>false,
			'value'=>0,
			'label'=>'Default Group ID',
			'icon'=>'verified_user',
			'description'=>'ID of the default group which will be using for cloning and synchronization',
			'type'=>'number',
			'editable'=>true,
			'picklist'=>null
		),
		'cep_domain'=>array(
			'realm'=>'cep',
			'category'=>'main',
			'name'=>'cep_domain',
			'required'=>false,
			'value'=>'cook',
			'label'=>'Domain',
			'icon'=>'domain',
			'description'=>'Domain of your Mapp CEP system (example: cook, amundsen, armstrong, ...)',
			'type'=>'select',
			'editable'=>false,
			'picklist'=>'cook,amundsen,aldrin,armstrong'
		),
		'cep_secret'=>array(
			'realm'=>'cep',
			'category'=>'main',
			'name'=>'cep_secret',
			'required'=>false,
			'value'=>'',
			'label'=>'Verification for incoming data',
			'icon'=>'verified_user',
			'description'=>'Secret keyword that has to be transfered in the incoming HTTP request body as an additional validation in the "validation" attribute',
			'type'=>'text',
			'editable'=>true,
			'picklist'=>null
		),
		'sfdc_use_oauth'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_authentication_method',
			'required'=>true,
			'value'=>true,
			'label'=>'Use OAuth authentication flow',
			'icon'=>'security',
			'description'=>'If selected, OAuth flow is used for authentication. Otherwise, user-password flow is used (not recommended) ',
			'type'=>'checkbox',
			'editable'=>true,
			'picklist'=>null
		),
		'sfdc_username'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_username',
			'required'=>false,
			'value'=>'',
			'label'=>'Username',
			'icon'=>'verified_user',
			'description'=>'Your Salesforce username (example: first.name@company.com)',
			'type'=>'email',
			'editable'=>true,
			'picklist'=>null
		),
		'sfdc_password'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_password',
			'required'=>false,
			'value'=>'',
			'label'=>'Password',
			'icon'=>'verified_user',
			'description'=>'Your Salesforce password',
			'type'=>'password',
			'editable'=>true,
			'picklist'=>null
		),
		'sfdc_security_token'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_security_token',
			'required'=>false,
			'value'=>'',
			'label'=>'Security Token',
			'icon'=>'verified_user',
			'description'=>'Security token is obtained in your Salesforce user profile.',
			'type'=>'password',
			'editable'=>true,
			'picklist'=>null
		),
		'sfdc_redirect_uri'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_redirect_uri',
			'required'=>false,
			'value'=>'',
			'label'=>'Callback URL',
			'icon'=>'domain',
			'description'=>'This is the URL where you will be redirect after a successful OAuth flow',
			'type'=>'text',
			'editable'=>true,
			'picklist'=>null
		),
		'sfdc_consumer_secret'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_consumer_secret',
			'required'=>false,
			'value'=>'',
			'label'=>'Consumer Secret',
			'icon'=>'verified_user',
			'description'=>'Consumer Secret for the Connected App in Salesforce.',
			'type'=>'password',
			'editable'=>true,
			'picklist'=>null
		),
		'sfdc_consumer_key'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_consumer_key',
			'required'=>false,
			'value'=>'',
			'label'=>'Consumer Key',
			'icon'=>'verified_user',
			'description'=>'Consumer Key for the Connected App in Salesforce',
			'type'=>'password',
			'editable'=>true,
			'picklist'=>null
		),
		'sfdc_access_token'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_access_token',
			'required'=>false,
			'value'=>'',
			'label'=>'Access Token',
			'icon'=>'verified_user',
			'description'=>'OAuth Access Token',
			'type'=>'password',
			'editable'=>false,
			'picklist'=>null
		),
		'sfdc_access_token_expires_at'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_access_token_expires_at',
			'required'=>false,
			'value'=>'',
			'label'=>'Access Token',
			'icon'=>'verified_user',
			'description'=>'Expiry date of the current access token',
			'type'=>'text',
			'editable'=>false,
			'picklist'=>null
		),
		'sfdc_refresh_token'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_refresh_token',
			'required'=>false,
			'value'=>'',
			'label'=>'Refresh Token',
			'icon'=>'verified_user',
			'description'=>'OAuth Refresh Token',
			'type'=>'password',
			'editable'=>false,
			'picklist'=>null
		),
		'sfdc_server_url'=>array(
			'realm'=>'sfdc',
			'category'=>'authentication',
			'name'=>'sfdc_server_url',
			'required'=>false,
			'value'=>'',
			'label'=>'Server URL',
			'icon'=>'verified_user',
			'description'=>'URL to be passed to SOAP Client for making requests',
			'type'=>'password',
			'editable'=>false,
			'picklist'=>null
		),
		'sandbox_flag'=>array(
			'realm'=>'sfdc',
			'category'=>'main',
			'name'=>'sandbox_flag',
			'required'=>false,
			'value'=>true,
			'label'=>'Use Sandbox',
			'icon'=>'https',
			'description' => 'If TRUE, Mapp Integrator will attempt to connect to your Sandbox instance, depending on user credentials',
			'type'=>'checkbox',
			'editable'=>false,
			'picklist'=>null
		),
		'assignment_rule'=>array(
			'realm'=>'sfdc',
			'category'=>'main',
			'name'=>'assignment_rule',
			'required'=>false,
			'value'=>false,
			'label'=>'Lead Assignment Rule ID',
			'icon'=>'assignment_ind',
			'description'=>'If specified, upon creation of a Lead, this Lead will be assigned according to the assignment rule',
			'type'=>'text',
			'editable'=>true,
			'picklist'=>null
		),
		'assignment_rule_flag'=>array(
			'realm'=>'sfdc',
			'category'=>'main',
			'name'=>'assignment_rule_flag',
			'required'=>false,
			'value'=>true,
			'label'=>'Use Active Assignment Rule',
			'icon'=>'assignment_ind',
			'description' => 'If checked, newly created Leads will be assigned using the Active Assignment Rule. Any specified Assignment rule below will be ignored',
			'type'=>'checkbox',
			'editable'=>true,
			'picklist'=>null
		),
		'contact_first_flag'=>array(
			'realm'=>'sfdc',
			'category'=>'main',
			'name'=>'contact_first_flag',
			'required'=>false,
			'value'=>true,
			'label'=>'Look for Contacts first',
			'icon'=>'assignment_ind',
			'description' => 'If checked, Mapp Integrator will look for existing Contacts first before searching for Leads.',
			'type'=>'checkbox',
			'editable'=>true,
			'picklist'=>null
		),
		'email_flag'=>array(
			'realm'=>'sfdc',
			'category'=>'main',
			'name'=>'email_flag',
			'required'=>false,
			'value'=>true,
			'label'=>'Send Email to Record Owner',
			'icon'=>'email',
			'description' => 'Once a record gets created, you have the option to notify the user with an email sent from Salesforce',
			'type'=>'checkbox',
			'editable'=>true,
			'picklist'=>null
		),
		'campaign_member_status_default'=>array(
			'realm'=>'sfdc',
			'category'=>'main',
			'name'=>'campaign_member_status_default',
			'required'=>false,
			'value'=>'Responded',
			'label'=>'Default Campaign Member Status',
			'icon'=>'group_add',
			'description'=>'If specified, upon creation of a new Campaign Member, this status will be assigned. Please note the status needs to be available in your campaign',
			'type'=>'text',
			'editable'=>true,
			'picklist'=>null
		),
		'lead_source_default'=>array(
			'realm'=>'sfdc',
			'category'=>'main',
			'name'=>'lead_source_default',
			'required'=>false,
			'value'=>'',
			'label'=>'Default Lead Source',
			'icon'=>'group_add',
			'description'=>'Applied at creation of the a new Lead, unless a Lead Source field is specified',
			'type'=>'text',
			'editable'=>true,
			'picklist'=>null
		),
		'campaign_type_default'=>array(
			'realm'=>'sfdc',
			'category'=>'main',
			'name'=>'campaign_type_default',
			'required'=>false,
			'value'=>'Responded',
			'label'=>'Default Campaign Type',
			'icon'=>'merge type',
			'description'=>'If specified, upon creation of a new Campaign, this Type will be assigned.',
			'type'=>'text',
			'editable'=>true,
			'picklist'=>null
		),
		'primary_identifier'=>array(
			'realm'=>'global',
			'category'=>'main',
			'name'=>'primary_identifier',
			'required'=>false,
			'value'=>'Email',
			'label'=>'Primary Identifier',
			'icon'=>'location_searching',
			'description'=>'Which field should be used to match existing Mapp CEP and Salesforce records',
			'type'=>'select',
			'editable'=>true,
			'picklist'=>'Email,Identifier,mobileNumber'
		)
	);

	
	
}

   
   
?>
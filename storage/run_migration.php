<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Model/Mapping.php';
require __DIR__ . '/../src/Model/Setting.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use \MappIntegrator\Setting as Setting;

$settings = require __DIR__ . '/../src/settings.php';

//set location for databases
$db_new = __DIR__ .'/database.sqlite';
$db_old = __DIR__ .'/'.date("Y_m_d").'_'.rand(10000,99999).'_database.sqlite';
$db = new SQLite3($db_new);

// Eloquent Database
$capsule = new Capsule;
$capsule->addConnection([
            'driver'    => 'sqlite',
            'database' => $db_new,
            'charset'   => 'utf8',
            'prefix'    => '',
        ]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

//perform backup
try{
  copy($db_new, $db_old);
}catch(\Exception $e){
  echo "Cannot perform migrations, back-up procedure has failed to copy $db_new...\n";
}
//schema for mapping
Capsule::schema()->dropIfExists('mapping');
Capsule::schema()->create('mapping', function($table) {
	$table->increments('id');
	$table->boolean('active')->default(true);
	$table->string('cep');
	$table->string('cep_name');
	$table->string('cep_api_name');
	$table->string('cep_attr_type');
	$table->string('cep_object');
	$table->string('sfdc_function')->default('insert');
	$table->string('sfdc_object');
	$table->string('sfdc_name');
	$table->timestamps();
});

//schema for settings
Capsule::schema()->dropIfExists('settings');
Capsule::schema()->create('settings', function($table) {
	$table->increments('id');
	$table->string('realm')->default('global');
	$table->string('category')->default('main');
	$table->string('type')->default('text');
	$table->string('name');
	$table->boolean('required')->default(true);
	$table->string('value')->default(null)->nullable();
	$table->string('label');
	$table->string('icon');
	$table->string('description')->nullable();
	$table->boolean('editable');
	$table->string('picklist')->nullable();
	$table->timestamps();
});	

//insert default settings
$settings_dictionary = new \MappIntegrator\SettingsDictionary();
$default_settings = $settings_dictionary->dictionary;

Setting::insert($default_settings);

echo "Migrations successful";



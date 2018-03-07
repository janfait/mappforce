<?php

/** @var \Dotenv\Dotenv $dotenv */


if (file_exists(__DIR__ . '/../.env')) {
  $dotenv = new Dotenv\Dotenv(__DIR__, "/../.env");
  $dotenv->load();
}

return [
    'settings' => [
        //production
        'displayErrorDetails' => true,
		'debug' => true,
        //template location
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],
        //log name and location
        'logger' => [
            'name' => 'mappforce',
            'path' => __DIR__ . '/../storage/logs/app.log',
        ],
		//eloquent configuration
		'db' => [
            'driver'    => 'sqlite',
            'database' => __DIR__ . '/../storage/database.sqlite',
            'charset'   => 'utf8',
            'prefix'    => '',
        ],
		//sfdc settings
		'sfdc' => [
			'wsdl'=>__DIR__ . '/../vendor/developerforce/force.com-toolkit-for-php/soapclient/partner.wsdl.xml',
			'oauth'=>true,
			'query_limit'=>getenv('QUERY_LIMIT')
		],
		'cep' => [
			'instance' => getenv('CEP')
		],
		//encryption key
		'secret'=> getenv('SECRET'),
		//contact address
		'contact' =>getenv('CONTACT')
    ],
];

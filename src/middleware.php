<?php

use \MappIntegrator\Setting as Setting;
use \MappIntegrator\CepUser as CepUser;

// Authenticator using Mapp /systemuser domain to get user data
class MappApiAuthenticator
{	

	public function __construct($container) {
        $this->container = $container;
    }
	
	
	private function store_user($data){
		//update or create the user by instance and username
		$user = CepUser::updateOrCreate([
			'instance'=>$data['instance'],
			'username'=>$data['username']
		],$data);
	}
	
    public function __invoke($request, $response, $next)
    {
		$host = $request->getUri()->getHost();
        $scheme = $request->getUri()->getScheme();
        $server_params = $request->getServerParams();
		$realm = "MappForce";
        $user = false;
        $password = false;
		$output = array("error" => true, "error_message"=>"Authentication failed");
		
		//todo: allow access controls

		//collect instance and username
		if (isset($server_params["PHP_AUTH_USER"])) {
			$user_data = $server_params["PHP_AUTH_USER"];
			$user_data = explode('|', $user_data);
			
			if(count($user_data)<2){
				$output['error_message'] = "Authentication failed: Your username has to be in the following format system_name|username:password, f.e. mysystem|my@email.com:secretpassword";
				$response = $response
					->withStatus(401)
					->withHeader("WWW-Authenticate", sprintf('Basic realm="%s"', $realm))
					->withJson($output);
				return $response;
			}
			$instance = $user_data[0];
			$user = $user_data[1];
		}
		//collect password
		if (isset($server_params["PHP_AUTH_PW"])) {
			$password = $server_params["PHP_AUTH_PW"];
		}
		//challenge
		if(false === $user | false === $password){
			$response = $response
                ->withStatus(401)
                ->withHeader("WWW-Authenticate", sprintf('Basic realm="%s"', $realm))
				->withJson($output);
			return $response;
		}
		//collect user
		if($this->container->has('mappCep')) {
			//initialize a blank Mapp CEP instance
			$mapp_cep = $this->container->mappCep;
			$mapp_cep -> setInstance($instance);
			$mapp_cep -> setAuthentication($user,$password);
			//attempt a call to the /systemuser endpoint
			$mapp_cep_user = $mapp_cep->getSystemUser();
			
			//challenge again if authentication failed
			if(true === $mapp_cep_user['error']){
				$response = $response
					->withStatus(401)
					->withHeader("WWW-Authenticate", sprintf('Basic realm="%s"', $realm))
					->withJson($output);
				return $response;
			}else{
				//update or create user
				if(false){
					$this->store_user(
						array(
							'instance'=>$instance,
							'username'=>$user,
							'password'=>$password,
							'cep_role'=>'API'
						)
					);
				}
				//pass on the request
				$request = $request->withAttribute('user', $mapp_cep_user['data'])->withAttribute('time',microtime());
			}
			
        }else{
			$output = array("error" => true, "error_message"=>"Interal Server Error");
			return $response->withStatus(500)->withJson($output);
		}

        $response =  $next($request, $response);
		return $response;
    }
	
}

class SessionAuthenticator {

	public function __construct($container) {
		$this->container = $container;
	}
	
	public function __invoke($request, $response, $next)
    {
		//check the session for the LOGGEDIN field
		if (isset($_SESSION['LOGGEDIN']) && $_SESSION['LOGGEDIN'] == true) {
			
			//validate if last activity is within maximum allowed idleness period
			if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > getenv('IDLE_TIMEOUT'))) {
				session_unset();
				session_destroy();
				return $response->withStatus(401)->withRedirect($this->container->router->pathFor('showLogin',['error'=>true,'error_message'=>'Your session has expired.']));
			}
			//register last activity
			$_SESSION['LAST_ACTIVITY'] = time();
			
			//pass session user to the request
			$request = $request->withAttribute('user',$_SESSION['USER']);
			
			return $next($request, $response);
			
		} else {
			return $response->withStatus(401)->withRedirect($this->container->router->pathFor('showLogin'));
		}
    }

}






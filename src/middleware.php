<?php

use \MappIntegrator\Setting as Setting;
use \MappIntegrator\CepUser as CepUser;
use Slim\Http\Request;
use Slim\Http\Response;

// Authenticator using Mapp /systemuser domain to get user data
class MappApiAuthenticator
{	

	public function __construct($container) {
        $this->container = $container;
    }
	
	private function storeUser($data){
		//update or create the user by instance and username
		$user = CepUser::updateOrCreate([
			'instance'=>$data['instance'],
			'username'=>$data['username']
		],$data);
	}
	
	private function validateJson($body){
		 json_decode($body);
	     return (json_last_error() == JSON_ERROR_NONE);
	}

	private function renderError(Request $request, Response $response, $message = "Authentication failed", $status = 401){
		$output = array("error" => true);
		$output['error_message'] = $message;
		$response = $response->withStatus($status)->withHeader("WWW-Authenticate", sprintf('Basic realm="%s"',"MappForce"))->withJson($output);
		return $response;
	}
	
    public function __invoke($request, $response, $next)
    {
		$host = $request -> getUri()->getHost();
        $scheme = $request -> getUri()->getScheme();
        $server_params = $request -> getServerParams();
		$body = $request ->getBody();
		$post = $request -> isPost();
        $user = false;
        $password = false;

		//collect instance and username
		if (isset($server_params["PHP_AUTH_USER"])) {
			$user_data = $server_params["PHP_AUTH_USER"];
			$user_data = explode('|', $user_data);
			if(count($user_data)<2){
				return $this->renderError($request,$response,"Authentication failed: Your username has to be in the following format system_name|username:password",401);
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
			return $this->renderError($request,$response);
		}
		//validate json body
		if($post & !$this->validateJson($body)){
			return $this->renderError($request,$response,"Invalid JSON body",400);
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
				return $this->renderError($request,$response);
			}else{
				//update or create user
				if(false){
					$this->storeUser(
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
			return $this->renderError($request,$response,"Internal Server Error",500);
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
				return $response->withStatus(401)->withRedirect($this->container->router->pathFor('showLogin',[],['error'=>true,'error_message'=>'Your session has expired.','from_page'=>$request->getAttribute('route')]));
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






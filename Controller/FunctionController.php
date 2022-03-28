<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class FunctionController extends BaseController
{
	/**
	 * Expected API endpoint:
	 * https://nextcloud-dev.nist.gov/api/genapi.php/{resource}/{additional options}
	 * uri positions                0   1          2          3                   4+
	 *
	 * {resource} can be one of the following
	 * - Files (UNAVAILABLE)
	 * - Users (UNAVAILABLE)
	 * - Groups
	 * - ExtStorage (UNAVAILABLE) External Storage
	 * - Test (Returns Method and Query uri)
	 * - Auth
	 */

	/**
	 * Path to occ command
	 */
	private static $occ = 'php /var/www/nextcloud/occ';

	// JWT key
	private static $key = 'privatekey';

	/**
	 * All resource endpoints
	 */
	public function controller()
	{
		$arrQueryUri = $this->getUriSegments();

		if (count($arrQueryUri) < 4) // "Invalid endpoint"
		{
			$strErrorDesc = $this->getUri() . ' is not a valid endpoint';

			$this->sendError404Output($strErrorDesc);
		}
		else
		{
			$resource = strtoupper($arrQueryUri[3]);
			
			if ($resource == 'AUTH')
			{
				$this->auth(); // "/genapi.php/auth/" Endpoint
			}
			elseif ($resource == 'GROUPS') // "/genapi.php/groups/" group of endpoints
			{
				$this->groups();
			}
			elseif ($resource == 'EXTSTORAGES') // "/genapi.php/extstorage/" group of endpoints
			{
				$this->extStorages();
			}
			elseif ($resource == 'HEADERS') // "/genapi.php/headers/" Endpoint - prints headers from API call
			{
				$this->headers();
			}
			elseif ($resource == 'TEST') // "/genapi.php/test/" Endpoint - prints Method and URI
			{
				$this->test();
			}
			elseif ($resource == 'TESTAUTH') // "/genapi.php/testauth/" Endpoint - prints Method and URI if authenticated
			{
				$this->testAuth();
			}
			else //Unavailable/unsupported resource
			{
				$strErrorDesc = $resource . ' is not an available resource';
				
				$this->SendError404Output($strErrorDesc);
			}
		}
	}


	/**
	 * Auth resource endpoints
	 */
	private function auth()
	{
		$strErrorDesc = '';
		
		$requestMethod = $this->getRequestMethod();
		$arrQueryUri = $this->getUriSegments();

		if ($requestMethod == 'GET') // Get method
		{
			if (count($arrQueryUri) == 4) // "/genapi.php/auth" Endpoing - returns authentication token
			{
				$this->getAuth();
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';
				
				$this->sendError404Output($strErrorDesc);
			}
		}
		else // unsupported method
		{
			$strErrorDesc = $requestMethod . ' is not an available request Method';
			
			$this->sendError405Output($strErrorDesc);
		}
	}

	/**
	 * "-X GET /auth" Endpoint - get authentication token
	 */
	private function getAuth()
	{
		$iat = time();
		$exp = $iat + 60 * 60;
		$payload = array(
			'iss' => 'https://nextcloud-dev.nist.gov/api/',
			'aud' => 'https://nextcloud-dev.nist.gov/api/',
			'iat' => $iat,
			'exp' => $exp,
		);
		
		$this->sendOkayOutput(self::$key);

		$jwt = JWT::encode($payload, $this->key, 'HS256');
		$responseData = json_encode(array(
			'token' => $jwt,
			'expires' => $exp
		));
		$this->sendOkayOutput($responseData);
	}

	/**
	 * Group resource endpoints
	 */
	private function groups()
	{
		$strErrorDesc = '';
		
		$requestMethod = $this->getRequestMethod();
		$arrQueryUri = $this->getUriSegments();

		if ($requestMethod == 'GET') // GET method
		{
			if (count($arrQueryUri) == 4) // "/genapi.php/groups" Endpoing - returns list of all groups
			{
				$this->getGroups();
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';
				
				$this->sendError404Output($strErrorDesc);
			}
		}
		else // unsupported method
		{
			$strErrorDesc = $requestMethod . ' is not an available request Method';
			
			$this->sendError405Output($strErrorDesc);
		}
	}
	
	/**
	 * "-X GET /groups" Endpoint - Get list of all groups
	 */
	private function getGroups()
	{
		$command = self::$occ . ' group:list';
		if (exec($command, $arrGroup))
		{
			$responseData = json_encode($arrGroup);

			$this->sendOkayOutput($responseData);
		}
	}
	
	private function extStorages()
	{
		$strErrorDesc = '';

		$requestMethod = $this->getRequestMethod();
		$arrQueryUri = $this->getUriSegments();

		if ($requestMethod == 'GET')
		{
			if (count($arrQueryUri) == 4)
			{
				$this->getExtStorages();
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';

				$this->sendError404Output($strErrorDesc);
			}
		}
		else
		{
			$strErrorDesc = $requestMethod . ' is not an available request Method';

			$this->sendError405Output($strErrorDesc);
		}
	}


	private function getExtStorages()
	{
		$command = self::$occ . ' files_external:list';
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "/headers/" Endpoint - prints headers from API call
	 */
	private function headers()
	{
		$strErrorDesc = '';
		$strErrorHeader = '';
		$requestMethod = $this->getRequestMethod();
		$arrQueryStringParams = $this->getQueryStringParams();
		$arrQueryUri = $this->getUriSegments();

		$headers = apache_request_headers();

		$this->sendOkayOutput(json_encode($headers));
	}

	/**
	 *"/test/" Endpoint - prints method with query uri
	 */
	private function test()
	{
		$strErrorDesc = '';
		$strErrorHeader = '';
		$requestMethod = $this->getRequestMethod();
		$arrQueryStringParams = $this->getQueryStringParams();
		$arrQueryUri = $this->getUriSegments();
		
		array_unshift($arrQueryUri, $requestMethod);
		$responseData = json_encode($arrQueryUri);
		
		// send output
		if (!$strErrorDesc)
		{
			$this->sendOkayOutput($responseData);
		}
		else
		{
			$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
		}
	}

	/**
	 *"/testauth/" Endpoint - prints method with query uri if authenticated
	 */
	private function testAuth()
	{
		$strErrorDesc = '';
		$strErrorHeader = '';
		$requestMethod = $this->getRequestMethod();
		$arrQueryStringParams = $this->getQueryStringParams();
		$arrQueryUri = $this->getUriSegments();

		$headers = apache_request_headers();
		$token = str_replace('Bearer ', '', $headers['Authorization']);

		$this->sendOkayOutput(json_encode(self::$key));

		try 
		{
			$payload = JWT::decode($token, new Key($this->key, 'HS256'));

			$this->sendOkayOutput(json_encode($payload));

			array_unshift($arrQueryUri, $requestMethod);
			$responseData = json_encode($arrQueryUri);
			
			// send output
			if (!$strErrorDesc)
			{
				$this->sendOkayOutput($responseData);
			}
			else
			{
				$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
			}
		}
		catch (\Exception $e)
		{
			$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' requires authorization: ' . $e->getMessage();

			$this->sendError401Output($strErrorDesc);
		}
	}
}
?>

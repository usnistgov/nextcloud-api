<?php

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
	 * - Groups (UNAVAILABLE)
	 * - ExtStorage (UNAVAILABLE) External Storage
	 * - Test (Returns Method and Query uri)
	 */

	/**
	 * All resource endpoints
	 */
	public function controller()
	{
		$arrQueryUri = $this->getUriSegments();
		$resource = strtoupper($arrQueryUri[3]);

		if ($resource == 'GROUPS') // "/genapi.php/groups/" group of endpoints
		{
			$this->group();
		}
		elseif ($resource == 'EXTSTORAGE') // "/genapi.php/extstorage/" group of endpoints
		{
			$this->extStorage();
		}
		elseif ($resource == 'TEST') // "/genapi.php/test/" Endpoint - prints Method and URI
		{
			$this->test();
		}
		else //Unavailable/unsupported resource
		{
			$strErrorDesc = $resource . ' is not an available resource';
			//$strErrorHeader = 'HTTP/1.1 404 Resource not found';

			//$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
			$this->SendError404Output($strErrorDesc);
		}
	}


	/**
	 * Group resource endpoints
	 */
	private function group()
	{
		$strErrorDesc = '';
		//$strErrorHeader = '';

		//$requestMethod = strtoupper($_SERVER["REQUEST_METHOD"]);
		$requestMethod = $this->getRequestMethod();
		$arrQueryUri = $this->getUriSegments();

		if ($requestMethod == 'GET') // GET method
		{
			if (count($arrQueryUri) == 4) // "/genapi.php/groups" Endpoing - returns list of all groups
			{
				$this->listGroup();
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';
				//$strErrorHeader = 'HTTP/1.1 404 Not found';

				//$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
				$this->sendError404Output($strErrorDesc);
			}
		}
		else // unsupported method
		{
			$strErrorDesc = $requestMethod . ' is not an available request Method';
			$strErrorHeader = 'HTTP/1.1 405 Method not allowed';

			$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
		}
	}
	
	/**
	 * "-X GET /groups" Endpoint - Get list of all groups
	 */
	private function listGroup()
	{
		$command = globalVars::$occ . ' group:list';
		if (exec($command, $arrGroup))
		{
			$responseData = json_encode($arrGroup);

			$this->sendOkayOutput($responseData);
		}
	}
	
	private function extStorage()
	{
		$strErrorDesc = '';
		//$strErrorHeader = '';

		$requestMethod = $this->getRequestMethod();
		$arrQueryUri = $this->getUriSegments();

		if ($requestMethod == 'GET')
		{
			if (count($arrQueryUri) == 4)
			{
				$this->listExtStorage();
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';
				//$strErrorHeader = 'HTTP/1.1 404 Not found';

				//$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
				$this->sendError404Output($strErrorDesc);
			}
		}
		else
		{
			$strErrorDesc = $requestMethod . ' is not an available request Method';
			//$strErrorHeader = 'HTTP/1.1 405 Method not allowed';

			//$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
			$this->sendError405Output($strErrorDesc);
		}
	}


	private function listExtStorage()
	{
		$command = globalVars::$occ . ' files_external:list';
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendOkayOutput($responseData);
		}
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
}
?>

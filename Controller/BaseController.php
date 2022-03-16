<?php
class BaseController extends GlobalVars
{
	/**
	 * __call magic method
	 */
	public function __call($name, $arguments)
	{
		$this->sendOutput('', array('HTTP/1.1 404 Not Found'));
	}

	/**
	 * Get URI
	 */
	protected function getUri()
	{
		return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	}

	/**
	 * Get URI elements
	 *
	 * @return array
	 */
	protected function getUriSegments()
	{
		$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$uri = explode('/', $uri);

		return $uri;
	}

	/**
	 * Get querystring params
	 *
	 * @return array
	 */
	protected function getQueryStringParams()
	{
		return parse_str($_SERVER['QUERY_STRING'], $query);
	}

	/**
	 * Get query Method
	 * 
	 * @return $str
	 */
	protected function getRequestMethod()
	{
		return strtoupper($_SERVER["REQUEST_METHOD"]);
	}

	/**
	 * Send API output
	 *
	 * @param mixed  $data
	 * @param string $httpHeader
	 */
	protected function sendOutput($data, $httpHeaders=array())
	{
		header_remove('Set-Cookie');

		if (is_array($httpHeaders) && count($httpHeaders))
		{
			foreach ($httpHeaders as $httpHeader)
			{
				header($httpHeader);
			}
		}

		echo $data;
		exit;
	}

	/**
	 * Send API output okay
	 *
	 * @param string $responseData
	 */
	protected function sendOkayOutput($data)
	{
		$this->sendOutput(
			$data,
			array('Content-Type: application/json', 'HTTP/1.1 200 OK')
		);
	}

	/**
	 * Send API output error
	 *
	 * @param string $strErrorDesc
	 * @param string $strErrorHeader
	 */
	protected function sendErrorOutput($strErrorDesc, $StrErrorHeader)
	{
		$this->sendOutput(
			json_encode(array('error' => $strErrorDesc)),
			array('Content-Type: application/json', $strErrorHeader)
		);
	}

	/**
	 * Send API output 404 error
	 * 
	 * @param string $strErrorDesc
	 */
	protected function sendError404Output($strErrorDesc)
	{
		$strErrorHeader = 'HTTP/1.1 404 Not found';

		$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
	}

	/**
	 * Send API output 405 error
	 * 
	 * @param string $strErrorDesc
	 */
	protected function sendError405Output($strErrorDesc)
	{
		$strErrorHeader = 'HTTP/1.1 405 Method not allowed';

		$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
	}
}
?>

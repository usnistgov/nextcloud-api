<?php

namespace NamespaceBase;


class BaseController
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
        $requestUri = $_SERVER['REQUEST_URI'];
        $path = $this->extractPathFromRequestUri($requestUri);
        return $path;	
	}

	/**
	 * Get URI elements
	 *
	 * @return array
	 */
	protected function getUriSegments()
	{
		$requestUri = $_SERVER['REQUEST_URI'];
        $path = $this->extractPathFromRequestUri($requestUri);
        $segments = explode('/', $path);
        return $segments;
	}

	/**
     * Extract the path from the request URI.
     *
     * @param string $requestUri The request URI.
     * @return string The extracted path.
     */
    protected function extractPathFromRequestUri($requestUri)
    {
        // Check for query string and remove it if present
        $path = explode('?', $requestUri)[0];
        return $path;
    }

	/**
	 * Get querystring params
	 *
	 * @return array
	 */
	protected function getQueryStringParams()
	{
		parse_str($_SERVER['QUERY_STRING'], $query);
		return $query;
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
	protected function sendOutput($data, $httpHeaders = array())
	{
		header_remove('Set-Cookie');

		if (is_array($httpHeaders) && count($httpHeaders)) {
			foreach ($httpHeaders as $httpHeader) {
				header($httpHeader);
			}
		}

		echo $data;

		return $data;

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
	 * Send API output created
	 * 
	 * @param string $responseData
	 */
	protected function sendCreatedOutput($data)
	{
		$this->sendOutput(
			$data,
			array('Content-Type: application/json', 'HTTP/1.1 201 Created')
		);
	}

	/**
	 * Send API output error
	 *
	 * @param string $strErrorDesc
	 * @param string $strErrorHeader
	 */
	protected function sendErrorOutput($strErrorDesc, $strErrorHeader)
	{
		$this->sendOutput(
			json_encode(array('error' => $strErrorDesc)),
			array('Content-Type: application/json', $strErrorHeader)
		);
	}

	/**
	 * Send API output 401 error
	 * 
	 * @param string $strErrorDesc
	 */
	protected function sendError401Output($strErrorDesc)
	{
		$strErrorHeader = 'HTTP/1.1 401 Unauthorized';

		$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
	}

	/**
	 * Send API output 400 error
	 * 
	 * @param string $strErrorDesc
	 */
	protected function sendError400Output($strErrorDesc)
	{
		$strErrorHeader = 'HTTP/1.1 400 Bad Request';

		$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
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

	/**
	 * Send API output 500 error
	 * 
	 * @param string $strErrorDesc
	 */
	protected function sendError500Output($strErrorDesc)
	{
		$strErrorHeader = 'HTTP/1.1 500 Internal Server Error';

		$this->sendErrorOutput($strErrorDesc, $strErrorHeader);
	}

	/**
	 * Check if $haystack starts with $needle
	 * 
	 * @param string $haystack
	 * @param string $needle
	 */
	protected function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return substr($haystack, 0, $length) === $needle;
	}

	/**
	 * Check if $haystack ends with $needle
	 * 
	 * @param string $haystack
	 * @param string $needle
	 */
	protected function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if (!$length) {
			return true;
		}
		return substr($haystack, -$length) === $needle;
	}
}

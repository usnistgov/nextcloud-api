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
	 * - Files {not available}
	 * - Users
	 * - Groups
	 * - ExtStorage
	 * - Test (Returns Method and Query uri)
	 * - Auth
	 */

	/**
	 * Path to occ command
	 */
	private static $occ = 'php /var/www/nextcloud/occ';

	// passthrough credentials
	private static $usr = '';

	// db credentials
	private static $dbhost = '';
	private static $dbuser = '';
	private static $dbpass = '';
	private static $dbname = '';

	// JWT key
	private static $privateKey = <<<EOD
	-----BEGIN RSA PRIVATE KEY-----
	AAABAQC3HonxTxT8Pa817GinG1H9lgi8YqaoHgFg4wo9r5OadCa1L4HB91aQeD80
	0eY3A86vY2GCqeWSw9YCjKInbRj0z5b12eOt6BGtmdxDJ6wuU/OSlSrqPdA5rKPB
	ZcvwrJGh41SUMFBNpKIO9bpqtwjdaBDiui2NjL721LV2szBv0L+w5tolNtvkd0Nz
	yX3woPj4/f92yLf/oIwKDOsHJO35gfDHs57O6oyEtSfl5pevge0nhctURTJqSMPl
	0M1eN2iQPeKK3FCNf+sZ3luessYIP2fq/0UxF71iZqK2OMkd37i7piz6Zh11L8UR
	5MGuB9ntqPCmDA83BGHQhXLBjOYBAAAAgQD6Pp1xyvYr9evVukOT5a1tvadqSgPm
	K64rJFE7HpVX8Plq5MabyZiqgj8jQwOoeVbkca5XRctDhZuUXm7HJC7jwXBx9jKH
	9B/LOmz7HS6LmqvWx0UJDziuB32IviMzww/DbbzsAHKtTEnXk/w+rTWcYy9QaKIS
	OVb810n0xtkDGwAAAIEAwvxv+9sTJHGx7u3yOu3oi3KYrTy+9UCjq5YqP1UtwJKr
	JnlwARm4cD1fbr++slN3ldVBpKjn45nWXYAVqRz+ttDgoEjFW7WiIrBcM6XeRSAt
	z1yC1oDwT2/WyCDIiDhvrbxb++jkxdzrwC3TECNfcqzp6z6VgO044hQOv00WKsEA
	AACAaJiXsByhuVpGSF/Zn4a+Calhi5SjXQu2MmtAxtQRr1PrlxpE1Ghn+3AlAIfd
	J/eJNEZgUID04N/2sgBtliMMzI93osmPv7qzkk+0m21Po6LpWqeH+jM1+UmHlj2b
	NHiXH8uQtPbfkNh4FA1lT26l1bgveW+uSqWrHgBQExbaLUE=
	-----END RSA PRIVATE KEY-----
	EOD;

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
			elseif ($resource == 'FILES') // "/genapi.php/files/" group of endpoints
			{
				$this->files();
			}
			elseif ($resource == 'USERS') // "/genapi.php/users/" group of endpoints
			{
				$this->users();
			}
			elseif ($resource == 'GROUPS') // "/genapi.php/groups/" group of endpoints
			{
				$this->groups();
			}
			elseif ($resource == 'EXTSTORAGES') // "/genapi.php/extstorages/" group of endpoints
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

		$jwt = JWT::encode($payload, self::$privateKey, 'HS256');
		$responseData = json_encode(array(
			'token' => $jwt,
			'expires' => $exp
		));
		
		$this->sendOkayOutput($responseData);
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

		try 
		{
			//$payload = JWT::decode($token, new Key(self::$key, 'HS256'));
			$payload = JWT::decode($token, new Key(self::$privateKey, 'HS256'));
			$decoded_array = (array)$payload;
			echo "Decode:\n" . print_r($decoded_array, true) . "\n";
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

	/**
	 * Files resource endpoints
	 * PUT
	 * - files/scan
	 * - files/scan/{user}
	 */
	private function files()
	{
		$strErrorDesc = '';
		
		$requestMethod = $this->getRequestMethod();
		$arrQueryUri = $this->getUriSegments();

		if ($requestMethod == 'POST') // POST method
		{
			if ($arrQueryUri[4] == 'createdir') // "/genapi.php/files/createdir/{directory name}" Endpoint - creates directory
			{
				$dir = $arrQueryUri[5];
				for ($i = 6; $i < count($arrQueryUri); $i++)
				{
					$dir .= "/" . $arrQueryUri[$i];
				}
				$this->createDir($dir);
			}
			elseif ($arrQueryUri[4] == 'sharediruser') // "/genapi.php/files/sharediruser/{user}/{permissions}/{directory}" Endpoint - share directory with user with permissions
			{
				$user = $arrQueryUri[5];
				$perm = $arrQueryUri[6];
				$dir = $arrQueryUri[7];
				for ($i = 8; $i < count($arrQueryUri); $i++)
				{
					$dir .= "/" . $arrQueryUri[$i];
				}
				$this->shareDirUser($user, $perm, $dir);
			}
			elseif ($arrQueryUri[4] == 'sharedirgroup') // "/genapi.php/files/sharedirgroup/{group}/{permissions}/{directory}" Endpoint - share directory with group with permissions
			{
				$group = $arrQueryUri[5];
				$perm = $arrQueryUri[6];
				$dir = $arrQueryUri[7];
				for ($i = 8; $i < count($arrQueryUri); $i++)
				{
					$dir .= "/" . $arrQueryUri[$i];
				}
				$this->shareDirGroup($group, $perm, $dir);
			}
		}
		elseif ($requestMethod == 'PUT') // PUT method
		{
			if (count($arrQueryUri) == 5) // "/genapi.php/files/scan" Endpoint - scans all file systems
			{
				$this->scanAllFiles();
			}
			elseif (count($arrQueryUri) == 6) // "/genapi.php/files/scan/{user}" Endpoint - scan user's file system
			{
				$this->scanUserFiles($arrQueryUri[4]);
			}
		}
		else // unsupported method
		{
			$strErrorDesc = $requestMethod . ' is not an available request Method';
			
			$this->sendError405Output($strErrorDesc);
		}
	}

	/**
	 * "-X POST /files/createdir/{directory name}" Endpoint - creates directory
	 */
	private function createDir($dir)
	{
		$command = "curl -X MKCOL -k -u " . self::$usr . " https://localhost/remote.php/dav/files/oar_api/" . $dir;
		if (exec($command, $arrUser))
		{
			$responseData = json_encode($arrUser);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X PUT /files/scan" Endpoint - scans all users file systems
	 */
	private function scanAllFiles()
	{
		$command = self::$occ . ' files:scan --all';
		if (exec($command, $arrUser))
		{
			$responseData = json_encode($arrUser);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X PUT /files/scan/{user}" Endpoint - scan user file system
	 */
	private function scanUserFiles($user)
	{
		$command = self::$occ . ' files:scan ' . $user;
		if (exec($command, $arrUser))
		{
			$responseData = json_encode($arrUser);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X POST /files/sharediruser/{user}/{permissions}/{directory}" Endpoint - share directory with user with permissions
	 */
	private function shareDirUser($user, $perm, $dir)
	{
		$command = "curl -X POST -H \"ocs-apirequest:true\" -k -u " . self::$usr . " \"https://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=0" . "&path=" . $dir . "&shareWith=" . $user . "&permissions=" . $perm . "\"";
		if (exec($command, $arrUser))
		{
			$responseData = json_encode($arrUser);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X POST /files/sharedirgroup/{group}/{permissions}/{directory}" Endpoing - share directory with group with permissions
	 */
	private function shareDirGroup($group, $perm, $dir)
	{
		$command = "curl -X POST -H \"ocs-apirequest:true\" -k -u " . self::$usr . " \"https://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=1" . "&path=" . $dir . "&shareWith=" . $group . "&permissions=" . $perm . "\"";
		if (exec($command, $arrUser))
		{
			$responseData = json_encode($arrUser);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * Users resource endpoints
	 * GET
	 * - users
	 * - users/{user}
	 * PUT
	 * - users/{user}/enable
	 * - users/{user}/disable
	 */
	private function users()
	{
		$strErrorDesc = '';
		
		$requestMethod = $this->getRequestMethod();
		$arrQueryUri = $this->getUriSegments();

		if ($requestMethod == 'GET') // GET method
		{
			if (count($arrQueryUri) == 4) // "/genapi.php/users" Endpoint - Gets info on all users
			{
				$this->getUsers();
			}
			elseif (count($arrQueryUri) == 5) // "/genapi.php/users/{user}" Endpoint - Gets info on one user
			{
				$this->getUser($arrQueryUri[4]);
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';
				
				$this->sendError404Output($strErrorDesc);
			}
		}
		elseif ($requestMethod == 'PUT') // PUT method
		{
			if (count($arrQueryUri) == 6)
			{
				if ($arrQueryUri[5] == 'enable') // "/genapi.php/users/{user}/enable" Endpoint - enables user
				{
					$this->enableUser($arrQueryUri[4]);
				}
				elseif ($arrQueryUri[5] == 'disable') // "/genapi.php/users/{user}/diable" Endpoint - disables user
				{
					$this->disableUser($arrQueryUri[4]);
				}
			}
		}
		elseif ($requestMethod == 'POST') // POST method
		{
			if (count($arrQueryUri) == 5)
			{
				$this->createUser($arrQueryUri[4]); // "/genapi.php/users/{user}" Endpoint - creates user
			}
		}
		else // unsupported method
		{
			$strErrorDesc = $requestMethod . ' is not an available request Method';
			
			$this->sendError405Output($strErrorDesc);
		}
	}

	/**
	 * "-X GET /users" Endpoint - Gets list of all users
	 */
	private function getUsers()
	{
		$command = self::$occ . ' user:list -i --output json';
		if (exec($command, $arrUser))
		{
			$this->sendOkayOutput($arrUser[0]);
		}
	}

	/**
	 * "-X GET /users/{user}" Endpoint - Gets single user info
	 */
	private function getUser($user)
	{
		$command = self::$occ . ' user:info ' . $user . ' --output json';
		if (exec($command, $arrUser))
		{
			$this->sendOkayOutput($arrUser[0]);
		}
	}

	/**
	 * "-X PUT /users/{user}/enable" Endpoint - Enables user
	 */
	private function enableUser($user)
	{
		$command = self::$occ . ' user:enable ' . $user;
		if (exec($command, $arrUser))
		{
			$responseData = json_encode($arrUser);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X PUT /users/{user}/disable" Endpoint - Disables user
	 */
	private function disableUser($user)
	{
		$command = self::$occ . ' user:disable ' . $user;
		if (exec($command, $arrUser))
		{
			$responseData = json_encode($arrUser);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X POST /users/{user}" Endpoint - creates user
	 */
	private function createUser($user)
	{
		echo 'trying to connect';
		// create connection
		$db = new mysqli(self::$dbhost, self::$dbuser, self::$dbpass, self::$dbname);
		// check connection
		if ($db->connect_error)
		{
			die ("connection failed: " . $db->connect_error);
		}
		else
		{
			echo 'connected';
		}
	}

	/**
	 * Group resource endpoints
	 * GET
	 * - groups
	 * - groups/{group name}
	 * POST
	 * - groups/{group name}
	 * - groups/{group name}/{member}
	 * DELETE
	 * - groups/{group name}
	 * - groups/{group name}/{member}
	 */
	private function groups()
	{
		$strErrorDesc = '';
		
		$requestMethod = $this->getRequestMethod();
		$arrQueryUri = $this->getUriSegments();

		if ($requestMethod == 'GET') // GET method
		{
			if (count($arrQueryUri) == 4) // "/genapi.php/groups" Endpoint - returns list of all groups
			{
				$this->getGroups();
			}
			elseif (count($arrQueryUri) == 5) // "/genapi.php/groups/{group name}" Endpoint - returns list of members of specific group
			{
				$this->getGroupMembers($arrQueryUri[4]);
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';
				
				$this->sendError404Output($strErrorDesc);
			}
		}
		elseif ($requestMethod == 'POST')
		{
			if (cound($arrQueryUri) == 5) // "/genapi.php/groups/{group name}" Endpoing - creates group
			{
				$this->addGroup($arrQueryUri[4]);
			}
			elseif (count($arrQueryUri) == 6) // "/genapi.php/groups/{group name}/{member}" Endpoint - adds member to group
			{
				$this->addGroupMember($arrQueryUri[4], $arrQueryUri[5]);
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';
				
				$this->sendError404Output($strErrorDesc);
			}
		}
		elseif ($requestMethod == 'DELETE')
		{
			if (count($arrQueryUri) == 5) // "/genapi.php/groups/{group name}" Endpoint - deletes group
			{
				$this->deleteGroup($arrQueryUri[4]);
			}
			elseif (count($arrQueryUri) == 6) // "/genapi.php/groups/{group name}/{member}" Endpoint - removes member from group
			{
				$this->removeGroupMember($arrQueryUri[4], $arrQueryUri[5]);
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
	 * Returns array of array of occ group:list output
	 */
	private function parseGroups($groups)
	{
		// Building json file from occ output
		$jsonArr = [];
		$group = "";

		foreach ($groups as $var)
		{
			// Group name found
			if ($this->endsWith($var, ':'))
			{
				$group = rtrim(substr($var, 4), ":"); // parse out group
				$jsonArr[$group] = [];
			}
			else // member found
			{
				$member = substr($var, 6); // parse out member
				array_push($jsonArr[$group], $member);
			}

			unset($var);
		}

		return $jsonArr;
	}
	
	/**
	 * "-X GET /groups" Endpoint - Get list of all groups
	 */
	private function getGroups()
	{
		$command = self::$occ . ' group:list';
		if (exec($command, $arrGroup))
		{
			$responseData = json_encode($this->parseGroups($arrGroup));

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X GET /groups/{group name}" Endpoint - Get list of all members of given group
	 */
	private function getGroupMembers($group)
	{
		$command = self::$occ . ' group:list';
		if (exec($command, $arrGroup))
		{
			$responseData = json_encode(($this->parseGroups($arrGroup))[$group]);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X POST /groups/{group name}" Endpoint - Create group
	 */
	private function addGroup($group)
	{
		$command = self::$occ . ' group:add ' . $group;
		if (exec($command, $arrGroup))
		{
			$responseData = json_encode($arrGroup);

			$this->sendCreatedOutput($responseData);
		}
	}

	/**
	 * "-X POST /groups/{group name}/{member}" Endpoint - Add member to group
	 */
	private function addGroupMember($group, $member)
	{
		$command = self::$occ . ' group:adduser ' . $group . ' ' . $member;
		if (exec($command, $arrGroup))
		{
			$responseData = json_encode($arrGroup);

			$this->sendCreatedOutput($responseData);
		}
	}

	/**
	 * -X DELETE /groups/{group name}" Endpoint - Delete group
	 */
	private function deleteGroup($group)
	{
		$command = self::$occ . ' group:delete ' . $group;
		if (exec($command, $arrGroup))
		{
			$responseData = json_encode($arrGroup);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X DELETE /groups/{group name}/{member}" Endpoint - Remove member from group
	 */
	private function removeGroupMember($group, $member)
	{
		$command = self::$occ . ' group:removeuser ' . $group . ' ' . $member;
		if (exec($command, $arrGroup))
		{
			$responseData = json_encode($arrGroup);

			$this->sendOkayOutput($responseData);
		}
	}
	
	/**
	 * External Storages resource endpoints
	 * GET
	 * - extstorages
	 * POST
	 * - extstorages/local/{name}
	 * - extstorages/s3/{name}
	 * - extstorages/users/{user}
	 * - extstorages/groups/{group}
	 * PUT
	 * - extstorages/{storage id}/config/{key}/{value}
	 * - extstorages/{storage id}/option/{key}/{value}
	 * DELETE
	 * - extstorages/{storage id}
	 * - extstorages/users/{user}
	 * - extstorages/groups/{group}
	 */
	private function extStorages()
	{
		$strErrorDesc = '';

		$requestMethod = $this->getRequestMethod();
		$arrQueryUri = $this->getUriSegments();

		if ($requestMethod == 'GET')
		{
			if (count($arrQueryUri) == 4) // /genapi.php/extstorages endpoint - list all external storages
			{
				$this->getExtStorages();
			}
			elseif (count($arrQueryUri) == 5) // /genapi.php/extstorages/{storage id} endpoint - get specific external storage
			{
				$this->getExtStorage($arrQueryUri[4]);
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';

				$this->sendError404Output($strErrorDesc);
			}
		}
		elseif ($requestMethod == 'POST')
		{
			if (count($arrQueryUri) == 7)
			{
				if ($arrQueryUri[5] == 'users') // /genapi.php/extstorages/{storage id}/users/{user} endpoint - add user to external storage applicable users
				{
					$this->addUserExtStorage($arrQueryUri[4], $arrQueryUri[6]);
				}
				elseif ($arrQueryUri[5] == 'groups') // /genapi.php/extstorages/{storage id}/groups/{group} endpoint - add group to external storage applicable groups
				{
					$this->addGroupExtStorage($arrQueryUri[4], $arrQueryUri[6]);
				}
			}
			elseif (count($arrQueryUri) == 6)
			{
				if ($arrQueryUri[4] == 'local') // /genapi.php/extstorages/local/{name} endpoint - create external storage of type local (not configured)
				{
					$this->createLocalExtStorage($arrQueryUri[5]);
				}
				elseif ($arrQueryUri[4] == 's3') // /genapi.php/extstorages/s3/{name} endpoint - create external storage of type s3 (not configured)
				{
					$this->createS3ExtStorage($arrQueryUri[5]);
				}
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';
				
				$this->sendError404Output($strErrorDesc);
			}
		}
		elseif ($requestMethod == 'PUT')
		{
			if ($arrQueryUri[5] == 'config') // /genapi.php/extstorages/{storage id}/config/{key}/{value} endpoint - sets external storages config key/value
			{
				$value = $arrQueryUri[7];
				for ($i = 8; $i < count($arrQueryUri); $i++)
				{
					$value .= '/' . $arrQueryUri[$i];
				}

				$this->setConfigExtStorage($arrQueryUri[4], $arrQueryUri[6], $value);
			}
			elseif ($arrQueryUri[5] == 'option') // /genapi.php/extstorages/{storage id}/option/{key}/{value} endpoint - sets external storages option key/value
			{
				$value = $arrQueryUri[7];
				for ($i = 8; $i < count($arrQueryUri); $i++)
				{
					$value .= '/' . $arrQueryUri[$i];
				}

				$this->setOptionExtStorage($arrQueryUri[4], $arrQueryUri[6], $value);
			}
			else
			{
				$strErrorDesc = $requestMethod . ' ' . $this->getUri() . ' is not an available Method and Endpoint';
				
				$this->sendError404Output($strErrorDesc);
			}
		}
		elseif ($requestMethod == 'DELETE')
		{
			if (count($arrQueryUri) == 5) // /genapi.php/extstorages/{storage id} endpoint - delete external storage
			{
				$this->deleteExtStorage($arrQueryUri[4]);
			}
			elseif (count($arrQueryUri) == 7)
			{
				if ($arrQueryUri[5] == 'users') // /genapi.php/extstorages/{storage id}/users/{user} endpoint - remove user from external storage applicable users
				{
					$this->removeUserExtStorage($arrQueryUri[4], $arrQueryUri[6]);
				}
				elseif ($arrQueryUri[5] == 'groups') // /genapi.php/extstorages/{storage id}/groups/{group} endpoint - remove group from external storage applicable groups
				{
					$this->removeGroupExtStorage($arrQueryUri[4], $arrQueryUri[6]);
				}
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

	/**
	 * returns array of array of occ files_external:list output
	 * Current fields are:
	 * - Mount ID			- 0
	 * - Mount Point		- 1
	 * - Storage			- 2
	 * - Authentication		- 3
	 * - Configuration		- 4
	 * - Options			- 5
	 * - Applicable Users	- 6
	 * - Applicable Groups	- 7
	 */
	private function parseExtStorages($extStorages)
	{
		$parsedExtStorages = [];

		// remove blank array items
		unset($extStorages[count($extStorages) - 1]);
		unset($extStorages[2]);
		unset($extStorages[0]);

		// get header rows
		$headers = explode("|", $extStorages[1]);
		array_shift($headers);
		array_pop($headers);

		// clean up $headers
		for ($i = 0; $i < count($headers); $i++)
		{
			$headers[$i] = trim($headers[$i]);
		}

		// remove header row
		unset($extStorages[1]);

		foreach ($extStorages as $extStorage)
		{
			// clean up each entry
			$row = explode("|", $extStorage);
			array_shift($row);
			array_pop($row);

			for ($i = 0; $i < count($row); $i++)
			{
				$row[$i] = trim($row[$i]);
			}

			// set storage id as entry key
			$parsedExtStorages[$row[0]] = [];

			for ($i = 0; $i < count($headers); $i++)
			{
				if ($i == 6 || $i == 7) // Either Applicable Users or Applicable Groups, set output as array
				{
					$parsedExtStorages[$row[0]][$headers[$i]] = explode(", ", $row[$i]);
				}
				elseif ($i == 4 || $i == 5) // Configuration and option set as keyed array
				{
					if (strlen($row[$i]) == 0)
					{
						$parsedExtStorages[$row[0]][$headers[$i]] = [];
					}
					else
					{
						$rowArr = explode(", ", $row[$i]);
						foreach ($rowArr as $rowEle)
						{
							$keyValue = explode(": ", $rowEle, 2);
							$parsedExtStorages[$row[0]][$headers[$i]][$keyValue[0]] = $keyValue[1];
						}
					}
				}
				else
				{
					$parsedExtStorages[$row[0]][$headers[$i]] = $row[$i];
				}
			}
		}

		return $parsedExtStorages;
	}

	/**
	 * "-X GET /extstorages/" Endpoint - list all external storages
	 */
	private function getExtStorages()
	{
		$command = self::$occ . ' files_external:list';
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($this->parseExtStorages($arrExtStorage));

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X GET /extstorages/{storage id}" Endpoint - get specified external storage
	 */
	private function getExtStorage($storageId)
	{
		$command = self::$occ . ' files_external:list';
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode(($this->parseExtStorages($arrExtStorage))[$storageId]);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X POST /extstorages/local/{name}" Endpoint - creates external storage of type local (not configured)
	 */
	private function createLocalExtStorage($name)
	{
		$command = self::$occ . ' files_external:create ' . $name . ' local null::null';
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendCreatedOutput($responseData);
		}
	}

	/**
	 * "-X POST /extstorages/s3/{name}" Endpoint - creates external storage of type s3 (not configured)
	 */
	private function createS3ExtStorage($name)
	{
		$command = self::$occ . ' files_external:create ' . $name . ' amazons3 amazons3::accesskey';
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendCreatedOutput($responseData);
		}
	}

	/**
	 * "-X PUT /extstorages/{storage id}/config/{key}/{value}" Endpoint - sets key/value pair in external storage configuration
	 */
	private function setConfigExtStorage($storageId, $key, $value)
	{
		$command = self::$occ . ' files_external:config ' . $storageId . ' ' . $key . ' ' . $value;
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X PUT /extstorages/{storage id}/option/{key}/{value}" Endoint - sets key/value pair in external storage options
	 */
	private function setOptionExtStorage($storageId, $key, $value)
	{
		$command = self::$occ . ' files_external:option ' . $storageId . ' ' . $key . ' ' . $value;
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X DELETE /extstorages/{storage id}" Endpoint - deletes specified external storage
	 */
	private function deleteExtStorage($storageId)
	{
		$command = self::$occ . ' files_external:delete -y ' . $storageId;
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X POST /extstorages/{storage id}/users/{user}" Endpoint - add user to external storage applicable users
	 */
	private function addUserExtStorage($storageId, $user)
	{
		$command = self::$occ . ' files_external:applicable --add-user ' . $user . ' ' . $storageId;
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X DELETE /extstorages/{storage id}/users/{user}" Endpoint - remove user from external storage applicable users
	 */
	private function removeUserExtStorage($storageId, $user)
	{
		$command = self::$occ . ' files_external:applicable --remove-user ' . $user . ' ' . $storageId;
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendOkayOutput($responseData);
		}
	}
	
	/**
	 * "-X POST /extstorages/{storage id}/groups/{group}" Endpoint - add group to external storage applicable groups
	 */
	private function addGroupExtStorage($storageId, $group)
	{
		$command = self::$occ . ' files_external:applicable --add-group ' . $group . ' ' . $storageId;
		if (exec($command, $arrExtStorage))
		{
			$responseData = json_encode($arrExtStorage);

			$this->sendOkayOutput($responseData);
		}
	}

	/**
	 * "-X DELETE /extstorages/{storage id}/groups/{group}" Endpoint - remove group from external storage applicable groups
	 */
	private function removeGroupExtStorage($storageId, $group)
	{
		$command = self::$occ . ' files_external:applicable --remove-group ' . $group . ' ' . $storageId;
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
}
?>

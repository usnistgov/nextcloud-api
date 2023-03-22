<?php

namespace NamespaceFunction;

use PHPUnit\Framework\TestCase;
use ArrayIterator;

require_once('./Controller/FunctionController.php');


/**
 * @backupGlobals enabled
 */
class FunctionControllerTest extends TestCase {
    use \phpmock\phpunit\PHPMock;

    private $api;
    private $returnValue;
    private $arrExtStorage;

    public function setUp(): void {
        $this->api = new FunctionController();
 
    }

    public function tearDown(): void {
        unset($this->api);
    }

    public function expectExecCommand($command, $returnValue) {
        $execMock = $this->getFunctionMock(__NAMESPACE__, "exec");
        $execMock->expects($this->any())
                 ->with($command, $this->anything())
                 ->willReturnCallback([$this, 'execCommandCallback']);
    
        $this->returnValue = $returnValue;
    }
    
    public function execCommandCallback($command, &$output) {
        $output[] = $this->returnValue;
        return true;
    }

    /**
      * @runInSeparateProcess
    */    
    public function testCreateDirSuccess() {
        $testdir = 'dir1/dir2';
        $command = 'curl -X MKCOL -k -u  https://localhost/remote.php/dav/files/oar_api/' . $testdir;
        $expectedResponse = '{"success":true}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('createDir');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($testdir));

        // Test creating a directory successfully
        $this->assertEquals(json_encode([$expectedResponse]), $result);
    }

    /**
      * @runInSeparateProcess
    */    
    public function testCreateDirFailure() {
        $testdir = 'dir1/dir2';
        $command = 'curl -X MKCOL -k -u  https://localhost/remote.php/dav/files/oar_api/' . $testdir;
        $expectedResponse = '{"error":"Failed to create directory"}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('createDir');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($testdir));

        // Test failing to create a directory
        $this->assertEquals(json_encode([$expectedResponse]), $result);
    }

    /**
      * @runInSeparateProcess
    */    
    public function testShareUserSuccess() {
        $user = 'testuser';
        $perm = 'read';
        $dir = 'testdir';
        $command = 'curl -X POST -H "ocs-apirequest:true" -k -u  "https://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=0&path=' . $dir . '&shareWith=' . $user . '&permissions=' . $perm . '"';
        $expectedResponse = '{"success":true}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('shareUser');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($user, $perm, $dir));

        // Test sharing a file/folder with user successfully
        $this->assertEquals(json_encode([$expectedResponse]), $result);
    }

    /**
      * @runInSeparateProcess
    */    
    public function testShareUserFailure() {
        $user = 'testuser';
        $perm = 'read';
        $dir = 'testdir';
        $command = 'curl -X POST -H "ocs-apirequest:true" -k -u  "https://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=0&path=' . $dir . '&shareWith=' . $user . '&permissions=' . $perm . '"';
        $expectedResponse = '{"error":"Failed to share file/folder with user"}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('shareUser');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($user, $perm, $dir));

        // Test failing to share a file/folder with user
        $this->assertEquals(json_encode([$expectedResponse]), $result);
    }

    /**
      * @runInSeparateProcess
    */    
    public function testShareGroupSuccess() {
        $user = 'testuser';
        $group = 'testgroup';
        $perm = 'write';
        $dir = 'testdir';
        $command = 'curl -X POST -H "ocs-apirequest:true" -k -u  "https://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=1&path=' . $dir . '&shareWith='. $group . '&permissions=' . $perm . '"';
        $expectedResponse = '{"success":true}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('shareGroup');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($group, $perm, $dir));

        // Test sharing a file/folder with group successfully
        $this->assertEquals(json_encode([$expectedResponse]), $result);
    }

    /**
      * @runInSeparateProcess
    */
    public function testShareGroupFailure() {
        $user = 'testuser';
        $group = 'testgroup';
        $perm = 'write';
        $dir = 'testdir';  
        $command = 'curl -X POST -H "ocs-apirequest:true" -k -u  "https://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares?shareType=1&path=' . $dir . '&shareWith='. $group . '&permissions=' . $perm . '"';
        
        $expectedResponse = '{"error":"Failed to share file/folder with group"}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('shareGroup');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($group, $perm, $dir));

        // Test failing to share a file/folder with group
        $this->assertEquals(json_encode([$expectedResponse]), $result);
    }

    /**
      * @runInSeparateProcess
    */
    public function testScanAllFilesSuccess() {
        $command = 'php /var/www/nextcloud/occ files:scan --all';
        $expectedResponse = '{"success":true}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('scanAllFiles');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array());

        // Test scanning all files successfully
        $this->assertEquals(json_encode([$expectedResponse]), $result);
    }

    /**
      * @runInSeparateProcess
    */
    public function testScanAllFilesFailure() {
        $command = 'php /var/www/nextcloud/occ files:scan --all';
        $expectedResponse = '{"error":"Failed to scan all files"}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('scanAllFiles');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array());

        // Test failing to scan all files
        $this->assertEquals(json_encode([$expectedResponse]), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testScanUserFilesSuccess() {
        $user = 'testuser';
        $command = 'php /var/www/nextcloud/occ files:scan ' . $user;
        $expectedResponse = '{"success":true}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('scanUserFiles');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($user));

        // Test scanning user files successfully
        $this->assertEquals(json_encode([$expectedResponse]), $result);

    }

    /**
      * @runInSeparateProcess
    */
    public function testScanUserFilesFailure() {
        $user = 'testuser';
        $command = 'php /var/www/nextcloud/occ files:scan ' . $user;

        $expectedResponse = '{"error":"Failed to scan user files"}';

        // Mock the exec() function to avoid side effects and increase code reproducibility
        $this->expectExecCommand($command, $expectedResponse);

        // Use ReflectionClass to access private methods
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('scanUserFiles');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($user));

        // Test failing to scan user files
        $this->assertEquals(json_encode([$expectedResponse]), $result);

    }

    /**
     * @runInSeparateProcess
     */
    public function testGetUsers()
    {
        $command = 'php /var/www/nextcloud/occ user:list -i --output json';
        $expectedResponse = '[{"uid":"user1"}, {"uid":"user2"}]';

        $this->expectExecCommand($command, $expectedResponse);

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('getUsers');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invoke($this->api);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetUser()
    {
        $user = 'testuser';
        $command = 'php /var/www/nextcloud/occ user:info ' . $user . ' --output json';
        $expectedResponse = '{"uid":"testuser", "email":"testuser@example.com"}';

        $this->expectExecCommand($command, $expectedResponse);

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('getUser');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($user));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testEnableUser()
    {
        $user = 'testuser';
        $command = 'php /var/www/nextcloud/occ user:enable ' . $user;
        $expectedResponse = 'User enabled';

        $this->expectExecCommand($command, $expectedResponse);

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('enableUser');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($user));

        $this->assertEquals(json_encode(array($expectedResponse)), $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDisableUser()
    {
        $user = 'testuser';
        $command = 'php /var/www/nextcloud/occ user:disable ' . $user;
        $expectedResponse = 'User disabled';

        $this->expectExecCommand($command, $expectedResponse);

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('disableUser');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($user));

        $this->assertEquals(json_encode(array($expectedResponse)), $result);
    }
    
    public function testParseGroups()
    {
        $groups = [
            "  - group1:",
            "    - user1",
            "    - user2",
            "  - group2:",
            "    - user3",
            "    - user4"
        ];

        $expectedResponse = [
            "group1" => ["user1", "user2"],
            "group2" => ["user3", "user4"]
        ];

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('parseGroups');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($groups));

        $this->assertEquals($expectedResponse, $result);
    }


    /**
     * @runInSeparateProcess
     */
    public function testAddGroup()
    {
        $group = 'testgroup';
        $command = 'php /var/www/nextcloud/occ group:add ' . $group;
        $arrGroup = ['Group created'];

        $expectedResponse = json_encode($arrGroup);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrGroup));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('addGroup');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($group));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddGroupMember()
    {
        $group = 'testgroup';
        $member = 'testuser';
        $command = 'php /var/www/nextcloud/occ group:adduser ' . $group . ' ' . $member;
        $arrGroup = ['User added to group'];

        $expectedResponse = json_encode($arrGroup);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrGroup));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('addGroupMember');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($group, $member));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteGroup()
    {
        $group = 'testgroup';
        $command = 'php /var/www/nextcloud/occ group:delete ' . $group;
        $arrGroup = ['Group deleted'];

        $expectedResponse = json_encode($arrGroup);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrGroup));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('deleteGroup');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($group));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoveGroupMember()
    {
        $group = 'testgroup';
        $member = 'testuser';
        $command = 'php /var/www/nextcloud/occ group:removeuser ' . $group . ' ' . $member;
        $arrGroup = ['User removed from group'];

        $expectedResponse = json_encode($arrGroup);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrGroup));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('removeGroupMember');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($group, $member));

        $this->assertEquals($expectedResponse, $result);
    }

    public function testParseExtStorages()
    {
        $extStorages = [
            "+----------+-------------+-----------+---------------------+-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+----------------+------------------+-------------------+",
            "| Mount ID | Mount Point | Storage   | Authentication Type | Configuration                                                                                                                                                                                             | Options        | Applicable Users | Applicable Groups |",
            "+----------+-------------+-----------+---------------------+-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+----------------+------------------+-------------------+",
            "| 9        | /S3Folder1  | Amazon S3 | Access key          | bucket: \"nist-rso-nextcloud-test\\Folder1\\\", hostname: \"\", port: \"\", region: \"us-east-1\", use_ssl: true, use_path_style: true, legacy_auth: true, key: \"AKIA2MCRCAU46HMS4OWV\", secret: \"r9FSU0...H1wU++\" | readonly: true |                  | empty             |",
            "| 10       | /Local-Test | Local     | None                | datadir: \"\\/var\\/www\\/nextcloud\\/data\\/external\"                                                                                                                                                          |                |                  | empty             |",
            "| 14       | /EFS-Test   | Local     | None                | datadir: \"\\/share\\/Nextcloud-Test\"                                                                                                                                                                        |                |                  | empty             |",
            "| 15       | /S3Root     | Amazon S3 | Access key          | bucket: \"nist-rso-nextcloud-test\\/\", hostname: \"\", port: \"\", region: \"us-east-1\", use_ssl: true, use_path_style: true, legacy_auth: true, key: \"AKIA2MCRCAU46HMS4OWV\", secret: \"r9FSU0...H1wU++\"          |                |                  | empty             |",
            "| 21       | /123        | Local     | None                | datadir: \"\\/share\\/Nextcloud-Test\\/123\"                                                                                                                                                                   |                |                  | empty             |",
            "+----------+-------------+-----------+---------------------+-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+----------------+------------------+-------------------+",
        ];

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('parseExtStorages');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invoke($this->api, $extStorages);
        
        $expected = [
            '9' => [
                'Mount ID' => '9',
                'Mount Point' => '/S3Folder1',
                'Storage' => 'Amazon S3',
                'Authentication Type' => 'Access key',
                'Configuration' => [
                    'bucket' => '"nist-rso-nextcloud-test\Folder1\"',
                    'hostname' => '""',
                    'port' => '""',
                    'region' => '"us-east-1"',
                    'use_ssl' => 'true',
                    'use_path_style' => 'true',
                    'legacy_auth' => 'true',
                    'key' => '"AKIA2MCRCAU46HMS4OWV"',
                    'secret' => '"r9FSU0...H1wU++"',
                ],
                'Options' => ['readonly' => 'true'],
                'Applicable Users' => [''],
                'Applicable Groups' => ['empty'],
            ],
            '10' => [
                'Mount ID' => '10',
                'Mount Point' => '/Local-Test',
                'Storage' => 'Local',
                'Authentication Type' => 'None',
                'Configuration' => [
                    'datadir' => '"\/var\/www\/nextcloud\/data\/external"',
                ],
                'Options' => [],
                'Applicable Users' => [''],
                'Applicable Groups' => ['empty'],
            ],
            '14' => [
                'Mount ID' => '14',
                'Mount Point' => '/EFS-Test',
                'Storage' => 'Local',
                'Authentication Type' => 'None',
                'Configuration' => [
                    'datadir' => '"\/share\/Nextcloud-Test"',
                ],
                'Options' => [],
                'Applicable Users' => [''],
                'Applicable Groups' => ['empty'],
            ],
            '15' => [
                'Mount ID' => '15',
                'Mount Point' => '/S3Root',
                'Storage' => 'Amazon S3',
                'Authentication Type' => 'Access key',
                'Configuration' => [
                    'bucket' => '"nist-rso-nextcloud-test\/"',
                    'hostname' => '""',
                    'port' => '""',
                    'region' => '"us-east-1"',
                    'use_ssl' => 'true',
                    'use_path_style' => 'true',
                    'legacy_auth' => 'true',
                    'key' => '"AKIA2MCRCAU46HMS4OWV"',
                    'secret' => '"r9FSU0...H1wU++"',
                ],
                'Options' => [],
                'Applicable Users' => [''],
                'Applicable Groups' => ['empty'],
            ],
            '21' => [
                'Mount ID' => '21',
                'Mount Point' => '/123',
                'Storage' => 'Local',
                'Authentication Type' => 'None',
                'Configuration' => [
                    'datadir' => '"\/share\/Nextcloud-Test\/123"',
                ],
                'Options' => [],
                'Applicable Users' => [''],
                'Applicable Groups' => ['empty'],
            ],
        ];
        
        $this->assertEquals($expected, $result);
    }


   


    /**
     * @runInSeparateProcess
     */
    public function testCreateLocalExtStorage()
    {
        $name = 'local_storage';
        $command = 'php /var/www/nextcloud/occ files_external:create ' . $name . ' local null::null';
        $arrExtStorage = ['Local external storage created'];

        $expectedResponse = json_encode($arrExtStorage);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrExtStorage));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('createLocalExtStorage');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($name));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateS3ExtStorage()
    {
        $name = 's3_storage';
        $command = 'php /var/www/nextcloud/occ files_external:create ' . $name . ' amazons3 amazons3::accesskey';
        $arrExtStorage = ['S3 external storage created'];

        $expectedResponse = json_encode($arrExtStorage);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrExtStorage));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('createS3ExtStorage');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($name));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetConfigExtStorage()
    {
        $storageId = '1';
        $key = 'config_key';
        $value = 'config_value';
        $command = 'php /var/www/nextcloud/occ files_external:config ' . $storageId . ' ' . $key . ' ' . $value;
        $arrExtStorage = ['Configuration set'];

        $expectedResponse = json_encode($arrExtStorage);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrExtStorage));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('setConfigExtStorage');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($storageId, $key, $value));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetOptionExtStorage()
    {
        $storageId = '1';
        $key = 'option_key';
        $value = 'option_value';
        $command = 'php /var/www/nextcloud/occ files_external:option ' . $storageId . ' ' . $key . ' ' . $value;
        $arrExtStorage = ['Option set'];

        $expectedResponse = json_encode($arrExtStorage);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrExtStorage));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('setOptionExtStorage');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($storageId, $key, $value));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteExtStorage()
    {
        $storageId = '1';
        $command = 'php /var/www/nextcloud/occ files_external:delete -y ' . $storageId;
        $arrExtStorage = ['External storage deleted'];

        $expectedResponse = json_encode($arrExtStorage);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrExtStorage));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('deleteExtStorage');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($storageId));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddUserExtStorage()
    {
        $storageId = '1';
        $user = 'user1';
        $command = 'php /var/www/nextcloud/occ files_external:applicable --add-user ' . $user . ' ' . $storageId;
        $arrExtStorage = ['User added'];

        $expectedResponse = json_encode($arrExtStorage);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrExtStorage));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('addUserExtStorage');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($storageId, $user));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoveUserExtStorage()
    {
        $storageId = '1';
        $user = 'user1';
        $command = 'php /var/www/nextcloud/occ files_external:applicable --remove-user ' . $user . ' ' . $storageId;
        $arrExtStorage = ['User removed'];

        $expectedResponse = json_encode($arrExtStorage);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrExtStorage));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('removeUserExtStorage');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($storageId, $user));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddGroupExtStorage()
    {
        $storageId = '1';
        $group = 'group1';
        $command = 'php /var/www/nextcloud/occ files_external:applicable --add-group ' . $group . ' ' . $storageId;
        $arrExtStorage = ['Group added'];

        $expectedResponse = json_encode($arrExtStorage);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrExtStorage));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('addGroupExtStorage');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($storageId, $group));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoveGroupExtStorage()
    {
        $storageId = '1';
        $group = 'group1';
        $command = 'php /var/www/nextcloud/occ files_external:applicable --remove-group ' . $group . ' ' . $storageId;
        $arrExtStorage = ['Group removed'];

        $expectedResponse = json_encode($arrExtStorage);

        $this->expectExecCommand($command, implode(PHP_EOL, $arrExtStorage));

        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('removeGroupExtStorage');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->api, array($storageId, $group));

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHeaders()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    
        // Mock $_SERVER keys
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'key=value';
        $_SERVER['REQUEST_URI'] = '/test?key=value';

        // Mock apache_request_headers() function
        $this->getFunctionMock(__NAMESPACE__, 'apache_request_headers')
            ->expects($this->once())
            ->willReturn($headers);
    
        $reflectionClass = new \ReflectionClass($this->api);
        $reflectionMethod = $reflectionClass->getMethod('headers');
        $reflectionMethod->setAccessible(true);
    
        $result = $reflectionMethod->invoke($this->api);
    
        $this->assertEquals($headers, $result);
    }

}

<?php
namespace Foomo\SimpleData\MongoDB\Jobs\Test; 

/**
 * job config used to configure the test run
 *
 * @author bostjanm
 */
class DomainConfig extends \Foomo\Config\AbstractConfig {
	const NAME = 'Foomo.SimpleData.MongoDB.Test.config';
	
	public $databases = array(\Foomo\SimpleData\MongoDB\Jobs\BackupJobTest::TEST_DATABASE);
	
	public $host = '127.0.0.1';
	
	public $port = '27017';
	
	public $username = '';
	
	public $password = '';
	
	public $backupFolder = '';
	
	
}



<?php

namespace Foomo\SimpleData\MongoDB;

/**
 * Backup a set of mongo databases defined in a DomainConfig
 *
 * @author bostjanm
 */
class BackupJob extends \Foomo\Jobs\AbstractJob 
{

	protected $executionRule = '0   0       *       *       *';
	
	public static $testRun = false;
	/**
	 * @var DomainConfig
	 */
	protected $config;
	public function getId() 
	{
		// use the config in here too ...
		return sha1(__CLASS__);
	}
	/**
	 * configure
	 * 
	 * @param \Foomo\SimpleData\MongoDB\Jobs\DoimainConfig $config
	 * 
	 * @return \Foomo\SimpleData\MongoDB\Jobs\BackupJob
	 */
	public function withConfig(DoimainConfig $config) 
	{
		$this->config = $config;
		return $this;
	}
	public function getDescription()
	{
		// add some information what you are dumping
		return 'mongo dump ';
	}

	public function run() {
		if (!isset($this->config)) {
			throw new \RuntimeException('mongo backup not configured properly');
		}
		$backupFolder = $this->config->backupFolder;

		if (!file_exists($backupFolder)) {
			if (self:: $testRun === false) {
				trigger_error('Output folder does not exist: ' . $backupFolder . '. defaulting to:  ' . \Foomo\Config::getVarDir($module = \Foomo\SimpleData\MongoDB\Module::NAME), E_USER_WARNING);
			}
			$backupFolder = self::getDefaultOutputFolder();
		}

		if (!is_dir($backupFolder)) {
			throw new \RuntimeException('Output location is not a folder: ' . $backupFolder);
		}
		// please refactor to use the CliCall
		foreach ($this->config->databases as $db) {
			$command = 'mongodump'
					. (!empty($this->config->username) ? ' --username ' . $this->config->username : '')
					. (!empty($this->config->password) ? ' --password ' . $this->config->password : '')
					. (!empty($this->config->host) ? ' --host ' . $this->config->host : '')
					. (!empty($this->config->port) ? ' --port ' . $this->config->port : '')
					. ' -db ' . $db
					. ' -out ' . $backupFolder;
			exec($command);
		}
	}

	public static function getDefaultOutputFolder() 
	{
		return \Foomo\Config::getVarDir($module = \Foomo\SimpleData\MongoDB\Module::NAME);
	}

}


<?php

namespace Foomo\SimpleData\MongoDB\Jobs;

/**
 * Backup a set of mongo databases defined in a DomainConfig
 *
 * @author bostjanm
 */
class BackupJob extends \Foomo\Jobs\AbstractJob {

	protected $executionRule = '0   0       *       *       *';
	public static $testRun = false;

	public function getId() {
		return sha1(__CLASS__);
	}

	public function getDescription() {
		return 'mongo dump';
	}

	public function run() {
		$config = $this->getConfig();
		if (!isset($config)) {
			throw new \RuntimeException('mongo backup not configured properly');
		}
		$backupFolder = $config->backupFolder;

		if (!file_exists($backupFolder)) {
			if (self:: $testRun === false) {
				trigger_error('Output folder does not exist: ' . $backupFolder . '. defaulting to:  ' . \Foomo\Config::getVarDir($module = \Foomo\SimpleData\MongoDB\Module::NAME), E_USER_WARNING);
			}
			$backupFolder = self::getDefaultOutputFolder();
		}

		if (!is_dir($backupFolder)) {
			throw new \RuntimeException('Output location is not a folder: ' . $backupFolder);
		}

		foreach ($config->databases as $db) {
			$command = 'mongodump'
					. (!empty($config->username) ? ' --username ' . $config->username : '')
					. (!empty($config->password) ? ' --password ' . $config->password : '')
					. (!empty($config->host) ? ' --host ' . $config->host : '')
					. (!empty($config->port) ? ' --port ' . $config->port : '')
					. ' -db ' . $db
					. ' -out ' . $backupFolder;
			exec($command);
		}
	}

	private function getConfig() {
		if (self::$testRun === false) {
			$config = \Foomo\Config::getConf(\Foomo\SimpleData\MongoDB\Module::NAME, \Foomo\SimpleData\MongoDB\Jobs\DomainConfig::NAME);
		} else {
			$config = new \Foomo\SimpleData\MongoDB\Jobs\Test\DomainConfig();
		}
		return $config;
	}

	public static function getDefaultOutputFolder() {
		return \Foomo\Config::getVarDir($module = \Foomo\SimpleData\MongoDB\Module::NAME);
	}

}


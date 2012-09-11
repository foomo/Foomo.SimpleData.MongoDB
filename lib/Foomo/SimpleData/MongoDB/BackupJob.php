<?php

/*
 * This file is part of the foomo Opensource Framework.
 * 
 * The foomo Opensource Framework is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License as
 * published  by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * The foomo Opensource Framework is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License along with
 * the foomo Opensource Framework. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Foomo\SimpleData\MongoDB;

/**
 * backup a mongo db 
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author bostjan <bostjan.marusic@bestbytes.de>
 */
class BackupJob extends \Foomo\Jobs\AbstractJob
{

	protected $executionRule = '0   0       *       *       *';
	public static $testRun = false;

	/**
	 * @var DomainConfig
	 */
	protected $config;
	protected $outputFolder;

	/**
	 * get job id
	 * @return string
	 */
	public function getId()
	{
		return sha1(serialize($this->config));
	}

	/**
	 * configure
	 * 
	 * @param \Foomo\SimpleData\MongoDB\Jobs\DomainConfig $config
	 * 
	 * @return \Foomo\SimpleData\MongoDB\BackupJob
	 */
	public function withConfig(DomainConfig $config)
	{
		$this->config = $config;
		return $this;
	}

	/**
	 * set non-default folder
	 * @param string $outputFolder absolute path
	 * @return \Foomo\SimpleData\MongoDB\BackupJob
	 */
	public function withOutputFolder($outputFolder)
	{
		$this->outputFolder = $outputFolder;
		return $this;
	}

	public function run()
	{
		if (!isset($this->config)) {
			throw new \RuntimeException('mongo backup not configured properly');
		}
		$backupFolder = $this->outputFolder;

		if (!isset($backupFolder)) {
			$backupFolder = self::getDefaultOutputFolder();
		}

		if (!file_exists($backupFolder)) {
			throw new \RuntimeException('output folder does not exist: ' . $backupFolder);
		}
		if (!is_dir($backupFolder)) {
			throw new \RuntimeException('output location is not a folder: ' . $backupFolder);
		}

		if (!isset($this->config->mongo)) {
			throw new RuntimeException('the mongo connection data is not available');
		}

		$dbData = parse_url($this->config->mongo);
		
		if (!$dbData) {
			throw new RuntimeException('the mongo connection data is invalid');
		}
		

		$pathArray = explode('/', $dbData['path']);

		$database = $pathArray[1];

		if (!$dbData) {
			throw new RuntimeException('could not parse the mongo dsn');
		}

		$arguments = array();
		if (!empty($dbData['user'])) {
			$arguments[] = '--username';
			$arguments[] = $dbData['user'];
		}

		if (!empty($dbData['pass'])) {
			$arguments[] = '--password';
			$arguments[] = $dbData['pass'];
		}

		if (!empty($dbData['host'])) {
			$arguments[] = '--host';
			$arguments[] = $dbData['host'];
		}


		if (!empty($dbData['port'])) {
			$dbData['host'] = '--port';
			$dbData['host'] = $dbData['port'];
		}

		$arguments[] = '-db';
		$arguments[] = $database;
		$arguments[] = '-out';
		$arguments[] = $backupFolder;

		$cliCall = new \Foomo\CliCall('mongodump', $arguments);
		$cliCall = $cliCall->execute();
		if ($cliCall->exitStatus != 0) {
			\Foomo\Utils::appendToPhpErrorLog($cliCall->report);
			throw new \RuntimeException('execution of mongodump failed with exit code ' . $cliCall->exitStatus);
		}
	}

	/**
	 * default backup storage folder
	 * @return string
	 * @throws \RuntimeException if cant create the test folder
	 */
	public static function getDefaultOutputFolder()
	{
		if (self::$testRun === false) {
			$folder = \Foomo\Config::getVarDir($module = \Foomo\SimpleData\MongoDB\Module::NAME) . DIRECTORY_SEPARATOR . 'mongoDumps';
			$folder = self::validateFolder($folder);
			return $folder;
		} else {
			$testFolder = \Foomo\Config::getVarDir($module = \Foomo\SimpleData\MongoDB\Module::NAME) . DIRECTORY_SEPARATOR . 'testDumps';
			$testFolder = self::validateFolder($testFolder);
			return $testFolder;
		}
	}

	/**
	 * check if folder exists if not create
	 * @param string $folder
	 * @return string folder
	 * @throws \RuntimeException if cant create
	 */
	private static function validateFolder($folder)
	{
		if (!file_exists($folder)) {
			$success = mkdir($folder);
			if (!$success) {
				throw new \RuntimeException('could not create folder ' . $folder);
			}
		}
		return $folder;
	}

}


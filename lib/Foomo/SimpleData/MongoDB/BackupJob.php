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

	const MODE_NORMAL = 'MODE_NORMAL';
	const MODE_DAILY = 'MODE_DAILY';
	const MODE_WEEKLY = 'MODE_WEEKLY';
	const MODE_YEARLY = 'MODE_YEARLY';
	const MODE_MONTHLY = 'MODE_MONTHLY';

	/**
	 * defines the backups keeping strategy
	 * @var string 
	 */
	public $mode = self::MODE_NORMAL;
	protected $executionRule = '0   0       *       *       *';
	public static $testRun = false;

	/**
	 * @var DomainConfig
	 */
	protected $config;

	/**
	 *
	 * @var string 
	 */
	protected $outputFolder;

	

	/**
	 * configure
	 * 
	 * @param \Foomo\SimpleData\MongoDB\DomainConfig $config
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
	public function setOutputFolder($outputFolder)
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

		switch (self::$mode) {
			case self::MODE_DAILY:
				$dayOfWeek = date('D');
				$backupFolder = self::getOutputSubfolder($backupFolder, $dayOfWeek);
				break;
			case self::MODE_WEEKLY:
				$week = self::getWeekNum(time());
				$backupFolder = self::getOutputSubfolder($backupFolder, 'week_' . $week);
				break;
			case self::MODE_MONTHLY:
				$month = date('F');
				$backupFolder = self::getOutputSubfolder($backupFolder, $month);
				break;
			case self::MODE_YEARLY:
				$year = date('Y');
				$backupFolder = self::getOutputSubfolder($backupFolder, $year);
				break;
			default:
				break;
		}

		if (!file_exists($backupFolder)) {
			throw new \RuntimeException('output folder does not exist: ' . $backupFolder);
		}
		if (!is_dir($backupFolder)) {
			throw new \RuntimeException('output location is not a folder: ' . $backupFolder);
		}

		if (!isset($this->config->mongo)) {
			throw new \RuntimeException('the mongo connection data is not available');
		}

		$dbData = parse_url($this->config->mongo);
		if (!$dbData) {
			throw new \RuntimeException('the mongo connection data is invalid');
		}

		$pathArray = explode('/', $dbData['path']);
		$database = $pathArray[1];
		if (!$dbData) {
			throw new \RuntimeException('could not parse the mongo dsn');
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
		$cliCall->execute();
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
	 * a backup job that keeps daily backups for all days of the current week
	 * @param \Foomo\SimpleData\MongoDB\DomainConfig $mongoConfig
	 * @param integer $hour
	 * @param integer $minute
	 * @param string $outputFolder
	 * @return \Foomo\SimpleData\MongoDB\BackupJob
	 * @throws \IllegalArgumentException
	 */
	public static function getDailyBackupJob(DomainConfig $mongoConfig, $hour = 0, $minute = 0, $outputFolder = null)
	{
		self::validateArgument($minute, self::ARG_TYPE_MINUTE);
		self::validateArgument($hour, self::ARG_TYPE_HOUR);

		if (!$outputFolder) {
			$outputFolder = self::getDefaultOutputFolder();
		}
		self::$mode = self::MODE_DAILY;

		$ret = \Foomo\SimpleData\MongoDB\BackupJob::create()
				->withConfig($mongoConfig)
				->setOutputFolder($outputFolder)
				->setDescription("daily mongo dump of the db: " . self::getDbName($mongoConfig))
				->lock()
				->executionRule($minute . ' ' . $hour . ' * * *');

		return $ret;
	}

	/**
	 * a backup job that keeps weekly backups for all weeks of the current month
	 * @param Foomo\SimpleData\MongoDB\DomainConfig $mongoConfig
	 * @param string $dayOfWeek
	 * @param string $hour
	 * @param string $minute
	 * @param string $outputFolder
	 * @return Foomo\SimpleData\MongoDB\BackupJob
	 * @throws \IllegalArgumentException
	 */
	public static function getWeeklyBackupJob(DomainConfig $mongoConfig, $dayOfWeek = 0, $hour = 23, $minute = 59, $outputFolder = null)
	{
		self::validateArgument($dayOfWeek, self::ARG_TYPE_DAY_OF_WEEK);
		self::validateArgument($minute, self::ARG_TYPE_MINUTE);
		self::validateArgument($hour, self::ARG_TYPE_HOUR);

		if (!$outputFolder) {
			$outputFolder = self::getDefaultOutputFolder();
		}
		self::$mode = self::MODE_WEEKLY;

		$ret = \Foomo\SimpleData\MongoDB\BackupJob::create()
				->withConfig($mongoConfig)
				->setOutputFolder($outputFolder)
				->setDescription("weekly mongo dump of the db: " . self::getDbName($mongoConfig))
				->lock()
				->executionRule($minute . ' ' . $hour . ' * * ' . $dayOfWeek);

		return $ret;
	}

	/**
	 * a backup job that keeps monthly backups for all months of the current year
	 * @param \Foomo\SimpleData\MongoDB\DomainConfig $mongoConfig
	 * @param integer $day
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $outputFolder
	 * @return Foomo\SimpleData\MongoDB\BackupJob
	 * @throws \IllegalArgumentException
	 */
	public static function getMonthlyBackupJob(DomainConfig $mongoConfig, $day = 1, $hour = 23, $minute = 59, $outputFolder = null)
	{
		self::validateArgument($day, self::ARG_TYPE_DAY_OF_MONTH);
		self::validateArgument($minute, self::ARG_TYPE_MINUTE);
		self::validateArgument($hour, self::ARG_TYPE_HOUR);

		if (!$outputFolder) {
			$outputFolder = self::getDefaultOutputFolder();
		}
		self::$mode = self::MODE_MONTHLY;

		$ret = \Foomo\SimpleData\MongoDB\BackupJob::create()
				->withConfig($mongoConfig)
				->setOutputFolder($outputFolder)
				->setDescription("monthly mongo dump of the db: " . self::getDbName($mongoConfig))
				->lock()
				->executionRule($minute . ' ' . $hour . ' ' . $day . ' * *');
		return $ret;
	}

	/**
	 * a yearly backup job
	 * @param \Foomo\SimpleData\MongoDB\DomainConfig $mongoConfig
	 * @param string $day
	 * @param string $month
	 * @param string $hour
	 * @param string $minute
	 * @param string $outputFolder
	 * @return Foomo\SimpleData\MongoDB\BackupJob
	 * @throws \IllegalArgumentException
	 */
	public static function getYearlyBackupJob(DomainConfig $mongoConfig, $day = 1, $month = 1, $hour = 23, $minute = 59, $outputFolder = null)
	{

		self::validateArgument($day, self::ARG_TYPE_DAY_OF_MONTH);
		self::validateArgument($month, self::ARG_TYPE_MONTH);
		self::validateArgument($minute, self::ARG_TYPE_MINUTE);
		self::validateArgument($hour, self::ARG_TYPE_HOUR);


		if (!$outputFolder) {
			$outputFolder = self::getDefaultOutputFolder();
		}
		self::$mode = self::MODE_YEARLY;

		$ret = \Foomo\SimpleData\MongoDB\BackupJob::create()
				->withConfig($mongoConfig)
				->setOutputFolder($outputFolder)
				->setDescription("yearly mongo dump of the db: " . self::getDbName($mongoConfig))
				->lock()
				->executionRule($minute . ' ' . $hour . ' ' . $day . ' ' . $month . ' *');
		return $ret;
	}

	/**
	 * create subfolder and return path
	 * @param string $folder
	 * @param string $subfolder
	 * @return string the folder
	 * @throws \RuntimeException
	 */
	public static function getOutputSubfolder($folder, $subfolder)
	{
		$path = $folder . DIRECTORY_SEPARATOR . $subfolder;
		if (file_exists($folder)) {
			return self::validateFolder($path);
		} else {
			throw new \RuntimeException('could not create folder ' . $path);
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

	public static function getWeekNum($timestamp)
	{
		$maxday = date("t", $timestamp);
		$thismonth = getdate($timestamp);
		$timeStamp = mktime(0, 0, 0, $thismonth['mon'], 1, $thismonth['year']); //Create time stamp of the first day from the give date.
		$startday = date('w', $timeStamp); //get first day of the given month
		$day = $thismonth['mday'];
		$weeks = 0;
		$weekNum = 0;

		for ($i = 0; $i < ($maxday + $startday); $i++)
		{
			if (($i % 7) == 0) {
				$weeks++;
			}
			if ($day == ($i - $startday + 1)) {
				$weekNum = $weeks;
			}
		}
		return $weekNum;
	}

	private static function getDbName($config)
	{

		$dbData = parse_url($config->mongo);
		if (!$dbData) {
			throw new \RuntimeException('the mongo connection data is invalid');
		}

		$pathArray = explode('/', $dbData['path']);
		$database = $pathArray[1];
		return $database;
	}

}


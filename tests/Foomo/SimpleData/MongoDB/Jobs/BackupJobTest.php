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

namespace Foomo\SimpleData\MongoDB\Jobs;

/**
 * test of the mongo backup job
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author bostjan <bostjan.marusic@bestbytes.de>
 */
class BackupJobTest extends \PHPUnit_Framework_TestCase
{

	const TEST_DATABASE = 'testMongoDatabase';
	const TEST_COLLECTION = 'testMongoCollection';

	public function testMongoBackup()
	{
		$jobs = Test\JobsList::getJobs();
		\Foomo\Jobs\Runner::runAJob($jobs[0]);
		$folder = $this->getOutputFolder() . DIRECTORY_SEPARATOR . self::TEST_DATABASE;
		$file = $this->getOutputFolder() . DIRECTORY_SEPARATOR . self::TEST_DATABASE . DIRECTORY_SEPARATOR . self::TEST_COLLECTION . '.bson';
		$this->assertTrue(file_exists($folder), 'mogo dump did not dump database');
		$this->assertTrue(file_exists($file), 'mongo collection was not dumped');
	}

	public function setUp()
	{
		parent::setUp();
		\Foomo\SimpleData\MongoDB\BackupJob::$testRun = true;
		// connect
		$m = new \Mongo();
		$database = self::TEST_DATABASE;
		$collection = self::TEST_COLLECTION;
		// select a database
		$db = $m->$database;

		// select a collection (analogous to a relational database's table)
		$coll = $db->$collection;

		// add a record
		$obj = array("title" => "Calvin and Hobbes", "author" => "Bill Watterson");
		$coll->insert($obj);

		// create test config
		\Foomo\Config::restoreConfDefault(\Foomo\SimpleData\MongoDB\Module::NAME, \Foomo\SimpleData\MongoDB\Jobs\Test\DomainConfig::NAME, '');
		
	}

	public function tearDown()
	{
		parent::tearDown();
		$m = new \Mongo();
		$db = $m->TEST_DATABASE;
		$db->drop();
		\Foomo\SimpleData\MongoDB\BackupJob::$testRun = false;
		//remove folder
		$this->rrmdir($this->getOutputFolder());
		\Foomo\Config::removeConf(\Foomo\SimpleData\MongoDB\Module::NAME, \Foomo\SimpleData\MongoDB\Jobs\Test\DomainConfig::NAME, '');
		\Foomo\Config\Utils::removeOldConfigs(\Foomo\SimpleData\MongoDB\Module::NAME, \Foomo\SimpleData\MongoDB\Jobs\Test\DomainConfig::NAME, '');
	}

	private function getOutputFolder()
	{
		return \Foomo\SimpleData\MongoDB\BackupJob::getDefaultOutputFolder();
	}

	private function rrmdir($dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object)
			{
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir")
						$this->rrmdir($dir . "/" . $object); else
						unlink($dir . "/" . $object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

}


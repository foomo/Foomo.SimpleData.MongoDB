<?php

namespace Foomo\SimpleData\MongoDB\Jobs;

/**
 * test of the mongo backup job
 * assumes default mongo setup onb the test machine
 *
 * @author bostjanm
 */
class BackupJobTest extends \PHPUnit_Framework_TestCase {

	const TEST_DATABASE = 'testMongoDatabase';
	const TEST_COLLECTION = 'testMongoCollection';

	public function testMongoBackup() {
		\Foomo\Jobs\Runner::runAJob(BackupJob::create());
		$folder = $this->getOutputFolder() . DIRECTORY_SEPARATOR . self::TEST_DATABASE;
		$file = $this->getOutputFolder() . DIRECTORY_SEPARATOR . self::TEST_DATABASE . DIRECTORY_SEPARATOR . self::TEST_COLLECTION . '.bson';
		
		$this->assertTrue(file_exists($folder), 'mogo dump did not dump database');
		$this->assertTrue(file_exists($file), 'mongo collection was not dumped');
		
	}

	public function setUp() {
		parent::setUp();
		BackupJob::$testRun = true;
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
	}

	public function tearDown() {
		parent::tearDown();
		$m = new \Mongo();
		$db = $m->TEST_DATABASE;
		$db->drop();
		BackupJob::$testRun = false;
		//remove folder
		$this->rrmdir($this->getOutputFolder());
	}

	private function getOutputFolder() {
		return BackupJob::getDefaultOutputFolder();
	}

	private function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
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


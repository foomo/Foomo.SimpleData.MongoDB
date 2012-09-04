<?php

namespace Foomo\SimpleData\MongoDB\Jobs;

/**
 * job list for this module
 *
 * @author bostjanm
 */
class JobList implements \Foomo\Jobs\JobListInterface {
	public static function getJobs() {
		return array(
			\Foomo\SimpleData\MongoDB\Jobs\BackupJob::create()
		);
	}

}

<?php
namespace Foomo\SimpleData\MongoDB\Jobs;

/**
 * Configuration of the mongo backup job
 *
 * @author bostjanm
 */
class DomainConfig extends \Foomo\Config\AbstractConfig{
	const NAME = 'Foomo.SimpleData.MongoDB.config';
	
	public $databases = array();
	
	public $host = '127.0.0.1';
	
	public $port = '27017';
	
	public $username = '';
	
	public $password = '';
	
	public $backupFolder = '';
	
	public $executionRule = '0   0       *       *       *';
	
}

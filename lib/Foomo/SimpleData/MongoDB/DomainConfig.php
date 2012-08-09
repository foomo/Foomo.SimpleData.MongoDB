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
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author Jan Halfar jan@bestbytes.com
 */
class DomainConfig extends \Foomo\Config\AbstractConfig
{
	const NAME = 'Foomo.SimpleData.MongoDB.db';
	/**
	 * @var MongoDB
	 */
	protected $mongoDB;
	/**
	 * to which mongodb to connect to
	 * 
	 * @var string
	 */
	public $mongo = 'mongodb://user:password@server/db';
	/**
	 * get our mongo instance
	 *  
	 * @return \MongoDB
	 */
	public function getDB()
	{
		if(is_null($this->mongoDB)) {
			$url = parse_url($this->mongo);
			$dsn = 	
				// nested ternary bäh
				(!empty($url['user'])?$url['user'] . (!empty($url['pass'])?$url['pass']:'') . '@' : '') .
				$url['host']
			;
			$mongoConnection = new \Mongo($dsn);
			$dbName = substr($url['path'], 1); 
			$this->mongoDB = $mongoConnection->{$dbName};
		}
		return $this->mongoDB;
	}
	public function getCollection($name)
	{
		return $this->getDB()->$name;
	}
}
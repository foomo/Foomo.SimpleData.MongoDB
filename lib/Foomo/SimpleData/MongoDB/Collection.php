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
 
use Foomo\SimpleData\VoMapper;

/**
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author Jan Halfar jan@bestbytes.com
 */
class Collection
{
	/**
	 * @var string
	 */
	private $defaultVoClassName;
	/**
	 * @var DomainConfig
	 */
	private $config;
	/**
	 *
	 * @var type 
	 */
	private $name;
	/**
	 * will be lazily loaded
	 * 
	 * @var \MongoCollection
	 */
	private $collection;
	
    //--------------------------------------------------------------------------
    // ~ Constructor
    //--------------------------------------------------------------------------
	/**
	 * @param \Foomo\SimpleData\MongoDB\DomainConfig $config
	 * @param string $name name of the collection
	 * @param string $defaultVoClassName name of the class to which documents will be mapped to by default
	 */
    public function __construct(DomainConfig $config, $name, $defaultVoClassName)
    {
		$this->config = $config;
		$this->name = $name;
		$this->defaultVoClassName = $defaultVoClassName;
    }


    //--------------------------------------------------------------------------
    // ~ public interface
    //--------------------------------------------------------------------------
	/**
	 * you will need this for commands, etc
	 * 
	 * @return \MongoDB
	 */
	public function getDB()
	{
		return $this->config->getDB();
	}
	/**
	 * if you want to hack on it yourself ;)
	 * 
	 * @return \MongoCollection
	 */
	public function getCollection()
	{
		if(!$this->collection) {
			$this->collection = $this->config->getCollection($this->name);
		}
		return $this->collection;
	}
	/**
	 * drop the collection - no alerts ;)
	 */
	public function drop()
	{
		$this->getCollection()->drop();
		$this->collection = null;
	}
	/**
	 * uses \MongoCollection->find() in the background, but hydrates into your
	 * value object
	 * 
	 * @param array $query
	 * @param array $fields
	 * @param string $voClassName
	 * 
	 * @see http://www.php.net/manual/mongocollection.find.php
	 * 
	 * @return $voClassName[]
	 */
	public function find(array $query, array $fields = array(), $voClassName = null)
	{
		return new Cursor($this->config->getConnection(), $this->config->getDBName() . '.' . $this->name , $query, $fields, $this->getVoClassName($voClassName));
	}
	
	/**
	 * uses \MongoCollection->findOne() in the background, but hydrates into your
	 * value object
	 * 
	 * @param array $query
	 * @param array $fields
	 * @param string $voClassName
	 * 
	 * @see http://www.php.net/manual/mongocollection.findone.php
	 * 
	 * @return $voClassName
	 */
	public function findOne(array $query, array $fields = array(), $voClassName = null)
	{
		$voClassName = $this->getVoClassName($voClassName);
		$doc = $this->getCollection()->findOne($query, $fields);
		if(!is_null($doc)) {
			return self::hydrate($doc, $voClassName);
		} else {
			return null;
		}
	}

	/**
	 * create a vo in the collection
	 * 
	 * @param object $vo
	 * @param array $options \MongoCollection::insert options
	 * 
	 * @return $vo get it back with an id on it
	 */
	public function create($vo, array $options = array())
	{
		$voArray = (array) $vo;
		$this->getCollection()->insert($voArray, $options);
		$vo->id = (string) $voArray['_id'];
		return $vo;
	}
	public function batchCreate(array $vos, array $options = array())
	{
		$insertArray = array();
		foreach($vos as $vo) {
			$insertArray[] = (array) $vo;
		}
		$this->getCollection()->batchInsert($insertArray, $options);
		$i = 0;
		foreach($insertArray as $insertArrayEntry) {
			$vos[$i]->id = (string) $insertArrayEntry['_id'];
			$i ++;
		}
		return $vos;
	}
	/**
	 * wraps \MongoCollection::update
	 * 
	 * @param object $vo needs an id
	 * @param array $options options paramter for the wrapped \MongoCollection::update
	 * 
	 * @see http://www.php.net/manual/mongocollection.update.php
	 * 
	 * @return bool|array
	 */
	public function update($vo, array $options = array())
	{
		$this->checkVoForId($vo);
		return false !== $this->getCollection()->update(array('_id' => new \MongoId($vo->id)), (array) $vo, $options);
	}
	/**
	 * wraps \MongoCollection::remove
	 * 
	 * @param string $id
	 * 
	 * @see http://www.php.net/manual/mongocollection.remove.php
	 * 
	 * @return boolean | array
	 */
	public function remove($id)
	{
		return $this->getCollection()->remove(array('_id' => new \MongoId($id)));
	}
	

    //--------------------------------------------------------------------------
    // ~ private helpers
    //--------------------------------------------------------------------------

	private function checkVoForId($vo)
	{
		if(!isset($vo->id)) {
			trigger_error('you object is not a balid vo, bcause it has no id', E_USER_ERROR);
		}
	}
	private function getVoClassName($voClassName)
	{
		return is_null($voClassName)?$this->defaultVoClassName:$voClassName;
	}
	public static function hydrate($document, $voClassName)
	{
		if(!is_array($document) && !is_object($document)) {
			throw new \InvalidArgumentException('$document has to be an array or object');
		} else {
			if(is_object($document)) {
				$document = (array) $document;
			}
			if(isset($document['_id'])) {
				$document['id'] = (string) $document['_id'];
			}
			return VoMapper::map($document, new $voClassName);
		}
	}
}

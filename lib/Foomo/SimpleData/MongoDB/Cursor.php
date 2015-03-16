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

use Mongo;
/**
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author Jan Halfar jan@bestbytes.com
 */
class Cursor extends \MongoCursor
{
	private $voClassName;
	public function __construct( \MongoClient $connection , $ns , array $query = array() , array $fields = array(), $voClassName = null) {
		parent::__construct($connection, $ns, $query, $fields);
		$this->voClassName = $voClassName;
	}
	public function current()
	{
		$document = parent::current();
		if($document) {
			return Collection::hydrate($document, $this->voClassName);
		} else {
			return $document;
		}
	}
}
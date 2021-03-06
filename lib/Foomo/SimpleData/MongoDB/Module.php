<?php

/*
 * This file is part of the foomo Opensource Framework.
 *
 * The foomo Opensource Framework is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License as
 * published  by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * The foomo Opensource Framework is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with
 * the foomo Opensource Framework. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Foomo\SimpleData\MongoDB;

/**
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 */
class Module extends \Foomo\Modules\ModuleBase
{
	//---------------------------------------------------------------------------------------------
	// ~ Constants
	//---------------------------------------------------------------------------------------------
	const VERSION = '0.3.2';
	/**
	 * the name of this module
	 *
	 */

	const NAME = 'Foomo.SimpleData.MongoDB';

	//---------------------------------------------------------------------------------------------
	// ~ Overriden static methods
	//---------------------------------------------------------------------------------------------

	/**
	 * Your module needs to be set up, before being used - this is the place to do it
	 */
	public static function initializeModule()
	{

	}

	/**
	 * Get a plain text description of what this module does
	 *
	 * @return string
	 */
	public static function getDescription()
	{
		return 'simple data fun with mongodb';
	}

	/**
	 * get all the module resources
	 *
	 * @return \Foomo\Modules\Resource[]
	 */
	public static function getResources()
	{
		if (!class_exists('MongoCursor')) {
			\Foomo\Modules\Resource\ComposerPackage::getResource('alcaeus/mongo-php-adapter','*')->tryCreate();
			return array(
				\Foomo\Modules\Resource\ComposerPackage::getResource('alcaeus/mongo-php-adapter','*'),
				\Foomo\Modules\Resource\Module::getResource('Foomo.SimpleData', '0.3.*')
			);
		} else {
			return array(
				\Foomo\Modules\Resource\Module::getResource('Foomo.SimpleData', '0.3.*')
			);
		}
		return $resources;
	}

}

<?php
/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 */




/**
 * XIMDEX_ROOT_PATH
 */
if (!defined('XIMDEX_ROOT_PATH'))
	define('XIMDEX_ROOT_PATH', realpath(dirname(__FILE__) . '/../../../'));

include_once (XIMDEX_ROOT_PATH . '/inc/helper/GenericData.class.php');

class Versions_ORM extends GenericData   {
	var $_idField = 'IdVersion';
	var $_table = 'Versions';
	var $_metaData = array(
				'IdVersion' => array('type' => "int(12)", 'not_null' => 'true', 'auto_increment' => 'true', 'primary_key' => true),
				'IdNode' => array('type' => "int(12)", 'not_null' => 'true'),
				'Version' => array('type' => "int(12)", 'not_null' => 'true'),
				'SubVersion' => array('type' => "int(12)", 'not_null' => 'true'),
				'File' => array('type' => "varchar(255)", 'not_null' => 'true'),
				'IdUser' => array('type' => "int(12)", 'not_null' => 'false'),
				'Date' => array('type' => "int(14)", 'not_null' => 'false'),
				'Comment' => array('type' => "blob", 'not_null' => 'false'),
				'IdSync' => array('type' => "int(12)", 'not_null' => 'false'),
				);
	var $_uniqueConstraints = array(

				);
	var $_indexes = array('IdVersion');
	var $IdVersion;
	var $IdNode = 0;
	var $Version = 0;
	var $SubVersion = 0;
	var $File;
	var $IdUser = 0;
	var $Date = 0;
	var $Comment;
	var $IdSync;
}
?>

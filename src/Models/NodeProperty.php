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

namespace Ximdex\Models;

use Ximdex\Logger;

class NodeProperty extends \Ximdex\Data\GenericData
{
    var $_idField = 'IdNodeProperty';
    var $_table = 'NodeProperties';
    var $_metaData = array(
        'IdNodeProperty' => array('type' => "int(11)", 'not_null' => 'true', 'auto_increment' => 'true', 'primary_key' => true),
        'IdNode' => array('type' => "int(11)", 'not_null' => 'true'),
        'Property' => array('type' => "varchar(255)", 'not_null' => 'true'),
        'Value' => array('type' => "longblob", 'not_null' => 'true')
    );
    var $_uniqueConstraints = array();
    var $_indexes = array('IdNodeProperty');
    var $IdNodeProperty;
    var $IdNode;
    var $Property;
    var $Value;
    
    const DEFAULTSERVERLANGUAGE = 'DefaultServerLanguage';

	public function create($idNode, $property, $value = NULL)
	{
		if (is_null($idNode) || is_null($property)) {
			Logger::error('Params node and property are mandatory');
			return false;
		}
		$this->set('IdNode', $idNode);
		$this->set('Property', $property);
		$this->set('Value', $value);
		parent::add();
		$propertyId = $this->get('IdNodeProperty');
		if (!($propertyId > 0)) {
			Logger::error("When adding NodeProperty (idNode: $idNode, property: $property, value: $value)");
			return false;
		}
		return true;
	}

	/**
	 * Return the value of a property for a node
	 *
	 * @param int idNode
	 * @param string property
	 * @return string / null
	 */
	public function getProperty($idNode, $property)
	{	
		if (is_null($idNode) || is_null($property)) {
			Logger::error('Params node and property are mandatory');
			return NULL;
		}
		$result = $this->find('Value', "Property = %s AND IdNode = %s", array($property, $idNode), MONO);
		return empty($result) ? NULL : $result;
	}

	/**
	 * Deletes all node properties
	 *
	 * @param int idNode
	 * @return true / false
	 */
	public function deleteByNode($idNode)
	{	
		if (is_null($idNode)) {
			Logger::error('Param nodeId is mandatory');
			return false;
		}
 		$dbObj = new \Ximdex\Runtime\Db();
        $sql = sprintf("DELETE FROM NodeProperties WHERE IdNode = %d", $idNode);
		$dbObj->Execute($sql);
		return true;
	}

	/**
	 * Deletes all values for a property in a given node
	 *
	 * @param int idNode
	 * @param string property
	 * @return true / false
	 */
	public function deleteByNodeProperty($idNode, $property)
	{	
		if (is_null($idNode) || is_null($property)) {
			Logger::error('Params nodeId and property are mandatories');
			return false;
		}
 		$dbObj = new \Ximdex\Runtime\Db();
        $sql = "DELETE FROM NodeProperties WHERE IdNode = $idNode AND Property = '$property'";
		$dbObj->Execute($sql);
		return true;
	}

	/**
	 * Gets all node properties
	 *
	 * @param int idNode
	 * @return array / NULL
	 */
	public function getPropertiesByNode($idNode)
	{
		$result = $this->find('Property, Value', 'IdNode = %s', array($idNode), MULTI);
		if (empty($result)) {
			return NULL;
		}
		return $result;
	}
	
	public function cleanUpPropertyValue($property, $value)
	{    
		$db = new \Ximdex\Runtime\Db();
		$query = sprintf("DELETE FROM NodeProperties WHERE Property = %s AND Value = %s", 
		    $db->sqlEscapeString($property), $db->sqlEscapeString($value));
		$db->execute($query);
	}

	public function getNodeByPropertyValue($property, $value)
	{
		return $this->find('IdNode', 'Property = %s AND Value = %s', array($property, $value), MONO);
	}
}
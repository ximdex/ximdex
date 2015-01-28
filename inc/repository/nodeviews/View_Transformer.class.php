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



if (!defined('XIMDEX_ROOT_PATH')) {
	define ('XIMDEX_ROOT_PATH', realpath(dirname(__FILE__)) . '/../../../');
}

require_once(XIMDEX_ROOT_PATH . '/inc/repository/nodeviews/Abstract_View.class.php');
require_once(XIMDEX_ROOT_PATH . '/inc/repository/nodeviews/Interface_View.class.php');

class View_Transformer extends Abstract_View implements Interface_View {
	public function transform($idVersion = NULL, $pointer = NULL, $args = NULL) {
		
		if (!array_key_exists('TRANSFORMER', $args) || empty($args['TRANSFORMER'])) {

			$version = new Version($idVersion);
			$node = $version->get('IdNode');
			$node = new Node($node);

			$args['TRANSFORMER'] = end($node->getProperty('Transformer'));

			if (empty($args['TRANSFORMER'])) {
				XMD_Log::fatal('No se ha especificado el transformador en la tabla Config');
			}
		}


		$transformer = ucfirst($args['TRANSFORMER']);
		if("Xlst" == $transformer) {
			$transformer="Xslt";
		}
		$factory = new \Ximdex\Utils\Factory(XIMDEX_ROOT_PATH . '/inc/repository/nodeviews', 'View_');
		$instanceOfView = $factory->instantiate($transformer);

		return $instanceOfView->transform($idVersion, $pointer, $args);

	}

}
?>
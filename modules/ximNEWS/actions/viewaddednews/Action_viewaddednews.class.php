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




ModulesManager::file('/inc/utils.php');

class Action_viewaddednews extends ActionAbstract {

    public function index() {

		$idAction = (int) $this->request->getParam("actionid");
		$idNode	= (int) $this->request->getParam("nodeid");
	
	    $relNewsColector = new RelNewsColector();
	    $news = $relNewsColector->getAddedNews($idNode);
	    $associatedNews = array();
	    
	    if(count($news) > 0){
			foreach($news as $newsData) {
				$new = new XimNewsNew($newsData['IdNew']);
			    $name = $new->get('Name');
			    $state = $newsData['State'];
			    $fechaIn = date("d-m-Y", $newsData['FechaIn']);
			    $fechaOut = ($newsData['FechaOut'] == '') ? 'Undetermined' : date("d-m-Y",$newsData['FechaOut']);
			    $version = $newsData['Version'] . "." . $newsData['Subversion'];
				$associatedNews[] = array(
					'Name' => $name,
					'State' => $state,
					'FechaIn' => $fechaIn,
					'FechaOut' => $fechaOut,
					'Version' => $version
				);
			}
	    }
	    
		$values = array(
			'id_node' => $idNode,
			'news' => $associatedNews
		);

		$this->render($values, 'index', 'default-3.0.tpl');
	}
}
?>
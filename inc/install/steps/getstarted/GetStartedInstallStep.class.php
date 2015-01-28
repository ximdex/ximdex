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

require_once(XIMDEX_ROOT_PATH . '/inc/install/steps/generic/GenericInstallStep.class.php');
require_once(XIMDEX_ROOT_PATH . '/inc/install/managers/InstallModulesManager.class.php');

class GetStartedInstallStep extends GenericInstallStep {

	/**
	 * Main function. Show the step	 
	 */
	public function index(){
		
		$modules = $this->installManager->getModulesByDefault();	
		$imManager = new InstallModulesManager(InstallModulesManager::WEB_MODE);		
		foreach ($modules as $module) {
			$imManager->installModule($module["name"]);
			$imManager->enableModule($module["name"]);
		}	
		$values=array();
		$values["go_method"]="startXimdex";
		$this->render($values);
	}

	/**
	 * Change Permissions, Load next Action  and redirect to index.php
	 * @return [type] [description]
	 */
	public function startXimdex(){

		$this->changePermissions();
        $this->deleteTempFiles();
		$this->loadNextAction();		
		header(sprintf("Location: %s", "index.php"));
		die();
	}

	/**
	 * Change permissions to only reader mod.
	 */
	private function changePermissions(){
		chmod(XIMDEX_ROOT_PATH."/conf/install-params.conf.php",0644);
	}

    /**
     * Delete tmp files before start.
     */
    private function deleteTempFiles(){
        exec("rm -f ".XIMDEX_ROOT_PATH."/data/tmp/templates_c/*");
        exec("rm -f ".XIMDEX_ROOT_PATH."/data/tmp/js/es_ES/*");
        exec("rm -f ".XIMDEX_ROOT_PATH."/data/tmp/js/en_US/*");
    }
}

?>
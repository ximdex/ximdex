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


include_once 'bootstrap/start.php' ;

$obj = \Ximdex\Runtime\App::get('\Ximdex\Utils\QueryManager');

print_R($obj );

die('fin');
$log = new Logger('sss');
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/sss.log', Logger::DEBUG));

Ximdex\Logger::addLog( $log , 'sss' ) ;


XMD_Log::setActiveLog( 'sss') ;





//General class
if(file_exists(XIMDEX_ROOT_PATH . '/conf/install-params.conf.php') )
	include_once(XIMDEX_ROOT_PATH . '/conf/install-params.conf.php');

require_once(XIMDEX_ROOT_PATH . '/inc/fsutils/DiskUtils.class.php');

ModulesManager::file('/inc/utils.inc');
ModulesManager::file('/inc/io/BaseIO.class.php');
ModulesManager::file( '/conf/extensions.conf.php');
ModulesManager::file('/inc/i18n/I18N.class.php');
ModulesManager::file('/inc/mvc/mvc.php'); // MVC
ModulesManager::file('/inc/install/InstallController.class.php'); 

function echo_gt_or_not($msg) {
	if (function_exists('_')) {
        	return _($msg);
	}
	return $msg;
}

function check_php_version() {
	if (version_compare(PHP_VERSION,'5','<')) {
		$msg = "ERROR: PHP5 is needed. PHP version detected: [" . PHP_VERSION . "].";
		die(echo_gt_or_not($msg));
	}
}

function check_config_files() {
	$install_params = file_exists(XIMDEX_ROOT_PATH . '/conf/install-params.conf.php');
	$install_modules = file_exists(XIMDEX_ROOT_PATH .'/conf/install-modules.conf');

	if (!$install_params || !$install_modules) {
		$_GET["action"] = "installer";
	}
}

function checkFolders () {
	$msg = null;

	$foldersToCheck = array(	
				//array('FOLDER' => '/data/backup', 'MODULE' => 'ximIO'),
				array('FOLDER' => '/data/cache', 'MODULE' => ''),
				//array('FOLDER' => '/data/cache/ximRAM', 'MODULE' => 'ximRAM'),
				array('FOLDER' => '/data/files', 'MODULE' => ''),
				array('FOLDER' => '/data/nodes', 'MODULE' => ''),
				array('FOLDER' => '/data/sync', 'MODULE' => ''),
				array('FOLDER' => '/data/tmp', 'MODULE' => ''),
				array('FOLDER' => '/data/tmp/uploaded_files', 'MODULE' => ''),
				array('FOLDER' => '/data/tmp/js', 'MODULE' => ''),
				array('FOLDER' => '/data/tmp/templates_c', 'MODULE' => ''),
				//array('FOLDER' => '/data/trash', 'MODULE' => 'ximTRASH'),
				array('FOLDER' => '/logs', 'MODULE' => '')
				);
	reset($foldersToCheck);
	while(list(, $folderInfo) = each($foldersToCheck)) {
		if (!empty($folderInfo['MODULE'])) {
			if (!ModulesManager::isEnabled($folderInfo['MODULE'])) {
				continue;
			}
		}
		$folder = XIMDEX_ROOT_PATH . $folderInfo['FOLDER'];
		if (!is_dir($folder)) {
			$msg = sprintf(echo_gt_or_not("Folder %s could not be found"), $folder);
			continue;
		}

		$file = FsUtils::getUniqueFile($folder);
		$file = $folder . DIRECTORY_SEPARATOR . $file;

		FsUtils::file_put_contents($file, 'test');

		if (FsUtils::file_get_contents($file) != 'test') {
			$msg = sprintf(echo_gt_or_not("Temporary file created in %s could not be read or written"), $folder);
		}

		if (is_file($file)) {
			FsUtils::delete($file);
		}
	}


	if(!empty($msg))
		die(echo_gt_or_not($msg));

}

function goLoadAction() {
	header(sprintf("Location: %s", \App::getValue( 'UrlRoot')));
}

//Main thread
if(!InstallController::isInstalled()){
	$installController = new InstallController();
	$installController->dispatch();
}
else{
	$locale = \Ximdex\Utils\Session::get('locale');
	I18N::setup($locale); // Check coherence with HTTP_ACCEPT_LANGUAGE

	check_php_version();
	checkFolders();
	check_config_files();


	//goLoadAction();

	// FrontController dipatches HTTP requests
	$frontController = new FrontController();
	$frontController->dispatch();
}
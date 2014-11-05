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
	define('XIMDEX_ROOT_PATH', realpath(dirname(__FILE__) . "/../../"));
}

if (!defined('CLI_MODE')) {
	define('CLI_MODE', 0);
}

// Composer AutoLoad
include_once(XIMDEX_ROOT_PATH . '/extensions/vendors/autoload.php');

include_once(XIMDEX_ROOT_PATH . '/inc/modules/modules.const');
include_once(XIMDEX_ROOT_PATH . '/inc/modules/ModulesConfig.class.php');
include_once(XIMDEX_ROOT_PATH . '/inc/fsutils/FsUtils.class.php');

ModulesManager::file( '/conf/extensions.conf.php');
ModulesManager::file(MODULES_INSTALL_PARAMS);

/**
 *
 */
class ModulesManager {

	public $modules;
	public $caller;
	private static $core_modules = array("ximIO", "ximSYNC");
	private static $deprecated_modules = array("ximDAV", "ximTRASH","ximLOADERDEVEL","ximTHEMES","ximOTF","ximPAS","ximSIR","ximDEMOS","ximPORTA","ximTEST","ximTAINT");
	public static $msg = null;
	public static $loadedFiles = array();


	/**
		Core modules are specials: 
		They are installed always and they never can be uninstalled or disabled
	*/
	public function getCoreModules() { return self::$core_modules; }

	/**
		Deprecated modules. 
		They don't have to be shown on Ximdex CMS interface.
	*/
	public function getDeprecatedModules() { return self::$deprecated_modules; }

	/**
		Get install params for GUI 
	*/
	public function getInstallParams($name) {

		$module = ModulesManager::instanceModule($name);

		if ( is_null($module) ) {
			return array();
		}

		return $module->getInstallParams();
	}

	function writeStates() {
		$config = FsUtils::file_get_contents(XIMDEX_ROOT_PATH.MODULES_INSTALL_PARAMS);
		
		$modMan=new ModulesManager();
		$modules=$modMan->getModules();
		$str="<?php\n\n";
		foreach($modules as $mod){
			$str.=PRE_DEFINE_MODULE.strtoupper($mod["name"]).POST_PATH_DEFINE_MODULE.str_replace(XIMDEX_ROOT_PATH,'',$mod["path"])."');"."\n";
		}
		$str.="\n?>";
		FsUtils::file_put_contents(XIMDEX_ROOT_PATH.MODULES_INSTALL_PARAMS,$str);

	}


	function ModulesManager($caller = NULL) {

		// Init stuff.
		$this->caller = $caller;
		//$this->modules = $this->getModules();
	}

	function parseModules($constModule, &$modules){
		$paths = FsUtils::readFolder($constModule, false/*, $excluded = array()*/);
		if($paths){
			foreach ($paths as $moduleName) {
				$modulePath = $constModule . $moduleName;
				if (!in_array($moduleName, self::getDeprecatedModules())) {
					if (is_dir($modulePath)) {
						$i = count($modules);
						$modules[$i]["name"] = $moduleName;
						$modules[$i]["path"] = $modulePath;
						$modules[$i]["enable"] = (int) self::isEnabled($moduleName);
					}
				}
			}
		}
	}

	function parseMetaParent($constModule, &$metaParent) {
		$paths = FsUtils::readFolder($constModule, false);
		if($paths){
			foreach ($paths as $moduleName) {
				$modulePath = $constModule . $moduleName;
				//if (is_dir($modulePath) && preg_match('/^xim+/', $moduleName, $matches)) {
				if (is_dir($modulePath) && file_exists($modulePath . "/conf.ini")) {
					$conf = parse_ini_file( $modulePath . "/conf.ini" );
					foreach($conf['module'] as $id => $childrenModName)
						$metaParent[$childrenModName] = $moduleName;
				}
			}
		}
	}

	function getModules() {
		$modules = array();
		self::parseModules(XIMDEX_MODULES_DIR, $modules);
		self::parseModules(XIMDEX_MODULES_PRO_DIR, $modules);
		return $modules;
	}

	function getMetaParent() {
		$modules = array();
		self::parseMetaParent(XIMDEX_MODULES_DIR, $metaParent);
		self::parseMetaParent(XIMDEX_MODULES_PRO_DIR, $metaParent);
		return $metaParent;
	}

	function hasMetaParent($name) {
		$metaParent = self::getMetaParent();
		if(!empty($metaParent) && isset( $metaParent[ $name ] ) && $this->caller != $metaParent[$name]) {
			return $metaParent;
		} else {
			return false;
		}
	}

	function moduleExists($name) {
		$path = ModulesManager::path($name);
		if(!empty($path) ) {
			return true;
		}
		return false;
	}

   	static function  path($name) {
   		$str =  "MODULE_".strtoupper($name)."_PATH";
   		if(defined($str) ) {
   	   		return constant($str);
   		}else {
   			return "";
   		}
   	}

	function installModule($name) {
		if($metaParent = self::hasMetaParent($name)) {
			self::$msg = sprintf("Can't install module %s directly. Try installing Meta-module %s instead", $name, $metaParent[$name]);

			return false;
		}

		if (ModulesManager::isEnabled($name)) {
			self::$msg = "checkModule: MODULE_STATE_ENABLED, module is enabled... try to reinstall ";
			/* BUG? it returns true but as installation failed should return false*/
			return MODULE_STATE_INSTALLED;
		}
		$module = ModulesManager::instanceModule($name);

		if ( is_null($module) ) {
			print(" * ERROR: Can't install module $name\n");
			return false;
		}

		return $module->install();
	}

	function uninstallModule($name) {
		if($metaParent = self::hasMetaParent($name)) {
			self::$msg = sprintf("Can't uninstall module %s directly. Try uninstalling Meta-module %s instead", $name, $metaParent[$name]);


			return false;
		}

		$module = ModulesManager::instanceModule($name);

		if ( is_null($module) || $module->isCoreModule()  ) {
			self::$msg =  "Can't uninstall module $name";
		
			return false;
		}

		return $module->uninstall();
	}

	function checkModule($name) {


		$module = ModulesManager::instanceModule($name);

		if ( is_null($module) ) {
			self::$msg = "Module instance down";
			return MODULE_STATE_ERROR;
		}

		return $module->state();
/*
		switch ($module->state()) {

			case MODULE_STATE_INSTALLED:
				print("checkModule: MODULE_STATE_INSTALLED\n");
				break;

			case MODULE_STATE_UNINSTALLED:
				print("checkModule: MODULE_STATE_UNINSTALLED\n");
				break;

			case MODULE_STATE_ERROR:
				print("checkModule: MODULE_STATE_ERROR\n");
				break;

			default:
				print("checkModule: DEFAULT\n");
				break;

		}
	*/
	}

//END


    /**
     *  Enable a Module.
     */
	function enableModule($name) {
		if($metaParent = self::hasMetaParent($name)) {
			self::$msg = sprintf("Can't enable module %s directly. Try enabling Meta-module %s instead", $name, $metaParent[$name]);
			return false;
		}

		$module = ModulesManager::instanceModule($name);

		if ( is_null($module) ) {
			self::$msg = " * ERROR: instance module down";
			return false;
		}

		$modConfig = new ModulesConfig();
		$modConfig->enableModule($module->getModuleName());

		$module->enable();

	}

    /**
     *  Disable a Module.
     */
	function disableModule($name) {
		if($metaParent = self::hasMetaParent($name)) {
			self::$msg = sprintf("Can't disable module %s directly. Try disabling Meta-module %s instead", $name, $metaParent[$name]);
			return false;
		}
		$module = ModulesManager::instanceModule($name);

		if ( is_null($module) || $module->isCoreModule()  ) {
			self::$msg = "instance module down";
			return false;
		}


		$modConfig = new ModulesConfig();
		$modConfig->disableModule($module->getModuleName());

		$module->disable();

	}

    /**
     *  Instantiate a module by name.
     *  @protected
     *  @param $name Name of the module.
     *  @return NULL | & Module (child).
     */
	function instanceModule($name) {

		// If no name provided exit.
		if (is_null($name)) {
			self::$msg = "Module name not provided.";
			return NULL;
		}

		// If module not exists exit.

		$moduleClassName = MODULE_PREFIX . $name;
		$moduleClassFile = MODULE_PREFIX . $name . ".class.php";
		//$moduleClassPath = XIMDEX_ROOT_PATH . "/modules/$name/" . $moduleClassFile;
		$moduleClassPath = XIMDEX_ROOT_PATH.self::path($name)."/".$moduleClassFile;
		if (file_exists($moduleClassPath)) {
			include_once($moduleClassPath);
		} else {
			self::$msg =  "Module definition file not found [$moduleClassPath].";
			return NULL;
		}

		$module = new $moduleClassName;

		if ( is_null($module) ) {
			self::$msg = " Module not instantiated [$moduleClassName].";
			return NULL;
		}

		return $module;
	}

	public static function isEnabled($name) {
		$str = "MODULE_" . strtoupper($name) . "_ENABLED";

		if (defined($str)) {
			return true;
		} else {
			return false;
		}

	}


	public static function getEnabledModules() {

		$modules = self::getModules();
		foreach ($modules as $key => $module) {
//                print("  - {$module['name']}\n");

			if (!self::isEnabled($module['name'])) {
				unset($modules[$key]);
			}
		}
		return $modules;
	}


	public static function component($_file, $_component = 'XIMDEX') {
		if("XIMDEX" == $_component) {
                        $dir = '';
                }else {
                        $dir = self::path($_component);
               }

		self::file($dir.$_file);
	}


	public static function file($_file, $_module = 'XIMDEX') {
		if("XIMDEX" == $_module) {
			$dir = '';
	    }else {
			$dir = self::path($_module);
	    }
	    
	    $filePath = XIMDEX_ROOT_PATH."{$dir}{$_file}" ;
	    // only load new files
	    if ( in_array( $filePath, self::$loadedFiles)) {
	    	return true ;
	    }

		if ( file_exists( $filePath ) && ( self::isEnabled($_module) || 'XIMDEX' == $_module) ) {
			// put filename on list of loaded files
			array_push( self::$loadedFiles , $filePath );
	 		return require_once($filePath);
	    }
		
	}	
}

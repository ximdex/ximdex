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
 * @author Ximdex DevTeam <dev@ximdex.com>
 * @version $Revision$
 */

use Ximdex\Helpers\ServerConfig;
use Ximdex\Utils\Crypto;
use Ximdex\Utils\FsUtils;


/**
 * Manager for install process
 */
class InstallManager
{
    const STATUSFILE = "/conf/_STATUSFILE";

    const INSTALL_CONF_FILE = "install.xml";
    const INSTALL_PARAMS_TEMPLATE = "/install/templates/install-params.conf.php";
    const INSTALL_PARAMS_FILE = "/conf/install-params.conf.php";
    const LAST_STATE = "INSTALLED";
    const FIRST_STATE = "INIT";


    /**
     * Construct method
     * @param string $mode Install mode: Web or console
     */
    public function __construct()
    {
        $installConfFile = XIMDEX_ROOT_PATH . "/inc/install/conf/" . self::INSTALL_CONF_FILE;
    }

    /**
     * Get status from _STATUSFILE
     * @return string get Status
     */
    public function getCurrentState()
    {
        $statusFile = XIMDEX_ROOT_PATH . self::STATUSFILE;
        if (!file_exists($statusFile))
            return false;
        return trim(strtolower(FsUtils::file_get_contents($statusFile)));
    }

    /**
     * Check if Ximdex is already installed.
     * @return boolean true if is already installed.
     */
    public function isInstalled()
    {
        $currentState = $this->getCurrentState();
        if (!$currentState)
            return false;

        return $currentState == strtolower(self::LAST_STATE);
    }

    public function createStatusFile()
    {
        FsUtils::file_put_contents(XIMDEX_ROOT_PATH . self::STATUSFILE, self::FIRST_STATE);
    }

    public function getModulesByDefault($default = true)
    {
        $query = "/install/modules/module";
        $query .= $default ? "[@default='1']" : "[not(@default) or @default='0']";
        return $this->getModulesByQuery($query);
    }

    public function getModulesByQuery($query)
    {
        $result = array();
        $xpath = new DomXPath($this->installConfig);
        $modules = $xpath->query($query);

        foreach ($modules as $module) {
            $auxModuleArray = array();
            foreach ($module->attributes as $attribute) {
                $auxModuleArray[$attribute->name] = $attribute->value;
            }
            $auxModuleArray["description"] = $module->nodeValue;
            $result[] = $auxModuleArray;

        }
        return $result;
    }

    public function getAllModules()
    {
        $query = "/install/modules/module";
        return $this->getModulesByQuery($query);
    }

    public function getModuleByName($name, $exclude_alias = true)
    {
        $extra_query = $exclude_alias ? "" : " or @alias='{$name}'";
        $query = "/install/modules/module[@name='{$name}' $extra_query]";
        return $this->getModulesByQuery($query);
    }
}
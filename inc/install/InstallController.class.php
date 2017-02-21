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
require_once(XIMDEX_ROOT_PATH . '/inc/install/managers/InstallManager.class.php');

/**
 * Controller for install steps.
 * It's called only when the install process is not finished.
 */
class InstallController
{


    /*Properties*/
    private $installManager = null; //Install manager object

    /*Methods*/

    /**
     * Constructor. Init the class properties.
     */
    public function __construct()
    {
        $this->installManager = new InstallManager();
        if (!$currentState) {
            $this->installManager->createStatusFile();
        }
    }

    /**
     * Indicate if Ximdex is installed.
     * @return boolean True if installed, false otherwise
     */
    public static function isInstalled()
    {

        $installManager = new InstallManager();
        return $installManager->isInstalled();
    }

}

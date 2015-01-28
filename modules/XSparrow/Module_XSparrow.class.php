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

use Ximdex\Modules\Module;

class Module_XSparrow extends Module
{

    public function __construct()
    {
        // Call Module constructor.
        parent::__construct('XSparrow', dirname(__FILE__));
    }

    function install()
    {
        // Install logic.
        // get module from ftp, webdav, subversion, etc...?
        // need to be extracted?
        // extract and copy files to modules location.

        // get constructor SQL
        $this->loadConstructorSQL("XSparrow.constructor.sql");

        // Install !
        $install_ret = parent::install();
        if ($install_ret) {
            echo "XSparrow module has been successfully installed on Ximdex CMS!.\n";
        }
    }

    function uninstall()
    {
        // Uninstall logic.
        // get destructor SQL
        $this->loadDestructorSQL("XSparrow.destructor.sql");
        // Uninstall !
        parent::uninstall();
    }
}
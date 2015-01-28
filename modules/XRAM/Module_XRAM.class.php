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

use Ximdex\Modules\Module;

class Module_XRAM extends Module
{

    public function __construct()
    {
        // Call Module constructor.
        parent::__construct('XRAM', dirname(__FILE__));
        // Initialization stuff.
    }

    function install()
    {
        // Install logic.
        // get module from ftp, webdav, subversion, etc...?
        // need to be extracted?
        // extract and copy files to modules location.
        // get constructor SQL   
        $this->loadConstructorSQL("XRAM.constructor.sql");
        $install_ret = parent::install();
        if ($install_ret) {
//            echo "XRAM module has been successfully installed on Ximdex CMS!.\n";
        }
        return $install_ret;
    }

    function uninstall()
    {

        // Uninstall logic.
        // get destructor SQL          
        $this->loadDestructorSQL("XRAM.destructor.sql");

        // Uninstall !      
        parent::uninstall();
    }

    function preInstall()
    {
        /* Check curl extension and Solr PECL extension */
        // PHP-CURL
        if (!extension_loaded('curl')) {
//            echo "Se necesita tener instalada la extension php-curl\n";
            return false;
        } else {
//            echo "La extension php-curl se ha detectado correctamente.\n";
        }

        return true;
    }

}

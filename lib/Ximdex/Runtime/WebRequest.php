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


namespace Ximdex\Runtime;

use Ximdex\Models\Action;
use Ximdex\MVC\ActionFactory;
use Ximdex\Utils\AssociativeArray;


/**
 *
 * @brief Http request parameters container
 *
 * This class is intended to store the request parameters
 *
 */

/**
 * Class Request
 * @package Ximdex\Runtime
 */
class WebRequest extends \Illuminate\Http\Request
{
    /**
     * @param $key
     * @param $value
     * @param string $defValue
     */
    public function add($key, $value, $defValue = "")
    {
        $value = isset ($value) ? $value : $defValue;
        $this[$key] = $value;
    }

    /**
     * @param $vars
     */
    public function setParameters($vars)
    {
        if (!empty($vars) > 0) {
            foreach ($vars as $key => $value) {
                if (is_object($value) || is_array($value)) {
                    $this->setParam($key, $value);
                } else {
                    $this->setParam($key, trim($value));
                }
            }
        }
    }

    /**
     * @param $key
     * @param $value
     * @param string $defValue
     */
    public function setParam($key, $value, $defValue = "")
    {
        $value = isset ($value) ? $value : $defValue;

        $this[$key] = $value;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        return $this->input($key, $default);
    }

    /**
     * @return array
     */
    public function getRequests()
    {
        return $this->all();
    }

    /**
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }


    // Transitional methods. You MUST use Request object returned from ApplicationController.

    /**
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * @return bool
     */
    public function isCookie()
    {
        return $this->cookies->count() > 0;
    }

    /**
     * @return bool
     */
    public function isFile()
    {
        return count($this->allFiles()) > 0;
    }

    public function post($key, $default = ""){
        return $this->input($key, $default);
    }

}
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


namespace Ximdex\Utils\Logs;


use Ximdex\Logger;

class Loggeable

{


    /**
     * @param $loggerName
     * @return \Monolog\Logger
     * TODO repair
     */
    private static function _getLogger($loggerName )
    {
        return  Logger::get($loggerName);
     //   return $loggerName;
    }

    /**
     * @param $msg
     * @param $loggerName
     * @param int $level
     */
    public static function write($msg, $loggerName, $level = LOGGER_LEVEL_INFO)
    {
        $logger = Loggeable::_getLogger($loggerName);
        if (!is_null($logger)){
            switch ($level){
            case LOGGER_LEVEL_ALL:
                $logger->addDebug($msg);
                break;
            case LOGGER_LEVEL_DEBUG:
                $logger->addDebug($msg);
                break;
            case LOGGER_LEVEL_INFO:
                $logger->addInfo($msg);
                break;
            case LOGGER_LEVEL_WARNING:
                $logger->addWarning($msg);
                break;
            case LOGGER_LEVEL_ERROR:
                $logger->addError($msg);
                break;
            case LOGGER_LEVEL_FATAL:
                $logger->addCritical($msg);
                break;
            case LOGGER_LEVEL_NONE:
                $logger->addEmergency($msg);
                break;
            }

        }
    }


    /**
     * @param $msg
     * @param $loggerName
     */
    public static function debug($msg, $loggerName)
    {
        $logger = Loggeable::_getLogger($loggerName);
        if (!is_null($logger)) $logger->addDebug($msg);
    }

    /**
     * @param $msg
     * @param $loggerName
     */
    public static function info($msg, $loggerName)
    {
        $logger = Loggeable::_getLogger($loggerName);
        if (!is_null($logger)) {
            $logger->addInfo($msg);
        }
    }


    /**
     * @param $msg
     * @param $loggerName
     */
    public static function warning($msg, $loggerName)
    {
        $logger = Loggeable::_getLogger($loggerName);
        if (!is_null($logger)) $logger->addWarning($msg);
    }


    /**
     * @param $msg
     * @param $loggerName
     */
    public static function error($msg, $loggerName)
    {
        $logger = Loggeable::_getLogger($loggerName);
        if (!is_null($logger)) $logger->addError($msg);
    }





    /**
     * @param $msg
     * @param $loggerName
     */
    public static function fatal($msg, $loggerName)
    {
        $logger = Loggeable::_getLogger($loggerName);
        if (!is_null($logger)) $logger->addCritical($msg);
    }

}
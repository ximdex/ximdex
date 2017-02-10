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
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Ximdex\Runtime\App;


$log = new Logger('XMD');
$log->pushHandler(new StreamHandler( App::getValue('XIMDEX_ROOT_PATH') .'/logs/xmd.log', Logger::DEBUG, true, 0766));
\Ximdex\Logger::addLog( $log );


$log = new Logger('Actions');
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/actions.log', Logger::DEBUG));
\Ximdex\Logger::addLog( $log ) ;


$log = new Logger('sync_logger');
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/sync.log', Logger::DEBUG));
\Ximdex\Logger::addLog( $log ) ;


$log = new Logger('publication_logger');
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/publication.log', Logger::DEBUG));
\Ximdex\Logger::addLog( $log ) ;


$log = new Logger("action_logger");
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/actions.log', Logger::DEBUG));
\Ximdex\Logger::addLog( $log ) ;


$log = new Logger('automatic_logger');
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/automatic.log', Logger::DEBUG));
\Ximdex\Logger::addLog( $log ) ;


$log = new Logger('sql_logger');
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/sql.log', Logger::DEBUG));
\Ximdex\Logger::addLog( $log ) ;


$log = new Logger('updatedb_logger');
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/updatedb.log', Logger::DEBUG));
\Ximdex\Logger::addLog( $log ) ;


$log = new Logger('updatedb_historic');
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/updatedb_historic.log', Logger::DEBUG));
\Ximdex\Logger::addLog( $log ) ;


$log = new Logger('mn_logger');
$log->pushHandler(new StreamHandler(App::getValue('XIMDEX_ROOT_PATH') .'/logs/mail.log', Logger::DEBUG));
\Ximdex\Logger::addLog( $log ) ;
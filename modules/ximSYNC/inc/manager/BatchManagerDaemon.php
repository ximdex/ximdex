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

// For legacy compatibility
if (!defined('XIMDEX_ROOT_PATH')) {
    
    require_once dirname(__FILE__) . '/../../../../bootstrap.php';
}

use Ximdex\Logger;
use Ximdex\Models\Node;
use Ximdex\Models\NodeType;
use Ximdex\Runtime\App;
use Ximdex\Runtime\DataFactory;
use Ximdex\Sync\SynchroFacade;

include_once(XIMDEX_ROOT_PATH . '/modules/ximSYNC/inc/manager/BatchManager.class.php');
include_once(XIMDEX_ROOT_PATH . '/modules/ximSYNC/inc/model/Batch.class.php');

function main($argc, $argv)
{
    Logger::generate('PUBLICATION', 'publication');
    Logger::setActiveLog('publication');

    // Command line mode call
    if ($argv != null && isset($argv[1]) && is_numeric($argv[1])) {      
        Logger::logTrace("IdNode passed:" . " " . $argv[1]);
        
        // Add node to publishing pool and exit (SyncManager will call this daemon again when inserting node job is done)
        $syncFac = new SynchroFacade();
		$syncFac->pushDocInPublishingPool($argv[1], time(), null);
        exit(1);
    }

    // One block of nodes to publish, sorted and grouped by dateup
    // Every node in the block shares same dateup
    $nodesToPublish = NodesToPublish::getNext();
    while ($nodesToPublish != null) {
        Logger::info("Publication cycle triggered by" . " " . $nodesToPublish['idNodeGenerator']);
        createBatchsForBlock($nodesToPublish);

        // Gext next block (if any) of nodes to publish
        $nodesToPublish = NodesToPublish::getNext();
    }
}

function createBatchsForBlock($nodesToPublish)
{
    $idNodeGenerator = $nodesToPublish['idNodeGenerator'];
    
    // If the node which trigger publication do not exists anymore return null and cancel.
    $node = new Node($idNodeGenerator);
    if (!($node->get('IdNode') > 0)) {
        
        Logger::error("Required node does not exist" . " " . $idNodeGenerator);
        return NULL;
    }

    // Get list of physicalServers related to generator node.
    $idServer = $node->GetServer();
    $nodeServer = new Node($idServer);
    if (App::getValue('PublishOnDisabledServers') == 1) {
        Logger::info("PublishOnDisabledServers is true");
        $physicalServers = $nodeServer->class->GetPhysicalServerList(true);
    } else {
        $physicalServers = $nodeServer->class->GetPhysicalServerList(true, true);
    }
    if (count($physicalServers) == 0) {
        Logger::error('Physical server does not exist for nodeId:' . " " . $idNodeGenerator . " " . 'returning empty arrays.');
        return null;
    }

    // BatchManager 'publicate' method does all the creating batchs job
    $batchMng = new BatchManager();
    $docsPublicated = $batchMng->publicate(
        $nodesToPublish['idNodeGenerator'],
        $nodesToPublish['docsToPublish'],
        $nodesToPublish['docsToPublishVersion'],
        $nodesToPublish['docsToPublishSubVersion'],
        $nodesToPublish['dateUp'],
        $nodesToPublish['dateDown'],
        $physicalServers,
        $nodesToPublish['forcePublication'],
        $nodesToPublish['userId']
    );

    // Clean up caches, tmp files, etc...
    if (is_null($docsPublicated)) {        
        Logger::error("PUSHDOCINPOOL - docsPublicated null");
        return null;
    }
    $unchanged = array('unchanged' => $docsPublicated[1]);
    $result = array_merge($docsPublicated[0], $docsPublicated[1]);

    // Purge subversions in docs publicated successfully
    if (sizeof($docsPublicated[1]) > 0) {
        if (array_key_exists('ok', $docsPublicated[1])) {
            foreach ($docsPublicated[1]['ok'] as $id => $dataResult) {
                $node = new Node($id);
                $nodeTypeID = $node->get('IdNodeType');
                $nodeType = new NodeType($nodeTypeID);
                $nodeTypeName = $nodeType->get('Name');
                Logger::info("Purging subversions for node" . " $id");
                $data = new DataFactory($id);
                $curVersion = $data->getLastVersion(true);
                $prevVersion = $curVersion - 1;
                if (App::getValue("PurgeSubversionsOnNewVersion")) {
                    $data->_purgeSubVersions($prevVersion, true);
                }
            }
        }

        // Delete major version in docs with error
        if (array_key_exists('notok', $docsPublicated[1])) {
            foreach ($docsPublicated[1]['notok'] as $id => $dataResult) {
                $data = new DataFactory($id);
                $curVersion = $data->getLastVersion(true);
                Logger::info("Publication error: deleting version $curVersion for node $id");
                if (App::getValue("PurgeSubversionsOnNewVersion")) {
                    $data->DeleteVersion($curVersion);
                }
            }
        }
    }

    // Back node to initial state
    $node = new Node($idNodeGenerator);
    if ($node->get('IdState') > 0) {
        $workflow = new \Ximdex\Workflow\WorkFlow($idNodeGenerator);
        $firstState = $workflow->GetInitialState();
        $node->SetState($firstState);
    }
}

main($argc, $argv);
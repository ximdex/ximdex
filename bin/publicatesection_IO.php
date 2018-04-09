#!/usr/bin/env php
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

require_once dirname(__DIR__) . '/bootstrap.php';



use Ximdex\Logger;
use Ximdex\Models\Node;
use Ximdex\Sync\SyncManager;



if (\Ximdex\Modules\Manager::isEnabled('ximSYNC')) {
	\Ximdex\Modules\Manager::file('/inc/manager/SyncManager.class.php', 'ximSYNC');
}
$userximio = "ximIO";
session_id("ximIO");
session_name("ximIO");
\Ximdex\Runtime\Session::set("userID",$userximio);

/// Array with call parameters
$config = array();

/// Calls main process
Main($argv, $argc);


function Main($argv, $argc)
	{
	global $config;
	
	Logger::info("Launching publicate section");

	Logger::info("---------------------------------------------------------------------");
	Logger::info("Executing: Publicate Section");
	Logger::info("---------------------------------------------------------------------");
	
	
	$node		= new Node();
	/// Saves and checks parameters


	for($i=1; $i<sizeof($argv); $i++)
		{

		if($argv[$i] == "--sectionid")
			{

			$node->SetID($argv[$i+1]);

			if(!$node->numErr)
				$config['sectionid'] = $argv[++$i];
			else
				{
				Logger::info("Section does not exist: '".$argv[++$i]."'");
				exit(1);
				}
			}
			
		}
	$rec=$argv[3];


	Logger::info("");
	
	if(!$config['sectionid'])
		{
		Logger::info("Uso del comando:");
		Logger::info("./publicatesection_IO.php --sectionid {id de la seccion} [-r]");
		exit(1);
		}
	Logger::info("---------------------------------------------------------------------");
	Logger::info("Read parameters: ");
	Logger::info("\tXimdex section: ".$config['sectionid'].", ".$node->GetNodeName());
	Logger::info("---------------------------------------------------------------------");

	Logger::info("");
	Logger::info(" Are read parameters corrects?");
	Logger::info("To confirm press uppercase 'A' and then press 'Intro'.");
	Logger::info(" Press Ctrl+C to exit.");

	session_write_close();
	ob_flush();

	$stdin = fopen('php://stdin', 'r');
	do	{
		;
		} while(fread($stdin, 1)!= 'A');
	fclose($stdin);	

	\Ximdex\Runtime\Session::set("userID",$userximio);

	if ($rec=="-r") {
		PublicateSection($config['sectionid'], time(), true);
	}
	else {
		PublicateSection($config['sectionid'], time(), false);
	}
	
	Logger::info("Saliendo de publicate section");
}


function PublicateSection($sectionID,$dateUp,$recurrence) {

        $node = new Node($sectionID);
	if (!($node->nodeType->GetName() == 'Section' || $node->nodeType->GetName() == 'Server')) {
	    Logger::info("Aborting publication, it is just allowed to publish over sections and servers");
	    die();
	}

        $nodename=$node->GetNodeName();
        $childList = $node->GetChildren();

        $nodeList= array();
	$arrayTypes = array('Section');

        foreach ($childList as $child) {
                $node->SetID($child);
		if($recurrence == 1 || (!in_array($node->nodeType->GetName(),$arrayTypes) && $recurrence == 0)){
                        $nodeList = array_merge($nodeList, $node->TraverseTree(6));
                }
        }

        foreach ($nodeList as $nodeID) {
		Logger::info("Publicando IdNode: $nodeID");
		$syncMngr = new SyncManager();

		$syncMngr->setFlag('markEnd', false);

		$syncMngr->setFlag('linked',true);
		$syncMngr->setFlag('deleteOld',true);

		$syncMngr->pushDocInPublishingPool($nodeID, $dateUp, NULL);
        }
}
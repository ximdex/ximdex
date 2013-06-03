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


ModulesManager::file('/inc/model/role.inc');
ModulesManager::file('/inc/model/user.inc');
ModulesManager::file('/inc/model/node.inc');
ModulesManager::file('/inc/mail/Mail.class.php');
ModulesManager::file('/inc/sync/SynchroFacade.class.php');
ModulesManager::file('/inc/workflow/Workflow.class.php');
ModulesManager::file('inc/pipeline/PipeTransition.class.php');
ModulesManager::file('/inc/Toldox.class.php', 'tolDOX');
ModulesManager::file('/actions/browser3/inc/GenericDatasource.class.php');
ModulesManager::file('/inc/serializer/Serializer.class.php');

class Action_workflow_forward extends ActionAbstract {

	public function index() {

		$idNode = $this->request->getParam('nodeid');
		$nodes = $this->request->getParam('nodes');

		if (empty($idNode)) {
			$idNode = $nodes[0];
		}

		if (!$this->validateInIndex($idNode)){
			$this->renderMessages();
		}

		$this->addJs('/actions/workflow_forward/resources/js/workflow_forward.js');
		$this->addCss('/actions/workflow_forward/resources/css/style.css');
		$this->addCss('/xmd/style/jquery/ximdex_theme/widgets/calendar/calendar.css');

		$idUser = XSession::get('userID');
		$user = new User($idUser);

		//Getting list of groups where user is added: to show it in select input
		$group = new Group();
		$groupList=$group->find('IdGroup', NULL, NULL, MONO);
		$groupState=$this->_getStateForGroups($idNode, $groupList);

		//Getting user roles on current node
		$userRoles=$user->GetRolesOnNode($idNode); 
		
		//Getting current state
		$node = new Node($idNode);
		$workflow = new WorkFlow($idNode, $node->GetState());

		//Getting next state
		$nextState = $workflow->GetNextState();
		
		//Checking if the user has some role with permission to change to next State
		$allowed=FALSE;
		foreach($userRoles as $userRole => $myIdRole) {
			$role = new Role($myIdRole);
			if($role->HasState($nextState)) {
				$allowed=TRUE;
				break;
			}
		}

		if(!$allowed) {
			$this->messages->add(_('You have not privileges to move forward the node to next status.'), MSG_TYPE_WARNING);
			$this->messages->add(_('You have not assigned a role with privileges to modify workflow status on any of groups associated with the node or the section which contains it.'), MSG_TYPE_WARNING);

			$this->addJs('/actions/workflow_forward/resources/js/workflow_forward.js');
			$values = array(
				'messages' => $this->messages->messages
			);

			$this->render($values, 'show_results', 'default-3.0.tpl');

			return ;
		}

		// If node in final state show the latest form
		$workflowNext = new WorkFlow($idNode, $nextState);
		$nextStateName=$workflowNext->GetName();

		$AllStates=$workflow->GetAllStates();
		$find=false;
		foreach($AllStates as $state){
			//If this state is after currentState, append the next state
			if($find){
				$foundRol = false;
				foreach($userRoles as $userRole => $myIdRole) {
					$role = new Role($myIdRole);
                        		if($role->HasState($state)) {
						$workflowAll = new WorkFlow($idNode, $state);
						$AllowedStates[$state]=$workflowAll->GetName();
						$foundRol = true;
						break;
					}
				}
				//If we havent got permission for this workflow, we dont append nothing more
				if (!$foundRol)
					break;
			}
			//if found the current state, we activate the flag
			if ($state == $node->GetState())
				$find = true;
		}
		$conf = ModulesManager::file('/conf/notifications.conf');
		$defaultMessage=$this->buildMessage($conf["defaultMessage"],$workflow->GetName(),$node->get('Name'));
		$values = array(
                        	 'group_state_info' => $this->selectableGroups($idNode, $groupState),
                                 'state' => $nextStateName,
                                 'stateid' => $nextState,
                                 'required' => $conf['required'] === true ? 1 : 0,
                                 'defaultMessage' => $defaultMessage
                                );
		
		//Only for Strdocs
		if($node->nodeType->GetID()==5032){
			if ($workflowNext->IsFinalState()) {
				$values['go_method'] = 'publicateNode';
				$values = array_merge($values, $this->publicationGaps($idNode));
				$this->render($values, NULL,'default-3.0.tpl');
			}
			else{
				$defaultMessage=$this->buildMessage($conf["defaultMessage"],'siguiente',$node->get('Name'));
                                $values['defaultMessage']= $defaultMessage;
				$values2 = array(
					'go_method' => 'publicateForm',
					'allowedstates' => $AllowedStates,
					'nextStateName' => $nextStateName,
					'currentStateName' => $workflow->GetName()
				);	
				$values = array_merge($values2, $values);
				$this->render($values, 'next_state.tpl','default-3.0.tpl');
			}
		}
		//Rest of nodetypes
		else{
			//show the publication form
			$workflowPub = new WorkFlow($idNode, $workflow->GetFinalState());
			$pubStateName=$workflowPub->GetName();
                       
			$values['go_method']= 'publicateNode';
			$values['state']= $pubStateName;
			$defaultMessage=$this->buildMessage($conf["defaultMessage"],$pubStateName,$node->get('Name'));
			$values['defaultMessage']= $defaultMessage;

                        $values = array_merge($values, $this->publicationGaps($idNode));
                        $this->render($values, NULL,'default-3.0.tpl');
		}
	}

	private function buildMessage($message, $stateName,$nodeName){
		$mesg=preg_replace('/%doc/', $nodeName, $message);
		$mesg=preg_replace('/%state/', $stateName,$mesg);
		return $mesg;
	}

	public function publicateForm(){
		
		$idNode = $this->request->getParam('nodeid');
		$nextState = $this->request->getParam('nextstate');

		$conf = ModulesManager::file('/conf/notifications.conf');

		$node = new Node($idNode);
                $workflow = new WorkFlow($idNode, $nextState);

		$sendNotifications = $this->request->getParam('sendNotifications');
                $notificableUsers = $this->request->getParam('users');
                $idState = $this->request->getParam('stateid');
                $texttosend = $this->request->getParam('texttosend');

		$group = new Group();
                $groupList=$group->find('IdGroup', NULL, NULL, MONO);
                $groupState=$this->_getStateForGroups($idNode, $groupList);

                if ((boolean)$sendNotifications) {
                        $sent = $this->sendNotifications($idNode, $idState, $notificableUsers, $texttosend);
                        if (!$sent) {
                                $values = array(
                                        'goback' => true,
                                        'messages' => $this->messages->messages
                                );
                                $this->render($values, 'show_results', 'default-3.0.tpl');
                                return;
                        }
                }

		if($workflow->IsFinalState()){
                	$this->addJs('/actions/workflow_forward/resources/js/workflow_forward.js');
			$defaultMessage=$this->buildMessage($conf["defaultMessage"], $nextState,$node->GetName());
			$values = array(
                                        'group_state_info' => $this->selectableGroups($idNode, $groupState),
                                        'go_method' => 'publicateNode',
                                        'state' => $workflow->GetName(),
                                        'required' => $conf['required'] === true ? 1 : 0,
                                        'defaultMessage' => $defaultMessage
                                );

               		$this->render($values, 'index.tpl','default-3.0.tpl');	
		}
		else{
			$node->setState($nextState);
			$values = array(
                                'go_method' => 'publicateForm',
                                'nextState' => $nextState,
                                'currentState' => $node->GetState()
                        );
               		$this->render($values, 'success.tpl','default-3.0.tpl');	
		}
	}

	/**
	 * Obtains the groups to be selected
	 * Called from index
	 */
	 private function selectableGroups($idNode, $groupState) {
	 	$groupStateInfo = array();
		foreach ($groupState as $idGroup => $idState) {
			$group = new Node($idGroup);
			$workflow = new WorkFlow($idNode, $idState);
			$idS = $workflow->pipeStatus->get('id');
			$idG = $group->get('Name');
			$sN = $workflow->pipeStatus->get('Name');

			$groupStateInfo[] = array(
				'IdGroup' => $group->get('IdNode'),
				'groupName' => $idG,
				'IdState' => $idS,
				'stateName' => $sN
			);
		}
		return $groupStateInfo;
	 }

	/**
	 * Returns a JSON object with users of the selected group.
	 * Called from an ajax request.
	 */
	public function notificableUsers() {

		$idGroup = $this->request->getParam('groupid');
		$idState = $this->request->getParam('stateid');
		$idNode = $this->request->getParam('nodeid');
		//$idNode = $this->path2id($idNode);

		$group = new Group($idGroup);
		$workflow = new WorkFlow($idNode, $idState);
		$values = array(
			'messages' => array(
				_('You do not belong to any group with publication privileges')
			)
		);

		if ($group->get('IdGroup') > 0 && $this->validateInSelectedGroup($group,$workflow,$idState,$idGroup)) {

			$role = new Role();
			$roles = $role->getAllRolesForStatus($idState);
			$user = new User();
			$users = $user->GetAllUsers();
			$notificableUsers = array();

			if(!empty($users) && is_array($users)) {
				foreach ($users as $idUser) {
					$user = new User($idUser);
					$idRole = $user->GetRoleOnNode($idNode, $idGroup);
					if (($idRole > 0) && (in_array($idRole, $roles))) {
						$notificableUsers[] = array('idUser' => $idUser, 'userName' => $user->get('Name'));
					}
				}
			}

			$values = array(
				'group' => $idGroup,
				'groupName' => $group->get('Name'),
				'state' => $idState,
				'stateName' => $workflow->pipeStatus->get('Name'),
				'notificableUsers' => $notificableUsers
			);
		}

		header('Content-type: application/x-json');
		$values = Serializer::encode(SZR_JSON, $values);
		echo $values;
	}

	/**
	 * Obtains the publication intervals
	 * Called from index
	 */
	private function publicationGaps($idNode) {

		setlocale(LC_TIME, "es_ES");

		$idUser = XSession::get('userID');
		$user = new User($idUser);

		$node = new Node($idNode);
		$nodeTypeName = $node->nodeType->GetName();
		$showRepOption = ($nodeTypeName == "XimNewsBulletinLanguage") ? false : true;

		$gaps = SynchroFacade::getGaps($idNode);
		$gapInfo = array();

		if (count($gaps) > 0) {
			foreach ($gaps as $gap) {
				$gapInfo[] = array(
					'BEGIN_DATE' => strftime("%d/%m/%Y %H:%M:%S", $gap['start']),
					'END_DATE' => isset($gap['end']) ? strftime("%d/%m/%Y %H:%M:%S", $gap['end']) : null
				);
			}
		}

		return array(
			'gap_info' => $gapInfo,
			'has_unlimited_life_time' => SynchroFacade::HasUnlimitedLifeTime($idNode),
			'timestamp_from' => mktime(),
			'structural_publication' => $user->HasPermission('structural_publication') ? '1' : '0',
			'advanced_publication' => $user->HasPermission('advanced_publication') ? '1' : '0',
			'nodetypename' => $nodeTypeName,
			'synchronizer_to_use' => ModulesManager::isEnabled('ximSYNC') ? 'ximSYNC' : 'default',
			'ximpublish_tools_enabled' => ModulesManager::isEnabled('ximPUBLISHtools'),
			'show_rep_option' => $showRepOption
		);
	}

	/**
	 * Sends notifications and sets node state
	 * Called from publicateNode
	 */
	private function promoteNode($idNode, $idState) {

		$idUser = XSession::get("userID");
		$node = new Node($idNode);
		$idActualState = $node->get('IdState');
		$actualWorkflowStatus = new WorkFlow($idNode, $idActualState);

		$idTransition = $actualWorkflowStatus->pipeProcess->getTransition($idActualState);
		$transition = new PipeTransition($idTransition);
		$callback = $transition->get('Callback');

		if (!empty($callback) && is_file(XIMDEX_ROOT_PATH . sprintf('/inc/repository/nodeviews/View_%s.class.php', $callback))) {
			$dataFactory = new DataFactory();
			$idVersion = $dataFactory->GetLastVersionId();
			$transformedContent = $transition->generate($idVersion, $node->GetContent(), array());
			$node->SetContent($transformedContent);
		}

		$result = $node->setState($idState);
		if ($result) {
			$this->messages->add(_('State has been successfully changed'), MSG_TYPE_NOTICE);
		} else {
			$this->messages->mergeMessages($node->messages);
		}
	}

	/**
	 * Sends notifications and sets node state
	 * Called from publicateNode
	 */
	private function sendNotifications($idNode, $idState, $userList, $texttosend) {

		$send = true;

		if (count($userList) == 0) {
			$this->messages->add(_('Users to notify has not been selected.'), MSG_TYPE_WARNING);
			$send = false;
		}

		if (empty($texttosend)) {
			$this->messages->add(_('No message specified.'), MSG_TYPE_WARNING);
			$send = false;
		}

		if (!$send) {
			return false;
		}

		$idUser = XSession::get("userID");

		$node = new Node($idNode);
		$idActualState = $node->get('IdState');
		$actualWorkflowStatus = new WorkFlow($idNode, $idActualState);
		$nextWorkflowStatus = new WorkFlow($idNode, $idState);

		if (count($userList) > 0) {
			$userNameList = array();
			foreach($userList as $id) {
				$user = new User($id);
				$userNameList[] = $user->get('Login');
			}
			$userNameString = implode(', ', $userNameList);
		}

		$user = new User($idUser);
		$from = $user->get('Login');
		$userName = $user->get('Name');
		$nodeName = $node->get('Name');
		$nodePath = $node->GetPath();

		$nextStateName = $nextWorkflowStatus->pipeStatus->get('Name');
		$actualStateName = $actualWorkflowStatus->pipeStatus->get('Name');

		$subject = _("Ximdex CMS: new state for document:")." ".$nodeName;
		$content  =
			_("State forward notification.") . "\n"
			. "\n"
			. _("The user")." ".$userName." "._("has changed the state of")." ".$nodeName."\n"
			. "\n"
			. _("Full Ximdex path")." --> ".$nodePath."\n"
			. "\n"
			. _("Initial state")." --> ".$actualStateName."\n"
			. _("Final state")." --> ".$nextStateName."\n"
			. "\n"
			. "\n"
			. _("Comment").":"."\n"
			. $texttosend."\n"
			. "\n";

		foreach($userList as $id) {
			$user = new User($id);

			$userEmail = $user->get('Email');
			$userName = $user->get('Name');

			$mail = new Mail();
			$mail->addAddress($userEmail, $userName);
			$mail->Subject = $subject;
			$mail->Body = $content;
			if ($mail->Send()) {
				$this->messages->add(sprintf(_("Message successfully sent to %s"), $userEmail), MSG_TYPE_NOTICE);
			} else {
				$this->messages->add(sprintf(_("Error sending message to the mail address %s"), $userEmail), MSG_TYPE_WARNING);
			}
		}

		return true;
	}

	/**
	 * Publicate the node
	 */
	public function publicateNode() {

		$idNode = $this->request->getParam('nodeid');
		//$idNode = $this->path2id($idNode);

		$dateUp = $this->request->getParam('fechainicio');
		$dateDown = $this->request->getParam('fechafin');

		$up = (!is_null($dateUp) && $dateUp != "") ? $dateUp : mktime();
		$down = (!is_null($dateDown) && $dateDown != "") ? $dateDown : null;

		$markEnd = $this->request->getParam('markend') ? true : false;
		$republicate = $this->request->getParam('republicar') ? true : false;

		$structure = $this->request->getParam('no_structure') == 'on' ? false : true;
		$deepLevel = $this->request->getParam('all_levels') == 1 ? -1 : $this->request->getParam('deeplevel');

		$sendNotifications = $this->request->getParam('sendNotifications');
		$notificableUsers = $this->request->getParam('users');
		$idState = $this->request->getParam('stateid');
		$texttosend = $this->request->getParam('texttosend');
		$this->addJs('/actions/workflow_forward/resources/js/workflow_forward.js');

		if ((boolean)$sendNotifications) {
			$sent = $this->sendNotifications($idNode, $idState, $notificableUsers, $texttosend);
			if (!$sent) {
				$values = array(
					'goback' => true,
					'messages' => $this->messages->messages
				);
				$this->render($values, 'show_results', 'default-3.0.tpl');
				return;
			}
		}

		$this->promoteNode($idNode, $idState);

		$node = new Node($idNode);

		$flagsPublication = array(
			'markEnd' => $markEnd,
			'linked' => $republicate,
			'structure' => $structure,
			'deeplevel' => $deepLevel,
			'force' => true,
			'recurrence' => false,
			'workflow' => true
		);

		$result = SynchroFacade::pushDocInPublishingPool($idNode, $up, $down, $flagsPublication);

		$arrayOpciones = array(
			'ok' => _(' were successfully published'),
			'notok' => _(' were not published, due to some error along the process'),
			'unchanged' => _(' were not published because they are already published in its latest version')
		);

		$valuesToShow = array();
		foreach ($arrayOpciones as $idOpcion => $texto) {

			if (array_key_exists($idOpcion, $result)) {
				foreach ($result[$idOpcion] as $idNode => $physicalServer) {

					if(gettype($idNode) == 'string') {
						$idNode = intval(str_replace("#", "", $idNode));
					}

					$nodePublished = new Node($idNode);
					foreach ($physicalServer as $idPhysicalServer => $channel) {
						$server = new Server($idPhysicalServer);
						$keys = array_keys($channel);
						foreach ($keys as $idChannel) {
							$channel = new Node($idChannel);
							$valuesToShow[$idOpcion][] = array(
								'NODE' => $nodePublished->get('Name'),
								'PATH' => $nodePublished->GetPath(),
								'SERVER' => $server->get('Description'),
								'CHANNEL' => $channel->get('Name')
							);
						}
					}
				}
			}
		}

		$node = new Node($idNode);
		$workflow = new WorkFlow($idNode);
		$firstState = $workflow->GetInitialState();
		$node->setState($firstState);

		if (ModulesManager::isEnabled('ximSYNC')) {
			$values = array(
				'options' => $arrayOpciones,
				'result' => $valuesToShow,
				'messages' => $this->messages->messages
			);

			$this->render($values, 'show_results', 'default-3.0.tpl');
			return;
		} else {

			$values = array(
				'node_name' => $node->get('Name'),
				'messages' => $this->messages->messages,
				'options' => $arrayOpciones,
				'result' => $valuesToShow,
				'synchronizer_to_use' => ModulesManager::isEnabled('ximSYNC') ? 'ximSYNC' : 'default'
			);

			$this->render($values, 'show_results', 'default-3.0.tpl');
		}
	}

	private function _getStateForGroups($idNode, $groupList) {
		$node = new Node($idNode);
		$groupState = array();
		if (is_array($groupList) && !empty($groupList)) {
			foreach ($groupList as $idGroup) {
				$group = new Group($idGroup);
				$users = $group->GetUserList();
				if (is_array($users) && !empty($users)) {
					foreach ($users as $idUser) {
						$nextState = $node->GetNextAllowedState($idUser, $idGroup);
						if ($nextState > 0) {
							$groupState[$idGroup] = $nextState;
						}
					}
				}
			}
		}
		return $groupState;

	}

	//VALIDATE
	private function validateInIndex($idNode){
		//Validate IdNode
		$node = new Node($idNode);
		if (!($node->get('IdNode') > 0)) {
			$this->messages->add(_('The node could not be loaded'), MSG_TYPE_ERROR);
			return false;
		}

		$idServer = $node->getServer();
		$serverNode = new Node($idServer);

		//Validate Servers
		if (!($serverNode->get('IdNode') > 0)) {
			$this->messages->add(_('The server could not be loaded'), MSG_TYPE_ERROR);
		} else {
			$server = new Server();
			$servers = $server->find('IdServer', 'IdNode = %s', array($serverNode->get('IdNode')), MONO);
			if (!(count($servers) > 0)) {
				$this->messages->add(_('The document belongs to a server which has not any configured physical server.'), MSG_TYPE_ERROR);
			}
		}
		if ($this->messages->count(MSG_TYPE_ERROR) > 0) {
			return false;
		}

		//Validate workflow
		$idWorkFlowSlave = $node->get('SharedWorkflow');
		if ($idWorkFlowSlave > 0) {
			$masterNode = new Node($idWorkFlowSlave);
			$this->messages->add(sprintf(_('The publication cycle of the selected document is currently linked to the document [%s]'), $masterNode->GetPath()), MSG_TYPE_WARNING);
			$this->messages->add(_('The linked file should be selected in order to go forward the next state'), MSG_TYPE_WARNING);

			return false;
		}

		return true;
	}

	private function validateInSelectedGroup($group,$workflow,$idState,$idGroup){

		if (!$group->get('IdGroup') > 0) {
			$this->messages->add(sprintf(_('No information about the selected group (%s) could be obtained'), $idGroup), MSG_TYPE_ERROR);
		}
		if (!$workflow->pipeStatus->get('id') > 0) {
			$this->messages->add(sprintf(_('No information about the selected workflow state (%s) could be obtained'), $idState), MSG_TYPE_ERROR);
		}

		if ($this->messages->count(MSG_TYPE_ERROR) > 0) {
			$this->render(array('messages' => $this->messages->messages), NULL, 'messages_in_progress_action.tpl');
			return false;
		}

		return true;
	}

	/**
	 * Show message error when there arent any groups
	 *
	 */
	private function renderWithNotGroupStates($idNode, $groupState) {
	
		$this->messages->add(_('You have no privileges to modify the workflow state of this document.'), MSG_TYPE_WARNING);
		$this->addJs('/actions/workflow_forward/resources/js/workflow_forward.js');

		if (count($groupState) > 0) {
			$this->messages->add(_('The groups with privileges to perform this action are the following'), MSG_TYPE_WARNING);
			foreach ($groupState as $idGroup => $idState) {
				$group = new Node($idGroup);

				$workflow = new WorkFlow($idNode, $idState);
				$this->messages->add(sprintf(_("El grupo '%s' puede promocionar el documento al estado '%s'"),
				$group->get('Name'), $workflow->pipeStatus->get('Name')), MSG_TYPE_WARNING);
			}
		} 
		else {
			$this->messages->add(_("Currently there is no groups which can move forward the document to any state, and your role in the General group has no permissions to go forward to next state, consult your administrator"), MSG_TYPE_WARNING);
		}
		$values = array(
			'messages' => $this->messages->messages
		);
		$this->render($values, 'show_results', 'default-3.0.tpl');
	}
}
?>

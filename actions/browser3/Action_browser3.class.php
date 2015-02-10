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
 * @version $Revision: 8735 $
 */


ModulesManager::file('/inc/model/locale.php');
ModulesManager::file('/inc/search/QueryProcessor.class.php');
ModulesManager::file('/inc/xvfs/XVFS.class.php');
ModulesManager::file('/inc/serializer/Serializer.class.php');
ModulesManager::file('/inc/model/NodeSets.class.php');
ModulesManager::file('/inc/model/SearchFilters.class.php');
ModulesManager::file('/actions/browser3/inc/GenericDatasource.class.php');
ModulesManager::file('/inc/model/ActionsStats.class.php');
ModulesManager::file('/inc/validation/FormValidation.class.php');


class Action_browser3 extends ActionAbstract
{

    const CSS_PATH = '/actions/browser3/resources/css';
    const JS_PATH = '/actions/browser3/resources/js';

    // Used previously for session cache
    const ACTIONS_INTERSECTION = 'browser_actions_intersection';

    public function index()
    {
        if (!is_string(\Ximdex\Utils\Session::get('activeTheme'))) {
            \Ximdex\Utils\Session::set('activeTheme', 'ximdex_theme');
        }

        $params = $this->request->getParam('params');
        $loginName = \Ximdex\Utils\Session::get('user_name');
        $userID = (int)\Ximdex\Utils\Session::get('userID');

        /*Test Session*/
        $session_info = session_get_cookie_params();
        $session_lifetime = $session_info['lifetime']; // session cookie lifetime in seconds
        //$session_duration = session_cache_expire(); // in minutes
        $session_duration = $session_lifetime != 0 ? $session_lifetime : session_cache_expire() * 60;

        $sessionExpirationTimestamp = \Ximdex\Utils\Session::get("loginTimestamp") + $session_duration * 60;
        setcookie("loginTimestamp", \Ximdex\Utils\Session::get("loginTimestamp"));
        setcookie("sessionLength", $session_duration);
        /**/

        $locale = new XimLocale();
        $user_locale = $locale->GetLocaleByCode(\Ximdex\Utils\Session::get('locale'));
        $locales = $locale->GetEnabledLocales();

        $values = array(
            'params' => $params,
            'userID' => $userID,
            'time_id' => time() . "_" . \Ximdex\Utils\Session::get('userID'), /* For uid for scripts */
            'loginName' => $loginName,
            'user_locale' => $user_locale,
            'locales' => $locales,
            'xinversion' => \App::getValue( "VersionName")
        );

        $this->addCss('/xmd/style/jquery/smoothness/jquery-ui-1.8.2.custom.css');
        $this->addCss('/extensions/bootstrap/dist/css/bootstrap.min.css');
        $this->addCss('/extensions/ladda/dist/ladda-themeless.min.css');
        $this->addCss('/extensions/humane/flatty.css');
        $this->addCss('/xmd/style/jquery/ximdex_theme/widgets/treeview/treeview.css');
        $this->addActionCss('browser.css');
        if (ModulesManager::isEnabled('ximTOUR'))
            $this->addCss('/modules/ximTOUR/resources/css/tour.css');


        if (ModulesManager::isEnabled('ximADM')) {
            $time_id = time() . "_" . $userID;

            $this->addJs('/utils/user_connect.js.php?id=' . $time_id . '&lang=' . $user_locale["Lang"], 'ximADM');
        }

        $this->addJs('/inc/js/helpers.js');
        $this->addJs('/inc/js/collection.js');
        $this->addJs('/inc/js/dialogs.js');
        $this->addJs('/inc/js/console.js');
        $this->addJs('/inc/js/sess.js');
        $this->addJs('/inc/js/eventHandler.js');
        $this->addJs(Extensions::JQUERY);
        $this->addJs(Extensions::JQUERY_UI);
        $this->addJs('/inc/js/i18n.js');
        $this->addJs('/extensions/vendors/hammerjs/hammer.js/hammer.min.js');
        $this->addJs('/extensions/angular/angular.min.js');
        $this->addJs('/extensions/vendors/RyanMullins/angular-hammer/angular.hammer.min.js');
        $this->addJs('/extensions/angular/angular-animate.min.js');
        $this->addJs('/extensions/angular/angular-sanitize.min.js');
        $this->addJs('/extensions/angular-ui-sortable/src/sortable.js');
        $this->addJs('/extensions/ladda/dist/spin.min.js');
        $this->addJs('/extensions/ladda/dist/ladda.min.js');
        $this->addJs('/extensions/humane/humane.min.js');
        $this->addJs('/extensions/flow/ng-flow-standalone.min.js');
        $this->addJs('/extensions/angular-bootstrap/dist/ui-bootstrap-custom-tpls-0.13.0-SNAPSHOT.min.js');
        $this->addJs(Extensions::JQUERY_PATH . '/ui/jquery-ui-timepicker-addon.js');
        $this->addJs(Extensions::JQUERY_PATH . '/ui/jquery.ui.tabs.min.js');
        $this->addJs(Extensions::JQUERY_PATH . '/ui/jquery.ui.dialog.min.js');
        $this->addJs(Extensions::JQUERY_PATH . '/plugins/jquery-validate/jquery.validate.js');
        $this->addJs(Extensions::JQUERY_PATH . '/plugins/jquery-validate/localization/messages_' . $user_locale["Lang"] . '.js');
        $this->addJs('/inc/validation/js/ximdex.form.validation.js');
        $this->addJs(Extensions::JQUERY_PATH . '/plugins/jquery.json/jquery.json-2.2.min.js');
        $this->addJs(Extensions::JQUERY_PATH . '/plugins/jquery.labelwidth/jquery.labelwidth.js');
        $this->addJs(Extensions::JQUERY_PATH . '/plugins/jquery-file-upload/js/jquery.fileupload.js');
        $this->addJs(Extensions::JQUERY_PATH . '/plugins/jquery-file-upload/js/jquery.fileupload-process.js');
        $this->addJs(Extensions::JQUERY_PATH . '/plugins/jquery-file-upload/js/jquery.fileupload-angular.js');
        $this->addJs('/extensions/d3js/d3.v3.min.js');
        $this->addJs('/extensions/vendors/codemirror/Codemirror/lib/codemirror.js');
        $this->addJs('/inc/js/angular/app.js');
        $this->addJs('/inc/js/angular/animations/slide.js');
        $this->addJs('/inc/js/angular/services/xTranslate.js');
        $this->addJs('/inc/js/angular/services/xBackend.js');
        $this->addJs('/inc/js/angular/services/xUrlHelper.js');
        $this->addJs('/inc/js/angular/services/xEventRelay.js');
        $this->addJs('/inc/js/angular/services/xDialog.js');
        $this->addJs('/inc/js/angular/services/xCheck.js');
        $this->addJs('/inc/js/angular/services/xMenu.js');
        $this->addJs('/inc/js/angular/services/xTabs.js');
        $this->addJs('/inc/js/angular/services/angularLoad.js');
        $this->addJs('/inc/js/angular/directives/compileTemplate.js');
        $this->addJs('/inc/js/angular/directives/ximButton.js');
        $this->addJs('/inc/js/angular/directives/ximSelect.js');
        $this->addJs('/inc/js/angular/directives/ximValidators.js');
        $this->addJs('/inc/js/angular/directives/xtagsSuggested.js');
        $this->addJs('/inc/js/angular/directives/contenteditable.js');
        $this->addJs('/inc/js/angular/directives/ximFile.js');
        $this->addJs('/inc/js/angular/directives/ximUploader.js');
        $this->addJs('/inc/js/angular/directives/ximFocusOn.js');
        $this->addJs('/inc/js/angular/directives/rightClick.js');
        $this->addJs('/inc/js/angular/directives/ximGrid.js');
        $this->addJs('/inc/js/angular/directives/ximInverted.js');
        $this->addJs('/inc/js/angular/directives/ximFitText.js');
        $this->addJs('/inc/js/angular/directives/ximMenu.js');
        $this->addJs('/inc/js/angular/directives/ximTree.js');
        $this->addJs('/inc/js/angular/directives/ximList.js');
        $this->addJs('/inc/js/angular/filters/xFilters.js');
        $this->addJs('/inc/js/angular/controllers/XTabsCtrl.js');
        $this->addJs('/inc/js/angular/controllers/XTagsCtrl.js');
        $this->addJs('/inc/js/angular/controllers/XModifyUserGroupsCtrl.js');
        $this->addJs('/inc/js/angular/controllers/XModifyGroupUsersCtrl.js');
        $this->addJs('/inc/js/angular/controllers/XModifyStates.js');
        $this->addJs('/inc/js/angular/controllers/XModifyStatesRole.js');
        $this->addJs('/inc/js/angular/controllers/XTreeCtrl.js');
        $this->addJs('/inc/js/angular/controllers/XSetExtensions.js');
        $this->addJs('/inc/js/angular/controllers/ximPUBLISHtools.js');
        $this->addJs('/inc/js/angular/controllers/XUserMenuCtrl.js');
        $this->addActionJs('XMainCtrl.js');
        $this->addActionJs('controller.js');

        /* *********************************** SPLASH ************************************** */
        define("REMOTE_WELCOME", STATS_SERVER . "/stats/getsplash.php");
        $ctx = stream_context_create(array(
                'http' => array(
                    'timeout' => 2
                )
            )
        );

        //$url = REMOTE_WELCOME."?lang=".strtolower(\Ximdex\Utils\Session::get("locale"))."&ximid=".\App::getValue( 'ximid');
        $url = REMOTE_WELCOME . "?lang=" . strtolower(\Ximdex\Utils\Session::get("locale"));
        //get remote content
        $splash_content = @file_get_contents($url, 0, $ctx);
        if (!empty($splash_content)) {
            $values["splash_content"] = $splash_content;
            $values["splash_file"] = null;
        } elseif (file_exists(XIMDEX_ROOT_PATH . "/actions/browser3/template/Smarty/splash/index.tpl")) {
            $values["splash_content"] = null;
            $values["splash_file"] = XIMDEX_ROOT_PATH . "/actions/browser3/template/Smarty/splash/index.tpl";
        } else {
            $values["splash_content"] = "Sorry, splash image temporarily unavaliable.";
            $values["splash_file"] = null;
        }
        /* ************************************************************************************** */

        $this->render($values, 'index', 'only_template.tpl');
    }

    /**
     * Refresh the session regenerating the session ID and cookie
     *
     */
    public function refreshSession()
    {
        \Ximdex\Utils\Session::refresh();
    }

    /**
     * Returns templates for actions panel
     */
    public function actionTemplate()
    {

        $template = $this->request->getParam('template');
        $template = sprintf('actionPanel%s', ($template === null ? 'Main' : ucfirst(strtolower($template))));

        $values = array();

        $this->render($values, $template, 'only_template.tpl');
    }

    public function addActionCss($css)
    {
        parent::addCss(sprintf('%s/%s', Action_browser3::CSS_PATH, $css));
    }

    public function addActionJs($js)
    {
        parent::addJs(sprintf('%s/%s', Action_browser3::JS_PATH, $js));
    }

//	protected function sendJSON($data) {
//		$data = Serializer::encode(SZR_JSON, $data);
//		header('Content-type: application/json');
//		echo $data;
//	}

    protected function sendXML($data)
    {
        header('Content-type: text/xml');
        echo $data;
    }

    /**
     * Returns a JSON object with the allowed nodetypes for searches
     */
    public function nodetypes()
    {
        $ret = GenericDatasource::nodetypes($this->request);
        $this->sendJSON($ret);
    }

    /**
     * Returns a JSON document with all parents of the specified node id
     */
    public function parents()
    {
        $ret = GenericDatasource::parents($this->request);
        $this->sendJSON($ret);
    }

    /**
     * Returns a JSON document with all children of the specified node id
     */
    public function read()
    {
        $ret = GenericDatasource::read($this->request);
        $ret['collection'] = $this->checkNodeAction($ret['collection']);
        header('Content-type: application/json');
        $data = Serializer::encode(SZR_JSON, $ret);
        echo $data;
    }

    /**
     * Returns a JSON document with all children of the specified node id
     * filtered by the filter param
     */
    public function readFiltered()
    {
        $query = $this->request->getParam('query');

        $ret = GenericDatasource::read($this->request);
        $ret['collection'] = $this->checkNodeAction($ret['collection']);

        $sql = "SELECT count(*) as cont FROM FastTraverse f
              INNER JOIN Nodes n on n.IdNode=f.IdChild and f.IdNode = %d and not n.IdNode=%d and n.name like '%s'
              inner join NodeTypes nt on nt.IdNodeType = n.IdNodeType and NOT(nt.IsHidden) and not nt.IdNodeType in (5084,5085)";
        $db = new DB();
        $removed = 0;
        $queryToMatch = "/".$query."/i";
        $queryToMatch = str_replace(array(".","_"),array('\.',"."),$queryToMatch);
        foreach($ret["collection"] as $id => $child){
            $sql2 = sprintf($sql, $child["nodeid"], $child["nodeid"], '%'.$query.'%');
            $db->query($sql2);
            $cont = 0;
            while (!$db->EOF) {
                $cont = $db->getValue('cont');
                break;
            }
            $check = preg_match($queryToMatch,$child['name']);
            $ret["collection"][$id-$removed]["originalName"] = $child["name"];
            $ret["collection"][$id-$removed]["name"] = preg_replace ($queryToMatch, '<span class="filter-word-span">$0</span>', $child["name"]);
            if($cont=="0" && $check!==1){
                array_splice($ret["collection"],$id-$removed,1);
                $removed++;
            }elseif($cont=="0"){
                $ret["collection"][$id-$removed]["children"] = 0;
            }else{
                $ret["collection"][$id-$removed]["name"] .= sprintf ('&nbsp;<span class="filter-results-span">[Results: %s]</span>', $cont);
            }
        }

        header('Content-type: application/json');
        $data = Serializer::encode(SZR_JSON, $ret);
        echo $data;
    }

    /**
     * Check if the nodes have associated actions
     */
    protected function checkNodeAction(&$nodes)
    {

        $db = new DB();
        $sql = 'select count(1) as total from Actions a left join Nodes n using(IdNodeType) where IdNode = %s and a.Sort > 0';
        $sql2 = $sql . " AND a.Command='fileupload_common_multiple' ";

        if (!empty($nodes)) {
            foreach ($nodes as &$node) {
                $nodeid = $node['nodeid'];
                $_sql = sprintf($sql, $nodeid);

                $db->query($_sql);
                $total = $db->getValue('total');
                $node['hasActions'] = $total;


                $db = new DB();
                $sql2 = sprintf($sql2, $nodeid);
                $db->query($sql2);
                $total = $db->getValue('total');
                $node['canUploadFiles'] = $total;
            }

            return $nodes;
        } else {
            XMD_Log::info(_('Empty nodes in checkNodeAction [ browser3 ]'));
            return null;
        }
    }

    /**
     * Instantiates a QueryHandler based on the "handler" parameter and does
     * a search with the "query" parameter options.
     * The "query" parameter could be a XML or JSON string
     */
    public function search()
    {

        $handler = strtoupper($this->request->getParam('handler'));
        $handler = empty($handler) ? 'SQL' : $handler;
        $output = strtoupper($this->request->getParam('output'));
        $output = empty($output) ? 'JSON' : $output;
        $query = $this->request->getParam('query');

        $ret = $this->_search($handler, $output, $query);
        if ($output == 'JSON') {
            $this->sendJSON($ret);
        } else {
            $this->sendXML($ret);
        }
    }


    /**
     * Instantiates a QueryHandler based on the "handler" parameter and does
     * a search with the "query" parameter options.
     * The "query" parameter could be a XML or JSON string
     */
    protected function _search($handler, $output, $query)
    {

        $request = new Request();
        $request->setParameters(array(
            'handler' => $handler,
            'output' => $output,
            'query' => $query,
            'filters' => $this->request->getParam('filters')
        ));

        // By default "listview", used only when it's "treeview"
        $view = isset($query['view']) ? $query['view'] : null;

        $ret = GenericDatasource::search($request);

        if ("SQLTREE" != $handler) {
            $handler = QueryProcessor::getInstance($handler);
            $query = $handler->getQueryOptions($query);

            $ret['query'] = $query;
            $ret = $this->resutlsHierarchy($view,
                isset($query['parentid']) ? $query['parentid'] : null, $ret, $handler);
        } else {


            return $ret;
        }

        return $ret;
    }

    protected function resutlsHierarchy($view, $parentId, $results, $handler)
    {

        if ($view != 'treeview') return $results;

        $results = $results['data'];
        $data = array();

        foreach ($results as $item) {

            $node = new Node($item['nodeid']);
            if (!($node->get('IdNode') > 0)) continue;

            $ancestors = $node->getAncestors();
            $p = null;
            $i = 0;
            $count = count($ancestors);

            while ($p === null && $i < $count) {
                $id = $ancestors[$i];
                if ($id == $parentId) {
                    $p = $ancestors[$i + 1];
                }
                $i++;
            }

            if ($p !== null) $data[] = $p;
        }

        $data = array_unique($data);
//debug::log($data);

        $query = array(
            'parentid' => $parentId,
            'depth' => '0',
            'items' => '50',
            'page' => '1',
            'view' => 'treeview',
            'condition' => 'and',
            'filters' => array(
                array(
                    'field' => 'nodeid',
                    'content' => $data,
                    'comparation' => 'in'
                )
            ),
            'sorts' => array()
        );

        $results = $handler->search($query);

        return $results;
    }

    /**
     * Writes data on the configured datasource
     */
    public function write()
    {
        $ret = GenericDatasource::write($this->request);
        $this->sendJSON($ret);
    }

    // TODO: Change my name, extend me, do something with validations....
    public function validateFieldName($name)
    {
        $name = trim($name);
        if (strlen($name) == 0) {
            $name = false;
        }
        return $name;
    }

    // ----- Sets management -----

    /**
     * Returns a JSON object with all the node sets
     */
    public function listSets()
    {

        $idUser = \Ximdex\Utils\Session::get('userID');

        $sets = array();
        $it = NodeSets::getSets($idUser);
        while ($set = $it->next()) {
            $sets[] = array(
                'id' => $set->getId(),
                'name' => $set->getName(),
                'items' => $set->getItems(),
            );
        }

        $this->sendJSON($sets);
    }

    /**
     * Returns a JSON object with all related nodes of a node set
     */
    public function getSet()
    {

        $setid = $this->request->getParam('setid');
        $set = new NodeSets($setid);

        $nodes = array();
        $it = $set->getNodes();
        while ($node = $it->next()) {
            $node = $node->getNode();
            $nodes[] = array(
                'nodeid' => $node->get('IdNode'),
                'text' => $node->get('Name'),
                'icon' => $node->nodeType->get('Icon'),
                'isdir' => $node->nodeType->isFolder() ? '1' : '0',
                'path' => $node->getPath(),
            );
        }

        $this->sendJSON($nodes);
    }

    /**
     * Creates a new node set
     */
    public function addSet()
    {

        $name = $this->request->getParam('name');
        $nodes = $this->request->getParam('nodes');
        $nodes = GenericDatasource::normalizeEntities($nodes);
        $users = $this->request->getParam('users');
        $name = $this->validateFieldName($name);

        if ($name === false) {
            $this->sendJSON(
                array(array('type' => MSG_TYPE_ERROR, 'message' => _('The set name cannot be empty.')))
            );
            return;
        }

        $set = NodeSets::create($name);
        $errors = $set->messages->messages;

        if ($set->getId() > 0 && $nodes !== null) {
            $ret = $this->addNodeToSet($set->getId(), $nodes);
            $errors = array_merge($errors, $ret);
        }

        $sessionUser = \Ximdex\Utils\Session::get('userID');
        $errors = array_merge(
            $errors, $this->addUserToSet(
                $set->getId(),
                $sessionUser,
                RelNodeSetsUsers::OWNER_YES
            )
        );

        if ($set->getId() > 0 && $users !== null) {
            $ret = $this->addUserToSet($set->getId(), $users);
            $errors = array_merge($errors, $ret);
        }

        $this->sendJSON($errors);
    }

    /**
     * Deletes a node set
     */
    public function deleteSet()
    {
        $setid = $this->request->getParam('setid');
        $set = new NodeSets($setid);
        $set->delete();
        $this->sendJSON($set->messages->messages);
    }

    /**
     * Renames a node set
     */
    public function renameSet()
    {
        $setid = $this->request->getParam('setid');
        $name = $this->request->getParam('name');
        $name = $this->validateFieldName($name);
        if ($name === false) {
            $this->sendJSON(
                array(array('type' => MSG_TYPE_ERROR, 'message' => _('The set name cannot be empty.')))
            );
            return;
        }
        $set = new NodeSets($setid);
        $set->Name = $name;
        $set->update();
        $this->sendJSON($set->messages->messages);
    }

    /**
     * Adds multiple nodes to a specific node set.
     * The nodes parameter must by an array of node ids
     */
    public function addNodeToSet($idSet = null, $nodes = null)
    {

        $returnJSON = false;
        if ($idSet === null && $nodes === null) {
            $returnJSON = true;
            $idSet = $this->request->getParam('setid');
            $nodes = $this->request->getParam('nodes');
        }

        if (!is_array($nodes)) {
            $nodes = array($nodes);
        }
        $nodes = GenericDatasource::normalizeEntities($nodes);

        $addedNodes = 0;
        $errors = array();
        $set = new NodeSets($idSet);
        foreach ($nodes as $idNode) {
            $rel = $set->addNode($idNode);
            if ($rel->getId() > 0) $addedNodes++;
            $errors = array_merge($errors, $rel->messages->messages);
        }
        $errors = array_merge(
            array(array('type' => MSG_TYPE_NOTICE, 'message' => _("Nodes has been added correctly.") . $addedNodes)),
            $errors
        );

        if ($returnJSON) {
            $this->sendJSON($errors);
        } else {
            return $errors;
        }
    }

    /**
     * Deletes multiple nodes from a specific node set.
     * The nodes parameter must by an array of node ids
     */
    public function deleteNodeFromSet()
    {
        $setid = $this->request->getParam('setid');
        $nodes = $this->request->getParam('nodes');
        if (!is_array($nodes)) {
            $nodes = array($nodes);
        }
        $nodes = GenericDatasource::normalizeEntities($nodes);
        $deletedNodes = 0;
        $errors = array();
        $set = new NodeSets($setid);
        foreach ($nodes as $idNode) {
            $rel = $set->deleteNode($idNode);
            if (count($rel->messages->messages) == 0) $deletedNodes++;
            $errors = array_merge($errors, $rel->messages->messages);
        }
        $errors = array_merge(
            array(array('type' => MSG_TYPE_NOTICE, 'message' => _("Nodes have been deleted successfully.") . $deletedNodes)),
            $errors
        );
        $this->sendJSON($errors);
    }

    /**
     * Adds multiple users to a specific node set.
     * The users parameter must by an array of user ids
     */
    public function addUserToSet($idSet = null, $users = null, $owner = RelNodeSetsUsers::OWNER_NO)
    {

        $returnJSON = false;
        if ($idSet === null && $users === null) {
            $returnJSON = true;
            $idSet = $this->request->getParam('setid');
            $users = $this->request->getParam('users');
        }

        if (!is_array($users)) {
            $users = array($users);
        }
        $addedUsers = 0;
        $errors = array();

        $set = new NodeSets($idSet);
        foreach ($users as $idUser) {
            if (!empty($idUser) && $idUser > 0) {
                $rel = $set->addUser($idUser, $owner);
                if ($rel->getId() > 0) $addedUsers++;
                $errors = array_merge($errors, $rel->messages->messages);
            }
        }
        $errors = array_merge(
            array(array('type' => MSG_TYPE_NOTICE, 'message' => _("Users have been added correctly.") . $addedUsers)),
            $errors
        );

        if ($returnJSON) {
            $this->sendJSON($errors);
        } else {
            return $errors;
        }
    }

    /**
     * Deletes multiple users from a specific node set.
     * The users parameter must by an array of user ids
     */
    public function deleteUserFromSet()
    {

        $sessionUser = \Ximdex\Utils\Session::get('userID');
        $setid = $this->request->getParam('setid');
        $users = $this->request->getParam('users');

        if (!is_array($users)) {
            $users = array($users);
        }

        $sessionUser = RelNodeSetsUsers::getByUserId($setid, $sessionUser);

        $deletedUsers = 0;
        $errors = array();
        $set = new NodeSets($setid);
        foreach ($users as $idUser) {

            // Don't delete my own set subscription
            if ($idUser != $sessionUser->getIdUser()) {

                $user = RelNodeSetsUsers::getByUserId($setid, $idUser);
                // Don't allow a not owner to delete the owner subscription
                if (
                !($sessionUser->getOwner() == RelNodeSetsUsers::OWNER_NO
                    &&
                    $user->getOwner() == RelNodeSetsUsers::OWNER_YES)
                ) {

                    $rel = $set->deleteUser($idUser);
                    if (count($rel->messages->messages) == 0) $deletedUsers++;
                    $errors = array_merge($errors, $rel->messages->messages);
                }
            }
        }
        $errors = array_merge(
            array(array('type' => MSG_TYPE_NOTICE, 'message' => _("Users have been deleted successfully.") . $deletedUsers)),
            $errors
        );
        $this->sendJSON($errors);
    }

    /**
     * Updates the associated users of a set.
     */
    public function updateSetUsers()
    {
        $idSet = $this->request->getParam('setid');
        $users = $this->request->getParam('users');
        $rel = new RelNodeSetsUsers();
        $rel->deleteAll('IdSet = %s and Owner = 0', array($idSet));
        $this->addUserToSet();
    }

    /**
     * Return all users in the system except the current one.
     * If setid parameter is present, the users in this set will be tagged as "selected".
     */
    public function getUsers()
    {

        $sessionUser = \Ximdex\Utils\Session::get('userID');
        $idSet = $this->request->getParam('setid');

        $ret = array();
        $aux = array();

        $users = new User();
        $users = $users->find(ALL, 'IdUser <> %s', array($sessionUser));

        if ($users !== null) {
            foreach ($users as $user) {
                $idUser = $user['IdUser'];
                $ret[] = array(
                    'id' => $idUser,
                    'login' => $user['Login'],
                    'name' => $user['Name'],
                    'selected' => false,
                    'owner' => null,
                );
                $aux[$idUser] =& $ret[count($ret) - 1];
            }
        }

        if (!empty($idSet)) {

            $users = new RelNodeSetsUsers();
            $users = $users->find(ALL, 'IdSet = %s', array($idSet));

            if ($users !== null) {
                foreach ($users as $user) {
                    $idUser = $user['IdUser'];
                    if (isset($aux[$idUser])) {
                        $aux[$idUser]['selected'] = true;
                        $aux[$idUser]['owner'] = $user['Owner'] == 1 ? true : false;
                    }
                }
            }
        }

        $this->sendJSON($ret);
    }

    // ----- Sets management -----

    // ----- Filters management -----

    /**
     * Returns a JSON object with all the node filters
     */
    public function listFilters()
    {

        $filters = array();
        $it = SearchFilters::getFilters();
        while ($filter = $it->next()) {
            $filters[] = array(
                'id' => $filter->getId(),
                'name' => $filter->getName()
            );
        }

        $this->sendJSON($filters);
    }

    /**
     * Returns a JSON object with all related nodes of a filter
     */
    public function getFilter()
    {

        $filterid = $this->request->getParam('filterid');
        $output = $this->request->getParam('output');
        $output = $output !== null ? $output : 'JSON';

        $filter = new SearchFilters($filterid);
        if ($filter->getId() <= 0) {
            $this->sendJSON(
                array(array('type' => MSG_TYPE_ERROR, 'message' => _("The filter ") . $filterid . _("does not exists.")))
            );
            return;
        }

        $query = $filter->getFilter();
        $handler = $filter->getHandler();
        $ret = $this->_search($handler, $output, $query);

        if ($output == 'JSON') {
            $this->sendJSON($ret);
        } else {
            $this->sendXML($ret);
        }
    }

    /**
     * Creates a new filter
     */
    public function addFilter()
    {

        $name = $this->request->getParam('name');
        $name = $this->validateFieldName($name);
        if ($name === false) {
            $this->sendJSON(
                array(array('type' => MSG_TYPE_ERROR, 'message' => _('The filter name cannot be empty.')))
            );
            return;
        }

        $filter = $this->request->getParam('filter');
        $filter = $this->validateFieldName($filter);
        if ($filter === false) {
            $this->sendJSON(
                array(array('type' => MSG_TYPE_ERROR, 'message' => _('The filter cannot be empty.')))
            );
            return;
        }

        $handler = $this->request->getParam('handler');
        $handler = $this->validateFieldName($handler);
        if ($handler === false) {
            $this->sendJSON(
                array(array('type' => MSG_TYPE_ERROR, 'message' => _('The filter handler cannot be empty.')))
            );
            return;
        }


        $filter = SearchFilters::create($name, $handler, $filter);
        $this->sendJSON($filter->messages->messages);
    }

    /**
     * Deletes a filter
     */
    public function deleteFilter()
    {
        $filterid = $this->request->getParam('filterid');
        $filter = new SearchFilters($filterid);
        $filter->delete();
        $this->sendJSON($filter->messages->messages);
    }

    /**
     * Renames a filter
     */
    public function renameFilter()
    {
        $filterid = $this->request->getParam('filterid');
        $name = $this->request->getParam('name');
        $name = $this->validateFieldName($name);
        if ($name === false) {
            $this->sendJSON(
                array(array('type' => MSG_TYPE_ERROR, 'message' => _('The filter name cannot be empty.')))
            );
            return;
        }
        $filter = new SearchFilters($filterid);
        $filter->Name = $name;
        $filter->update();
        $this->sendJSON($filter->messages->messages);
    }

    // ----- Filters management -----


    // ----- Nodes contextual menus -----


    /**
     * Returns an instersection of actions on a group of nodes.
     */
    public function actions()
    {
        $nodes = $this->request->getParam('nodes');
        $nodes = GenericDatasource::normalizeEntities($nodes);
        $actions = $this->getActions($nodes);
        $this->sendJSON($actions);
    }

    /**
     * Returns an intersection of sets on a group of nodes.
     */
    public function nodesets()
    {
        $nodes = $this->request->getParam('nodes');
        $nodes = GenericDatasource::normalizeEntities($nodes);
        $sets = $this->getSetsIntersection($nodes);
        $this->sendJSON($sets);
    }

    /**
     * Get the action params for a node list in frontend.
     * Returns a contextual menu data, composed by actions and sets.
     */
    public function cmenu()
    {
        $nodes = $this->request->getParam('nodes');
        $nodes = GenericDatasource::normalizeEntities($nodes);
        $sets = $this->getSetsIntersection($nodes);
        $actions = $this->getActions($nodes);

        $arrayActionsParams = array();
        //For every action, build the params for json response

        foreach ($actions as $idAction) {
            $actionsParamsAux = array();
            $action = new Action($idAction);
            $name = $action->get("Name");

            //Changing name when node sets.
            if (count($nodes) > 1) {
                $auxName = explode(" ", $name);
                $name = $auxName[0] . " " . _("selection");
            }
            $actionsParamsAux["name"] = $name;

            $actionsParamsAux["module"] = $action->get("Module") ? $action->get("Module") : "";
            $actionsParamsAux["params"] = $action->get("Params") ? $action->get("Params") : "";
            $actionsParamsAux["command"] = $action->get("Command");
            $actionsParamsAux["icon"] = $action->get("Icon");
            $actionsParamsAux["callback"] = "callAction";
            $actionsParamsAux["bulk"] = $action->get("IsBulk");
            $arrayActionsParams[] = $actionsParamsAux;
        }

        $options = array_merge($sets, $arrayActionsParams);

        foreach ($options as $key => $value) {
            $options[$key]['params'] = urlencode($options[$key]['params']);
        }

        $this->sendJSON($options);
    }

    /**
     * Calculates the posible actions for a group of nodes.
     * @param array $nodes IdNodes array
     * @return array IdActions array
     */
    protected function getActions($nodes = null)
    {

        $idUser = \Ximdex\Utils\Session::get('userID');
        $nodes = $nodes !== null ? $nodes : $this->request->getParam('nodes');

        if (!is_array($nodes)) $nodes = array($nodes);

        $actions = $this->getActionsOnNodeList($idUser, $nodes);

        /**
         * Users can modify their account
         */
        if (is_array($nodes) && count($nodes) == 1 && $nodes[0] == $idUser && !in_array(6002, $actions)) {
            $actions[] = 6002;
        }

        return $actions;
    }

    /**
     * Calculates the posible actions for a group of nodes.
     * It depends on roles, states and nodetypes of nodes.
     * @param int $idUser Current user.
     * @param array $nodes IdNodes array.
     * @return array IdActions array.         *
     */
    public function getActionsOnNodeList($idUser, $nodes, $processActionName = true)
    {
        $user = new User($idUser);
        return $user->getActionsOnNodeList($nodes);
    }

    /**
     *
     */
    protected function actionIsExcluded($idAction, $idNode)
    {
        $node = new Node($idNode);
        $nodeTypeName = $node->nodeType->GetName();
        $ret = true;
        if ($nodeTypeName == 'XimletContainer') {
            $parent = new Node($node->GetParent());
            $nodeTypeNameParent = $parent->nodeType->GetName();
            $action = new Action($idAction);
            $command = $action->GetCommand();

            if ($nodeTypeNameParent == 'XimNewsColector' && $command == 'deletenode') {
                $ret = false;
            }
        }
        return $ret;
    }

    /**
     * Create contextual menu options for delete nodes from sets
     */
    protected function getSetsIntersection($nodes = null)
    {

        $nodes = $nodes !== null ? $nodes : $this->request->getParam('nodes');
        $nodes = !is_array($nodes) ? array() : array_unique($nodes);

        // Calculate which sets need to be shown (intersection)
        $sql = 'select count(1) as c, r.IdSet, s.Name
			from RelNodeSetsNode r left join NodeSets s on s.Id = r.IdSet
			where r.IdNode in (%s)
			group by r.IdSet, s.Name
			having c = %s';
        $db = new DB();
        $db->query(sprintf($sql, implode(',', $nodes), count($nodes)));

        $data = array();
        while (!$db->EOF) {
            $data[] = array(
                'id' => $db->getValue('IdSet'),
                'name' => sprintf('Delete from set "%s"', $db->getValue('Name')),
                'icon' => 'delete_section.png',
//				'setName' => $db->getValue('Name'),
                'callback' => 'deleteFromSet'
            );
            $db->next();
        }

        return $data;
    }

    /**
     * Launch a validation from the params values.
     */
    public function validation()
    {
        $request = $this->request->getRequests();
        $method = $this->request->getParam('validationMethod');
        if (empty($method)) {
            $request_content = file_get_contents("php://input");
            $request = (array)json_decode($request_content);
            if (array_key_exists('validationMethod', $request)) {
                $method = $request['validationMethod'];
            }
        }
        if (method_exists("FormValidation", $method)) {
            FormValidation::$method($request);
        }
        die("false");
    }
    // ----- Nodes contextual menus -----

    /**
     * Disables the tour pop-up
     */
    function disableTour()
    {
        $numRep = $this->request->getParam('numRep');

        \App::setValue('ximTourRep', $numRep, true );


        $result["success"] = true;
        $this->sendJSON($result);
    }

    /**
     * Return preferences like MaxItemsPerGroup as JSON
     */
    function getPreferences()
    {
        $res["preferences"] = array("MaxItemsPerGroup" => \App::getValue( "MaxItemsPerGroup"));
        $this->sendJSON($res);
    }
}

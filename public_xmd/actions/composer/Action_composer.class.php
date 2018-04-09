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

use Ximdex\Logger;
use Ximdex\Models\Group;
use Ximdex\Models\Node;
use Ximdex\Models\User;
use Ximdex\Models\XimLocale;
use Ximdex\MVC\ActionAbstract;
use Ximdex\Parsers\ParsingXimMenu;
use Ximdex\Runtime\App;
use Ximdex\Utils\Serializer;
use Ximdex\Runtime\Session;
use Xmd\Widgets\Widget;
use Ximdex\XML\Base;
 
\Ximdex\Modules\Manager::file('/actions/browser3/inc/GenericDatasource.class.php');

class Action_composer extends ActionAbstract
{
    public function index()
    {
        header('Location:' . App::getUrl('/'));
        exit();
        
        Session::check();

        $ximid = App::getValue("ximid");
        $versionname = App::getValue("VersionName");
        $userID = Session::get('userID');
        $theme = $this->request->getParam('theme');
        $theme = $theme ? $theme : 'ximdex_theme';
        $locale = new XimLocale();
        $user_locale = $locale->GetLocaleByCode(Session::get('locale'));

        //Stopping any active debug_render
        Session::set('debug_render', NULL);
        Session::set('activeTheme', $theme);

        $values = array('composer_index' => App::getUrl('/'),
            'ximid' => $ximid,
            "versionname" => $versionname,
            "userID" => $userID,
            "debug" => \Ximdex\Runtime\Session::checkUserID(),
            'theme' => $theme,
            'user_locale' => $user_locale);

        $this->render($values, "index_widgets", "only_template.tpl");
    }

    public function changeTheme()
    {
        $theme = $this->request->getParam('theme');
        Session::set('activeTheme', $theme);
    }

    public function quickRead($idNode, $from, $to, $items, $times = 2)
    {
        if ($times <= 0) {
            return null;
        }
        $times--;

        Session::check();
        $userID = Session::get('userID');

        $sql = "select N.Name, N.IdNode, N.IdNodeType, N.IdParent, NT.Icon, N.IdState,
	(NT.IsFolder or NT.IsVirtualFolder) as IsDir, N.Path, NT.System,
(select count(*) from FastTraverse ft3 where ft3.IdNode = N.IdNode and ft3.Depth = 1) as children
	FROM Nodes as N inner join NodeTypes as NT on N.IdNodeType = NT.IdNodeType
	WHERE NOT(NT.IsHidden) AND IdParent =%d AND (

NOT(NT.CanAttachGroups)

or

(select count(*) from RelUsersGroups rug inner join RelRolesPermissions rrp on rug.idrole = rrp.idrole
 where rug.iduser = %d and rug.IdGroup = 101
and rrp.IdPermission = 1001) > 0

or

N.IdNode in (select rgn.idnode from RelUsersGroups rug inner join RelGroupsNodes rgn on
rgn.IdGroup = rug.IdGroup where rug.iduser = %d
and rug.idrole in (select idrole from RelRolesPermissions where IdPermission = 1001)

)

) ORDER BY NT.System DESC, N.Name ASC";
        $sql = sprintf($sql, $idNode, $userID, $userID);
        $partial = !is_null($from) && !is_null($to);
        if ($partial) {
            $sql .= sprintf(" LIMIT %d OFFSET %d", $to - $from + 1, $from);
        }
        $db = new \Ximdex\Runtime\Db();
        $db->query($sql);
        $ret = $this->_echoNodeTree($idNode, App::getValue('displayEncoding'));
        if (($db->numRows > $items) && ($items != 0)) {
            //Paginated request
            $partes = floor($db->numRows / $items);
            $numArchivos = 0;
            if ($db->numRows % $items != 0) {
                $partes = $partes + 1;
            }

            for ($k = 1; $k <= $partes; $k++) {
                $db->Go($numArchivos);
                $nodoDesde = $db->getValue('IdNode');
                $textoDesde = $db->getValue('Name');

                $expr = $numArchivos + $items - 1;

                if ($db->numRows > $expr) {
                    $db->Go($expr);
                    $nodoHasta = $db->getValue('IdNode');
                    $textoHasta = $db->getValue('Name');
                    $hasta_aux = $expr;
                } else {
                    $db->Go($db->numRows - 1);
                    $nodoHasta = $db->getValue('IdNode');
                    $textoHasta = $db->getValue('Name');
                    $hasta_aux = $db->numRows - 1;
                }

                $ret['collection'][] = array(
                    'name' => $textoDesde . ' -> ' . $textoHasta,
                    'parentid' => $idNode,
                    'nodeFrom' => $nodoDesde,
                    'nodeTo' => $nodoHasta,
                    'startIndex' => $numArchivos,
                    'endIndex' => $hasta_aux,
                    'src' => App::getUrl(
                        '?method=treedata&amp;nodeid=%s&#38;from=%s&#38;to=%s',
                         $idNode, $numArchivos, $hasta_aux
                    ),
                    'nodeid' => '0',
                    'icon' => 'folder_a-z',
                    'openIcon' => 'folder_a-z.png',
                    'state' => '',
                    'children' => '5',
                    'isdir' => $ret["isdir"]
                );

                $numArchivos = $numArchivos + $items;
            }

        } else {
            while (!$db->EOF) {
                $ret['collection'][] = array(
                    'name' => $db->getValue('Name'),
                    'nodeid' => $db->getValue('IdNode'),
                    'nodetypeid' => $db->getValue('IdNodeType'),
                    'parentid' => $db->getValue('IdParent'),
                    'icon' => $db->getValue('Icon'),
                    'state' => $db->getValue('IdState'),
                    'isdir' => $db->getValue('IsDir'),
                    'children' => intval($db->getValue('children')),
                    'path' => $db->getValue('Path')
                );
                if (intval($db->getValue('children')) > 0) {
                    $res = $this->quickRead($db->getValue('IdNode'), null, null, $items, $times);
                    if (!is_null($res)) {
                        $ret['collection'][count($ret['collection']) - 1]['collection'] = $res["collection"];
                    }
                }

                $db->next();
            }
        }

        return $ret;
    }

    /**
     * Filtered quickRead with nodetype. Modified: doesn't check permissions in General group
     *
     * @param $idNode
     * @param $idNodetype
     * @param $offset
     * @param $size
     * @param $items
     * @param int $times
     * @return array|null
     */
    public function quickReadWithNodetype($idNode, $idNodetype, $offset, $size, $items, $times = 2)
    {
        if ($times <= 0) {
            return null;
        }
        $times--;

        Session::check();
        $userID = Session::get('userID');

        $sql = "select N.Name, N.IdNode, N.IdNodeType, N.IdParent, NT.Icon, N.IdState,
	(NT.IsFolder or NT.IsVirtualFolder) as IsDir, N.Path, NT.System,
(select count(*) from FastTraverse ft3 join Nodes n10 on n10.IdNode = ft3.IdChild join NodeTypes nt10
 on nt10.IdNodeType = n10.IdNodeType
 where ft3.IdNode = N.IdNode and ft3.Depth = 1 and n10.IdNodeType = %d) as children
	FROM Nodes as N inner join NodeTypes as NT on N.IdNodeType = NT.IdNodeType
	WHERE NOT(NT.IsHidden) AND IdParent =%d AND N.IdNodeType = %d AND (

NOT(NT.CanAttachGroups)

or

N.IdNode in (select rgn.idnode from RelUsersGroups rug inner join RelGroupsNodes rgn on
rgn.IdGroup = rug.IdGroup where rug.iduser = %d and rug.IdGroup != 101
and rug.idrole in (select idrole from RelRolesPermissions where IdPermission = 1001)

)

) ORDER BY NT.System DESC, N.Name ASC";
        $sql = sprintf($sql, $idNodetype, $idNode, $idNodetype, $userID);
        $partial = !is_null($offset) && !is_null($size);
        if ($partial) {
            $sql .= sprintf(" LIMIT %d OFFSET %d", $size, $offset);
        }
        $db = new \Ximdex\Runtime\Db();
        $db->query($sql);
        $ret = $this->_echoNodeTree($idNode, App::getValue('displayEncoding'));
        if (($db->numRows > $items) && ($items != 0)) {
            //Paginated request
            $partes = floor($db->numRows / $items);
            $numArchivos = 0;
            if ($db->numRows % $items != 0) {
                $partes = $partes + 1;
            }

            for ($k = 1; $k <= $partes; $k++) {
                $db->Go($numArchivos);
                $nodoDesde = $db->getValue('IdNode');
                $textoDesde = $db->getValue('Name');

                $expr = $numArchivos + $items - 1;

                if ($db->numRows > $expr) {
                    $db->Go($expr);
                    $nodoHasta = $db->getValue('IdNode');
                    $textoHasta = $db->getValue('Name');
                    $hasta_aux = $expr;
                } else {
                    $db->Go($db->numRows - 1);
                    $nodoHasta = $db->getValue('IdNode');
                    $textoHasta = $db->getValue('Name');
                    $hasta_aux = $db->numRows - 1;
                }

                $ret['collection'][] = array(
                    'name' => $textoDesde . ' -> ' . $textoHasta,
                    'parentid' => $idNode,
                    'nodeFrom' => $nodoDesde,
                    'nodeTo' => $nodoHasta,
                    'startIndex' => $numArchivos,
                    'endIndex' => $hasta_aux,
                    'src' => App::getUrl(
                        '?method=treedata&amp;nodeid=%s&#38;from=%s&#38;to=%s',
                        $idNode, $numArchivos, $hasta_aux
                    ),
                    'nodeid' => '0',
                    'icon' => 'folder_a-z',
                    'openIcon' => 'folder_a-z.png',
                    'state' => '',
                    'children' => '5',
                    'isdir' => $ret["isdir"]
                );

                $numArchivos = $numArchivos + $items;
            }

        } else {
            while (!$db->EOF) {
                $ret['collection'][] = array(
                    'name' => $db->getValue('Name'),
                    'nodeid' => $db->getValue('IdNode'),
                    'nodetypeid' => $db->getValue('IdNodeType'),
                    'parentid' => $db->getValue('IdParent'),
                    'icon' => $db->getValue('Icon'),
                    'state' => $db->getValue('IdState'),
                    'isdir' => $db->getValue('IsDir'),
                    'children' => intval($db->getValue('children')),
                    'path' => $db->getValue('Path')
                );
                if (intval($db->getValue('children')) > 0) {
                    $res = $this->quickReadWithNodetype($db->getValue('IdNode'), $idNodetype, null, null, $items, $times);
                    if (!is_null($res)) {
                        $ret['collection'][count($ret['collection']) - 1]['collection'] = $res["collection"];
                    }
                }

                $db->next();
            }
        }

        return $ret;
    }

    private function _echoNodeTree($node, $encoding)
    {
        if (is_numeric($node)) {
            $idNode = $node;
            $node = new Node($node);
            if (!($node->get('IdNode') > 0)) {
                return;
            }
        } else {
            if (strtolower(get_class($node)) != 'node') {
                return;
            }
        }
        // We could do binding to load all this object
        //Encoding the node name with display Encoding about config table
        $node_id = $node->get('IdNode');
        $node_parent = $node->get('IdParent');
        $node_icon = $node->getIcon();
        $node_state = $node->get('IdState');
        $node_childs = count($node->GetChildren());

        if (($node_childs > 0 && $node_id < 10000) || $node_id == 13) {
            $node_name = _($node->get('Name'));
        } else {
            $node_name = Base::recodeSrc($node->get('Name'), $encoding);
        }
        $path = Base::recodeSrc($node->getPath(), $encoding);
        $idNodeType = $node->get('IdNodeType');

        $isDir = $node->nodeType->isFolder() == 1 ? '1' : '0';
        $properties = $node->getAllProperties();
        $propertiesString = '';

        $processedProperties = array();
        if (is_array($properties)) {
            foreach ($properties as $key => $values) {
                $processedProperties[$key] = is_array($values) ? implode(',', $values) : $values;
            }
        }

        $modified = '0';
        if ($isDir == '0' && $node->nodeType->IsPublishable == '1') {
            $modified = $node->IsModified() == true ? '1' : '0';
        }

        $data = array(
            'name' => $node_name,
            'nodeid' => $node_id,
            'nodetypeid' => $idNodeType,
            'parentid' => $node_parent,
            'icon' => $node_icon,
            'state' => $node_state,
            'isdir' => $isDir,
            'children' => $node_childs,
            'path' => $path,
            'modified' => $modified
        );

        $data = array_merge($data, $processedProperties);

        return $data;
    }

    public function readTreedataFiltered($idNode, $find = null, $from = null, $to = null, $items = null, $times = 2)
    {
        if ($times <= 0) {
            return null;
        }
        $times--;

        Session::check();
        $userID = Session::get('userID');

        $sql = "select nodes.IdNode, nodes.Name, nodes.IdNodeType, nodes.IdParent, nt1.Icon, nodes.IdState, (nt1.IsFolder or nt1.IsVirtualFolder) as IsDir, nodes.Path, nt1.System,

        (select count(*) from FastTraverse ft3 where ft3.IdNode = nodes.IdNode and ft3.Depth = 1) as children,

		(SELECT count(*) FROM FastTraverse f4, Nodes n4, NodeTypes nt4 where
			n4.IdNode=f4.IdChild and f4.IdNode = nodes.IdNode and not n4.IdNode=nodes.IdNode
			and n4.name like '%s' and nt4.IdNodeType = n4.IdNodeType
			and NOT(nt4.IsHidden)) as results


        from Nodes nodes inner join NodeTypes nt1 on nodes.IdNodeType = nt1.IdNodeType
         and NOT(nt1.IsHidden) where nodes.idnode in
        (select ft1.IdChild as idnode FROM FastTraverse ft1 where ft1.IdNode = %d and ft1.depth = 1 and ft1.idchild in
			(
			select ft2.IdNode FROM FastTraverse ft2 where ft2.idchild in
			(SELECT n.idnode FROM FastTraverse f

			INNER JOIN Nodes n on n.IdNode=f.IdChild and f.IdNode = %d
				and not n.IdNode=%d and n.name like '%s'

			inner join NodeTypes nt on nt.IdNodeType = n.IdNodeType
				and NOT(nt.IsHidden))
			))
        AND (

        NOT(nt1.CanAttachGroups)

        or

        (select count(*) from RelUsersGroups rug inner join RelRolesPermissions rrp on rug.idrole = rrp.idrole
         where rug.iduser = %d and rug.IdGroup = 101
        and rrp.IdPermission = 1001) > 0

        or

        nodes.IdNode in (select rgn.idnode from RelUsersGroups rug inner join RelGroupsNodes rgn on
        rgn.IdGroup = rug.IdGroup where rug.iduser = %d
        and rug.idrole in (select idrole from RelRolesPermissions where IdPermission = 1001)

        )

        )
			 ORDER BY nt1.System DESC, nodes.Name ASC";
        $sql = sprintf($sql, '%' . $find . '%',
            $idNode, $idNode, $idNode, '%' . $find . '%', $userID, $userID);
        $partial = !is_null($from) && !is_null($to);
        if ($partial) {
            $sql .= sprintf(" LIMIT %d OFFSET %d", $to - $from + 1, $from);
        }
        $db = new \Ximdex\Runtime\Db();
        $db->query($sql);
        $queryToMatch = "/" . $find . "/i";
        $queryToMatch = str_replace(array(".", "_"), array('\.', "."), $queryToMatch);
        $ret = $this->_echoNodeTree($idNode, App::getValue('displayEncoding'));

        if (($db->numRows > $items) && ($items != 0)) {
            //Paginated request
            $partes = floor($db->numRows / $items);
            $numArchivos = 0;
            if ($db->numRows % $items != 0) {
                $partes = $partes + 1;
            }

            for ($k = 1; $k <= $partes; $k++) {
                $db->Go($numArchivos);
                $nodoDesde = $db->getValue('IdNode');
                $textoDesde = preg_replace($queryToMatch, '<span class="filter-word-span">$0</span>', $db->getValue('Name'));

                $expr = $numArchivos + $items - 1;

                if ($db->numRows > $expr) {
                    $db->Go($expr);
                    $nodoHasta = $db->getValue('IdNode');
                    $textoHasta = preg_replace($queryToMatch, '<span class="filter-word-span">$0</span>', $db->getValue('Name'));
                    $hasta_aux = $expr;
                } else {
                    $db->Go($db->numRows - 1);
                    $nodoHasta = $db->getValue('IdNode');
                    $textoHasta = preg_replace($queryToMatch, '<span class="filter-word-span">$0</span>', $db->getValue('Name'));
                    $hasta_aux = $db->numRows - 1;
                }

                $ret['collection'][] = array(
                    'name' => $textoDesde . ' -> ' . $textoHasta,
                    'parentid' => $idNode,
                    'nodeFrom' => $nodoDesde,
                    'nodeTo' => $nodoHasta,
                    'startIndex' => $numArchivos,
                    'endIndex' => $hasta_aux,
                    'src' => App::getUrl(
                        '?method=treedata&amp;nodeid=%s&#38;from=%s&#38;to=%s',
                         $idNode, $numArchivos, $hasta_aux
                    ),
                    'nodeid' => '0',
                    'icon' => 'folder_a-z',
                    'openIcon' => 'folder_a-z.png',
                    'state' => '',
                    'children' => '5',
                    'isdir' => $ret["isdir"]
                );

                $numArchivos = $numArchivos + $items;
            }

        } else {
            while (!$db->EOF) {
                $name = preg_replace($queryToMatch, '<span class="filter-word-span">$0</span>', $db->getValue('Name'));
                $results = intval($db->getValue('results'));
                $children = intval($db->getValue('children'));
                if ($results == 0) {
                    $children = 0;
                } else {
                    if (\Ximdex\NodeTypes\NodeTypeConstants::PROJECT == $db->getValue('IdNodeType')) {
                        $name .= sprintf('&nbsp;<span class="filter-results-span">[Results: %s]</span>', $results);
                    } else {
                        $name .= sprintf('&nbsp;<span class="filter-results-span">(+%s)</span>', $results);
                    }
                }
                $ret['collection'][] = array(
                    'originalName' => $db->getValue('Name'),
                    'name' => $name,
                    'nodeid' => $db->getValue('IdNode'),
                    'nodetypeid' => $db->getValue('IdNodeType'),
                    'parentid' => $db->getValue('IdParent'),
                    'icon' => $db->getValue('Icon'),
                    'state' => $db->getValue('IdState'),
                    'isdir' => $db->getValue('IsDir'),
                    'children' => $children,
                    'path' => $db->getValue('Path')
                );
                if (intval($db->getValue('children')) > 0) {
                    $res = $this->readTreedataFiltered($db->getValue('IdNode'), $find, null, null, $items, $times);
                    if (!is_null($res)) {
                        $ret['collection'][count($ret['collection']) - 1]['collection'] = $res["collection"];
                    }
                }
                $db->next();
            }
        }
        return $ret;
    }

    function treedata()
    {

        //Getting the request
        $idNode = $this->request->getParam('nodeid');
        $desde = $this->request->getParam('from');
        $hasta = $this->request->getParam('to');
        $nelementos = $this->request->getParam('items');
        $find = $this->request->getParam('find');

        $data = $this->readTreedata($idNode, true, $desde, $hasta, $nelementos, $find);

        //Creating response
        $this->response->set('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
        $this->response->set('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
        $this->response->set('Cache-Control', array('no-store, no-cache, must-revalidate', 'post-check=0, pre-check=0'));
        $this->response->set('Pragma', 'no-cache');
        $this->response->set('Content-type', 'text/xml');
        $this->response->sendHeaders();

        $xmlNodes = '';
        foreach ($data['children'] as $node) {
            $attributes = '';
            foreach ($node as $key => $value) {
                $attributes .= sprintf('%s="%s" ', $key, $value);
            }
            $xmlNodes .= sprintf('<tree %s/>', $attributes);
        }

        $xml = sprintf('<?xml version="1.0" encoding="' . $this->displayEncoding . '"?><tree>%s</tree>', $xmlNodes);
        echo $xml;
    }

    public function readTreedata($idNode, $children = false, $desde = null, $hasta = null, $nelementos = null, $find = null)
    {
        Session::check();
        $userID = Session::get('userID');

        if (!isset($this->displayEncoding)) {
            $this->displayEncoding = App::getValue('displayEncoding');
        }

        // The data to be returned
        $data = array(
            'node' => $this->_echoNodeTree($idNode, $this->displayEncoding),
            'children' => array()
        );

        if ($children !== true) {
            return $data;
        }

        $selectedNode = new Node($idNode);
        if (property_exists($selectedNode, 'nodeType') && is_object($selectedNode->nodeType)) {
            $isDir = $selectedNode->nodeType->isFolder() ? '1' : '0';
        } else {
            $isDir = '0';
            Logger::warning(sprintf('A Node without NodeType was requested: idNode=%s, nodeType=%s', $idNode, $selectedNode->nodeType));
        }

        //Filtering by debufilter
        if ($idNode == 1 && !empty($find) && \Ximdex\Runtime\Session::checkUserID()) {
            $_nodes = $selectedNode->GetChildren();
            if (count($_nodes) > 0) {
                foreach ($_nodes as $idNode) {
                    //Extracting number of each node to add it on xml
                    $data['children'][] = $this->_echoNodeTree($idNode, $this->displayEncoding);
                }
            }
            return $data;
        }

        $user = new User($userID);
        $group = new Group();

        if (!\Ximdex\Runtime\Session::get("nodelist")) {

            $groupList = $user->GetGroupList();
            // Removing general group
            if (is_array($groupList)) {
                $groupList = array_diff($groupList, array($group->GetGeneralGroup()));
            }

            $nodeList = array();
            // Putting on nodeList each performable node
            if ($groupList) {
                foreach ($groupList as $groupID) {
                    $group = new Group($groupID);
                    $nodeList = array_merge((array)$nodeList, (array)$group->GetNodeList());
                }
            }

            if (isset($nodeList) && is_array($nodeList)) {
                $nodeList = array_unique($nodeList);
            }

            // Adding node's fathers
            if (isset($nodeList)) {
                foreach ($nodeList as $idNode) {
                    $node = new Node($idNode);
                    $padre = $node->get('IdParent');
                    while ($padre) {
                        if (!in_array($padre, $nodeList)) {
                            $nodeList = array_merge((array)$nodeList, (array)$padre);
                        }
                        $node = new Node($padre);
                        $padre = $node->get('IdParent');
                    }
                }
                \Ximdex\Runtime\Session::set("nodelist", $nodeList);
            }

        } else {
            $nodeList = \Ximdex\Runtime\Session::get("nodelist");
        }


        if (!$selectedNode->numErr) {

            //Getting childrens
            $children = $selectedNode->GetChildrenInfoForTree();

            if ($children) {
                $countChildrens = count($children);
                $ti = new \Ximdex\Utils\Timer();
                $ti->start();
                for ($i = 0; $i < $countChildrens; $i++) {
                    $nodeName[$i] = $children[$i]['name'];
                    $systemType[$i] = 1000 - $children[$i]['system'];

                }
            }


            //Ordering the array and array slice
            $ti = new \Ximdex\Utils\Timer();
            $ti->start();
            if (isset($nodeName) && is_array($nodeName)) {
                $nodeName_min = $nodeName;
                array_multisort($systemType, $nodeName_min, $children);
            }
            if (($desde !== null) && ($hasta !== null)) {
                $children = array_slice($children, $desde, $hasta - $desde + 1);
                $systemType = array_slice($systemType, $desde, $hasta - $desde + 1);
                $nodeName_min = array_slice($nodeName, $desde, $hasta - $desde + 1);
            }

            //**********************************************************************
            $l = count($children);
            $numArchivos = 0;
            if (($l > $nelementos) && ($nelementos != 0)) {
                //Paginated request
                $partes = floor($l / $nelementos);

                if ($l % $nelementos != 0) {
                    $partes = $partes + 1;
                }

                for ($k = 1; $k <= $partes; $k++) {

                    $nodoDesde = $children[$numArchivos]['id'];
                    $textoDesde = $nodeName_min[$numArchivos];

                    $expr = $numArchivos + $nelementos - 1;

                    if ($l > $expr) {
                        $nodoHasta = $children[$expr]['id'];
                        $textoHasta = $nodeName_min[$expr];
                        $hasta_aux = $expr;
                    } else {
                        $nodoHasta = $children[$l - 1]['id'];
                        $textoHasta = $nodeName_min[$l - 1];
                        $hasta_aux = $l - 1;
                    }

                    $data['children'][] = array(
                        'name' => $textoDesde . ' -> ' . $textoHasta,
                        'parentid' => $idNode,
                        'nodeFrom' => $nodoDesde,
                        'nodeTo' => $nodoHasta,
                        'startIndex' => $numArchivos,
                        'endIndex' => $hasta_aux,
                        'src' => App::getUrl(
                            '?method=treedata&amp;nodeid=%s&#38;from=%s&#38;to=%s',
                            $selectedNode->GetParent(), $numArchivos, $hasta_aux
                        ),
                        'nodeid' => '0',
                        'icon' => 'folder_a-z',
                        'openIcon' => 'folder_a-z.png',
                        'state' => '',
                        'children' => '5',
                        'isdir' => $isDir
                    );

                    $numArchivos = $numArchivos + $nelementos;
                }

            } else {
                $user_perm_van = $user->HasPermission("view all nodes");

                if (($desde !== null) && ($hasta !== null)) {
                    $nodeList = \Ximdex\Runtime\Session::get("nodelist");
                    $endFor = $hasta - $desde + 1;

                    for ($i = 0; $i < $endFor; $i++) {

                        $my_in = (is_array($nodeList) && in_array($children[$i], $nodeList));
                        $user_ison_node = $user->IsOnNode($children[$i]['id'], true);

                        if ($user_perm_van or $my_in or $user_ison_node) {

                            $selectedNode = new Node($children[$i]['id']);
                            $data['children'][] = $this->_echoNodeTree($selectedNode, $this->displayEncoding);
                        }
                    }
                } else {

                    $countChildrens = count($children);
                    for ($i = 0; $i < $countChildrens; $i++) {
                        if (isset($nodeList)) {
                            $my_in = (is_array($nodeList) && in_array($children[$i], $nodeList));
                        } else {
                            $my_in = false;
                        }
                        $user_ison_node = $user->IsOnNode($children[$i], true);
                        if ($user_perm_van or $my_in or $user_ison_node) {
                            $selectedNode = new Node($children[$i]['id']);
                            $data['children'][] = $this->_echoNodeTree($selectedNode, $this->displayEncoding);
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function includeDinamicJs()
    {

        //A bad way to solve the problem, warning, achtung
        $jsFile = $this->request->getParam('js_file') ? $this->request->getParam('js_file') : $this->request->getParam('amp;js_file');

        if (empty($jsFile))
            $jsFile = "widgetsVars";

        $jsFile = "actions/commons/views/helper/{$jsFile}.tpl";

        // The class AssociativeArray does not return an array, then it obtains _GET value
        $params = isset($_GET['xparams']) ? $_GET['xparams'] : (isset($_GET['amp;xparams']) ? $_GET['amp;xparams'] : null);

        $values = array();
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if (!is_array($value)) $value = array($value);
                $aux = array();
                foreach ($value as $k => $v) {
                    $aux[$k] = Serializer::encode(SZR_JSON, $v);
                }
                $values[$key] = $aux;
            }
        }
        $values['js_file'] = $jsFile;

        // NOTE: it does not work!!!
//		$this->response->set('Content-type', 'application/javascript');

        $output = $this->render($values, 'include_dinamic_js', 'only_template.tpl', true);

        header('Content-type: application/javascript');
        echo $output;
        die();
    }

    /**
     * Returning an array of widget dependencies
     */
    public function wdeps()
    {
        $widget = $this->request->getParam('widget');
        $deps = Widget::getDependencies($widget);
        $deps = Serializer::encode(SZR_JSON, $deps);
        $this->response->set('Content-type', 'application/json');
        $this->response->sendHeaders();
        print($deps);
        exit;
    }

    /**
     * Returning a widget config file
     * @param string - wn Widget name
     * @param string - wi Widget ID
     * @param string - a Action name
     * @param string - m Module name
     */
    public function wconf()
    {

        $wn = $this->request->getParam('wn');
        $wi = $this->request->getParam('wi');
        $a = $this->request->getParam('a');
        $m = $this->request->getParam('m');

        $data = Widget::getWidgetconf($wn, $wi, $a, $m);

        $patron = '/_\(\s*([\'"])(.*)(?<!\\\\)\1\s*(\\/[*](.*)[*]\\/)?\s*\)/Usi';

        $data = preg_replace_callback($patron,
            create_function('$coincidencias', '$_out = null; eval(\'$_out = \'.$coincidencias[0].";"); return \'"\'.$_out.\'"\';'),
            $data);
        
        header('Content-type: text/javascript');
        print($data);
        exit;
    }

    /**
     * Storing or retrieve session variables
     * @param string wn Widget name
     * @param string wi Widget ID
     * @param string a Action name
     * @param string m Module name
     */
    public function sess()
    {

        $name = $this->request->getParam('name');
        $value = $this->request->getParam('value');

        if ($value !== null) {

            // setter
            $data = \Ximdex\Runtime\Session::get('browser');
            if (!is_array($data)) $data = array();
            $data[$name] = $value;
            \Ximdex\Runtime\Session::set('browser', $data);

        } else {

            // Getter
            $data = \Ximdex\Runtime\Session::get('browser');
            if (!is_array($data)) $data = array();
            $value = isset($data[$name]) ? $data[$name] : null;
            $data = Serializer::encode(SZR_JSON, array($name => $value));
            $this->response->set('Content-type', 'application/json');
            $this->response->sendHeaders();
            print($data);
            exit;
        }
    }

    public function ximmenu()
    {

        \Ximdex\Runtime\Session::check();

        $pxm = new ParsingXimMenu(XIMDEX_ROOT_PATH . '/conf/ximmenu.xml');
        $ximmenu = $pxm->processMenu(true);

        header('Content-type: text/xml');
        print $ximmenu;
    }

    function modules()
    {
        \Ximdex\Runtime\Session::check();

        $data = \Ximdex\Modules\Manager::getModules();

        $this->sendJSON($data);
        die();
    }

    public function nodetypes()
    {
        \Ximdex\Runtime\Session::check();

        $userID = \Ximdex\Runtime\Session::get('userID');

        $user = new User();
        $user->SetID($userID);

        $dbObj = new \Ximdex\Runtime\Db();
        $sql = "select IdNodeType, Name, Icon
			from NodeTypes
			where IdNodeType in (select IdNodeType from Nodes where IdParent >= 10000)
			order by Name";
        $dbObj->Query($sql);
        $ret = array();
        while (!$dbObj->EOF) {
            $ret[] = array(
                'idnodetype' => $dbObj->getValue('IdNodeType'),
                'name' => $dbObj->getValue('Name'),
                'icon' => $dbObj->getValue('Icon')
            );
            $dbObj->next();
        }

        $ret = Serializer::encode(SZR_JSON, array('nodetypes' => $ret));
        $this->response->set('Content-type', 'application/json');
        $this->response->sendHeaders();
        print($ret);
        exit;
    }

    /**
     * Returning a XMl document with all parents of a specific node id
     */
    public function parents()
    {
        $idNode = (int)$this->request->getParam('nodeid');
        $node = new Node($idNode);

        $data = array('node' => array());

        if ($node->get('IdNode') > 0) {

            $data['node']['name'] = $node->getNodeName();
            $data['node']['nodeid'] = $idNode;
            $data['node']['path'] = $node->getPath();
            $data['node']['parents'] = array();

            $parentId = $node->getParent();
            while ($parentId > 0) {

                $p = new Node($parentId);

                $data['node']['parents'][] = array(
                    'name' => $p->getNodeName(),
                    'nodeid' => $parentId,
                    'isdir' => '1'
                );

                $parentId = $p->getParent();
            }
        }

        $data = Serializer::encode(SZR_JSON, $data);
        $this->response->set('Content-type', 'application/json');
        $this->response->sendHeaders();
        echo $data;
    }

    function getPath()
    {
        $idNode = $this->request->getParam('id_node');
        $idNodeType = $this->request->getParam('nodetype');
        if (strstr($idNodeType, ',')) {
            $nodeTypes = explode(',', $idNodeType);
        } else {
            $nodeTypes = array($idNodeType);
        }

        $node = new Node($idNode);
        if (!in_array($node->get('IdNodeType'), $nodeTypes)) {
            $this->render(array('node' => ''));
            return;
        }
        $this->render(array('node' => $node->getPath()));
    }

    function getTraverseForNode()
    {
        $idNode = $this->request->getParam('id_node');
        $node = new Node($idNode);
        $this->render(array('nodes' => $node->TraverseToRoot()));
    }

    function getUserName()
    {
        $id = \Ximdex\Runtime\Session::get('userID');
        $user = new User($id);

        $this->render(array('username' => $user->GetLogin()));
    }

    function getDefaultNode()
    {

        $defaultNodeName = App::getValue("DefaultInitNodeName");
        $defaultNodePath = App::getValue("DefaultInitNodePath");
        $userID = Session::get('userID');
        $user = new User($userID);
        $groupList = $user->GetGroupList();
        $groupName = false;
        $nodes = array();


        $this->actionCommand = "xmleditor2";

        if ($this->tourEnabled($userID)) {
                $fullPath = "/ximdex/projects/Picasso" . $defaultNodePath;
                $node = new Node();
                $nodes = $node->GetByNameAndPath($defaultNodeName, $fullPath);
        }

        $this->render(array('nodes' => $nodes));

    }

    function getTraverseForPath()
    {
        $path = $this->request->getParam('nodeid');
        
        $entities[] = array();
        $this->request->setParam('nodeid', $path);
        while (($entity = GenericDatasource::read($this->request, false)) != NULL) {
            $entities[] = $entity;
            $path = $entity['parentid'];
            $this->request->setParam('nodeid', $path);
            if (isset($entity['bpath'])) {
                if ($entity['bpath'] == '/' || $entity['bpath'] == '/Tags') {
                    break;
                }
            }
        }

        // Returning partial reversed entities array
        $nodeQuantity = count($entities) - 1;
        $reversedEntities = array();
        for ($i = $nodeQuantity; $i > 0; $i--) {
            if ($entities[$i]['nodeid'] == 1) {
                continue;
            }
            $reversedEntities[] = array(
                'backend' => isset($entities[$i]['backend']) ? $entities[$i]['backend'] : null,
                'bpath' => isset($entities[$i]['bpath']) ? $entities[$i]['bpath'] : null,
                'nodeid' => $entities[$i]['nodeid']
            );
        }

        $data = Serializer::encode(SZR_JSON, array('nodes' => $reversedEntities));
        $this->render(array('nodes' => $reversedEntities));
    }

}
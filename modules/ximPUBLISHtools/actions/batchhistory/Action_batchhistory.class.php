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
use Ximdex\Models\Node;
use Ximdex\Models\User;
use Ximdex\Runtime\App;
use Ximdex\Runtime\Session;
use Ximdex\Utils\Serializer;
use Ximdex\MVC\ActionAbstract;

\Ximdex\Modules\Manager::file('/actions/FilterParameters.php', 'ximPUBLISHtools');
\Ximdex\Modules\Manager::file('/inc/model/PublishingReport.class.php', 'ximSYNC');

class Action_batchhistory extends ActionAbstract
{
    private $params = [];

    private function filterParams()
    {
        $this->params['idNode'] = FilterParameters::filterInteger($this->request->getParam("nodeid"));
        $this->params['idBatch'] = FilterParameters::filterInteger($this->request->getParam("idBatch"));
        $this->params['dateFrom'] = FilterParameters::filterInteger($this->request->getParam("dateFrom"));
        $this->params['dateTo'] = FilterParameters::filterInteger($this->request->getParam("dateTo"));
        $this->params['finished'] = FilterParameters::filterBool($this->request->getParam("finished"));
        $this->params['searchText'] = FilterParameters::filterText($this->request->getParam("searchText"));
    }

    // Main method: shows initial form
    public function index()
    {
        $nodeID = $this->request->getParam("nodeid");
        $node = new Node($nodeID);
        $acceso = true;
        $userID = Session::get('userID');

        $user = new User();
        $user->SetID($userID);

        if (!$user->HasPermission("view_publication_resume")) {
            $acceso = false;
            $errorMsg = "You have not access to this report. Consult an administrator.";
        }


        $jsFiles = array(
            App::getValue('UrlRoot') . \Ximdex\Modules\Manager::path('ximPUBLISHtools') . '/actions/batchhistory/resources/js/index.js'
        );

        $cssFiles = array();

        $arrValores = array(
            'acceso' => $acceso,
            'errorBox' => $errorMsg,
            'js_files' => $jsFiles,
            'node_Type' => $node->nodeType->GetName(),
            'css_files' => $cssFiles
        );
        $this->render($arrValores, null, 'default-3.0.tpl');
    }

    public function getFrameList()
    {
        $this->filterParams();
        $pr = new PublishingReport();
        $frames = $pr->getReports($this->params);
        $json = Serializer::encode(SZR_JSON, $frames);
        $this->render(array('result' => $json), null, "only_template.tpl");
    }
}

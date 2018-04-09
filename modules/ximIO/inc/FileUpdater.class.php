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
use Ximdex\Models\Node;
use Ximdex\Parsers\ParsingDependencies;
use Ximdex\Runtime\Constants;
use Ximdex\Utils\FsUtils;
use Ximdex\Utils\TarArchiver;

\Ximdex\Modules\Manager::file('/actions/workflow_forward/baseIO.php');

class FileUpdater
{
    var $revision = '';

    function __construct($revision)
    {
        $this->revision = $revision;
    }


    function updateFiles($mode = true)
    {
        // Estimamos las rutas que vamos a usar dependiendo del uso que le estemos dando a la clase
        // Revisión == 0  Estamos haciendo una copia
        // Revisión != 0  Estamos haciendo una importación
        if (!strcmp($this->revision, Constants::REVISION_COPY)) {
            $routeToFiles = sprintf('%s/data/files/', XIMDEX_ROOT_PATH);
        } else {
            $routeToBackupFolder = sprintf('%s/data/backup/%s_ximio', XIMDEX_ROOT_PATH, $this->revision);
            $routeToFiles = sprintf("%s/files", $routeToBackupFolder);
            // TODO esto esta un poco cutre, hay que arreglar la extension doble
            $compressedFile = sprintf('%s/files.tar.', $routeToBackupFolder);
            // Descomprimimos los archivos
            //TODO estas dos líneas van descomentadas (solo las comento para hacer pruebas rápidas)
            Logger::info("Starting the decompression of files of the package" . " {$this->revision}");
            $tarArchiver = new TarArchiver($compressedFile);
            $tarArchiver->unpack($routeToFiles);
            unset($compressedFile, $routeToBackupFolder);
        }
        // Buscamos los archivos que hay que copiar/modificar
        $query = sprintf("SELECT xnt.IdNodeTranslation, xnt.IdImportationNode, xnt.path, xnt.status"
            . " FROM XimIONodeTranslations xnt"
            . " INNER JOIN Nodes n ON xnt.path IS NOT NULL AND xnt.IdImportationNode = n.IdNode AND xnt.status >= %s AND (n.SharedWorkflow IS NULL OR n.SharedWorkflow = 0)"
            . " INNER JOIN XimIOExportations xe ON xe.idXimIOExportation = xnt.IdXimioExportation and xe.timeStamp = '%s'",
            Constants::IMPORTED_STATUS_OK,
            $this->revision);

        $dependencesGetter = new ParsingDependencies();

        $dbObj = new \Ximdex\Runtime\Db();
        $dbObj->Query($query);
        while (!$dbObj->EOF) {
            $idImportationNode = $dbObj->GetValue('IdImportationNode');
            $idNodeTranslation = $dbObj->GetValue('IdNodeTranslation');
            $path = $dbObj->GetValue('path');
            $status = $dbObj->GetValue('status');
            $pathExploded = explode('/', $path);
            $fileName = $pathExploded[count($pathExploded) - 1];
            $filePath = sprintf('%s/%s', $routeToFiles, $fileName);


            if (!is_file($filePath)) {
                Logger::info("Ignoring unexisting file" . " $filePath");
                $dbObj->Next();
                continue;
            }

            if ($mode == Constants::IMPORT_FILES) {
                $contents = FsUtils::file_get_contents($filePath);
            } elseif ($mode == Constants::UPDATE_LINKS) {
                $node = new Node($idImportationNode);
                if (!($node->GetID() > 0)) {
                    Logger::info(sprintf("The document %s with id %s could not been imported due to it could not been loaded", $filePath, $idImportationNode));
                    $dbObj->Next();
                    continue;
                }
                $contents = $node->GetContent();
            } else {
                die('Execution mode could not been estimated');
            }

            if (empty($contents)) {
                //File without content, continue
                Logger::info(sprintf("Content of document %s with filepath %s could not been obtained", $idImportationNode, $filePath));
                $dbObj->Next();
                continue;
            }
            // Special case
//				$linkMatches = $dependencesGetter->GetStructuredDocumentEnlace($contents);
            preg_match_all('/ a_enlaceid[_|\w|\d]*\s*=\s*[\'|"]([\d|\,]+)[\'|"]/i', $contents, $linkMatches);
            $totalMatches = count($linkMatches[0]);
            for ($i = 0; $i < $totalMatches; $i++) {
                $string = $linkMatches[0][$i];
                $info = $linkMatches[1][$i];
                $infoExploded = explode(',', $info);
                reset($infoExploded);
                $nodes = array();
                while (list(, $idNode) = each($infoExploded)) {
                    $nodes[] = $this->_getImportationNode($idNode);
                }
                $nodeSubstitution = implode(',', $nodes);
                $line = str_replace($info, $nodeSubstitution, $string);
                $contents = str_replace($string, $line, $contents);
            }
//				$urlMatches = $dependencesGetter->GetStructuredDocumentUrl($contents);
            preg_match_all('/<url.*>\s*(\d+)\s*<\/url>/i', $contents, $urlMatches);
            $contents = $this->_replaceMatches($contents, $urlMatches);

//				$importMatches = $dependencesGetter->GetStructuredDocumentImportLink($contents);
            preg_match_all('/ a_import_enlaceid[_|\w|\d]*\s*=\s*[\'|"](\d+)[\'|"]/i', $contents, $importMatches);
            $contents = $this->_replaceMatches($contents, $importMatches);

            $ximletMatches = $dependencesGetter->GetStructuredDocumentXimletsExtended($contents);
            $contents = $this->_replaceXimlet($contents, $ximletMatches, $idNodeTranslation);

            $idNode = $dbObj->GetValue('IdImportationNode');
            $node = new Node($idNode);
            $node->SetContent($contents);

            // For the moment, we are not going to make any notification
            if ($status == Constants::IMPORTED_STATUS_OK_TO_PUBLISH) {
                baseIO_PublishDocument($idNode, time(), null);
            }
            //baseIO_CambiarEstado($idNode, $finalState);

            unset($node, $contents);
            $dbObj->Next();
        }
        if (strcmp($this->revision, Constants::REVISION_COPY)) {
            FsUtils::deltree($routeToFiles);
        }

    }

    function _getImportationNode($idNode)
    {
        $query = sprintf("SELECT IdImportationNode"
            . " FROM XimIONodeTranslations"
            . " WHERE IdExportationNode = %d", $idNode);
        $tmpDbObj = new \Ximdex\Runtime\Db();
        $tmpDbObj->Query($query);
        $importationNode = $tmpDbObj->GetValue('IdImportationNode');
        unset($tmpDbObj);
        return $importationNode > 0 ? $importationNode : $idNode;
    }

    function _replaceMatches($contents, $matches)
    {
        $totalMatches = count($matches[0]);
        for ($i = 0; $i < $totalMatches; $i++) {
            $string = $matches[0][$i];
            $info = $matches[1][$i];
            $node = $this->_getImportationNode($info);
            $line = str_replace($info, $node, $string);
            $contents = str_replace($string, $line, $contents);
        }
        return $contents;
    }

    function _replaceXimlet($contents, $matches, $idNodeTranslation)
    {
        $totalMatches = 0;
        if (!empty($matches)) {
            $totalMatches = count($matches[0]);
        }
        $updateToPending = false;

        for ($i = 0; $i < $totalMatches; $i++) {
            $originalString = $matches[0][$i];
            $dataToReplace = $matches[1][$i];
            $originalNode = $matches[2][$i];
            $node = $this->_getImportationNode($dataToReplace);


            /**
             * If importation node is not found, we set idexportation node as searched node id
             */
            if ((int)$node == (int)$dataToReplace) {

                /*
                 * If IdExportationNode is unknown and node was not found, node is going to be 0
                 * and we have to insert in the XML information about who was the exportationNode
                 */
                $string = preg_replace("/<ximlet(.*?)>@@@GMximdex.ximlet\({$originalNode}\)/",
                    "<ximlet idExportationXimlet=\"{$dataToReplace}\">@@@GMximdex.ximlet(0)",
                    $originalString);

                $updateToPending = true;
            } else {
                $string = preg_replace("/ximlet\(($originalNode)\)/", "ximlet({$node})", $originalString);
            }
            $contents = str_replace($originalString, $string, $contents);
        }

        if ($updateToPending) {
            $dbObj = new \Ximdex\Runtime\Db();
            $query = sprintf("UPDATE XimIONodeTranslations SET status = %d"
                . " WHERE IdNodeTranslation = %d",
                Constants::IMPORTED_STATUS_PENDING_LINKS,
                $idNodeTranslation);

            $dbObj->Execute($query);
        }

        return $contents;
    }

}
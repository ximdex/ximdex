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

if (!defined('XIMDEX_ROOT_PATH')) {
	define ('XIMDEX_ROOT_PATH', realpath(dirname(__FILE__) . '/../../../'));
}

require_once(XIMDEX_ROOT_PATH . '/inc/model/Versions.php');
require_once(XIMDEX_ROOT_PATH . '/inc/model/channel.php');
require_once(XIMDEX_ROOT_PATH . '/inc/model/structureddocument.php');
require_once(XIMDEX_ROOT_PATH . '/inc/model/node.php');
require_once(XIMDEX_ROOT_PATH . '/inc/model/structureddocument.php');
require_once(XIMDEX_ROOT_PATH . '/inc/nodetypes/xmldocumentnode.php');
require_once(XIMDEX_ROOT_PATH . '/inc/repository/nodeviews/Abstract_View.class.php');
require_once(XIMDEX_ROOT_PATH . '/inc/repository/nodeviews/Interface_View.class.php');

class View_NodeToRenderizedContent extends Abstract_View implements Interface_View {

	private $_node = null;
	private $_structuredDocument = null;
	private $_idChannel = null;
	private $_idLanguage = null;
	private $_idSection = null;
	private $_linkedXimlets = "";
	private $_content = "";

	public function transform($idVersion = NULL, $pointer = NULL, $args = NULL) {
		$content = $this->retrieveContent($pointer);
		if(!$this->_setNode($idVersion))
			return NULL;

		if(!$this->_setStructuredDocument($idVersion))
			return NULL;

		if(!$this->_setIdChannel($args))
			return NULL;

		if(!$this->_setIdLanguage($args))
			return NULL;

		if(!$this->_setDocxapHeader($args))
			return NULL;

		if(!$this->_setIdSection($args))
			return NULL;

		if(!$this->_setLinkedXimlets($args))
			return NULL;

		if(!$this->_setContent($content))
			return NULL;

		$doctypeTag = \App::getValue( "DoctypeTag");
		$encodingTag = \App::getValue( "EncodingTag");

		$transformedContent = $encodingTag . "\n";
		$transformedContent .= $doctypeTag . "\n\n";
		$transformedContent .= $this->_docXapHeader;
		if($this->_linkedXimlets != "")
			$transformedContent .= $this->_linkedXimlets . "\n";
		$transformedContent .= $this->_content . "\n";
		$transformedContent .= "</docxap>\n";

		return $this->storeTmpContent($transformedContent);
	}

	private function _setNode ($idVersion = NULL) {

		if(!is_null($idVersion)) {
			$version = new Version($idVersion);
			if (!($version->get('IdVersion') > 0)) {
				XMD_Log::error('VIEW NODETORENDERIZEDCONTENT: Se ha cargado una versi�n incorrecta (' . $idVersion . ')');
				return false;
			}

			$this->_node = new Node($version->get('IdNode'));
			if (!($this->_node->get('IdNode') > 0)) {
				XMD_Log::error('VIEW NODETORENDERIZEDCONTENT: El nodo que se est� intentando convertir no existe: ' . $version->get('IdNode'));
				return false;
			}
		}

		return true;
	}

	private function _setContent ($content) {

		if($this->_structuredDocument && empty($content)) {
			// Return BD content if no content param.
			$this->_content = $this->_structuredDocument->GetContent();
		} else {
			$this->_content = $content;
		}

		return true;
	}

	private function _setLinkedXimlets ($args = array()) {

		if($this->_node) {
			if(array_key_exists('CALLER', $args) && $args['CALLER'] == 'xEDIT') {
				$nodeTypeName = $this->_node->nodeType->GetName();
				if ($nodeTypeName == 'Ximlet') {
					return true;
				}
			}
			$this->_linkedXimlets = $this->_node->class->InsertLinkedximletS($this->_idLanguage);
		} else {
			$xmlDocumentNode = new XmlDocumentNode();
			$this->_linkedXimlets = $xmlDocumentNode->InsertLinkedximletS($this->_idLanguage, $this->_idSection);
		}

		return true;
	}

	private function _setStructuredDocument ($idVersion = NULL) {

		if(!is_null($idVersion)) {
			$version = new Version($idVersion);
			if (!($version->get('IdVersion') > 0)) {
				XMD_Log::error('VIEW NODETORENDERIZEDCONTENT: Se ha cargado una versi�n incorrecta (' . $idVersion . ')');
				return false;
			}

			$this->_structuredDocument = new StructuredDocument($version->get('IdNode'));
			if (!($this->_structuredDocument->get('IdDoc') > 0)) {
				XMD_Log::error('VIEW NODETORENDERIZEDCONTENT: El structured document especificado no existe: ' . $this->_structuredDocument->get('IdDoc'));
				return false;
			}
		}

		return true;
	}

	private function _setIdChannel ($args = array()) {

		if (array_key_exists('CHANNEL', $args)) {
			$idChannel = $args['CHANNEL'];
		}

		// Check Params:
		if (!isset($idChannel) || !($idChannel > 0)) {
			XMD_Log::error('VIEW NODETORENDERIZEDCONTENT: Channel not specified for node ' . $args['NODENAME']);
			return false;
		}

		$channel = new Channel($idChannel);
		$this->_idChannel = $channel->get('IdChannel');

		return true;
	}

	private function _setIdSection ($args = array()) {

		if(!$this->_node) {
			if (array_key_exists('SECTION', $args)) {
				$this->_idSection = $args['SECTION'];
			}

			// Check Params:
			if (!isset($this->_idSection) || !($this->_idSection > 0)) {
				XMD_Log::error('VIEW NODETORENDERIZEDCONTENT: No se ha especificado la secci�n del nodo ' . $args['NODENAME'] . ' que quiere renderizar');
				return false;
			}
		}

		return true;
	}

	private function _setIdLanguage ($args = array()) {

		if($this->_node && $this->_structuredDocument) {
			$this->_idLanguage = $this->_structuredDocument->getLanguage();
		}

		if (array_key_exists('LANGUAGE', $args)) {
			$this->_idLanguage = $args['LANGUAGE'];
		}

		// Check Params:
		if (!isset($this->_idLanguage) || !($this->_idLanguage > 0)) {
			XMD_Log::error("VIEW NODETORENDERIZEDCONTENT: Node's language not specified " . $args['NODENAME'] . " que quiere renderizar");
			return false;
		}

		return true;
	}

	private function _setDocxapHeader ($args = array()) {

		if($this->_node && $this->_structuredDocument) {
			$documentType = $this->_structuredDocument->GetDocumentType();
			$this->_docXapHeader = $this->_node->class->_getDocXapHeader($this->_idChannel, $this->_idLanguage, $documentType);
		}

		if (array_key_exists('DOCXAPHEADER', $args)) {
			$this->_docXapHeader = $args['DOCXAPHEADER'];
		}

		// Check Params:
		if (!isset($this->_docXapHeader) || $this->_docXapHeader == "") {
			XMD_Log::error('VIEW NODETORENDERIZEDCONTENT: No se ha especificado la cabecera docxap del nodo ' . $args['NODENAME'] . ' que quiere renderizar');
			return false;
		}

		return true;
	}
} 

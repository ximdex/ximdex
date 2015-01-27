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



var AttributesTool = Object.xo_create(XimdocTool, {

	PROTECTED_ATTRIBUTES: ['uid'],
	SPECIAL_ATTRIBUTES_TYPES: ['ximlink'],

	specialAttributes: [],
	specialElements: {},

	initialize: function(editor) {

		AttributesTool._super(this, 'initialize', editor);

		this.selNode = null;
		this.attributes = null;

		this._parse_ximElements();
	},

	updateState: function(options) {

		if (this.selNode) {
			this._parse_ximElement(this.selNode);
		}
		var ximElement = options.selNode.ximElement;

		var attributes = this._parse_ximElement(ximElement);
		this.selNode = ximElement;

		this.attributes = attributes;

		if (!options.selNode || !options.selNode.getAttribute('uid') || (options.event && options.event.type != 'click')) {

			return;
		}

		AttributesTool._super(this, 'updateState', options);
	},

	beforeUpdateContent: function(options) {
//		this._parse_ximElements();
		AttributesTool._super(this, 'beforeUpdateContent', options);
	},

	afterUpdateContent: function(options) {
		this._parse_ximElements();
		AttributesTool._super(this, 'afterUpdateContent', options);
	},

	beforeTransform: function(xmldoc, xsldoc) {

		for (var itemUID in this.specialElements) {

			var ximElement = this.specialElements[itemUID];
			var domElement = $('[uid="%s"]'.printf(itemUID), xmldoc).get(0);

			if (Object.isEmpty(domElement)) {
				continue;
			}

			for (var i=0,l=this.specialAttributes.length; i<l; i++) {

				var attrName = this.specialAttributes[i];
				if (domElement.hasAttribute(attrName)) {
					domElement.setAttribute(attrName, ximElement.attributes[attrName]);
				}
			}
		}
	},

	saveAttributes: function(values) {

		var rngAttributes = this.selNode.rngElement
			? this.selNode.rngElement.attributes
			: this.selNode.schemaNode.attributes;

		for (var attrName in values) {

			if (!rngAttributes[attrName]) {
				continue;
			}

			var attrType = rngAttributes[attrName].type;

			if (this.PROTECTED_ATTRIBUTES.contains(attrName)) {
				continue;
			}

			if (this.specialAttributes.contains(attrName)) {

				var method = '_save_attribute_' + attrType;
				if (Object.isFunction(this[method])) {
					this[method](values[attrName]);
				} else {
					this.selNode.attributes[attrName] = values[attrName];
				}

			} else {

				this.selNode.attributes[attrName] = values[attrName];
			}
		}
	},

	_parse_ximElements: function() {

		var ximModel = this.editor.getXimDocument().getXimModel();
		var rngModel = this.editor.getRngDocument().getModel();
		this.specialElements = {};

		for (var elemName in rngModel) {

			var element = rngModel[elemName];
			for (var attrName in element.attributes) {

				var attr = element.attributes[attrName];
				if (this.SPECIAL_ATTRIBUTES_TYPES.contains(attr.type)) {

					this.specialAttributes.push(attrName);

					for (var itemUID in ximModel) {

						var ximElement = ximModel[itemUID];
						if (ximElement.schemaNode.tagName == elemName && ximElement.attributes[attrName]) {

							this.specialElements[itemUID] = ximElement;
							this._parse_ximElement(ximElement);
						}
					}
				}
			}
		}
	},

	_parse_ximElement: function(ximElement) {

		var attributes = {};
		var rngAttributes = ximElement.rngElement
			? ximElement.rngElement.attributes
			: ximElement.schemaNode.attributes;

		for (var attrName in rngAttributes) {

			var rngAttr = rngAttributes[attrName];
			var attrValue = null;
			var selectedValue = null;

			// If attribute has defined values in schema: print a combo box
			if (rngAttr.values.length > 1) {

				selectedValue = ximElement.attributes[attrName];
				attrValue = rngAttr.values;

			} else {

				attrValue = ximElement.attributes[attrName] || '';
			}

			attributes[attrName] = {
				type: rngAttr.type,
				value: attrValue,
				selectedValue: selectedValue
			};

			// Parsing specific attribute types
			var method = '_parse_attribute_' + (rngAttr.type || '');
			if (Object.isFunction(this[method])) {
				attributes[attrName] = this[method](attrName, attributes[attrName], ximElement);
			}
		}

		return attributes;
	}

});

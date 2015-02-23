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


X.actionLoaded(function(event, fn, params) {

    //Start angular compile and binding
    //X.angularTools.initView(params.context, params.tabId);
	
	fn('select[name=id_workflow]').change(function(event) {
		var id_pipeline = $(this).val();
		var url = params.url.replace(/&id_pipeline=(\d+)/, '') + '&id_pipeline=' + id_pipeline;
        angular.element(document).injector().get('xTabs').openAction({
			action: params.action,
			label: 'Modificar rol',
			nodes: params.nodes,
			url: url
		});
	});

	fn('.button-select-all').click(function(event) {
		fn('input:checkbox').attr('checked', true);
	});

	fn('.button-deselect-all').click(function(event) {
		fn('input:checkbox').attr('checked', false);
	});
	
});

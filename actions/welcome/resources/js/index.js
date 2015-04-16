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
    $(".details").hide();
    //TODO: get more info for a project
    //$(".project_item").on("click","button.config_button",function(e){
    //        $(e.delegateTarget).find(".details").toggle();
    //    });
    var xTabs = angular.element(document).injector().get('xTabs');
    fn('.project_new').click(function() {
        xTabs.openAction( {
                                        bulk: 0,
                                        callback: 'callAction',
                                        command: 'addfoldernode',
                                        icon: null,
                                        module: '', 
                                        name: _('Create a New Project')
                                        }, params.nodes);
                        });
    fn('.preview').click(function(e) {
        xTabs.openAction( {
                                        bulk: 0,
                                        callback: 'callAction',
                                        command: 'filepreview',
                                        icon: null,
                                        module: '', 
                                        name: _('Preview of an image')
                                        }, $(e.currentTarget).parent().parent().find(".nodeid").text());
                        });
    fn('.plaintext').click(function(e) {
        xTabs.openAction( {
                                        bulk: 0,
                                        callback: 'callAction',
                                        command: 'edittext',
                                        icon: null,
                                        module: '', 
                                        name: _('Edit file in text mode')
                                        }, $(e.currentTarget).parent().parent().find(".nodeid").text());
                        });
    fn('.xmltext').click(function(e) {
        xTabs.openAction( {
                                        bulk: 0,
                                        callback: 'callAction',
                                        command: 'xmleditor2',
                                        icon: null,
                                        module: '', 
                                        name: _('Edit file with Xedit')
                                        }, $(e.currentTarget).parent().parent().find(".nodeid").text());
                        });
});

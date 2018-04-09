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

X.actionLoaded(function (event, fn, params) {

    var form = fn('form');
    var fm = form.get(0).getFormMgr();
    var valid = true;

    // Creates an alias for convenience
    var empty = Object.isEmpty;

    fn('#protocol input[type="radio"]').each(function () {
        if (fn(this).attr("checked")) {
            show_local_fields(fn(this).val());
        }
    });

    fn('#protocol input[type="radio"]').click(function () {
        show_local_fields(fn(this).val());
    });

    //On click, reload the action with the selected server id. This will return the action with the input's value
    fn('div.row_item_selectable').click(function () {
        var urler = fn('#nodeURL').val() + '&serverid=' + fn(this).attr("value");
        urler += '&action=modifyserver';
        fn('#mdfsv_form').attr('action', urler);
        fm.sendForm();
        return false;
    });

  //On click, delete the selected server id.
    fn('#delete_server').click(function (event) {
        if (fn('#serverid').val() != "none") {
           confirm_dialog(event, _('Are you sure you want to remove this server?'), form, fm);	   
        }
    });

    //On click, reload the action without server id, so the inputs will be empty.
    fn("div.create-server").click(function (event) {
        var urler = fn("#nodeURL").val();
        urler += '&action=modifyserver';
        fn("#mdfsv_form").attr("action", urler);
        fm.sendForm();
        return false;
    });
    
    fn('#update_server').click(function () {
        setTimeout(function(){ fn("div.create-server").click(); }, 4500);
        return true;
    });
    
    fn('#save_server').click(function () {
    	var x = fn("input.error");
    	var protocolSelected = fn('#protocol input:checked').val();
        var encodeSelected = fn('.encoding input:checked').val();
        var channelSelected = fn('.channels-wrapper input:checked').val();
        
        var port = fn('#port').val();
        var host = fn('#host').val();
        var login = fn('#login').val();
        var password = fn('#password').val();
        
        if ((x.length == 0) && (typeof encodeSelected !== 'undefined') && (typeof protocolSelected !== 'undefined') && (typeof channelSelected !== 'undefined')) 
        {
        	if (protocolSelected == 'LOCAL')
        	{
        		setTimeout(function(){ fn("div.create-server").click(); }, 4500);
        	}
        	else
        	{
        		if ((port != '') && (host != '') && (login != '') && (password != ''))
        		{
        			setTimeout(function(){ fn("div.create-server").click(); }, 4500);
        		}
        	}
        }
        return true;
    });

    function confirm_dialog(event, msg, form, fm) {

        var div_dialog = $("<div/>").attr('id', 'dialog').appendTo(form);

        var dialogCallback = function (send) {
            $(div_dialog).dialog("destroy");
            if (send) {
            	setTimeout(function(){ fn("div.create-server").click(); }, 4800);
            	fm.sendForm({
            		button: event.currentTarget,
                    confirm: false,
                    jsonResponse: true
                });
            }      
        }.bind(this);

        div_dialog.html(msg);
        var dialogButtons = {};
        dialogButtons['Accept'] = function () {
        	dialogCallback(true);
        };
        dialogButtons['Cancel'] = function () {
        	dialogCallback(false);
        };
        div_dialog.dialog({
        	title: 'Ximdex Notifications',
        	buttons: {
        		_('Cancel'): function () {
                	fn('input[name=borrar]').val(0);
                	dialogCallback(false);
                },
        		_('Accept'): function () {
                	fn('input[name=borrar]').val(1);
                	dialogCallback(true);
                }
            }
        });
    }

    function show_local_fields(label) {
        var data = '';
        if (typeof label !== 'undefined') {
            data = label;
        }

        if (label === 'LOCAL') {
            var url = fn("input[name=url]");

            if (null == url.val() || "" == url.val()) {
                url.val(url_host + ximdex_url_root + "/data/previos");
            }
            
            var directory = fn("input[name=initialdirectory]");
            if (null == directory.val() || "" == directory.val()) {
                directory.val(ximdex_root + "/data/previos");
            }
            fn('#labelDirectorio').text(_('Directory'));
            fn('#labeldirRemota').text(_('Address'));
            fn('.not_local').hide();
        } else {
            if (label === 'SOLR') {
                fn('#labelDirectorio').text(_('Core'));
            }
            else {
                fn('#labelUrl').text(_('Remote URL'));
                fn('#labelDirectorio').text(_('Remote directory'));
            }
            
            fn('#labeldirRemota').text(_('Remote address'));
            fn('.password').show();
            fn('.port').show();
            fn('.login').show();
            fn('.host').show();
        }
    }
});
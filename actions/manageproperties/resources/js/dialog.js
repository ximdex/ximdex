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

function manageproperties_showDialog(messages, fn, params, callback) {

	var form = fn('form');

	var $dialog = $("<div/>").addClass('confirm-dialog').appendTo(form);

	var dialogCallback = function(send) {
		$dialog.dialog('destroy');
		if (Object.isFunction(callback)) callback(send);
	};

	var $messagesHTML = $('<ul>');
	messages.each(function(item, message) {
		$messagesHTML.append($('<li>').addClass('msg-warning').html(message));
	});

	$dialog
		.html($messagesHTML)
		.dialog({
			buttons: {
				cancel: function() {
					dialogCallback(false);
				},
				accept: function() {
	  				dialogCallback(true);
				}
			}
		});
}
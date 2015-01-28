{**
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
 *}

<form method="post" id="print_form" action="{$action_url}">
    <input name="theme" type="hidden">
        <div class="action_header">
            <h2>{t friendlyName=$friendlyName}Add %1{/t}</h2>
        </div>

        {if {empty($langs)}}
            <div class="message-warning message">
                <p>{t}There aren't any created languages. You should create a new one{/t}.</p>
            </div>
        {/if}

        <div class="action_content">
            <p class="icon icon-positioned project input">
                <input type="text" name="name" id="foldername" class="cajaxg validable not_empty js_val_alphanumeric js_val_unique_name full-size" placeholder="{t}Project name{/t}" data-idnode="{$nodeID}"/>
        </p>

        <div class="col1-2">
            <h3>{t}Available channels{/t}</h3>
               {foreach from=$channels key=index item=channelData }
                <p>
                    <span>
                        <input type="checkbox" class="validable canales check_group__canales hidden-focus" name="channels_listed[{$channelData.id}]" id="p_{$channelData.id}" value="{$channelData.id}" />
                        <label for="p_{$channelData.id}" class="checkbox-label icon" >{$channelData.name}</label>
                    </span>
                </p>
                {foreachelse}
                <p class="message_warning">{t}There are no channels created in the system{/t}.</p>
                {/foreach}
        </div>
  
        <div class="col1-1">
            <label for="theme">{t}Avaliable themes{/t}</label>
                <ul class="themes">
                {foreach from=$themes key=index item=theme}
                    <li class="theme">
                        <div class="img_container">
                            <img src="actions/addfoldernode/themes/{$theme.name}/{$theme.name}.png" alt="{$theme.title}" />
                            <div class="actions">
                                <a href="" class="icon select" data-theme="{$theme.name}">Select</a>
                      {if $theme.configurable}
                                <a data-theme="{$theme.name}" href="" class="icon custom">Custom</a>
                      {/if}
                            </div>
                        </div>
                        <p class="title">{$theme.title}</p>         
                        <p class="type">{$theme.description}</p>
                    </li>
                {/foreach}
                </ul>
        </div>
    </div>
    <fieldset class="buttons-form positioned_btn">
        {button label="Create `$friendlyName`" class='validate btn main_action' }
    </fieldset>
</form>


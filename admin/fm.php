<?php
/**
* @project    DarsiPro CMS
* @package    Template redactor
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once R.'admin/inc/adm_boot.php';


$pageTitle = __('Design - templates');
$pageNav = $pageTitle;







$Register = Register::getInstance();













include_once R.'admin/template/header.php';

?>











<div class="large-box-shadow white-gradient with-border" style="min-width: 670px;">
        {if $func == 'edit'}
            <style>html{ overflow:hidden; }
                #top-notifications > ul { position: absolute;right:0;width: 200px; }
            </style>
            <form method="post">
                <input type=hidden name="session_id" value="{$smarty.session.id}">
                <div class="with-small-padding small-margin-right align-center">
                    <textarea style="width:100%" name="content" cols="15" rows="20">{$edit_content}</textarea>
                    <span class="button-group">
                        <input type="reset" class="button" value="{$LANG->FILE_RS}">
                        <input type="submit" class="button" name="save" value="{$LANG->SAVE}">
                    </span>
                </div>
            </form>
        {else}
            <form id="cr_up" enctype="multipart/form-data" method="post">
                <input type=hidden name="session_id" value="{$smarty.session.id}">
                <input type="hidden" name="ajax" value="">
                {if !$no_des}<input type="hidden" name="fm_s" value="1">{/if}
                <div class="button-height with-mid-padding silver-gradient no-margin-top relative">
                    <span id="load_result" class="dark-stripes animated" style="display: none;"></span>
                    <span class="float-right" id="create" style="display:none;">
                        <select name="create_type" class="select" size="1">
                            <option value="file">{$LANG->FILE_FIL}</option>
                            <option value="directory">{$LANG->FILE_FOL}</option>
                        </select>
                        <input type="text" name="create_name" class="input">
                        <a id="submit_create" href="javascript:void(0)" class="submit_ajax button blue-gradient glossy">{$LANG->FILE_A}</a>
                    </span>
                <span class="float-right" id="upload" style="display:none;">
                    <input type="file" name="upload" class="file">
                    <a id="submit_upload" href="javascript:void(0)" class="submit_ajax button blue-gradient glossy">{$LANG->FILE_DW}</a>
                </span>
                <span class="button-group children-tooltip">
					<a href="{$URL_FM}{if $prev_url !='.'}?dir={$prev_url}{/if}" class="button blue-gradient icon-reply{if !$url} no-pointer-events disabled{/if}">{$LANG->BACK}</a>
					<a href="javascript:history.go(0)" class="button" title="{$LANG->FILE_U}"><span class="icon-redo icon-size2"></span></a>
				</span>
				<span class="button-group children-tooltip">
                    <label for="f_create" class="button green-active" title="{$LANG->FILE_A}">
                        <input type="radio" name="button-radio" id="f_create">
                        <span class="icon-list-add icon-size2"></span>
                    </label>
                    <label for="f_upload" class="button green-active" title="{$LANG->FILE_DW}">
                        <input type="radio" name="button-radio" id="f_upload">
                        <span class="icon-publish icon-size2">
                    </label>
				</span>
			</div>
			<table class="table">
                <thead>
                    <tr>
                        <th class="checkbox-cell"><a href="{$URL_FM}"><span class="icon-home icon-size2"></span></a></th>
                        {foreach $thead_table as $thead}
                            <th class="{$thead->column}{if $thead->column != 'filename'} align-center{/if}"{if $thead->column == 'size'} style="width:12%"{elseif $thead->column == 'date'} style="width:7%"{/if}><a href="{$URL_FM}{if $url}?dir={$url}&{else}?{/if}sort={$thead->column}{$thead->reverse}">{if $thead->column == 'filename'}{$LANG->USER_N}{elseif $thead->column == 'size'}{$LANG->FILE_S}{else}{$LANG->FILE_D}{/if}</a> {$thead->arr}</th>
                        {/foreach}
                        <th class="functions align-center" style="width:{if !$no_des}130{else}160{/if}px">{$LANG->ACTIONS}</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td class="align-right">
                            <span class="icon-level-down icon-size2"></span>
                        </td>
                    <td colspan="6">
                        <span id="fm_dir">
                            {$LANG->FILE_O_1} <b class="f_file">{$count_file}</b> {$LANG->FILE_O_2} <b class="f_dir">{$count_dir}</b> {$LANG->FILE_O_3}
                        </span>
                    </td>
                    </tr>
                </tfoot
                <tbody>
                    <tr id="fm_empty"{if $files} style="display:none;"{/if}><td colspan="6"><div class="no_p thin align-center with-padding large-margin-top"><h3>{$LANG->FILE_EMPTY}</h3></div></td></tr>
                    {foreach $files as $file}
                        {include file='fm_list_str.tpl'}
                    {/foreach}
                </tbody>
            </table>
        </form>	
{/if}	    
    </div>
{if $no_des}
    <ul class="bullet-list ctp large-margin-top large-margin-bottom">
        <li>{$LANG->FILE_ST_1}</li>
        {*<li>{$LANG->FILE_ST_2 - свободно}Максимальный размер загружаемого файла <b>15</b> Mb.</li>*}
        <li>{$LANG->FILE_ST_3}</li>
        <li>{$LANG->FILE_ST_4}</li>
        <li>{$LANG->FILE_ST_5}</li>
        <li>{$LANG->FILE_ST_6}<br><br></li>
        <li class="red">{$LANG->FILE_ST_7}</li>
    </ul>
{/if}







































<?php include_once 'template/footer.php'; ?>


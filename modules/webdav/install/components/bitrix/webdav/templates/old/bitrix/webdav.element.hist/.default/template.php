<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;

$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/utils.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/admin_tools.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/popup_menu.js"></script>', true);

$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/components/bitrix/webdav/templates/.default/script.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/components/bitrix/webdav/templates/.default/script_dropdown.js"></script>', true);


$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/themes/.default/pubstyles.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/themes/.default/jspopup.css");

/********************************************************************
				Input params
********************************************************************/
$arParams["SHOW_NAVIGATION"] = (is_array($arParams["SHOW_NAVIGATION"]) ? $arParams["SHOW_NAVIGATION"] : array("bottom"));
/********************************************************************
				/Input params
********************************************************************/
if (!empty($arResult["NAV_STRING"]) && in_array("top", $arParams["SHOW_NAVIGATION"])):
	?><div class="navigation navigation-top"><?=$arResult["NAV_STRING"]?></div><?
endif;
?><script type="text/javascript">
//<![CDATA[
	if (phpVars == null || typeof(phpVars) != "object")
	{
		var phpVars = {
			'ADMIN_THEME_ID': '.default',
			'titlePrefix': '<?=CUtil::addslashes(COption::GetOptionString("main", "site_name", $_SERVER["SERVER_NAME"]))?> - '};
	}

	if (typeof oObjectWD != "object")
		var oObjectWD = {};
	if (typeof oObjectWDRow != "object")
		var oObjectWDRow = {};

	function ShowHideThisMenu(id, oObj)
	{
		if (oObjectWD['object'] == null || !oObjectWD['object'])
			oObjectWD['object'] = new PopupMenu('webdav');
		
		oObjectWD['object'].ShowMenu(oObj, window.oObjectWD['wd_' + id]);
	}
//]]>
</script>
<form action="<?=POST_FORM_ACTION_URI?>" class="wd-form" method="POST" onsubmit="return confirm('<?=CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRMS"))?>')">
<?=bitrix_sessid_post()?>
<input type="hidden" name="ELEMENT_ID" value="<?=$arParams["ELEMENT_ID"]?>" />

<table cellpadding="0" cellspacing="0" border="0" width="100%" class="wd-main data-table">
	<thead>
		<tr class="wd-row">
			<th class="wd-cell"></th>
			<th class="wd-cell"></th>
			<th class="wd-cell">ID</th>
			<th class="wd-cell"><?=GetMessage('WD_FILE_NAME')?></th>
<?if ($arParams["SHOW_WORKFLOW"] != "N"):?>
			<th class="wd-cell"><?=GetMessage('WD_STATUS')?></th>
<?endif;?>
			<th class="wd-cell"><?=GetMessage('WD_COMMENTS')?></th>
			<th class="wd-cell"><?=GetMessage('WD_MODIFIED_BY')?></th>
			<th class="wd-cell"><?=GetMessage('WD_CHANGE_DATE')?></th>
		</tr>
	</thead>
	<tbody>
	<tr class="wd-row">
		<td class="wd-cell" colspan="8">
			<?=GetMessage("WD_CURRENT_VERSION")?>
		</td>
	</tr>
<?
$res = $arResult["ELEMENT"];
$arActions = array();
?>
	<tr class="wd-row">
		<td class="wd-cell"></td>
		<td class="wd-cell">
<?
	$arActions[] = array(
		"ICONCLASS" => "element_view",
		"TITLE" => GetMessage("WD_VIEW_ELEMENT"),
		"TEXT" => GetMessage("WD_VIEW"),
		"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VIEW"])."');"); 
	$arActions[] = array(
		"ICONCLASS" => "element_download",
		"TITLE" => GetMessage("WD_DOWNLOAD_ELEMENT"),
		"TEXT" => GetMessage("WD_DOWNLOAD"),
		"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DOWNLOAD"])."');"); 
	if ($res["SHOW"]["UNLOCK"] == "Y")
	{
		$arActions[] = array(
			"ICONCLASS" => "element_unlock",
			"TITLE" => GetMessage("WD_UNLOCK_ELEMENT"),
			"TEXT" => GetMessage("WD_UNLOCK"),
			"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~UNLOCK"])."');");
	}
	if ($res["SHOW"]["EDIT"] == "Y")
	{
		$arActions[] = array(
			"ICONCLASS" => "element_edit",
			"TITLE" => GetMessage("WD_CHANGE_ELEMENT"),
			"TEXT" => GetMessage("WD_CHANGE"),
			"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~EDIT"])."');");
	}
	if ($res["SHOW"]["DELETE"] == "Y")
	{
		$arActions[] = array(
			"ICONCLASS" => "element_delete",
			"TITLE" => GetMessage("WD_DELETE_ELEMENT"),
			"TEXT" => GetMessage("WD_DELETE"),
			"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRM_FILE"))."')){jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')};");
	}
?>
<table cellpadding="0" cellspacing="0" border="0" class="wd-dropdown-pointer" <?
	?>onmouseover="this.className+=' wd-dropdown-pointer-over';" <?
	?>onmouseout="this.className=this.className.replace(' wd-dropdown-pointer-over', '');" <?
	?>onclick="ShowHideThisMenu(<?=$res["ID"]?>, document.getElementById('wd_<?=$res["ID"]?>'));" <?
	?>title="<?=GetMessage("WD_ACTIONS")?>" id="wd_table_<?=$res["ID"]?>"><tr>
	<td>
		<div class="controls controls-view show-action">
			<a href="javascript:void(0);" class="action">
				<div id="wd_<?=$res["ID"]?>" class="empty"></div>
			</a>
		</div></td>
</tr></table>
<script>
oObjectWD['wd_<?=$res["ID"]?>'] = <?=CUtil::PhpToJSObject($arActions)?>;
</script>
			</td>
			<td class="wd-cell"><?=$res["ID"]?></td>
			<td class="wd-cell">
				<a target="_blank" href="<?=$res['URL']['DOWNLOAD']?>"><?=$res['NAME']?></a></td>
<?if ($arParams["SHOW_WORKFLOW"] != "N"):?>
			<td class="wd-cell">
				[<?=$res["WF_STATUS_ID"]?>] <?=$res["WF_STATUS_TITLE"]?>
			</td>
<?endif;?>
			<td class="wd-cell">
<?
		$maxLength = 10;
		if (strLen($res["~WF_COMMENTS"]) <= $maxLength):
?>
				<?=$res["WF_COMMENTS"]?>
<?
		else:
			$text = htmlspecialcharsEx(substr($res["~WF_COMMENTS"], 0, 7))."...";
?>
		<div class="popup" id="show_relative_<?=$res["ID"]?>">
			<div class="hidden" id="show_description_<?=$res["ID"]?>" style="position:absolute;display:none;" <?
					?>onmouseout="this.style.display='none'; document.getElementById('show_relative_<?=$res["ID"]?>').style.position='';">
				<?=$res["WF_COMMENTS"]?>
			</div>
			<div class="visible" onmouseover="document.getElementById('show_relative_<?=$res["ID"]?>').style.position='relative';<?
				?>document.getElementById('show_description_<?=$res["ID"]?>').style.display='block';">
				<?=$text?>
			</div>
		</div>
<?
		endif;
		
?>
			</td>
			<td class="wd-cell">
<?
	$arUser = $arResult["USERS"][$res["MODIFIED_BY"]];
	if (empty($arUser))
	{
?>
				<?=$res["USER_NAME"]?>
<?
	}
	else
	{
?>
			<div class="wd-user">
				[<a href="<?=$arUser["URL"]?>"><?=$res["MODIFIED_BY"]?></a>] (<?=$arUser["LOGIN"].") ".$arUser["NAME"]." ".$arUser["LAST_NAME"]?>
			</div>
<?
	}
?>
			</td>
			<td class="wd-cell"><?=$res['TIMESTAMP_X']?></td>
	</tr>
	<tr class="wd-row">
		<td class="wd-cell" colspan="8">
			<?=GetMessage("WD_ARCHIVE")?>
		</td>
	</tr>
<?

$iCount = 0;
foreach ($arResult['VERSIONS'] as $res):
	$iCount++;
?>
	<tr class="wd-row<?=($iCount%2 == 0 ? " selected" : "")?>"<?
		?> onmouseover="this.className+=' over';" onmouseout="this.className=this.className.replace(' over', '')" <?
		?> onclick="if(oObjectWDRow['wd_<?=$res["ID"]?>']){checkthisrow('<?=$res["ID"]?>', this)}" >
		<td class="wd-cell">
			<input type="checkbox" name="HISTORY_ID[]" id="history_id_<?=$res["ID"]?>" value="<?=$res["ID"]?>" onclick="this.checked = (!this.checked)" <?
			?><?=($res["SHOW"]["DELETE"] != "Y" ? "disabled='disabled'" : "")?><?
			?> />
		</td>
		<td class="wd-cell">
<?
	$arActions = array();
	$arActions[] = array(
		"ICONCLASS" => "element_view",
		"TITLE" => GetMessage("WD_VIEW_ELEMENT"),
		"TEXT" => GetMessage("WD_VIEW"),
		"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VIEW"])."');"); 
	$arActions[] = array(
		"ICONCLASS" => "element_download",
		"TITLE" => GetMessage("WD_DOWNLOAD_ELEMENT"),
		"TEXT" => GetMessage("WD_DOWNLOAD"),
		"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DOWNLOAD"])."');"); 
	if ($res["SHOW"]["RESTORE"] == "Y")
	{
		$arActions[] = array(
			"ICONCLASS" => "restore_element",
			"TITLE" => GetMessage("WD_RESTORE_ELEMENT"),
			"TEXT" => GetMessage("WD_RESTORE"),
			"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~RESTORE"])."');");
	}
	if ($res["SHOW"]["DELETE"] == "Y")
	{
		$arActions[] = array(
			"ICONCLASS" => "element_delete",
			"TITLE" => GetMessage("WD_DELETE_ELEMENT"),
			"TEXT" => GetMessage("WD_DELETE"),
			"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRM"))."')){jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')};");
	}
?>
<table cellpadding="0" cellspacing="0" border="0" class="wd-dropdown-pointer" <?
	?>onmouseover="this.className+=' wd-dropdown-pointer-over';" <?
	?>onmouseout="this.className=this.className.replace(' wd-dropdown-pointer-over', '');" <?
	?>onclick="ShowHideThisMenu(<?=$res["ID"]?>, document.getElementById('wd_<?=$res["ID"]?>'));" <?
	?>title="<?=GetMessage("WD_ACTIONS")?>" id="wd_table_<?=$res["ID"]?>"><tr>
	<td>
		<div class="controls controls-view show-action">
			<a href="javascript:void(0);" class="action">
				<div id="wd_<?=$res["ID"]?>" class="empty"></div>
			</a>
		</div></td>
</tr></table>
<script>
oObjectWD['wd_<?=$res["ID"]?>'] = <?=CUtil::PhpToJSObject($arActions)?>;
</script>
	</td>
	<td class="wd-cell"><?=$res["ID"]?></td>
	<td class="wd-cell">
		<a target="_blank" href="<?=$res['URL']['DOWNLOAD']?>"><?=$res['NAME']?></a></td>
<?if ($arParams["SHOW_WORKFLOW"] != "N"):?>
	<td class="wd-cell">
		[<?=$res["WF_STATUS_ID"]?>] <?=$res["WF_STATUS_TITLE"]?>
	</td>
<?endif;?>
	
	<td class="wd-cell">
			
<?
		$maxLength = 10;
		if (strLen($res["~WF_COMMENTS"]) <= $maxLength):
?>
				<?=$res["WF_COMMENTS"]?>
<?
		else:
			$text = htmlspecialcharsEx(substr($res["~WF_COMMENTS"], 0, 7))."...";
?>
		<div class="popup" id="show_relative_<?=$res["ID"]?>">
			<div class="hidden" id="show_description_<?=$res["ID"]?>" style="position:absolute;display:none;" <?
					?>onmouseout="this.style.display='none'; document.getElementById('show_relative_<?=$res["ID"]?>').style.position='';">
				<?=$res["WF_COMMENTS"]?>
			</div>
			<div class="visible" onmouseover="document.getElementById('show_relative_<?=$res["ID"]?>').style.position='relative';<?
				?>document.getElementById('show_description_<?=$res["ID"]?>').style.display='block';">
				<?=$text?>
			</div>
		</div>
<?
		endif;
		
?>
	</td>
	<td class="wd-cell">
<?
	$arUser = $arResult["USERS"][$res["MODIFIED_BY"]];
	if (empty($arUser))
	{
?>
		<?=$res["USER_NAME"]?>
<?
	}
	else
	{
?>
		<div class="wd-user">
			[<a href="<?=$arUser["URL"]?>"><?=$res["MODIFIED_BY"]?></a>] (<?=$arUser["LOGIN"].") ".$arUser["NAME"]." ".$arUser["LAST_NAME"]?>
		</div>
<?
	}
?>
	</td>
	<td class="wd-cell"><?=$res['TIMESTAMP_X']?></td>
</tr>
<?
endforeach;
?>
	</tbody>
	<tfoot>
<?
if (!empty($arResult["NAV_STRING"]) && in_array("bottom", $arParams["SHOW_NAVIGATION"])):
?>
		<tr class="wd-row">
			<td class="wd-cell" colspan="8">
				<div class="navigation navigation-bottom"><?=$arResult["NAV_STRING"]?></div>
			</td>
		</tr>
<?
else:
?>
<?
endif;
?>
		<tr class="wd-row">
			<td class="wd-cell" colspan="8">
				<input type="submit" name="wd_delete" value="<?=GetMessage("WD_DELETE")?>" />
			</td>
		</tr>
	</tfoot>
</table>
</form>
<?
if (!empty($arResult["NAV_STRING"]) && in_array("bottom", $arParams["SHOW_NAVIGATION"])):
	?><?
endif;

?>
<script>
function checkthisrow(id, row)
{
	var checkbox = document.getElementById('history_id_' + id);
	if (!checkbox || typeof checkbox != "object")
		return false;
	checkbox.checked = (!checkbox.checked);
	row.className = row.className.replace(' checked', '')
	if (checkbox.checked)
		row.className += ' checked';
	return false;
}
</script>
<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/utils.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/components/bitrix/webdav/templates/.default/script.js"></script>', true);
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/components/bitrix/webdav/templates/.default/script_dropdown.js"></script>', true);
$arActions = array();
$res = $arResult["ELEMENT"];
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
$arParams["SHOW_EDIT_CONTROLS"] = ($arParams["PERMISSION"] >= "U" ? "Y" : "N");
if ($arParams["CHECK_CREATOR"] == "Y" && $arResult["ELEMENT"]["CREATED_BY"] != $GLOBALS["USER"]->GetId()):
	$arParams["SHOW_EDIT_CONTROLS"] = "N";
	$arResult["ELEMENT"]["SHOW"] = array(
		"UNLOCK" => "N", 
		"EDIT" => "N", 
		"DELETE" => "N", 
		"HISTORY" => "N");
endif;

/********************************************************************
				/Input params
********************************************************************/
?>
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="wd-main webdav-form">
	<tbody class="info">
	<tr>
		<th><?=GetMessage("WD_FILE")?>:</th>
		<td>
			<div class="element-icon ic<?=substr($arResult["ELEMENT"]["EXTENTION"], 1)?>"></div>
<?
	if ($arParams["PERMISSION"] >= "U")
	{
		$lock_status = $arResult["ELEMENT"]['LOCK_STATUS'];
		if (in_array($lock_status, array("red", "yellow")))
		{
			$lamp_alt = ($lock_status == "yellow" ? GetMessage("IBLOCK_YELLOW_ALT") : GetMessage("IBLOCK_RED_ALT"));
			$locked_by = trim($arResult["ELEMENT"]['LOCKED_USER_NAME']);
			?><div class="element-icon element-lamp-<?=$lock_status?>" title='<?=$lamp_alt?> <?
			if ($lock_status=='red' && $locked_by!='')
			{
				?> <?=$locked_by?> <?
			}
			?>'></div><?
		}
?>
		<a href="<?=$arResult["URL"]["THIS"]?>" <?
		if ($arResult["ELEMENT"]["SHOW"]["EDIT"] == "Y" && 
			in_array($arResult["ELEMENT"]["EXTENTION"], array(".doc", ".docx", ".xls", ".xlsx", ".rtf", ".ppt", ".pptx")))
		{
			?> onclick="return EditDocWithProgID('<?=CUtil::JSEscape($arResult["URL"]["THIS"])?>')"<?
		}
		?> title="<?=GetMessage("WD_OPEN_FILE")?>"><?=$arResult["ELEMENT"]["NAME"]?></a>
<?
	}
	else 
	{
?>
		<a target="_blank" href="<?=$arResult["URL"]["DOWNLOAD"]?>" title="<?=GetMessage("WD_DOWNLOAD_FILE_TITLE")?>"><?=$arResult["ELEMENT"]["NAME"]?></a>
<?
	}
?>
<?
if ($arResult["ELEMENT"]["SHOW"]["UNLOCK"] == "Y"):
	?><span class="wd-item-controls element_unlock"><a href="<?=$arResult["URL"]["UNLOCK"]?>"><?=GetMessage("WD_UNLOCK_FILE")?></a></span><?
endif;
if ($arResult["ELEMENT"]["SHOW"]["EDIT"] == "Y"):
	?><span class="wd-item-controls element_edit"><a href="<?=$arResult["URL"]["EDIT"]?>"><?=GetMessage("WD_EDIT_FILE")?></a></span><?
endif;
if ($arResult["ELEMENT"]["SHOW"]["DELETE"] == "Y"):
	?><span class="wd-item-controls element_delete"><a href="<?=$arResult["URL"]["DELETE"]?>" onclick="return confirm('<?=CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRM"))?>')">
		<?=GetMessage("WD_DELETE_FILE")?></a></span><?
endif;
if ($arResult["ELEMENT"]["SHOW"]["HISTORY"] == "Y"):
	?><span class="wd-item-controls element_history"><a href="<?=$arResult["URL"]["HIST"]?>"><?=GetMessage("WD_HISTORY_FILE")?></a></span><?
endif;
?>
		</td>
	</tr>
	<tr>
		<th><?=GetMessage("WD_FILE_CREATED")?>: </th>
		<td><?=$arResult["ELEMENT"]["DATE_CREATE"]?>
		<?
			$arUser = $arResult["USERS"][$arResult["ELEMENT"]["CREATED_BY"]];
			if (empty($arUser))
			{
				?><?=$arResult["ELEMENT"][$key]?><?
			}
			else
			{
				?><span class="wd-user">
					<a href="<?=$arUser["URL"]?>"><?=$arUser["NAME"]." ".$arUser["LAST_NAME"]?></a>
				</span><?
			}
		?>
		</td>
	</tr>
	<tr>
		<th><?=GetMessage("WD_FILE_MODIFIED")?>: </th>
		<td><?=$arResult["ELEMENT"]["TIMESTAMP_X"]?> 
			<?
			$arUser = $arResult["USERS"][$arResult["ELEMENT"]["MODIFIED_BY"]];
			if (empty($arUser))
			{
				?><?=$arResult["ELEMENT"][$key]?><?
			}
			else
			{
				?><span class="wd-user">
					<a href="<?=$arUser["URL"]?>"><?=$arUser["NAME"]." ".$arUser["LAST_NAME"]?></a>
				</span><?
			}
		?>
		</td>
	</tr>
	<tr>
		<th><?=GetMessage("WD_FILE_SIZE")?>: </th>
		<td><?=$arResult["ELEMENT"]["FILE_SIZE"]?> 
			<span class="wd-item-controls element_download"><a target="_blank" href="<?=$arResult["URL"]["DOWNLOAD"]?>"><?=GetMessage("WD_DOWNLOAD_FILE")?></a></span></td>
	</tr>
<?
if (!empty($arResult["ELEMENT"]["TAGS"])):
?>
	<tr>
		<th><?=GetMessage("WD_TAGS")?>:</th>
		<td><?=$arResult["ELEMENT"]["TAGS"]?></td>
	</tr>
<?
endif;
if (!empty($arResult["ELEMENT"]["PREVIEW_TEXT"])):
?>
	<tr>
		<th><?=GetMessage("WD_DESCRIPTION")?>:</th>
		<td><?=$arResult["ELEMENT"]["PREVIEW_TEXT"]?></td>
	</tr>
<?
endif;
?>
	</tbody>
<?
if ($arParams["WORKFLOW"] == "workflow" && $arParams["PERMISSION"] >= "U"):
?>
	<tbody class="wowkflow">
	<tr class="header"><th colspan="2"><?=GetMessage("WD_WF_PARAMS")?></th></tr>
	<?if ($arParams["SHOW_WORKFLOW"] != "N"):?>
	<tr>
		<th><?=GetMessage("WD_FILE_STATUS")?>:</th>
		<td><?
		?><?=$arResult["ELEMENT"]["WF_STATUS_TITLE"]?><?
		if (intVal($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]) <= 0):
			?> <span class="comments"><?=GetMessage("WD_WF_COMMENTS2")?></span><?
		elseif ($arResult["ELEMENT"]["LAST_ID"] > $arResult["ELEMENT"]["REAL_ID"]):
			?> <span class="comments"><?=GetMessage("WD_WF_COMMENTS1")?></span><?
		endif;		
		?></td>
	</tr>
	<?endif;?>
	<?if (!empty($arResult["ELEMENT"]["WF_COMMENTS"])):?>
	<tr>
		<th><?=GetMessage("WD_FILE_COMMENTS")?>:</th>
		<td><?=$arResult["ELEMENT"]["WF_COMMENTS"]?></td>
	</tr>
	<?endif;?>
	<?if ($arParams["SHOW_WORKFLOW"] != "N" && !empty($arResult["ELEMENT"]["ORIGINAL"]) && $arResult["ELEMENT"]["ID"] != $arResult["ELEMENT"]["REAL_ID"]):?>
	<tr class="header2"><th colspan="2"><?=GetMessage("WD_FILE_ORIGINAL")?></th></tr>
	<tr>
		<th><?=GetMessage("WD_FILE")?>:</th>
		<td>
			<div class="element-icon ic<?=substr($arResult["ELEMENT"]["ORIGINAL"]["EXTENTION"], 1)?>"></div>
			<a href="<?=$arResult["URL"]["VIEW_ORIGINAL"]?>"><?=$arResult["ELEMENT"]["ORIGINAL"]["NAME"]?></a>
			<span class="wd-item-controls element_view"><a href="<?=$arResult["URL"]["VIEW_ORIGINAL"]?>"><?=GetMessage("WD_VIEW_FILE")?></a></span>
		</td>
	</tr>
<?
	endif;
?>
	</tbody>
<?
endif;
?>
</table>
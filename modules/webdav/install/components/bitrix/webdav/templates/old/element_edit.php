<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$ob = $arParams["OBJECT"]; 
if ($arParams["OBJECT"]->Type == "iblock")
{
?><?$APPLICATION->IncludeComponent("bitrix:webdav.element.edit", "", Array(
	"IBLOCK_TYPE"	=>	$arParams["IBLOCK_TYPE"],
	"IBLOCK_ID"	=>	$arParams["IBLOCK_ID"],
	"SECTION_ID"	=>	$arResult["VARIABLES"]["SECTION_ID"],
	"ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
	"REPLACE_SYMBOLS"	=>	$arParams["REPLACE_SYMBOLS"],
	"ACTION"	=>	$arResult["VARIABLES"]["ACTION"],
	"CONVERT"	=>	$arParams["CONVERT"],
	"PERMISSION" => $arParams["PERMISSION"], 
	"CHECK_CREATOR" => $arParams["CHECK_CREATOR"],
	
	"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
	"ELEMENT_URL" => $arResult["URL_TEMPLATES"]["element"],
	"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
	"ELEMENT_FILE_URL" => $arResult["URL_TEMPLATES"]["element_file"],
	"ELEMENT_HISTORY_URL" => $arResult["URL_TEMPLATES"]["element_history"],
	"ELEMENT_HISTORY_GET_URL" => $arResult["URL_TEMPLATES"]["element_history_get"],
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"], 
	"WEBDAV_BIZPROC_VIEW_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_view"], 
	"WEBDAV_BIZPROC_LOG_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_log"], 
	"WEBDAV_START_BIZPROC_URL" => $arResult["URL_TEMPLATES"]["webdav_start_bizproc"], 
	"WEBDAV_TASK_URL" => $arResult["URL_TEMPLATES"]["webdav_task"], 
	"WEBDAV_TASK_LIST_URL" => $arResult["URL_TEMPLATES"]["webdav_task_list"], 
	
	"SET_TITLE"	=>	$arParams["SET_TITLE"],
	"STR_TITLE" => $arParams["STR_TITLE"], 
	"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
	"CACHE_TIME"	=>	$arParams["CACHE_TIME"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?><?
return true; 
}
/********************************************************************
				Input params
********************************************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#", 
		"element_edit" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#");
	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arResult["URL_TEMPLATES"][strToLower($URL)]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $APPLICATION->GetCurPage().($URL == "index" ? "" : "?");
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialchars($arParams["~".strToUpper($URL)."_URL"]);
	}
//***************** STANDART ****************************************/
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] != "N" ? "Y" : "N"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default
/********************************************************************
				/Input params
********************************************************************/


if ($ob->permission < "W")
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return false; 
}
else
{	
	$ob->IsDir(); 
	if ($ob->arParams["not_found"])
	{
		$ob->IsDir(array("element_id" => $_REQUEST["ID"])); 
	}
	if ($ob->arParams["not_found"])
	{
		ShowError(GetMessage("WD_FILE_NOT_FOUND"));
		return false; 
	}
	if (check_bitrix_sessid())
	{
		if (empty($_REQUEST["cancel"]))
		{
			if ($arResult["VARIABLES"]["ACTION"] == "DELETE")
			{
				$ob->DELETE(array("path" => $ob->_path)); 
			}
			else
			{
				$options = array("path" => $ob->arParams["item_id"], "dest_url" => $ob->_get_path($ob->arParams["parent_id"], false).$_REQUEST["NAME"]);  
				$ob->MOVE(&$options); 
			}
		}
		$oError = $APPLICATION->GetException();
		if ($oError):
			ShowError($oError->GetString());
		else: 
			
			$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") === false ? true : false);
			if (!$arParams["CONVERT"])
				$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#")); 
				
			$arNavChain = $ob->GetNavChain(array("path" => $ob->_path), $arParams["CONVERT"]); 
			array_pop($arNavChain);
			if (!empty($_REQUEST["apply"]))
			{
				if (SITE_CHARSET != "UTF-8")
					$options["dest_url"] = $GLOBALS["APPLICATION"]->ConvertCharset($options["dest_url"], SITE_CHARSET, "UTF-8");
					
				$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
					array("PATH" => $options["dest_url"], "ACTION" => "EDIT"));
			}
			else
			{
				$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arNavChain)."/"));
				$url = str_replace("//", "/", $url); 
			}
			LocalRedirect($url);
		endif;
	}
	
	$arElement = $ob->arParams["element_array"]; 
	$arElement["FILE_SIZE"] = filesize($ob->real_path_full.$arElement["ID"]); 
	
	$arElement["FILE_SIZE_B"] = $arElement["FILE_SIZE"];
	$arElement["FILE_SIZE_KB"] = round($arElement["FILE_SIZE"]/1024, 2);
	$arElement["FILE_SIZE_MB"] = round($arElement["FILE_SIZE"]/(1048576), 2);
	$arElement["FILE_SIZE"] = $arElement["FILE_SIZE_KB"].GetMessage("WD_KB");
	if ($arElement["FILE_SIZE_KB"] < 1 )
		$arElement["FILE_SIZE"] = $arElement["FILE_SIZE_B"].GetMessage("WD_B");
	elseif ($arElement["FILE_SIZE_MB"] >= 1 )
		$arElement["FILE_SIZE"] = $arElement["FILE_SIZE_MB"].GetMessage("WD_MB");
	
	$arElement["BASE_NAME"] = str_replace($arElement["EXTENTION"], "", $arElement["NAME"]); 
//	$arElement["DATE_CREATE"] = MakeTimeStamp(); 
	$arElement["~TIMESTAMP_X"] = filemtime($ob->real_path_full.$arElement["ID"]); 
	$arElement["TIMESTAMP_X"] = ConvertTimeStamp($arElement["~TIMESTAMP_X"], "FULL"); 
	$arResult["NAV_CHAIN"] = $ob->GetNavChain(); 
	
	$arElement["URL"] = array(
		"THIS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"])))); 
	
?>
<form action="<?=POST_FORM_ACTION_URI?>" method="POST" class="wd-form" enctype="multipart/form-data">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="ELEMENT_ID" value="<?=$arElement["ID"]?>" />
	<input type="hidden" name="edit" value="Y" />
	<input type="hidden" name="ACTION" value="EDIT" />
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="wd-main webdav-form">
	<tbody class="info">
		<tr><th><?=GetMessage("WD_FILE")?>:</th>
			<td>
				<div class="element-icon ic<?=substr($arElement["EXTENTION"], 1)?>"></div>
				<a target="_blank" href="<?=$arElement["URL"]["THIS"]?>" title="<?=GetMessage("WD_OPEN_FILE")?>" <?
					if (in_array($arElement["EXTENTION"], array(".doc", ".docx", ".xls", ".xlsx", ".rtf", ".ppt", ".pptx")))
					{
						?> onclick="return EditDocWithProgID('<?=CUtil::JSEscape($arElement["URL"]["THIS"])?>')"<?
					}
				?>><?=$arElement["NAME"]?></a>
			</td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_FILE_SIZE")?>: </th>
			<td>
				<?=$arElement["FILE_SIZE"]?> <?
					?><span class="wd-item-controls element_download"><a target="_blank" href="<?=$arElement["URL"]["THIS"]?>"><?=GetMessage("WD_DOWNLOAD_FILE")?></a></span><?
?>
			</td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_FILE_MODIFIED")?>: </th>
			<td>
				<?=$arElement["TIMESTAMP_X"]?>
			</td>
		</tr>
		<tr>
			<th><span class="required starrequired">*</span><?=GetMessage("WD_NAME")?>:</th>
			<td><input type="text" class="text_file" name="NAME" value="<?=$arElement["NAME"]?>" /></td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="2">
				<input type="submit" name="save" value="<?=GetMessage("WD_SAVE")?>" />
				<input type="submit" name="apply" value="<?=GetMessage("WD_APPLY")?>" />
				<input type="submit" name="cancel" value="<?=GetMessage("WD_CANCEL")?>" />
			</td>
		</tr>
	</tfoot>
</table>
</form>
<?
}
/********************************************************************
				Standart operations
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("WD_TITLE"));
}
if ($arParams["SET_NAV_CHAIN"] == "Y")
{
	$arNavChain = array(); 
	foreach ($arResult['NAV_CHAIN'] as $res)
	{
		$arNavChain[] = $res;
		$url = CComponentEngine::MakePathFromTemplate(
			$arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arNavChain)));
		$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($res), $url);
	}
}
/********************************************************************
				/Standart operations
********************************************************************/
?>
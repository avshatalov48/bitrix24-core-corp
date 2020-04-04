<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
if (!empty($arResult["ERROR_MESSAGE"]))
{
	ShowError($arResult["ERROR_MESSAGE"]);
}
?>
<form action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data" class="wd-form">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="SECTION_ID" value="<?=$arParams["SECTION_ID"]?>" />
	<input type="hidden" name="edit_section" value="Y" />
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="webdav-form">
<?
if ($arParams["ACTION"] == "DROP"):
?>
<?
	if(strlen($arResult["SECTION"]["DATE_CREATE"]) > 0)
	{
?>
	<tr>
		<th><?=GetMessage("WD_CREATED")?>:</th>
		<td><span class="date"><?=$arResult["SECTION"]["DATE_CREATE"]?></span><?
		if (!empty($arResult["USER"]["USER_".$arResult["SECTION"]["CREATED_BY"]]))
		{
			$res = $arResult["USER"]["USER_".$arResult["SECTION"]["CREATED_BY"]];
?>
				[<a href="<?=$res["URL"]?>"><?=$res["ID"]?></a>]
				(<?=$res["LOGIN"]?>) <?=$res["NAME"]?> <?=$res["LAST_NAME"]?>
<?
		}
?>
		</td>
	</tr>
	<tr>
		<th><?=GetMessage("WD_LAST_UPDATE")?>:</th>
		<td><span class="date"><?=$arResult["SECTION"]["TIMESTAMP_X"]?></span><?
		if (!empty($arResult["USER"]["USER_".$arResult["SECTION"]["MODIFIED_BY"]]))
		{
			$res = $arResult["USER"]["USER_".$arResult["SECTION"]["MODIFIED_BY"]];
?>
				[<a href="<?=$res["URL"]?>"><?=$res["ID"]?></a>]
				(<?=$res["LOGIN"]?>) <?=$res["NAME"]?> <?=$res["LAST_NAME"]?>
<?
		}
?>		</td>
	</tr>
<?
	}
?>
	<tr>
		<th><?=GetMessage("WD_ACTIVE")?>:</th>
		<td><?=($arResult["SECTION"]["ACTIVE"]=="Y" ? GetMessage("WD_YES") : GetMessage("WD_NO"))?></td>
	</tr>
	<tr>
		<th><?=GetMessage("WD_NAME")?>:</th>
		<td><?=$arResult["SECTION"]["NAME"]?></td>
	</tr>
<?/*?>	<tr>
		<th><?=GetMessage("WD_SORT")?>:</th>
		<td><?=$arResult["SECTION"]["SORT"]?></td>
	</tr>
<?*/?>
<?/*?>	<tr>
		<th><?=GetMessage("WD_PICTURE")?>:</th>
		<td>
			<?=CFile::ShowImage($arResult["SECTION"]["PICTURE"], 200, 200, "border=0 class='file'", "", true)?>
		</td>
	</tr><?*/?>
	</tbody>
	
	<tfoot>
		<td colspan="2">
			<input type="submit" name="drop" value="<?=GetMessage("WD_DROP")?>" />
			<input type="submit" name="cancel" value="<?=GetMessage("WD_CANCEL")?>" />
			</td>
	</tfoot>
</table>
<?
else:
?>
	<tbody>
<?
if ($arResult["SECTION"]["ID"] > 0)
{
?>
<?
	if(strlen($arResult["SECTION"]["DATE_CREATE"]) > 0)
	{
?>
	<tr>
		<th><?=GetMessage("WD_CREATED")?>:</th>
		<td><span class="date"><?=$arResult["SECTION"]["DATE_CREATE"]?></span><?
		if (!empty($arResult["USER"]["USER_".$arResult["SECTION"]["CREATED_BY"]]))
		{
			$res = $arResult["USER"]["USER_".$arResult["SECTION"]["CREATED_BY"]];
?>
				[<a href="<?=$res["URL"]?>"><?=$res["ID"]?></a>]
				(<?=$res["LOGIN"]?>) <?=$res["NAME"]?> <?=$res["LAST_NAME"]?>
<?
		}
?>
		</td>
	</tr>
	<tr>
		<th><?=GetMessage("WD_LAST_UPDATE")?>:</th>
		<td><span class="date"><?=$arResult["SECTION"]["TIMESTAMP_X"]?></span><?
		if (!empty($arResult["USER"]["USER_".$arResult["SECTION"]["MODIFIED_BY"]]))
		{
			$res = $arResult["USER"]["USER_".$arResult["SECTION"]["MODIFIED_BY"]];
?>
				[<a href="<?=$res["URL"]?>"><?=$res["ID"]?></a>]
				(<?=$res["LOGIN"]?>) <?=$res["NAME"]?> <?=$res["LAST_NAME"]?>
<?
		}
?>		</td>
	</tr>
<?
	}
}
/*?>
	<tr>
		<th><label for="WEBDAV_ACTIVE"><?=GetMessage("WD_ACTIVE")?></label>:</th>
		<td><input type="checkbox" class="checkbox" name="ACTIVE" id="WEBDAV_ACTIVE" value="Y" <?=($arResult["SECTION"]["ACTIVE"]=="Y" ? "checked" : "")?> />
			<label for="WEBDAV_ACTIVE"><?=GetMessage("WD_ACTIVE_FOLDER")?></label></td>
	</tr>
<?*/
?>
	<tr>
		<th><?=GetMessage("WD_PARENT_SECTION")?>:</th>
		<td>
		<select name="IBLOCK_SECTION_ID" class="select">
			<option value="0" <?=(empty($arResult["SECTION"]["IBLOCK_SECTION_ID"]) ? "selected" : "")?>><?=GetMessage("WD_CONTENT")?></option>
<?
foreach ($arResult["SECTION_LIST"] as $res)
{
	if ($arParams["ACTION"] == "EDIT" && $arParams["SECTION_ID"] == $res["ID"])
	{
?>
			<optgroup label="<?=str_repeat(".", $res["DEPTH_LEVEL"])?><?=($res["NAME"])?>"></optgroup>
<?
		continue; 
	}
	
		
?>
			<option value="<?=htmlspecialchars($res["ID"])?>" <?=($arResult["SECTION"]["IBLOCK_SECTION_ID"] == $res["ID"] ? "selected" : "")?>>
				<?=str_repeat(".", $res["DEPTH_LEVEL"])?><?=($res["NAME"])?></option>
<?
}
?>
		</select>
		</td>
	</tr>
	<tr>
		<th><span class="required starrequired">*</span><?=GetMessage("WD_NAME")?>:</th>
		<td><input type="text" class="text" name="NAME" value="<?=$arResult["SECTION"]["NAME"]?>" /></td>
	</tr>
<?/*?>	<tr>
		<th><?=GetMessage("WD_SORT")?>:</th>
		<td><input type="text" class="text" name="SORT" value="<?=$arResult["SECTION"]["SORT"]?>" /></td>
	</tr>
<?*/?>
<?/*?>
	<tr>
		<th><?=GetMessage("WD_PICTURE")?>:</th>
		<td>
			<?=CFile::InputFile("PICTURE", 20, $arResult["SECTION"]["PICTURE"], false, 0, "IMAGE", "class='file'");?><br>
			<?=CFile::ShowImage($arResult["SECTION"]["PICTURE"], 200, 200, "border=0 class='file'", "", true)?>
		</td>
	</tr>
<?*/?>
	</tbody>
	<tfoot>
		<td colspan="2">
			<input type="submit" name="save" value="<?=GetMessage("WD_SAVE")?>" />
			<input type="submit" name="apply" value="<?=GetMessage("WD_APPLY")?>" />
			<input type="submit" name="cancel" value="<?=GetMessage("WD_CANCEL")?>" />
			</td>
	</tfoot>
</table>
<?
endif;
?></form>
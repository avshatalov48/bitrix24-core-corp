<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/webdav/quickedit.js');

if (!empty($arResult["ERROR_MESSAGE"]))
{
	ShowError($arResult["ERROR_MESSAGE"]);
}

$arResult["FIELDS"] = array(); 
if ($arResult["SECTION"]["ID"] > 0)
{
	$arResult["FIELDS"][] = array("id" => "NAME", "name" => GetMessage("WD_NAME"), "type" => "custom", "value" => 
		"<div class=\"quick-view wd-toggle-edit wd-file-name\">".$arResult["SECTION"]["NAME"]."</div>
		<input class=\"quick-edit wd-file-name\" type=\"text\" name=\"NAME\" value=\"".$arResult["SECTION"]["NAME"]."\" />");

	ob_start();
	$APPLICATION->IncludeComponent("bitrix:main.user.link",
		'',
		array(
			"ID" => $arResult["SECTION"]["CREATED_BY"],
			"HTML_ID" => "group_mods_".$arResult["SECTION"]["CREATED_BY"],
			"NAME" => $arResult["USERS"][$arResult["SECTION"]["CREATED_BY"]]["NAME"],
			"LAST_NAME" => $arResult["USERS"][$arResult["SECTION"]["CREATED_BY"]]["LAST_NAME"],
			"SECOND_NAME" => $arResult["USERS"][$arResult["SECTION"]["CREATED_BY"]]["SECOND_NAME"],
			"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
			"LOGIN" => $arResult["USERS"][$arResult["SECTION"]["CREATED_BY"]]["LOGIN"],
			"PROFILE_URL" => $pu,
			"USE_THUMBNAIL_LIST" => "Y",
			"THUMBNAIL_LIST_SIZE" => 28,
			"DESCRIPTION" => FormatDateFromDB($arResult["SECTION"]["DATE_CREATE"]),
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
		),
		false, 
		array("HIDE_ICONS" => "Y")
	);
	$createdUser = ob_get_clean();

	if (($arResult["SECTION"]["MODIFIED_BY"] == $arResult["SECTION"]["CREATED_BY"]) &&
		($arResult["SECTION"]["DATE_CREATE"] == $arResult["SECTION"]["TIMESTAMP_X"]))
	{
		$modifiedUser = "<div class=\"wd-modified-empty\">&nbsp;</div>";
		$arResult["FIELDS"][] = array("id" => "CREATED", "name" => GetMessage("WD_CREATED"), "type" => "label", "value" => "<div class=\"wd-created wd-modified\">".$createdUser.$modifiedUser."</div>"); 
	} else {
		ob_start();
		$APPLICATION->IncludeComponent("bitrix:main.user.link",
			'',
			array(
				"ID" => $arResult["SECTION"]["MODIFIED_BY"],
				"HTML_ID" => "group_mods_".$arResult["SECTION"]["MODIFIED_BY"],
				"DESCRIPTION" => $arResult["SECTION"]["TIMESTAMP_X"],
				"NAME" => $arResult["USERS"][$arResult["SECTION"]["MODIFIED_BY"]]["NAME"],
				"LAST_NAME" => $arResult["USERS"][$arResult["SECTION"]["MODIFIED_BY"]]["LAST_NAME"],
				"SECOND_NAME" => $arResult["USERS"][$arResult["SECTION"]["CREATED_BY"]]["SECOND_NAME"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"LOGIN" => $arResult["USERS"][$arResult["SECTION"]["MODIFIED_BY"]]["LOGIN"],
				"PROFILE_URL" => $pu,
				"USE_THUMBNAIL_LIST" => "Y",
				"THUMBNAIL_LIST_SIZE" => 28,
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
			),
			false, 
			array("HIDE_ICONS" => "Y")
		);
		$modifiedUser = ob_get_clean();
		$arResult["FIELDS"][] = array("id" => "CREATED", "name" => GetMessage("WD_CREATED"), "type" => "label", "value" => "<div class=\"wd-created\">".$createdUser."</div>"); 
		$arResult["FIELDS"][] = array("id" => "UPDATED", "name" => GetMessage("WD_LAST_UPDATE"), "type" => "label", "value" => "<div class=\"wd-modified\">".$modifiedUser."</div>"); 
	}

}

$arResult["FIELDS"][] = array("id" => "IBLOCK_SECTION_ID", "name" => GetMessage("WD_PARENT_SECTION"), "type" => "custom"); 
$arResult["DATA"]["IBLOCK_SECTION_ID"] = '<select class="quick-edit" name="IBLOCK_SECTION_ID">'.
	'<option value="0"'.
	($arResult["SECTION"]["IBLOCK_SECTION_ID"] == 0 ? ' selected=selected"' : '').
	($arResult["~SECTION"]["IBLOCK_SECTION_ID"] <= 0 ? ' class="selected"' : '').'>'.GetMessage("WD_CONTENT").'</option>'; 
$sectionName = GetMessage("WD_CONTENT");
foreach ($arResult["SECTION_LIST"] as $res)
{
	$arResult["DATA"]["IBLOCK_SECTION_ID"] .= 
		'<option value="'.$res["ID"].'"'.
		($arResult["SECTION"]["IBLOCK_SECTION_ID"] == $res["ID"] ? ' selected=selected"' : '').
		($arResult["~SECTION"]["IBLOCK_SECTION_ID"] == $res["ID"] ? ' class="selected"' : '').'>'.str_repeat(".", $res["DEPTH_LEVEL"]).($res["NAME"]).'</option>'; 
	if ($arResult["SECTION"]["IBLOCK_SECTION_ID"] == $res["ID"])
		$sectionName = str_repeat(".", $res["DEPTH_LEVEL"]).($res["NAME"]);
}
$arResult["DATA"]["IBLOCK_SECTION_ID"] .= '</select>'; 
$arResult["DATA"]["IBLOCK_SECTION_ID"] = "<div class=\"quick-view wd-toggle-edit wd-section\">".htmlspecialcharsbx($sectionName)."</div>".$arResult["DATA"]["IBLOCK_SECTION_ID"];


if (!isset($arParams['TAB_ID']))
{
	$APPLICATION->IncludeComponent("bitrix:main.interface.form", "", array(
			"FORM_ID" => $arParams["FORM_ID"],
			"TABS" => array(
				array(
					"id" => "tab1", "name" => GetMessage("WD_FOLDER"), 
					"fields" => $arResult["FIELDS"]
				)
			),
			"BUTTONS" => array(
				"back_url" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))), 
				"custom_html" => '<input type="hidden" name="SECTION_ID" value="'.$arParams["SECTION_ID"].'" /><input type="hidden" name="edit_section" value="Y" />'
			),
			"DATA"=> $arResult["DATA"],
		),
		($this->__component->__parent ? $this->__component->__parent : $component)
	);
}
else
{

	$arResult["FIELDS"][] = array("id" => "BUTTONS2", "name" => "", "type" => "custom", "colspan" => true, "value" => bitrix_sessid_post()."
		<table width=\"100%\"><tr>
<td style=\"width:30%; background-image:none; padding:0;\"></td><td style=\"padding:1px;background-image:none;\">
<input type=\"hidden\" name=\"SECTION_ID\" value=\"".$arParams["SECTION_ID"]."\" />
<input type=\"hidden\" name=\"edit_section\" value=\"Y\" />
<input type=\"button\" class=\"button-edit wd_commit\" style=\"margin-right:10px; float: left; display: none;\" value=\"".htmlspecialcharsbx(GetMessage("WD_SAVE"))."\" /> 
<input type=\"button\" class=\"button-edit wd_rollback\" style=\"margin-right:10px; float: left; display: none;\" value=\"".htmlspecialcharsbx(GetMessage("WD_CANCEL"))."\" /> 
</td></tr></table>");

	$arTabs = array(
		array(
			"id" => $arParams["TAB_ID"],
			"name" => GetMessage("WD_FOLDER"),
			"title" => GetMessage("WD_FOLDER"),
			"fields" => $arResult["FIELDS"],
		)
	); 

	if ($this->__component->__parent)
	{
		$this->__component->__parent->arResult["TABS"][] = $arTabs[0];
		if (empty($this->__component->__parent->arResult["DATA"]))
			$this->__component->__parent->arResult["DATA"] = array();
		$this->__component->__parent->arResult["DATA"] = array_merge($this->__component->__parent->arResult["DATA"], $arResult["DATA"]);
	}
}
?>
<?
if ($this->__component->__parent)
{
	$this->__component->__parent->arResult["arButtons"] = is_array($this->__component->__parent->arResult["arButtons"]) ? $this->__component->__parent->arResult["arButtons"] : array(); 
	$this->__component->__parent->arResult["arButtons"][] = array(
		"TEXT" => GetMessage("WD_DELETE_SECTION"),
		"LINK" => "javascript:WDDrop('".CUtil::JSEscape($arResult["URL"]["DELETE"])."');",
		"ICON" => "btn-delete section-delete"); 
}
?>
<script>
BX.ready(function() {
	var tab = BX('tab_section_edit_table');
	wdPermsEdit = new WDQuickEdit(tab, "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam())?>",
		BX.findChild(tab, {'class':'wd_commit'}, true),
		BX.findChild(tab, {'class':'wd_rollback'}, true),
		[
			BX.findChild(tab, {'tag':'input', 'class':'wd-file-name'}, true),
			BX.findChild(tab, {'tag':'input', 'property':{'name':'TAGS'}}, true)
		]);
	wdPermsEdit.Init();
});
</script>

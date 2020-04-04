<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
if (!empty($arResult["ERROR_MESSAGE"]))
{
	ShowError($arResult["ERROR_MESSAGE"]);
}

$arResult["FIELDS"] = array(
/*	array(
		"id" => "IBLOCK_SECTION_ID", 
		"name" => GetMessage("WD_PARENT_SECTION"), 
		"type" => "custom", 
		"value" => 
			'<input type="text" name="IBLOCK_SECTION_ID" readonly="readonly" value="'.$_REQUEST["IBLOCK_SECTION_ID"].'" />'.
			'<input type="button" name="" value="..." />'), */
	array("id" => "NAME", "name" => GetMessage("WD_NAME"), "type" => "text", "value" => $_REQUEST["NAME"])); 

?><?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
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
);?>
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

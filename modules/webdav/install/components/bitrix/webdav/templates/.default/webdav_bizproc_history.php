<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$db_res = $arParams["OBJECT"]->_get_mixed_list(null, $arParams + array("SHOW_VERSION" => "Y"), $arResult["VARIABLES"]["ELEMENT_ID"]); 

if (!($db_res && $arResult["ELEMENT"] = $db_res->GetNext()))
{
	if ($arParams["SET_STATUS_404"] == "Y"):
		CHTTP::SetStatus("404 Not Found");
	endif;
	return 0;
}
elseif ($arParams["OBJECT"]->permission < "W")
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}
elseif ($arParams["CHECK_CREATOR"] == "Y" && $arResult["ELEMENT"]["CREATED_BY"] != $GLOBALS['USER']->GetId())
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}
if ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0)
{
	$this->__component->arResult["arButtons"]["versions"] = array(
		"TEXT" => GetMessage("WD_VERSIONS"),
		"TITLE" => GetMessage("WD_VERSIONS_ALT"),
		"LINK" => CComponentEngine::MakePathFromTemplate($arResult["URL_TEMPLATES"]["element_versions"], 
			array("ELEMENT_ID" => $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"], "ACTION" => "EDIT")),
		"ICON" => "btn-list"); 
	$this->__component->arResult["arButtons"][] = array("NEWBAR" => true); 
}
$this->__component->arResult["arButtons"]["delete"] = array(
	"TEXT" => GetMessage("WD_EDIT_FILE"),
	"TITLE" => ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] <= 0 ? 
		GetMessage("WD_EDIT_FILE_ALT") : GetMessage("WD_EDIT_FILE_ALT2")),
	"LINK" => CComponentEngine::MakePathFromTemplate($arResult["URL_TEMPLATES"]["element_edit"], 
		array("ELEMENT_ID" => $arResult["ELEMENT"]["ID"], "ACTION" => "EDIT")),
	"ICON" => "btn-edit element-edit"); 

if ($arParams["SET_NAV_CHAIN"] != "N")
{
	$arResult["NAV_CHAIN"] = $arParams["OBJECT"]->GetNavChain(array("element_id" => ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 ? 
		$arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] : $arResult["ELEMENT"]["ID"])), "array");

	$arNavChain = array(); 
	foreach ($arResult["NAV_CHAIN"] as $res)
	{
		$arNavChain[] = $res["URL"];
		if (count($arNavChain) >= count($arResult["NAV_CHAIN"]))
			break; 
		$url = CComponentEngine::MakePathFromTemplate($arResult["URL_TEMPLATES"]["sections"], 
			array("PATH" => implode("/", $arNavChain), "SECTION_ID" => $res["ID"], "ELEMENT_ID" => "files"));
		$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($res["NAME"]), $url);
	}
	
	if ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0)
	{
		$GLOBALS["APPLICATION"]->AddChainItem(GetMessage("WD_ORIGINAL").": ".htmlspecialcharsEx($res["NAME"]), 
			CComponentEngine::MakePathFromTemplate($arResult["URL_TEMPLATES"]["element_versions"], 
				array("SECTION_ID" => $res["IBLOCK_SECTION_ID"], "ELEMENT_ID" => $res["ID"])));
	}
	
	$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($arResult["ELEMENT"]["NAME"])/*, 
		CComponentEngine::MakePathFromTemplate($arResult["URL_TEMPLATES"]["element_edit"], 
			array("SECTION_ID" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"], "ELEMENT_ID" => $arResult["ELEMENT"]["ID"], "ACTION" => "EDIT"))*/);
}
?><?$APPLICATION->IncludeComponent("bitrix:bizproc.document.history", "", Array(
	"OBJECT" => $arParams["OBJECT"], 
	"MODULE_ID" => MODULE_ID,
	"ENTITY" => ENTITY,
	"DOCUMENT_TYPE" => DOCUMENT_TYPE,
	"DOCUMENT_ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
	"DOCUMENT_URL" => str_replace(
		array("#ELEMENT_ID#", "#WORKFLOW_ID#", "#ELEMENT_NAME#"), 
		array($arResult["VARIABLES"]["ELEMENT_ID"], "#ID#", "#NAME#"), $arResult["URL_TEMPLATES"]["webdav_bizproc_history_get"]),
	"SET_TITLE"	=> "N",
	"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
if ($arParams["SET_TITLE"] != "N")
{
	$title = GetMessage("WD_HISTORY_DOCUMENT_TITLE", array("#NAME#" => $arResult["ELEMENT"]["~NAME"])); 
	if ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0)
		$title = GetMessage("WD_HISTORY_VERSION_TITLE", array("#NAME#" => $arResult["ELEMENT"]["~NAME"])); 
	$APPLICATION->SetTitle($title);
}
?>

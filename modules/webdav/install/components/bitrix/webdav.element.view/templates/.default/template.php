<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/webdav/templates/.default/script.js");
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
$aCols = __build_item_info($arResult["ELEMENT"], ($arParams + array("TEMPLATES" => array()))); 
$aCols = $aCols["columns"]; 
/********************************************************************
				/Input params
********************************************************************/


$arFields = array(
	array("id" => "FILE", "name" => GetMessage("WD_FILE"), "type" => "label", "value" => $aCols["NAME"])
);
if ($arParams["SHOW_RATING"] == "Y")
{
	ob_start();
	$APPLICATION->IncludeComponent(
	  "bitrix:rating.vote", $arParams["RATING_TYPE"],
		Array(
			"ENTITY_TYPE_ID" => "IBLOCK_ELEMENT",
			"ENTITY_ID" => $arResult["ELEMENT"]["ID"],
			"OWNER_ID" => $arResult["ELEMENT"]["CREATED_BY"],
			"PATH_TO_USER_PROFILE" => $arParams["USER_VIEW_URL"],
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);
	$sVal = ob_get_contents();
	ob_end_clean();	
	$arFields[] = array(
		"id" => "RATING", 
		"name" => GetMessage("WD_RATING"), 
		"type" => "label", 
		"value" => $sVal
	);
}
$arFields[] = array(
		"id" => "CREATED", "name" => GetMessage("WD_FILE_CREATED"), "type" => "label", 
		"value" => $arResult["ELEMENT"]["DATE_CREATE"].' '.$arResult["USERS"][$arResult["ELEMENT"]["CREATED_BY"]]["LINK"]);
$arFields[] = array(
		"id" => "MODIFIED", "name" => GetMessage("WD_FILE_MODIFIED"), "type" => "label", 
		"value" => $arResult["ELEMENT"]["TIMESTAMP_X"].' '.$arResult["USERS"][$arResult["ELEMENT"]["MODIFIED_BY"]]["LINK"]);
$arFields[] = array(
		"id" => "FILE_SIZE", "name" => GetMessage("WD_FILE_SIZE"), "type" => "label", 
		"value" => $arResult["ELEMENT"]["FILE_SIZE"].'<span class="wd-item-controls element_download"><a target="_blank" href="'.$arResult["URL"]["DOWNLOAD"].'">'.GetMessage("WD_DOWNLOAD_FILE").'</a></span>');
if (!empty($arResult["ELEMENT"]["TAGS"]))
{
	$arFields[] = array(
		"id" => "TAGS", 
		"name" => GetMessage("WD_TAGS"), 
		"type" => "label", 
		"value" => $arResult["ELEMENT"]["TAGS"]
		); 
}
if (!empty($arResult["ELEMENT"]["PREVIEW_TEXT"]))
{
	$arFields[] = array(
		"id" => "DESCRIPTION", 
		"name" => GetMessage("WD_DESCRIPTION"), 
		"type" => "label", 
		"value" => $arResult["ELEMENT"]["PREVIEW_TEXT"]); 
}
if ($arParams["WORKFLOW"] == "workflow" && $arParams["PERMISSION"] >= "U")
{
	$arFields[] = array("id" => "WF_PARAMS", "name" => GetMessage("WD_WF_PARAMS"), "type" => "section");
	$arFields[] = array(
		"id" => "FILE_STATUS", "name" => GetMessage("WD_FILE_STATUS"), "type" => "label", 
			"value" => $arResult["ELEMENT"]["WF_STATUS_TITLE"].
			(
				intVal($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]) <= 0 ? 
					' <span class="comments">'.GetMessage("WD_WF_COMMENTS2").'</span>'
					: 
					(
						$arResult["ELEMENT"]["LAST_ID"] > $arResult["ELEMENT"]["REAL_ID"] ? 
							' <span class="comments">'.GetMessage("WD_WF_COMMENTS1").'</span>'
							: 
							''			
					)
			)
		); 
	if (!empty($arResult["ELEMENT"]["WF_COMMENTS"]))
		$arFields[] = array(
			"id" => "WF_COMMENTS", 
			"name" => GetMessage("WD_FILE_COMMENTS"), 
			"type" => "label", 
			"value" => $arResult["ELEMENT"]["WF_COMMENTS"]); 
	if ($arParams["SHOW_WORKFLOW"] != "N" && !empty($arResult["ELEMENT"]["ORIGINAL"]) && $arResult["ELEMENT"]["ID"] != $arResult["ELEMENT"]["REAL_ID"])
	{
		$arFields[] = array(
			"id" => "WF_ORIGINAL", 
			"name" => GetMessage("WD_FILE_ORIGINAL"), 
			"type" => "section"
		);
		$arFields[] = array(
			"id" => "ORIGINAL_FILE", 
			"name" => GetMessage("WD_FILE"), 
			"type" => "label", 
			"value" => '<div class="element-icon ic'.substr($arResult["ELEMENT"]["ORIGINAL"]["EXTENTION"], 1).'"></div>'.
				'<a href="'.$arResult["URL"]["VIEW_ORIGINAL"].'">'.$arResult["ELEMENT"]["ORIGINAL"]["NAME"].'</a>'.
				'<span class="wd-item-controls element_view"><a href="'.$arResult["URL"]["VIEW_ORIGINAL"].'">'.GetMessage("WD_VIEW_FILE").'</a></span>'
			); 
	}
}
	

?><?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
		"FORM_ID" => $arParams["FORM_ID"],
		"TABS" => array(array("id" => "tab1", "name" => GetMessage("WD_DOCUMENT"), "title" => GetMessage("WD_DOCUMENT_ALT"), "fields" => $arFields)),
		"DATA" => array()
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);?><?

if ($this->__component->__parent)
{
	$this->__component->__parent->arResult["arButtons"] = is_array($this->__component->__parent->arResult["arButtons"]) ? $this->__component->__parent->arResult["arButtons"] : array(); 
	if ($arResult["ELEMENT"]["SHOW"]["BP_VERSIONS"] == "Y")
	{
		$this->__component->__parent->arResult["arButtons"]["versions"] = array(
			"TEXT" => GetMessage("WD_VERSIONS"),
			"TITLE" => str_replace("#NAME#", $arResult["ELEMENT_ORIGINAL"]["NAME"], GetMessage("WD_VERSIONS_ALT")),
			"LINK" => $arResult["ELEMENT"]["URL"]["VERSIONS"],
			"ICON" => "btn-list"); 
		if ($arResult["ELEMENT"]["SHOW"]["EDIT"] == "Y" || $arResult["ELEMENT"]["SHOW"]["HISTORY"] == "Y" || 
			$arResult["ELEMENT"]["SHOW"]["DELETE"] == "Y" || $arResult["ELEMENT"]["SHOW"]["UNLOCK"] == "Y")
			$this->__component->__parent->arResult["arButtons"]["separator"] = array("NEWBAR" => true); 
	}
	
	if ($arResult["ELEMENT"]["SHOW"]["HISTORY"] == "Y")
		$this->__component->__parent->arResult["arButtons"]["history"] = array(
			"TEXT" => GetMessage("WD_HISTORY_FILE"),
			"TITLE" => GetMessage("WD_HISTORY_FILE_ALT"),
			"LINK" => $arResult["URL"]["HIST"],
			"ICON" => "btn-list element-history"); 
	if ($arResult["ELEMENT"]["SHOW"]["EDIT"] == "Y")
		$this->__component->__parent->arResult["arButtons"]["edit"] = array(
			"TEXT" => GetMessage("WD_EDIT_FILE"),
			"TITLE" => GetMessage("WD_EDIT_FILE_ALT"),
			"LINK" => $arResult["URL"]["EDIT"],
			"ICON" => "btn-edit element-edit"); 
	if ($arResult["ELEMENT"]["SHOW"]["DELETE"] == "Y")
		$this->__component->__parent->arResult["arButtons"]["delete"] = array(
			"TEXT" => GetMessage("WD_DELETE_FILE"),
			"TITLE" => GetMessage("WD_DELETE_FILE_ALT"),
			"LINK" => "javascript:WDDrop('".CUtil::JSEscape($arResult["URL"]["DELETE"])."');",
			"ICON" => "btn-delete element-delete"); 

	if ($arResult["ELEMENT"]["SHOW"]["UNLOCK"] == "Y") 
	{
		$arResult["URL"]["~UNLOCK"] .= (strpos($arResult["URL"]["~UNLOCK"], "?") === false ? "?" : "&").
			"back_url=".urldecode($APPLICATION->GetCurPageParam()); 
		$this->__component->__parent->arResult["arButtons"][] = array(
			"TEXT" => GetMessage("WD_UNLOCK_FILE"),
			"TITLE" => GetMessage("WD_UNLOCK_FILE"),
			"LINK" => $arResult["URL"]["~UNLOCK"],
			"ICON" => "btn-edit element-unlock"); 
	}
}
?>

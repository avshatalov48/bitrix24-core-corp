<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if ($arResult["VARIABLES"]["PERMISSION"] < "W"):
	return false;
endif;
?><?$APPLICATION->IncludeComponent("bitrix:bizproc.workflow.list", ".default", Array(
	"MODULE_ID"	=>	"webdav",
	"ENTITY"	=>	ENTITY,
	"DOCUMENT_ID"	=>	DOCUMENT_TYPE,
	
	"EDIT_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_workflow_edit"],
	
	"SET_TITLE"	=>	$arParams["SET_TITLE"]
	),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>
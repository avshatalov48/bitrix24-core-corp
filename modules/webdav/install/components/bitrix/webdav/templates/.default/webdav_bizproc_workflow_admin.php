<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if ($arResult["VARIABLES"]["PERMISSION"] < "W"):
	return false;
endif;

if ($arParams['OBJECT']->CheckRight($arResult["VARIABLES"]["PERMISSION"], "iblock_rights_edit") < "W")
{
	ShowError(GetMessage('WD_ACCESS_DENIED'));
	return false;
}

if (check_bitrix_sessid())
{
	WDClearComponentCache(array("webdav.section.list"));

	global $CACHE_MANAGER;
	$CACHE_MANAGER->ClearByTag("iblock_id_".intval($arParams["IBLOCK_ID"])."");
}

?><?$APPLICATION->IncludeComponent("bitrix:bizproc.workflow.list", ".default", Array(
	"MODULE_ID"	=>	"webdav",
	"ENTITY"	=>	ENTITY,
	"DOCUMENT_ID"	=>	DOCUMENT_TYPE,

	"EDIT_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_workflow_edit"],

	"SET_TITLE"	=>	$arParams["SET_TITLE"],
	"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
	),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>

<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if ($arParams['OBJECT']->CheckRight($arResult["VARIABLES"]["PERMISSION"], "iblock_rights_edit") < "W")
{
	ShowError(GetMessage('WD_ACCESS_DENIED'));
	return false;
}
if ($_SERVER['REQUEST_METHOD'] == "POST" && check_bitrix_sessid())
	WDClearComponentCache(array("webdav.section.list", "webdav.menu"));

$APPLICATION->IncludeComponent(
		"bitrix:bizproc.workflow.edit",
		"",
		Array(
			"MODULE_ID" => "webdav",
			"ENTITY" => ENTITY,
			"DOCUMENT_TYPE" => DOCUMENT_TYPE,
			"ID" => $arResult['VARIABLES']['ID'],
			"EDIT_PAGE_TEMPLATE" => $arResult["URL_TEMPLATES"]["webdav_bizproc_workflow_edit"],
			"LIST_PAGE_URL" => $arResult['URL_TEMPLATES']['webdav_bizproc_workflow_admin'],
			"SHOW_TOOLBAR" => "Y",
			"SET_TITLE" => $arParams["SET_TITLE"]
		)
	);
?>

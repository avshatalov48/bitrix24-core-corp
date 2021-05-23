<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule("crm"))
	return false;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if ($arParams["PAGE_VAR"] == '')
	$arParams["PAGE_VAR"] = "page";
if ($arParams["ID_VAR"] == '')
	$arParams["ID_VAR"] = "id";

$arParams["PATH_TO_INDEX"] = trim($arParams["PATH_TO_INDEX"]);
if ($arParams["PATH_TO_INDEX"] == '')
	$arParams["PATH_TO_INDEX"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=index";

$arParams["PATH_TO_EDIT"] = trim($arParams["PATH_TO_EDIT"]);
if ($arParams["PATH_TO_EDIT"] == '')
	$arParams["PATH_TO_EDIT"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=edit&".$arParams["ID_VAR"]."=#id#";

$arParams["PATH_TO_SYNC"] = trim($arParams["PATH_TO_SYNC"]);
if ($arParams["PATH_TO_SYNC"] == '')
	$arParams["PATH_TO_SYNC"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=sync&".$arParams["ID_VAR"]."=#id#";

$arResult["FatalErrorMessage"] = "";
$arResult["ErrorMessage"] = "";

$arParams["ID"] = intval($arParams["ID"]);
$arResult["BP"] = false;

$arResult["PATH_TO_INDEX"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_INDEX"], array());
$arResult["PATH_TO_EDIT"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_EDIT"], array("id" => $arParams["ID"]));

if ($arResult["FatalErrorMessage"] == '')
{
	if ($arParams["ID"] > 0)
	{
		$dbRecordsList = CCrmExternalSale::GetList(
			array(),
			array("ID" => $arParams["ID"])
		);
		if ($arRecord = $dbRecordsList->GetNext())
			$arResult["BP"] = $arRecord;
		else
			$arResult["FatalErrorMessage"] .= GetMessage("BPWC_WLC_WRONG_BP").". ";
	}
	else
	{
		$arResult["FatalErrorMessage"] .= GetMessage("BPWC_WLC_WRONG_BP").". ";
	}
}

if ($arResult["FatalErrorMessage"] == '')
{
	if ($arResult["BP"]["ACTIVE"] != "Y")
		$arResult["FatalErrorMessage"] .= GetMessage("BPWC_WLC_WRONG_BP_ACTIVE").". ";
}

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage("BPABL_PAGE_TITLE"));
$APPLICATION->AddChainItem(GetMessage("BPABL_PAGE_TITLE"), $arResult["PATH_TO_INDEX"]);
?>
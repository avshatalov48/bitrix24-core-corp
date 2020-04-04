<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule("crm"))
	return false;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if (strlen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";
if (strlen($arParams["ID_VAR"]) <= 0)
	$arParams["ID_VAR"] = "id";

$arParams["PATH_TO_INDEX"] = trim($arParams["PATH_TO_INDEX"]);
if (strlen($arParams["PATH_TO_INDEX"]) <= 0)
	$arParams["PATH_TO_INDEX"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=index";

$arParams["PATH_TO_EDIT"] = trim($arParams["PATH_TO_EDIT"]);
if (strlen($arParams["PATH_TO_EDIT"]) <= 0)
	$arParams["PATH_TO_EDIT"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=edit&".$arParams["ID_VAR"]."=#id#";

$arParams["PATH_TO_SYNC"] = trim($arParams["PATH_TO_SYNC"]);
if (strlen($arParams["PATH_TO_SYNC"]) <= 0)
	$arParams["PATH_TO_SYNC"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=sync&".$arParams["ID_VAR"]."=#id#";

$arResult["FatalErrorMessage"] = "";
$arResult["ErrorMessage"] = "";

$arParams["ID"] = intval($arParams["ID"]);
$arResult["BP"] = false;

$arResult["PATH_TO_INDEX"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_INDEX"], array());
$arResult["PATH_TO_EDIT"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_EDIT"], array("id" => $arParams["ID"]));

if (strlen($arResult["FatalErrorMessage"]) <= 0)
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

if (strlen($arResult["FatalErrorMessage"]) <= 0)
{
	if ($arResult["BP"]["ACTIVE"] != "Y")
		$arResult["FatalErrorMessage"] .= GetMessage("BPWC_WLC_WRONG_BP_ACTIVE").". ";
}

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage("BPABL_PAGE_TITLE"));
$APPLICATION->AddChainItem(GetMessage("BPABL_PAGE_TITLE"), $arResult["PATH_TO_INDEX"]);
?>
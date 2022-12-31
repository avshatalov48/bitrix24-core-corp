<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['CAN_EDIT_USER'] = $USER->CanDoOperation('edit_all_users') || $USER->CanDoOperation('edit_subordinate_users');

global $USER;
if (!IsModuleInstalled("bitrix24") && CModule::IncludeModule("socialnetwork"))
{
	$arResult['CAN_EDIT_USER'] = $arResult['CAN_EDIT_USER'] && CSocNetUser::IsCurrentUserModuleAdmin();
}

if (intval($arParams['USER']["PERSONAL_PHOTO_SOURCE"]) > 0)
{
	$imageFile = CFile::GetFileArray($arParams['USER']["PERSONAL_PHOTO_SOURCE"]);
	if ($imageFile !== false)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$imageFile,
			array("width" => 100, "height" => 100),
			BX_RESIZE_IMAGE_EXACT,
			false
		);
		$arParams['USER']["PERSONAL_PHOTO_SOURCE"] = $arParams['~USER']["PERSONAL_PHOTO_SOURCE"] = $arFileTmp["src"];			
	}
	else
		$arParams['USER']["PERSONAL_PHOTO_SOURCE"] = $arParams['~USER']["PERSONAL_PHOTO_SOURCE"] = "";
}

if (!isset($arParams["USER"]["DETAIL_URL"]))
{
	$detailURL = COption::GetOptionString('intranet', 'search_user_url', '/user/#ID#/');
	if (!$detailURL)
	{
		if (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite())
			$detailURL = SITE_DIR."contacts/personal/user/#user_id#/";
		else
			$detailURL = SITE_DIR."company/personal/user/#user_id#/";
	}
	$arParams["USER"]["DETAIL_URL"] = str_replace(array('#ID#', '#USER_ID#'), $arParams["USER"]['ID'], $detailURL);
}

if (
	($GLOBALS["USER"]->GetID() != $arParams["USER"]["ID"])
	&& ($arParams["USER"]["ACTIVE"] != "N")
	&& CBXFeatures::IsFeatureEnabled("WebMessenger")
	&& (IsModuleInstalled("im"))
)
	$arResult['CAN_VIDEO_CALL'] = true;
?>
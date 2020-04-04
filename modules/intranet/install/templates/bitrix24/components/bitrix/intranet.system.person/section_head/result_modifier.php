<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['CAN_EDIT_USER'] = $USER->CanDoOperation('edit_all_users') || $USER->CanDoOperation('edit_subordinate_users');
if (!IsModuleInstalled("bitrix24") && CModule::IncludeModule("socialnetwork"))
{
	$arResult['CAN_EDIT_USER'] = $arResult['CAN_EDIT_USER'] && CSocNetUser::IsCurrentUserModuleAdmin();
}

if (IsModuleInstalled("video") && !array_key_exists("PATH_TO_VIDEO_CALL", $arParams))
{
	$arParams["~PATH_TO_VIDEO_CALL"] = $arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#USER_ID#/";
	$arResult["Urls"]["VideoCall"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_CALL"], array("user_id" => $arParams["ID"], "USER_ID" => $arParams["USER"]["ID"], "ID" => $arParams["USER"]["ID"]));
}

if (!isset($arParams["~USER"]["DETAIL_URL"]))
{
	$detailURL = COption::GetOptionString('intranet', 'search_user_url', '/user/#ID#/');
	if (!$detailURL)
	{
		if (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite())
			$detailURL = SITE_DIR."contacts/personal/user/#user_id#/";
		else
			$detailURL = SITE_DIR."company/personal/user/#user_id#/";
	}
	$arParams["~USER"]["DETAIL_URL"] = str_replace(array('#ID#', '#USER_ID#'), $arParams["USER"]['ID'], $detailURL);
}

if (
	($GLOBALS["USER"]->GetID() != $arParams["USER"]["ID"])
	&& ($arParams["USER"]["ACTIVE"] != "N")
	&& CBXFeatures::IsFeatureEnabled("WebMessenger")
	&& (IsModuleInstalled("im"))
)
	$arResult['CAN_VIDEO_CALL'] = true;
?>
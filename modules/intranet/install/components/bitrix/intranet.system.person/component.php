<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat();

$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;
$arResult["bUseLogin"] = $bUseLogin;
$arResult['CAN_EDIT_USER'] = ($USER->CanDoOperation('edit_all_users') || ($USER->CanDoOperation('edit_subordinate_users') && (count(array_diff(CUser::GetUserGroup($arParams["USER"]['ID']), CSocNetTools::GetSubordinateGroups())) == 0)));
$arResult['CAN_EDIT_USER_SELF'] = ($USER->CanDoOperation('edit_own_profile') && $arParams["USER"]['ID'] == $USER->GetID());

$arResult['USER_PROP'] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);

$arResult['CAN_MESSAGE'] = false;
$arResult['CAN_VIDEO_CALL'] = false;
$arResult['CAN_VIEW_PROFILE'] = false;

if (CModule::IncludeModule('socialnetwork') && $GLOBALS["USER"]->IsAuthorized())
{
	$arResult["CurrentUserPerms"] = CSocNetUserPerms::InitUserPerms($GLOBALS["USER"]->GetID(), $arParams["USER"]["ID"], CSocNetUser::IsCurrentUserModuleAdmin());

	if (
		($GLOBALS["USER"]->GetID() != $arParams["USER"]["ID"])
		&& ($arParams["USER"]["ACTIVE"] != "N")
		&& CBXFeatures::IsFeatureEnabled("WebMessenger") 
		&& (IsModuleInstalled("im") || $arResult["CurrentUserPerms"]["Operations"]["message"])
	)
		$arResult['CAN_MESSAGE'] = true;
		
	if ($arResult["CurrentUserPerms"]["Operations"]["viewprofile"])
		$arResult['CAN_VIEW_PROFILE'] = true;
		
	if (
		($GLOBALS["USER"]->GetID() != $arParams["USER"]["ID"])
		&& $arResult["CurrentUserPerms"]["Operations"]["videocall"]
		&& ($arParams["USER"]["ACTIVE"] != "N")
	)
		$arResult['CAN_VIDEO_CALL'] = true;
		
	if(!CModule::IncludeModule("video"))
		$arResult['CAN_VIDEO_CALL'] = false;
	elseif(!CVideo::CanUserMakeCall())
		$arResult['CAN_VIDEO_CALL'] = false;
}

$arResult["Urls"]["VideoCall"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_CALL"], array("user_id" => $arParams["USER"]["ID"], "USER_ID" => $arParams["USER"]["ID"], "ID" => $arParams["USER"]["ID"]));

$arResult['Urls']['TooltipCall'] = $APPLICATION->GetCurPageParam("", array("bxajaxid", "logout"));

$this->IncludeComponentTemplate();
?>
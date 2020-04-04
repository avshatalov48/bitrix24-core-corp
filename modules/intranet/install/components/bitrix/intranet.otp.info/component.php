<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (
	!CModule::IncludeModule("security")
	|| !\Bitrix\Security\Mfa\Otp::isOtpEnabled()
	|| !$USER->IsAuthorized()
	|| !CSecurityUser::IsOtpMandatory()
)
	return;

foreach (GetModuleEvents("intranet", "OnIntranetPopupShow", true) as $arEvent)
{
	if (ExecuteModuleEventEx($arEvent) === false)
		return;
}

if(defined("BX_COMP_MANAGED_CACHE"))
	$ttl = 2592000;
else
	$ttl = 600;
$cache_id = 'user_otp_'.intval($USER->GetID()/100);
$cache_dir = '/otp/user_id';
$obCache = new CPHPCache;

if($obCache->InitCache($ttl, $cache_id, $cache_dir))
{
	$arUserOtp = $obCache->GetVars();
}
else
{
	$arUserOtp = array(
		"ACTIVE" => CSecurityUser::IsUserOtpActive($USER->GetID())
	);

	if(defined("BX_COMP_MANAGED_CACHE"))
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache($cache_dir);
		$CACHE_MANAGER->RegisterTag("USER_OTP_".intval($USER->GetID() / 100));
		$CACHE_MANAGER->EndTagCache();
	}

	if($obCache->StartDataCache())
	{
		$obCache->EndDataCache($arUserOtp);
	}
}

$arParams["PATH_TO_PROFILE_SECURITY"] = trim($arParams["PATH_TO_PROFILE_SECURITY"]);
if(strlen($arParams["PATH_TO_PROFILE_SECURITY"])<=0)
	$arParams["PATH_TO_PROFILE_SECURITY"] = SITE_DIR."company/personal/user/#user_id#/security/";
$arResult["PATH_TO_PROFILE_SECURITY"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_PROFILE_SECURITY"], array("user_id" => $USER->GetID()));

//for all mandatory
$IsUserSkipMandatoryRights = CSecurityUser::IsUserSkipMandatoryRights($USER->GetID());
$dateDeactivate = CSecurityUser::GetDeactivateUntil($USER->GetID());

if (
	!$arUserOtp["ACTIVE"]
	&& !isset($_SESSION["OTP_MANDATORY_INFO"])
	&& !$IsUserSkipMandatoryRights
	&& $dateDeactivate
)
{
	$arResult["POPUP_NAME"] = "otp_mandatory_info";
	$_SESSION["OTP_MANDATORY_INFO"] = "Y";
	$arResult["USER"]["OTP_DAYS_LEFT"] = ($dateDeactivate) ? FormatDate("ddiff", time()-60*60*24,  MakeTimeStamp($dateDeactivate)) : "";

	$this->IncludeComponentTemplate();
}
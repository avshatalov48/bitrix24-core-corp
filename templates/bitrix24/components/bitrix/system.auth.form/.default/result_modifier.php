<?
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arParams["PATH_TO_SONET_PROFILE"] = (isset($arParams["PATH_TO_SONET_PROFILE"]) ? $arParams["PATH_TO_SONET_PROFILE"] : SITE_DIR."company/personal/user/#user_id#/");
$arParams["PATH_TO_SONET_PROFILE_EDIT"] = (isset($arParams["PATH_TO_SONET_PROFILE_EDIT"]) ? $arParams["PATH_TO_SONET_PROFILE_EDIT"] : SITE_DIR."company/personal/user/#user_id#/edit/");
$arParams["THUMBNAIL_SIZE"] = (isset($arParams["THUMBNAIL_SIZE"]) ? intval($arParams["THUMBNAIL_SIZE"]) : 100);

$arResult["USER_FULL_NAME"] = CUser::FormatName("#NAME# #LAST_NAME#", array(
	"NAME" => $USER->GetFirstName(),
	"LAST_NAME" => $USER->GetLastName(),
	"SECOND_NAME" => $USER->GetSecondName(),
	"LOGIN" => $USER->GetLogin()
));

$user_id = intval($GLOBALS["USER"]->GetID());

if(defined("BX_COMP_MANAGED_CACHE"))
	$ttl = 2592000;
else
	$ttl = 600;
$cache_id = 'user_avatar_'.$user_id;
$cache_dir = '/bx/user_avatar';
$obCache = new CPHPCache;

if($obCache->InitCache($ttl, $cache_id, $cache_dir))
{
	$arResult["USER_PERSONAL_PHOTO_SRC"] = $obCache->GetVars();
}
else
{
	if ($GLOBALS["USER"]->IsAuthorized())
	{
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->StartTagCache($cache_dir);
		}

		$dbUser = CUser::GetByID($GLOBALS["USER"]->GetID());
		$arUser = $dbUser->Fetch();

		$arResult["USER_DATE_REGISTER"] = MakeTimeStamp($arUser["DATE_REGISTER"]);
		$imageFile = false;

		if (intval($arUser["PERSONAL_PHOTO"]) > 0)
		{
			$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => $arParams["THUMBNAIL_SIZE"], "height" => $arParams["THUMBNAIL_SIZE"]),
					BX_RESIZE_IMAGE_EXACT,
					false
				);
				$arResult["USER_PERSONAL_PHOTO_SRC"] = $arFileTmp["src"];
			}
		}
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->RegisterTag("USER_CARD_".intval($user_id / TAGGED_user_card_size));
			$CACHE_MANAGER->EndTagCache();
		}
	}

	if($obCache->StartDataCache())
	{
		$obCache->EndDataCache($arResult["USER_PERSONAL_PHOTO_SRC"]);
	}
}

// add chache here!!!

if(
	IsModuleInstalled('bitrix24')
	&& COption::GetOptionString('bitrix24', 'network', 'N') == 'Y'
	&& CModule::IncludeModule('socialservices')
)
{
	// also check for B24Net turned on in module settings

	$dbSocservUser = CSocServAuthDB::GetList(
		array(),
		array(
			'USER_ID' => $user_id,
			"EXTERNAL_AUTH_ID" => CSocServBitrix24Net::ID
		), false, false, array("PERSONAL_WWW")
	);
	$arSocservUser = $dbSocservUser->Fetch();
	if($arSocservUser)
	{
		$arResult['B24NET_WWW'] = $arSocservUser['PERSONAL_WWW'];
	}
}

$arResult["NEED_CHECK_HELP_NOTIFICATION"] = "N";
$arResult["HELP_NOTIFY_NUM"] = "";
$arResult["CAN_HAVE_HELP_NOTIFICATIONS"] = 'N';
$arResult["CURRENT_HELP_NOTIFICATIONS"] = '';
$arResult["LAST_CHECK_NOTIFICATIONS_TIME"] = '';

if (CModule::IncludeModule('bitrix24') && !(CModule::IncludeModule("extranet") && SITE_ID == CExtranet::GetExtranetSiteID()))
{
	$helpNotify = CUserOptions::GetOption("bitrix24", "new_helper_notify");
	if (!isset($helpNotify["counter_update_date"]))
	{
		$helpNotify["counter_update_date"] = time();
		CUserOptions::SetOption("bitrix24", "new_helper_notify", $helpNotify);
	}
	$arResult["COUNTER_UPDATE_DATE"] = $helpNotify["counter_update_date"]; //time when user read notifications last time

	if (!isset($helpNotify["time"]) || $helpNotify["time"] < time())
	{
		$arResult["NEED_CHECK_HELP_NOTIFICATION"] = "Y";
	}
	if (isset($helpNotify["num"]))
	{
		$arResult["HELP_NOTIFY_NUM"] = intval($helpNotify["num"]);
	}
	if (isset($helpNotify["notifications"]))
	{
		$arResult['CURRENT_HELP_NOTIFICATIONS'] = $helpNotify["notifications"];
	}
	if (isset($helpNotify["lastCheckNotificationsTime"]))
	{
		$arResult['LAST_CHECK_NOTIFICATIONS_TIME'] = $helpNotify["lastCheckNotificationsTime"];
	}
	$arResult["CAN_HAVE_HELP_NOTIFICATIONS"] = 'Y';
}

$arResult["SHOW_LICENSE_BUTTON"] = false;
if (
	CModule::IncludeModule('bitrix24')
	&& \CBitrix24::getLicenseFamily() !== "company"
	&& !(Loader::includeModule("extranet") && CExtranet::IsExtranetSite())
)
{
	$arResult["SHOW_LICENSE_BUTTON"] = true;
	$arResult["B24_LICENSE_PATH"] = CBitrix24::PATH_LICENSE_ALL;
	$arResult["LICENSE_BUTTON_COUNTER_URL"] = CBitrix24::PATH_COUNTER;
	$arResult["HOST_NAME"] = defined('BX24_HOST_NAME')? BX24_HOST_NAME: SITE_SERVER_NAME;
}

$arResult["HELPDESK_URL"] = "";
if (Loader::includeModule("ui"))
{
	$arResult["HELPDESK_URL"] = \Bitrix\UI\Util::getHelpdeskUrl(true);
}

$arResult["OPEN_HELPER_AFTER_PAGE_LOADING"] = false;
if (isset($_GET["helper"]) && $_GET["helper"] === "Y")
{
	$arResult["OPEN_HELPER_AFTER_PAGE_LOADING"] = true;
}
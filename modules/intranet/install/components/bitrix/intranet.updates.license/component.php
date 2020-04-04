<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

$arResult["LICENSE_KEY"] = LICENSE_KEY;
$arResult["ERROR"] = "";

//license key
if($_SERVER["REQUEST_METHOD"]=="POST" && check_bitrix_sessid() && isset($_POST["LICENSE_BUTTON"]))
{
	if (isset($_POST["SET_LICENSE_KEY"]) && LICENSE_KEY !== $_POST["SET_LICENSE_KEY"])
	{
		$SET_LICENSE_KEY = preg_replace("/[^A-Za-z0-9_.-]/", "", $_POST["SET_LICENSE_KEY"]);
		$arResult["LICENSE_KEY"] = $SET_LICENSE_KEY;

		file_put_contents(
			$_SERVER["DOCUMENT_ROOT"].BX_ROOT."/license_key.php",
			"<"."? $"."LICENSE_KEY = \"".EscapePHPString($SET_LICENSE_KEY)."\"; ?".">"
		);

		LocalRedirect( $APPLICATION->GetCurPageParam());
	}
}

//coupon
if($_SERVER["REQUEST_METHOD"]=="POST" && check_bitrix_sessid() && isset($_POST["ACTIVATE_COUPON_BUTTON"]))
{
	$coupon = $APPLICATION->UnJSEscape($_REQUEST["COUPON"]);
	if (StrLen($coupon) <= 0)
		$errorMessage .= GetMessage("SUPA_ACE_CPN").". ";

	if (StrLen($errorMessage) <= 0)
	{
		if (!CUpdateClient::ActivateCoupon($coupon, $errorMessage, LANGUAGE_ID))
			$errorMessage .= GetMessage("SUPA_ACE_ACT").". ";
	}

	if (StrLen($errorMessage) <= 0)
	{
		CUpdateClient::AddMessage2Log("Coupon activated", "UPD_SUCCESS");

		$url = $APPLICATION->GetCurPageParam("coupon=Y");
		LocalRedirect($url);
	}
	else
	{
		CUpdateClient::AddMessage2Log("Error: ".$errorMessage, "UPD_ERROR");
		$arResult["ERROR"] = $errorMessage;
	}
}

//activate key
$arResult["NEED_ACTIVATE"] = false;
$arUpdateList = CUpdateClient::GetUpdatesList($errorMessage);
if (isset($arUpdateList["CLIENT"]) && !isset($arUpdateList["UPDATE_SYSTEM"]) && count($arUpdateList["CLIENT"]) > 0 && $arUpdateList["CLIENT"][0]["@"]["RESERVED"] == "Y")
{
	$arResult["NEED_ACTIVATE"] = true;
}

if ($arResult["NEED_ACTIVATE"] && $_SERVER["REQUEST_METHOD"]=="POST" && check_bitrix_sessid() && isset($_POST["ACTIVATE_BUTTON"]))
{
	$name = $APPLICATION->UnJSEscape($_REQUEST["NAME"]);
	if (strlen($name) <= 0)
		$errorMessage .= GetMessage("SUPA_AERR_NAME").". ";

	$email = $APPLICATION->UnJSEscape($_REQUEST["EMAIL"]);
	if (strlen($email) <= 0)
		$errorMessage .= GetMessage("SUPA_AERR_EMAIL").". ";
	elseif (!CUpdateSystem::CheckEMail($email))
		$errorMessage .= GetMessage("SUPA_AERR_EMAIL1").". ";

	$siteUrl = $APPLICATION->UnJSEscape($_REQUEST["SITE_URL"]);
	if (strlen($siteUrl) <= 0)
		$errorMessage .= GetMessage("SUPA_AERR_URI").". ";

	$phone = $APPLICATION->UnJSEscape($_REQUEST["PHONE"]);
	if (strlen($phone) <= 0)
		$errorMessage .= GetMessage("SUPA_AERR_PHONE").". ";

	$contactEMail = $APPLICATION->UnJSEscape($_REQUEST["CONTACT_EMAIL"]);
	if (strlen($contactEMail) <= 0)
		$errorMessage .= GetMessage("SUPA_AERR_CONTACT_EMAIL").". ";
	elseif (!CUpdateSystem::CheckEMail($contactEMail))
		$errorMessage .= GetMessage("SUPA_AERR_CONTACT_EMAIL1").". ";

	$contactPerson = $APPLICATION->UnJSEscape($_REQUEST["CONTACT_PERSON"]);
	if (strlen($contactPerson) <= 0)
		$errorMessage .= GetMessage("SUPA_AERR_CONTACT_PERSON").". ";

	$contactPhone = $APPLICATION->UnJSEscape($_REQUEST["CONTACT_PHONE"]);
	if (strlen($contactPhone) <= 0)
		$errorMessage .= GetMessage("SUPA_AERR_CONTACT_PHONE").". ";

	$generateUser = $APPLICATION->UnJSEscape($_REQUEST["GENERATE_USER"]);
	if ($generateUser == "Y")
	{
		$userName = $APPLICATION->UnJSEscape($_REQUEST["USER_NAME"]);
		if (strlen($userName) <= 0)
			$errorMessage .= GetMessage("SUPA_AERR_FNAME").". ";
		$userLastName = $APPLICATION->UnJSEscape($_REQUEST["USER_LAST_NAME"]);
		if (strlen($userLastName) <= 0)
			$errorMessage .= GetMessage("SUPA_AERR_LNAME").". ";
		$userLogin = $APPLICATION->UnJSEscape($_REQUEST["USER_LOGIN_A"]);
		if (strlen($userLogin) <= 0)
			$errorMessage .= GetMessage("SUPA_AERR_LOGIN").". ";
		elseif (strlen($userLogin) < 3)
			$errorMessage .= GetMessage("SUPA_AERR_LOGIN1").". ";
		$userPassword = $APPLICATION->UnJSEscape($_REQUEST["USER_PASSWORD"]);
		$userPasswordConfirm = $APPLICATION->UnJSEscape($_REQUEST["USER_PASSWORD_CONFIRM"]);
		if (strlen($userPassword) <= 0)
			$errorMessage .= GetMessage("SUPA_AERR_PASSW").". ";
		if ($userPassword != $userPasswordConfirm)
			$errorMessage .= GetMessage("SUPA_AERR_PASSW_CONF").". ";
	}
	else
	{
		$userLogin = $APPLICATION->UnJSEscape($_REQUEST["USER_LOGIN"]);
		if (strlen($userLogin) <= 0)
			$errorMessage .= GetMessage("SUPA_AERR_LOGIN").". ";
		elseif (strlen($userLogin) < 3)
			$errorMessage .= GetMessage("SUPA_AERR_LOGIN1").". ";
	}

	if (strlen($errorMessage) <= 0)
	{
		$contactInfo = $APPLICATION->UnJSEscape($_REQUEST["CONTACT_INFO"]);

		$arFields = array(
			"NAME"           => $name,
			"EMAIL"          => $email,
			"SITE_URL"       => $siteUrl,
			"CONTACT_INFO"   => $contactInfo,
			"PHONE"          => $phone,
			"CONTACT_EMAIL"  => $contactEMail,
			"CONTACT_PERSON" => $contactPerson,
			"CONTACT_PHONE"  => $contactPhone,
			"GENERATE_USER"  => (($generateUser == "Y") ? "Y" : "N"),
			"USER_NAME"      => $userName,
			"USER_LAST_NAME" => $userLastName,
			"USER_LOGIN"     => $userLogin,
			"USER_PASSWORD"  => $userPassword
		);
		CUpdateClient::ActivateLicenseKey($arFields, $errorMessage, LANGUAGE_ID);
	}

	if (StrLen($errorMessage) <= 0)
	{
		CUpdateClient::AddMessage2Log("Licence activated", "UPD_SUCCESS");
	}
	else
	{
		CUpdateClient::AddMessage2Log("Error: ".$errorMessage, "UPD_ERROR");
	}

	if (CUpdateClient::RegisterVersion($errorMessage, LANGUAGE_ID))
	{
		CUpdateClient::AddMessage2Log("Registered", "UPD_SUCCESS");
		$url = $APPLICATION->GetCurPageParam("activate=Y");
		LocalRedirect($url);
	}
	else
	{
		CUpdateClient::AddMessage2Log("Error: ".$errorMessage, "UPD_ERROR");
	}

	$arResult["ERROR"] = $errorMessage;
}

$arResult["UPDATE_LIST"] = array();
if (CUpdateClient::Lock())
{
	if ($arUpdateList = CUpdateClient::GetUpdatesList($errorMessage, LANGUAGE_ID))
	{
		$arResult["UPDATE_LIST"] = $arUpdateList;

		if ($arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_SOURCE"])
		{
			$timestamp = MakeTimeStamp($arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_SOURCE"], "YYYY-MM-DD");
			$arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_FORMAT"] = ConvertTimeStamp($timestamp);
		}
	}
}

$this->IncludeComponentTemplate();
?>

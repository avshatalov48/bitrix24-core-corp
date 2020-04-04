<?
//**********************************************************************/
//**    DO NOT MODIFY THIS FILE                                       **/
//**    MODIFICATION OF THIS FILE WILL ENTAIL SITE FAILURE            **/
//**********************************************************************/
//define("US_KERNEL_PATH", "/bitrix/clients");
define("US_CALL_TYPE", "KERNEL");
//define("US_SAVE_UPDATERS_DIR", "/bitrix/clients/updaters");
//define("US_SAVE_DESCRIPTIONS_DIR", "/bitrix/clients/updaters_descr");
define("US_BASE_MODULE", "controller");

$US_LICENSE_KEY = "";
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/license_key.php"))
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/license_key.php");
if ($US_LICENSE_KEY == "" || strtoupper($US_LICENSE_KEY) == "DEMO")
	define("US_LICENSE_KEY", "DEMO");
else
	define("US_LICENSE_KEY", $US_LICENSE_KEY);


if (!defined("UPD_INTERNAL_CALL") || UPD_INTERNAL_CALL != "Y")
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
	define("US_SHARED_KERNEL_PATH", COption::GetOptionString("controller", "shared_kernel_path", "/bitrix/clients"));
	define("US_SAVE_UPDATERS_DIR", US_SHARED_KERNEL_PATH."/updaters");
	define("US_SAVE_DESCRIPTIONS_DIR", US_SHARED_KERNEL_PATH."/updaters_descr");
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
}
else
{
	define("US_SHARED_KERNEL_PATH", COption::GetOptionString("controller", "shared_kernel_path", "/bitrix/clients"));
	define("US_SAVE_UPDATERS_DIR", US_SHARED_KERNEL_PATH."/updaters");
	define("US_SAVE_DESCRIPTIONS_DIR", US_SHARED_KERNEL_PATH."/updaters_descr");
}
CModule::IncludeModule("controller");

@set_time_limit(0);
ini_set("track_errors", "1");
ignore_user_abort(true);

IncludeModuleLangFile(__FILE__);

if (!$USER->CanDoOperation("controller_member_updates_run") || !check_bitrix_sessid())
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
	die();
}

$errorMessage = "";

$stableVersionsOnly = COption::GetOptionString("controller", "stable_versions_only", "Y");

$queryType = $_REQUEST["query_type"];
if (!in_array($queryType, array("licence", "activate", "key", "register", "sources", "updateupdate", "coupon", "stability", "mail")))
	$queryType = "licence";

/************************************/
if ($queryType == "licence")
{
	COption::SetOptionString("main", "new_license6_sign", "Y");
	echo "Y";
}
elseif ($queryType == "activate")
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
		$userLogin = $APPLICATION->UnJSEscape($_REQUEST["USER_LOGIN"]);
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

	if (strlen($errorMessage) <= 0)
	{
		$contactInfo = $APPLICATION->UnJSEscape($_REQUEST["CONTACT_INFO"]);

		$arFields = array(
			"NAME" => $name,
			"EMAIL" => $email,
			"SITE_URL" => $siteUrl,
			"CONTACT_INFO" => $contactInfo,
			"PHONE" => $phone,
			"CONTACT_EMAIL" => $contactEMail,
			"CONTACT_PERSON" => $contactPerson,
			"CONTACT_PHONE" => $contactPhone,
			"GENERATE_USER" => (($generateUser == "Y") ? "Y" : "N"),
			"USER_NAME" => $userName,
			"USER_LAST_NAME" => $userLastName,
			"USER_LOGIN" => $userLogin,
			"USER_PASSWORD" => $userPassword
		);
		CUpdateClient::ActivateLicenseKey($arFields, $errorMessage, LANG, $stableVersionsOnly);
	}

	if (StrLen($errorMessage) <= 0)
	{
		CUpdateClient::AddMessage2Log("Licence activated", "UPD_SUCCESS");
		echo "Y";
	}
	else
	{
		CUpdateClient::AddMessage2Log("Error: ".$errorMessage, "UPD_ERROR");
		echo $errorMessage;
	}
}
elseif ($queryType == "key")
{
	$newLicenseKey = $APPLICATION->UnJSEscape($_REQUEST["NEW_LICENSE_KEY"]);

	if (strlen($newLicenseKey) <= 0)
		$errorMessage .= "[PULK01] ".GetMessage("SUP_ENTER_KEY").". ";
	elseif (strtolower($newLicenseKey) == "demo")
		$errorMessage .= "[PULK02] ".GetMessage("SUP_ENTER_CORRECT_KEY").". ";

	if (strlen($errorMessage) <= 0)
	{
		if (!($fp = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php", "w")))
			$errorMessage .= "[PULK03] ".GetMessage("SUP_CANT_OPEN_FILE").". ";
	}

	if (strlen($errorMessage) <= 0)
	{
		fputs($fp, "<"."? \$"."LICENSE_KEY = \"".addslashes($NEW_LICENSE_KEY)."\"; ?".">");
		fclose($fp);
		echo "Y";
	}
	else
	{
		echo $errorMessage;
	}
}
elseif ($queryType == "register")
{
	if (CUpdateClient::RegisterVersion($errorMessage, LANG, $stableVersionsOnly))
	{
		CUpdateClient::AddMessage2Log("Registered", "UPD_SUCCESS");
		echo "Y";
	}
	else
	{
		CUpdateClient::AddMessage2Log("Error: ".$errorMessage, "UPD_ERROR");
		echo $errorMessage;
	}
}
elseif ($queryType == "sources")
{
	$arRequestedModules = array();
	if (array_key_exists("requested_modules", $_REQUEST))
	{
		$arRequestedModulesTmp = explode(",", $_REQUEST["requested_modules"]);
		for ($i = 0, $cnt = count($arRequestedModulesTmp); $i < $cnt; $i++)
			if (!in_array($arRequestedModulesTmp[$i], $arRequestedModules))
				$arRequestedModules[] = $arRequestedModulesTmp[$i];
	}

	if (count($arRequestedModules) <= 0)
	{
		$errorMessage .= "[CL00] ".GetMessage("SUPA_ASE_NO_LIST").". ";
		CUpdateClient::AddMessage2Log(GetMessage("SUPA_ASE_NO_LIST"), "CL00");
	}

	if (StrLen($errorMessage) <= 0)
	{
		if (!CUpdateClient::GetPHPSources($errorMessage, LANG, $stableVersionsOnly, $arRequestedModules))
		{
			$errorMessage .= "[CL01] ".GetMessage("SUPA_ASE_SOURCES").". ";
			CUpdateClient::AddMessage2Log(GetMessage("SUPA_ASE_SOURCES"), "CL01");
		}
	}

	if (StrLen($errorMessage) <= 0)
	{
		$temporaryUpdatesDir = "";
		if (!CUpdateClient::UnGzipArchive($temporaryUpdatesDir, $errorMessage, true))
		{
			$errorMessage .= "[CL02] ".GetMessage("SUPA_ASE_PACK").". ";
			CUpdateClient::AddMessage2Log(GetMessage("SUPA_ASE_PACK"), "CL02");
		}
	}

	if (strlen($errorMessage) <= 0)
	{
		if (!CUpdateClient::CheckUpdatability($temporaryUpdatesDir, $errorMessage))
		{
			$errorMessage .= "[CL03] ".GetMessage("SUPA_ASE_CHECK").". ";
			CUpdateClient::AddMessage2Log(GetMessage("SUPA_ASE_CHECK"), "CL03");
		}
	}

	$arStepUpdateInfo = array();
	if (strlen($errorMessage) <= 0)
	{
		$arStepUpdateInfo = CUpdateClient::GetStepUpdateInfo($temporaryUpdatesDir, $errorMessage);
	}

	if (StrLen($errorMessage) <= 0)
	{
		if (isset($arStepUpdateInfo["DATA"]["#"]["ERROR"]))
		{
			for ($i = 0, $cnt = count($arStepUpdateInfo["DATA"]["#"]["ERROR"]); $i < $cnt; $i++)
				$errorMessage .= "[".$arStepUpdateInfo["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ".$arStepUpdateInfo["DATA"]["#"]["ERROR"][$i]["#"];
		}
	}

	$arItemsUpdated = array();
	if (StrLen($errorMessage) <= 0)
	{
		if (isset($arStepUpdateInfo["DATA"]["#"]["ITEM"]))
		{
			for ($i = 0, $cnt = count($arStepUpdateInfo["DATA"]["#"]["ITEM"]); $i < $cnt; $i++)
				$arItemsUpdated[$arStepUpdateInfo["DATA"]["#"]["ITEM"][$i]["@"]["NAME"]] = $arStepUpdateInfo["DATA"]["#"]["ITEM"][$i]["@"]["VALUE"];
		}
	}

	if (StrLen($errorMessage) <= 0)
	{
		if (isset($arStepUpdateInfo["DATA"]["#"]["NOUPDATES"]))
		{
			CUpdateClient::ClearUpdateFolder($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$temporaryUpdatesDir);
			CUpdateClient::AddMessage2Log("Finish - NOUPDATES", "STEP");
			echo "FIN";
		}
		else
		{
			if (strlen($errorMessage) <= 0)
			{
				if (!CUpdateClient::UpdateStepModules($temporaryUpdatesDir, $errorMessage))
				{
					$errorMessage .= "[CL04] ".GetMessage("SUPA_ASE_UPD").". ";
					CUpdateClient::AddMessage2Log(GetMessage("SUPA_ASE_UPD"), "CL04");
				}
			}

			if (StrLen($errorMessage) > 0)
			{
				CUpdateClient::AddMessage2Log("Error: ".$errorMessage, "UPD_ERROR");
				echo "ERR".$errorMessage;
			}
			else
			{
				echo "STP";
				echo count($arItemsUpdated)."|";
				$bFirst = True;
				foreach ($arItemsUpdated as $key => $value)
				{
					CUpdateClient::AddMessage2Log("Sources loaded: ".$key.((StrLen($value) > 0) ? "(".$value.")" : ""), "UPD_SUCCESS");
					echo ($bFirst ? "" : ", ").$key.((StrLen($value) > 0) ? "(".$value.")" : "");
					$bFirst = False;
				}
			}
		}
	}
	else
	{
		CUpdateClient::AddMessage2Log("Error: ".$errorMessage, "UPD_ERROR");
		echo "ERR".$errorMessage;
	}
}
elseif ($queryType == "updateupdate")
{
	if (StrLen($errorMessage) <= 0)
	{
		if (!CUpdateClient::UpdateUpdate($errorMessage, LANG, $stableVersionsOnly))
			$errorMessage .= GetMessage("SUPA_AUE_UPD").". ";
	}

	if (StrLen($errorMessage) <= 0)
	{
		CUpdateClient::AddMessage2Log("Update system updated", "UPD_SUCCESS");
		echo "Y";
	}
	else
	{
		CUpdateClient::AddMessage2Log("Error: ".$errorMessage, "UPD_ERROR");
		echo $errorMessage;
	}
}
elseif ($queryType == "coupon")
{
	$coupon = $APPLICATION->UnJSEscape($_REQUEST["COUPON"]);
	if (StrLen($coupon) <= 0)
		$errorMessage .= GetMessage("SUPA_ACE_CPN").". ";

	if (StrLen($errorMessage) <= 0)
	{
		if (!CUpdateClient::ActivateCoupon($coupon, $errorMessage, LANG, $stableVersionsOnly))
			$errorMessage .= GetMessage("SUPA_ACE_ACT").". ";
	}

	if (StrLen($errorMessage) <= 0)
	{
		CUpdateClient::AddMessage2Log("Coupon activated", "UPD_SUCCESS");
		echo "Y";
	}
	else
	{
		CUpdateClient::AddMessage2Log("Error: ".$errorMessage, "UPD_ERROR");
		echo $errorMessage;
	}
}
elseif ($queryType == "stability")
{
	$stability = $APPLICATION->UnJSEscape($_REQUEST["STABILITY"]);
	if (!in_array($stability, array("Y", "N")))
		$errorMessage .= GetMessage("SUPA_ASTE_FLAG").". ";

	if (StrLen($errorMessage) <= 0)
		COption::SetOptionString(US_BASE_MODULE, "stable_versions_only", (($stability == "Y") ? "N" : "Y"));

	if (StrLen($errorMessage) <= 0)
		echo "Y";
	else
		echo $errorMessage;
}
elseif ($queryType == "mail")
{
	$email = $APPLICATION->UnJSEscape($_REQUEST["EMAIL"]);
	if (StrLen($email) <= 0)
		$errorMessage .= GetMessage("SUPA_AME_EMAIL").". ";

	if (StrLen($errorMessage) <= 0)
	{
		if (!CUpdateClient::SubscribeMail($email, $errorMessage, LANG, $stableVersionsOnly))
			$errorMessage .= GetMessage("SUPA_AME_SUBSCR").". ";
	}

	if (StrLen($errorMessage) <= 0)
		echo "Y";
	else
		echo $errorMessage;
}
/************************************/

if (!defined("UPD_INTERNAL_CALL") || UPD_INTERNAL_CALL != "Y")
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_after.php");
}
?>
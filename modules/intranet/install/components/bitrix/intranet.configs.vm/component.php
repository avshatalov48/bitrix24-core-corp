<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (
	!(\Bitrix\Main\Loader::includeModule("intranet") && $GLOBALS['USER']->IsAdmin())
	|| \Bitrix\Main\Loader::includeModule("bitrix24")
	|| !\Bitrix\Main\Loader::includeModule("scale")
)
{
	$APPLICATION->AuthForm(GetMessage("CONFIG_ACCESS_DENIED"));
}

//scale
$arResult["IS_SCALE_AVAILABLE"] = false;
if (
	!IsModuleInstalled("bitrix24")
	&& \Bitrix\Scale\Helper::isScaleCanBeUsed()
)
{
	$arResult["IS_SCALE_AVAILABLE"] = true;
	$arResult["IS_BXENV_CORRECT_VERSION"] = true;

	$scaleSiteList = \Bitrix\Scale\SitesData::getList();
	$siteNameConf = "";
	$arResult["SCALE_SMTP_INFO"] = array();

	if (is_array($scaleSiteList))
	{
		foreach($scaleSiteList as $name => $site)
		{
			if($site['LID'] == SITE_ID)
			{
				$siteNameConf = $arResult["SITE_NAME_CONF"] = $name;
				$arResult["SCALE_SMTP_INFO"] = array(
					"SMTP_HOST" => $site["SMTPHost"],
					"SMTP_PORT" => $site["SMTPPort"],
					"SMTP_USER" => $site["SMTPUser"],
					"EMAIL" => $site["EmailAddress"],
					"SMTPTLS" => $site["SMTPTLS"] == "on" ? "Y" : "N",
					"SMTP_USE_AUTH" => $site["SMTP_USE_AUTH"],
					"SMTP_PASSWORD" => $site["SMTPPassword"] == "##USER_PARAMS:USER_PASSWORD##" ? "" : $site["SMTPPassword"]
				);

				$arResult["SCALE_CERTIFICATE_INFO"] = array(
					"CERTIFICATE_TYPE" => $site["HTTPSCertType"] == "general" || $site["HTTPSCertType"] == "own" ? "self" : "lets_encrypt",
					"PRIVATE_KEY_PATH" => $site["HTTPSPriv"],
					"CERTIFICATE_PATH" => $site["HTTPSCert"],
					"CERTIFICATE_PATH_CHAIN" => $site["HTTPSCertChain"],
					"EMAIL" => $site["EMAIL"],
					"DNS" => $site["DOMAINS"],
				);
			}
		}
	}
}


if ($_SERVER["REQUEST_METHOD"] == "POST"  && (isset($_POST["save_settings"]) )&& check_bitrix_sessid())
{
	if($arResult["IS_SCALE_AVAILABLE"])
	{
		if (!empty($_POST["smtp_host"]))
		{
			$smtpUserParams = array(
				"SMTP_HOST" => $_POST["smtp_host"],
				"SMTP_PORT" => $_POST["smtp_port"],
				"SMTP_USER" => $_POST["smtp_user"],
				"EMAIL" => $_POST["smtp_email"],
				"SMTPTLS" => isset($_POST["smtp_tls"]) ? "--smtptls" : "",
				"USE_AUTH" => isset($_POST["smtp_use_auth"]) ? "Y" : "N",
				"SITE_NAME_CONF" => $siteNameConf
			);

			if (isset($_POST["smtp_password"]))
			{
				if ($_POST["smtp_password"] != $_POST["smtp_repeat_password"])
				{
					$arResult["ERROR"] = GetMessage("CONFIG_SMTP_PASS_ERROR");
				}
				else
				{
					$smtpUserParams["USER_PASSWORD"] = $_POST["smtp_password"];
				}
			}

			$action = \Bitrix\Scale\ActionsData::getActionObject("SET_EMAIL_SETTINGS", "", $smtpUserParams);
			$result = $action->start();

			if (!$result)
			{
				$arResult["ERROR"] = GetMessage("CONFIG_SMTP_ERROR");
			}
		}
	}

	if (!$arResult["ERROR"])
	{
		$url = $APPLICATION->GetCurPageParam("success=Y");
		LocalRedirect($url);
	}
}

$this->IncludeComponentTemplate();
?>

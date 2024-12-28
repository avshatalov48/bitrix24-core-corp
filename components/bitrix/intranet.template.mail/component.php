<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("intranet"))
{
	ShowError(GetMessage("INTRANET_MODULE_NOT_INSTALL"));
	return;
}

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
if (!function_exists('getComponentMailFooterLink'))
{
	function getComponentMailFooterLink(): array
	{
		$region = Application::getInstance()->getLicense()->getRegion();
		if (in_array($region, ['ru', 'by', 'kz']))
		{
			$result['COLLAB'] = 'https://www.bitrix24.' . $region . '/features/company.php';
			$result['IM'] = 'https://www.bitrix24.' . $region . '/features/chat/';
			$result['TASKS'] = 'https://www.bitrix24.' . $region . '/features/tasks.php';
			$result['CRM'] = 'https://www.bitrix24.' . $region . '/features/crm/';
			$result['WF'] = 'https://www.bitrix24.' . $region . '/features/copilot';
			$result['LAST_PHRASE'] = 'INTRANET_INVITATION_COLLAB_LINK_COPILOT_NAME';
		}
		else
		{
			$result['COLLAB'] = 'https://www.bitrix24.com/tools/communications/';
			$result['IM'] = 'https://www.bitrix24.com/tools/communications/online-workspace.php';
			$result['TASKS'] = 'https://www.bitrix24.com/tools/tasks_and_projects/';
			$result['CRM'] = 'https://www.bitrix24.com/tools/crm/';
			$result['WF'] = 'https://www.bitrix24.com/tools/hr_automation/';
			$result['LAST_PHRASE'] = 'INTRANET_INVITATION_COLLAB_LINK_WF_NAME';
		}

		return $result;
	}
}
if (!function_exists('getMailCompanyLogo'))
{
	function getMailCompanyLogo(string $color = 'white'): string
	{
		$result = [
			'white' => '/images/logo-en.png',
			'black' => '/images/logo-dark-en.png',
		];

		if (LANGUAGE_ID === 'ru')
		{
			$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

			if ($region === 'by')
			{
				$result = [
					'white' => '/images/logo-by.png',
					'black' => '/images/logo-dark-by.png',
				];
			}
			else
			{
				$result = [
					'white' => '/images/logo-ru.png',
					'black' => '/images/logo-dark-ru.png',
				];
			}
		}

		return $result[$color];
	}
}
if (
	$arParams["TEMPLATE_TYPE"] == "USER_INVITATION"
	|| $arParams["TEMPLATE_TYPE"] == "EXTRANET_INVITATION"
	|| $arParams["TEMPLATE_TYPE"] == "USER_ADD"
	|| $arParams["TEMPLATE_TYPE"] == "COLLAB_INVITATION"
)
{
	$arParams["USER_TEXT"] = htmlspecialcharsback($arParams["USER_TEXT"]);

	if (isset($arParams["USER_ID_FROM"]))
	{
		$rsUsers = CUser::GetList("ID", "ASC", array("ID_EQUAL_EXACT" => $arParams["USER_ID_FROM"]), array("FIELDS" => array("NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO")));
		if ($arUser = $rsUsers->Fetch())
		{
			$arResult["USER_NAME"] = CUser::FormatName("#NAME# #LAST_NAME#", array(
				"NAME" => $arUser["NAME"],
				"LAST_NAME" => $arUser["LAST_NAME"],
				"SECOND_NAME" => $arUser["SECOND_NAME"],
				"LOGIN" => $arUser["LOGIN"]
			), true);

			if (intval($arUser["PERSONAL_PHOTO"]) > 0)
			{
				$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => 40, "height" => 40),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$arResult["USER_PHOTO"] = $arFileTmp["src"];
				}
			}
		}
	}
}

if ($arParams["TEMPLATE_TYPE"] == "EXTRANET_INVITATION")
{
	$arParams["LINK"] = "https://".$arParams["SERVER_NAME"]."/extranet/confirm/?checkword=".$arParams["CHECKWORD"]."&user_id=".$arParams["USER_ID"];
}

if ($arParams["TEMPLATE_TYPE"] == "COLLAB_INVITATION")
{
	$protocol = \Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? 'https' : 'http';
	$baseUrl = "$protocol://".$arParams["SERVER_NAME"];
	/** @var $this CBitrixComponent */
	$arResult["LOGO"] = $this->getPath().'/templates/.default'.getMailCompanyLogo();
	if ($arParams['FIELDS']['ACTIVE_USER'] ?? false)
	{
		$arParams["LINK"] = $baseUrl;
	}
	else
	{
		$arParams["LINK"] = $baseUrl."/extranet/confirm/?checkword=".$arParams["CHECKWORD"]."&user_id=".$arParams["USER_ID"].'&collab_name='.urlencode($arParams['FIELDS']['COLLAB_NAME']);
	}

	$arResult["FOOTER_LINK"] = getComponentMailFooterLink();
}

if ($arParams["TEMPLATE_TYPE"] == "IM_NEW_NOTIFY" || $arParams["TEMPLATE_TYPE"] == "IM_NEW_MESSAGE")
{
	if (isset($arParams["FROM_USER_ID"]))
	{
		$rsUsers = CUser::GetList("ID", "ASC", array("ID_EQUAL_EXACT" => $arParams["FROM_USER_ID"]), array("FIELDS" => array("PERSONAL_PHOTO")));
		if ($arUser = $rsUsers->Fetch())
		{
			if (intval($arUser["PERSONAL_PHOTO"]) > 0)
			{
				$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => 40, "height" => 40),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$arResult["USER_PHOTO"] = $arFileTmp["src"];
				}
			}
		}
	}

	$parser = new CTextParser();
	$parser->allow = array('ANCHOR' => 'N');
	$arParams["MESSAGE"] = $parser->convertText($arParams["MESSAGE"]);
}

if ($arParams["TEMPLATE_TYPE"] == "IM_NEW_MESSAGE_GROUP")
{
	$arResult["MESSAGES_FROM_USERS"] = array();
	$fromUserId = explode(",", $arParams["FROM_USER_ID"]);

	if (is_array($fromUserId) && !empty($fromUserId))
	{
		$rsUsers = CUser::GetList("ID", "ASC", array("ID" => implode("|", $fromUserId)), array("FIELDS" => array("ID", "PERSONAL_PHOTO")));
		while ($arUser = $rsUsers->Fetch())
		{
			if (intval($arUser["PERSONAL_PHOTO"]) > 0)
			{
				$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => 40, "height" => 40),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$arResult["FROM_USERS"][$arUser["ID"]] = $arFileTmp["src"];
				}
			}
		}
	}

	$messagesFromUser = unserialize($arParams["~MESSAGES_FROM_USERS"], ["allowed_classes" => false]);

	foreach ($messagesFromUser as $userId => $message)
	{
		$arResult["MESSAGES_FROM_USERS"][$userId] = [
			"MESSAGE" => $message,
			"USER_PHOTO" => $arResult["FROM_USERS"][$userId]
		];
	}
}

$this->arResult["LICENSE_PREFIX"] = "";
if (Loader::includeModule("bitrix24"))
{
	$this->arResult["LICENSE_PREFIX"] = \CBitrix24::getLicensePrefix();
	$this->arResult["HOST_NAME"] = defined('BX24_HOST_NAME') ? BX24_HOST_NAME : SITE_SERVER_NAME;
}

if($arParams["TEMPLATE_TYPE"] == "COLLAB_INVITATION")
{
	$this->IncludeComponentTemplate("collab");
}
elseif (
	$arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN"
	|| $arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN_REQUEST"
	|| $arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN_REQUEST_CONFIRM"
	|| $arParams["TEMPLATE_TYPE"] == "BITRIX24_USER_JOIN_REQUEST_REJECT"
)
{
	if (Loader::includeModule("ui"))
	{
		$arResult["HELPDESK_URL"] = \Bitrix\UI\Util::getHelpdeskUrl();
	}

	$this->IncludeComponentTemplate("bitrix24_user_join");
}
else
{
	$this->IncludeComponentTemplate();
}

?>
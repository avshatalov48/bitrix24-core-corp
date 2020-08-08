<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class CIntranetUserProfileSecurityComponent extends \CBitrixComponent
{
	public function onPrepareComponentParams($params)
	{
		return $params;
	}

	protected function listKeysSignedParameters()
	{
		return array(
			'USER_ID', 'PATH_TO_USER_CODES'
		);
	}

	protected function getMenuItems()
	{
		global $USER;

		$menuItems = array();
		$isOwnProfile = $this->arParams["USER_ID"] === $USER->GetID();
		$isAdminRights = (
			Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin(\Bitrix\Main\Engine\CurrentUser::get()->getId())
			|| \Bitrix\Main\Engine\CurrentUser::get()->isAdmin()
		)
			? true : false;

		if (Loader::includeModule('socialservices'))
		{
			$authManager = new \CSocServAuthManager();
			$activeSocServ = $authManager->GetActiveAuthServices(array());
			if (Loader::includeModule('bitrix24'))
			{
				$isNeedSocServTab = false;
				if (isset($activeSocServ['zoom']) &&
					\Bitrix\Main\Config\Option::get('socialservices', 'zoom_cloud_enabled', 'N') === 'Y'
				)
				{
					if (\CBitrix24::IsLicensePaid() || \CBitrix24::IsNfrLicense() || \CBitrix24::IsDemoLicense())
					{
						$isNeedSocServTab = true;
					}
				}
			}
			else
			{
				$isNeedSocServTab = true;
			}
		}

		if ($isAdminRights || $isOwnProfile)
		{
			$menuItems["auth"] = array(
				"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_AUTH_TITLE_2"),
				"ATTRIBUTES" => Array(
					"href" => "?page=auth",
					"data-role" => "auth",
				),
				"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "auth" ? true : false
			);
		}

		if ($isOwnProfile)
		{
			$menuItems["synchronize"] = array(
				"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_SYNCHRONIZE_TITLE"),
				"ATTRIBUTES" => Array(
					"data-role" => "synchronize",
				),
				"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "synchronize" ? true : false
			);

			$menuItems["app_passwords"] = array(
				"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_SECURITY_PASSWORDS_TITLE"),
				"ATTRIBUTES" => Array(
					"data-role" => "app_passwords",
				),
				"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "app_passwords" ? true : false
			);
		}

		if ($this->arResult["OTP"]["IS_ENABLED"] == "Y")
		{
			$menuItems["security"] = array(
				"NAME"       => Loc::getMessage("INTRANET_USER_PROFILE_SECURITY_OTP_TITLE"),
				"ATTRIBUTES" => Array(
					"data-role" => "security",
				),
				"ACTIVE"     => isset($_GET["page"]) && $_GET["page"] === "security" ? true : false
			);
		}

		if ($isOwnProfile)
		{
			$menuItems["socnet_email"] = array(
				"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_SOCNET_EMAIL_TITLE"),
				"ATTRIBUTES" => Array(
					"data-role" => "socnet_email",
				),
				"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "socnet_email" ? true : false
			);

			if ($isNeedSocServTab && ModuleManager::isModuleInstalled("socialservices"))
			{
				$socservPageUrl = CComponentEngine::MakePathFromTemplate($this->arParams["PATH_TO_USER_SOCIAL_SERVICES"], array("user_id" => $this->arParams["USER_ID"]));
				$menuItems["socserv"] = array(
					"NAME"       => Loc::getMessage("INTRANET_USER_PROFILE_SOCSERV_TITLE"),
					"ATTRIBUTES" => Array(
						"data-role" => "socserv",
						"data-url" => $socservPageUrl
					),
					"ACTIVE"     => isset($_GET["page"]) && $_GET["page"] === "socserv" ? true : false
				);
			}
		}

		return $menuItems;
	}

	public function executeComponent()
	{
		\CJSCore::Init("loader");

		//otp
		if (\Bitrix\Main\Loader::includeModule("security") && Bitrix\Security\Mfa\Otp::isOtpEnabled())
		{
			$this->arResult["OTP"]["IS_ENABLED"] = "Y";
			$this->arResult["OTP"]["IS_EXIST"] = \CSecurityUser::IsUserOtpExist($this->arParams["USER_ID"]);
		}
		else
		{
			$this->arResult["OTP"]["IS_ENABLED"] = "N";
		}

		$this->arResult["MENU_ITEMS"] = $this->getMenuItems();

		if (
			isset($_GET["page"])
			&& in_array($_GET["page"], array("auth", "synchronize", "app_passwords", "security", "socnet_email",
				"otp", "recovery_codes", "socserv")
			)
		)
		{
			$this->arResult["CURRENT_PAGE"] = $_GET["page"];
		}
		else
		{
			$this->arResult["CURRENT_PAGE"] = "auth";
		}

		$this->includeComponentTemplate();
	}
}
?>
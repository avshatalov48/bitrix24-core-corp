<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Bitrix24\Feature;
use Bitrix\Bitrix24\Sso;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class CIntranetUserProfileSecurityComponent extends \CBitrixComponent
{
	public function onPrepareComponentParams($params)
	{
		$params['USER_ID'] = (int)$params['USER_ID'];
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
		$isOwnProfile = $this->arParams["USER_ID"] === (int)$USER->GetID();
		$isAdminRights = (
			$this->arResult["IS_CLOUD"] && \CBitrix24::IsPortalAdmin(CurrentUser::get()->getId())
			|| CurrentUser::get()->isAdmin()
		)
			? true : false;

		if (Loader::includeModule('socialservices'))
		{
			$authManager = new \CSocServAuthManager();
			$activeSocServ = $authManager->GetActiveAuthServices(array());
			if ($this->arResult["IS_CLOUD"])
			{
				$isNeedSocServTab = false;
				if (isset($activeSocServ['zoom'])				)
				{
					if (\CBitrix24::IsLicensePaid() || \CBitrix24::IsNfrLicense() || \CBitrix24::IsDemoLicense())
					{
						$isNeedSocServTab = true;
					}
				}

				if (
					isset($activeSocServ['Dropbox'])
					|| isset($activeSocServ['GoogleOAuth'])
					|| isset($activeSocServ['Office365'])
					|| isset($activeSocServ['Box'])
					|| isset($activeSocServ['YandexOAuth'])
					|| isset($activeSocServ['LiveIDOAuth'])
				)
				{
					$isNeedSocServTab = true;
				}
			}
			elseif (!empty($activeSocServ))
			{
				$isNeedSocServTab = true;
			}
			else
			{
				$isNeedSocServTab = false;
			}
		}

		if ($isAdminRights || $isOwnProfile)
		{
			$menuItems["auth"] = array(
				"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_AUTH_TITLE_2"),
				"ATTRIBUTES" => Array(
					"data-action" => "auth",
				),
				"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "auth" ? true : false
			);
		}

		if ($isOwnProfile)
		{
			$menuItems["synchronize"] = array(
				"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_SYNCHRONIZE_TITLE"),
				"ATTRIBUTES" => Array(
					"data-action" => "synchronize",
				),
				"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "synchronize" ? true : false
			);

			$menuItems["appPasswords"] = array(
				"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_SECURITY_PASSWORDS_TITLE"),
				"ATTRIBUTES" => Array(
					"data-action" => "appPasswords",
				),
				"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "appPasswords" ? true : false
			);
		}

		if ($this->arResult["OTP"]["IS_ENABLED"] == "Y")
		{
			$menuItems["otpConnected"] = array(
				"NAME"       => Loc::getMessage("INTRANET_USER_PROFILE_SECURITY_OTP_TITLE"),
				"ATTRIBUTES" => Array(
					"data-action" => "otpConnected",
				),
				"ACTIVE"     => isset($_GET["page"]) && $_GET["page"] === "otpConnected" ? true : false
			);
		}

		if ($isOwnProfile)
		{
			$menuItems["socnet_email"] = array(
				"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_SOCNET_EMAIL_TITLE"),
				"ATTRIBUTES" => Array(
					"data-action" => "socnetEmail",
				),
				"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "socnet_email" ? true : false
			);

			if ($isNeedSocServTab && ModuleManager::isModuleInstalled("socialservices"))
			{
				$socservPageUrl = CComponentEngine::MakePathFromTemplate($this->arParams["PATH_TO_USER_SOCIAL_SERVICES"], array("user_id" => $this->arParams["USER_ID"]));
				$menuItems["socserv"] = array(
					"NAME"       => Loc::getMessage("INTRANET_USER_PROFILE_SOCSERV_TITLE"),
					"ATTRIBUTES" => Array(
						"data-action" => "socserv",
						"data-url" => $socservPageUrl
					),
					"ACTIVE"     => isset($_GET["page"]) && $_GET["page"] === "socserv" ? true : false
				);
			}

			if ($this->arResult["IS_CLOUD"])
			{
				$menuItems["mailingAgreement"] = array(
					"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_MAILING_AGREEMENT_TITLE"),
					"ATTRIBUTES" => Array(
						"data-action" => "mailingAgreement",
					),
					"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "mailingAgreement" ? true : false
				);

				if (class_exists(Sso\Configuration::class) && Sso\Configuration::isSsoAvailable())
				{
					$menuItems["sso"] = [
						"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_SSO_TITLE"),
						"LABEL" => Loc::getMessage('INTRANET_USER_PROFILE_SECURITY_MENU_ITEM_LABEL_NEW'),
						"ACTIVE" => isset($_GET["page"]) && $_GET["page"] === "sso",
					];

					$isSsoLocked = Sso\Configuration::isSsoLocked();
					if ($isSsoLocked)
					{
						$menuItems['sso']["ATTRIBUTES"] = array_filter([
							'data-action' => null,
							'onclick' => "BX.UI.InfoHelper.show('limit_office_sso');",
						]);
					}
					elseif ($isAdminRights)
					{
						$menuItems['sso']["ATTRIBUTES"] = array_filter([
							'data-action' => 'sso',
							'onclick' => null,
						]);
					}
					else
					{
						$errorMessage = \CUtil::JSEscape(Loc::getMessage('INTRANET_USER_PROFILE_SSO_ERROR_NO_RIGHTS'));
						$menuItems['sso']["ATTRIBUTES"] = array_filter([
							'data-action' => null,
							'onclick' => "BX.UI.Notification.Center.notify({
								content: '{$errorMessage}',
							});",
						]);
					}
				}
			}
		}

		$lockedShowLoginHistory = $this->arResult["IS_CLOUD"] && !Feature::isFeatureEnabled('user_login_history');

		if (($isAdminRights || $isOwnProfile) && !$lockedShowLoginHistory)
		{
			$menuItems["history"] = [
				"NAME" => Loc::getMessage("INTRANET_USER_PROFILE_LOGIN_HISTORY_TITLE"),
				"ATTRIBUTES" => [
					'onclick' => "openUserLoginHistory();",
				],
				"ACTIVE" => false,
			];
		}

		return $menuItems;
	}

	public function executeComponent()
	{
		\CJSCore::Init("loader");

		$this->arResult["IS_CLOUD"] = Loader::includeModule("bitrix24");

		//otp
		if (Loader::includeModule("security") && Bitrix\Security\Mfa\Otp::isOtpEnabled())
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
			&& in_array($_GET["page"], array("auth", "history", "synchronize", "appPasswords", "otpConnected", "socnetEmail",
				"otp", "recoveryCodes", "socserv", "mailingAgreement", "sso")
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

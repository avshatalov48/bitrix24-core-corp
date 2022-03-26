<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Error;
use Bitrix\Main\Config\Option;
use Bitrix\Bitrix24\Util;

Loc::loadMessages(__FILE__);

class CIntranetInviteDialogComponent extends \CBitrixComponent
{
	protected function prepareParams()
	{
		$this->arParams['USER_OPTIONS'] =
			isset($this->arParams['USER_OPTIONS']) && is_array($this->arParams['USER_OPTIONS'])
				? $this->arParams['USER_OPTIONS']
				: []
		;
	}

	private function prepareMenuItems(): void
	{
		$this->arResult["MENU_ITEMS"] = [];

		if ($this->arResult['canCurrentUserInvite'])
		{
			if ($this->arResult["IS_CLOUD"])
			{
				$this->arResult["MENU_ITEMS"]["self"] = [
					"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_SELF"),
					"ATTRIBUTES" => [
						"data-role" => "menu-self",
						"data-action" => "self"
					],
					"ACTIVE" => true
				];
			}

			$this->arResult["MENU_ITEMS"]["invite"] = [
				"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_INVITE_".($this->arResult["IS_SMS_INVITATION_AVAILABLE"] ? "EMAIL_AND_PHONE" : "EMAIL")),
				"ATTRIBUTES" => [
					"data-role" => "menu-invite",
					"data-action" => "invite"
				],
				"ACTIVE" => $this->arResult["IS_CLOUD"] ? false : true
			];

			$this->arResult["MENU_ITEMS"]["mass_invite"] = [
				"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_MASS_INVITE"),
				"ATTRIBUTES" => [
					"data-role" => "menu-mass-invite",
					"data-action" => "mass-invite"
				]
			];

			$this->arResult["MENU_ITEMS"]["invite_with_group_dp"] = [
				"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_INVITE_WITH_GROUP_DP"),
				"ATTRIBUTES" => [
					"data-role" => "menu-invite_with_group_dp",
					"data-action" => "invite-with-group-dp"
				]
			];

			$this->arResult["MENU_ITEMS"]["add"] = [
				"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_ADD"),
				"ATTRIBUTES" => [
					"data-role" => "menu-add",
					"data-action" => "add"
				]
			];
		}

		if (
			$this->arResult["IS_EXTRANET_INSTALLED"]
			&& (
				!isset($this->arParams['USER_OPTIONS']['intranetUsersOnly'])
				|| $this->arParams['USER_OPTIONS']['intranetUsersOnly'] !== true
			)
		)
		{
			$this->arResult["MENU_ITEMS"]["extranet"] = [
				"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_EXTRANET"),
				"ATTRIBUTES" => [
					"data-role" => "menu-extranet",
					"data-action" => "extranet"
				]
			];
		}

		if ($this->arResult['canCurrentUserInvite'])
		{
			if ($this->arResult["IS_CLOUD"])
			{
				$this->arResult["MENU_ITEMS"]["integrator"] = [
					"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_INTEGRATOR"),
					"ATTRIBUTES" => [
						"data-role" => "menu-integrator",
						"data-action" => "integrator"
					]
				];
			}

			if ($this->arResult["IS_CLOUD"] && in_array($this->arResult["LICENSE_ZONE"], ['ru']))
			{
				$this->arResult["MENU_ITEMS"]["active_directory"] = [
					"NAME_HTML" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_ACTIVE_DIRECTORY"),
					"ATTRIBUTES" => [
						"data-role" => "menu-active-directory",
						"data-action" => "active-directory"
					]
				];
			}
		}
	}

	private function prepareLinkRegisterData(): void
	{
		$registerSettings = array();
		if(Loader::includeModule("socialservices"))
		{
			$registerSettings = \Bitrix\Socialservices\Network::getRegisterSettings();
		}

		$this->arResult["REGISTER_SETTINGS"] = $registerSettings;

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$this->arResult["REGISTER_URL_BASE"] = ($request->isHttps() ? "https://" : "http://").
			(defined('BX24_HOST_NAME') ? BX24_HOST_NAME : SITE_SERVER_NAME)."/?secret=";

		if(strlen($this->arResult["REGISTER_SETTINGS"]["REGISTER_SECRET"]) > 0)
		{
			$this->arResult["REGISTER_URL"] = $this->arResult["REGISTER_URL_BASE"].urlencode($this->arResult["REGISTER_SETTINGS"]["REGISTER_SECRET"]);
		}
		else
		{
			$this->arResult["REGISTER_URL"] = $this->arResult["REGISTER_URL_BASE"]."yes";
		}
	}

	private function prepareUserData(): void
	{
		if (!Loader::includeModule("bitrix24"))
		{
			return;
		}

		if (\CBitrix24BusinessTools::isAvailable())
		{
			$this->arResult["USER_MAX_COUNT"] = intval(COption::GetOptionString("main", "PARAM_MAX_USERS"));
		}
		else
		{
			$this->arResult["USER_MAX_COUNT"] = \CBitrix24::getMaxBitrix24UsersCount();
		}

		$this->arResult["USER_CURRENT_COUNT"] = \Bitrix\Bitrix24\Util::getCurrentUserCount();
	}

	public function executeComponent()
	{
		$this->arResult["IS_CLOUD"] = Loader::includeModule("bitrix24");
		if ($this->arResult["IS_CLOUD"])
		{
			$this->arResult["LICENSE_ZONE"] = \CBitrix24::getLicensePrefix();
		}

		$this->arResult["IS_EXTRANET_INSTALLED"] = Loader::includeModule("extranet");
		$this->arResult["EXTRANET_SITE_ID"] = Option::get("extranet", "extranet_site", "");
		if (empty($this->arResult["EXTRANET_SITE_ID"]))
		{
			$this->arResult["IS_EXTRANET_INSTALLED"] = false;
		}

		if (
			(
				!\Bitrix\Intranet\Invitation::canCurrentUserInvite()
				&& !$this->arResult['IS_EXTRANET_INSTALLED']
			)
			|| !Loader::includeModule('iblock')
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return;
		}

		CJSCore::Init(array('clipboard'));

		$this->arResult["IS_CURRENT_USER_ADMIN"] = (
			$this->arResult["IS_CLOUD"] && \CBitrix24::IsPortalAdmin(\Bitrix\Main\Engine\CurrentUser::get()->getId())
			|| \Bitrix\Main\Engine\CurrentUser::get()->isAdmin()
		)
			? true : false;

		$this->arResult["IS_SMS_INVITATION_AVAILABLE"] = $this->arResult["IS_CLOUD"]
			&& Option::get('bitrix24', 'phone_invite_allowed', 'N') === 'Y';

		$this->arResult['canCurrentUserInvite'] = \Bitrix\Intranet\Invitation::canCurrentUserInvite();

		$this->prepareMenuItems();
		$this->arResult["IS_CREATOR_EMAIL_CONFIRMED"] = true;
		if ($this->arResult["IS_CLOUD"])
		{
			$this->prepareLinkRegisterData();
			$this->prepareUserData();
			$this->arResult["IS_CREATOR_EMAIL_CONFIRMED"] = \CBitrix24::isEmailConfirmed();
		}

		if ($this->arResult['canCurrentUserInvite'])
		{
			$this->arResult['FIRST_INVITATION_BLOCK'] = $this->arResult["IS_CLOUD"] ? 'self' : 'invite';
		}
		else
		{
			$this->arResult['FIRST_INVITATION_BLOCK'] = 'extranet';
		}

		if (
			isset($_GET['firstInvitationBlock'])
			&& !empty($_GET['firstInvitationBlock'])
			&& in_array($_GET['firstInvitationBlock'], [
				'self',
				'invite',
				'mass-invite',
				'invite-with-group-dp',
				'add',
				'extranet',
				'integrator',
				'active-directory',
			])
		)
		{
			$this->arResult['FIRST_INVITATION_BLOCK'] = $_GET['firstInvitationBlock'];
		}

		$this->includeComponentTemplate();
	}
}

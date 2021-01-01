<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Error;
use Bitrix\Main\Config\Option;

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

	private function prepareMenuItems()
	{
		$this->arResult["MENU_ITEMS"] = [];

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

		if ($this->arResult["IS_EXTRANET_INSTALLED"])
		{
			$this->arResult["MENU_ITEMS"]["extranet"] = [
				"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_EXTRANET"),
				"ATTRIBUTES" => [
					"data-role" => "menu-extranet",
					"data-action" => "extranet"
				]
			];
		}

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

		/*$this->arResult["MENU_ITEMS"]["active_directory"] = [
			"NAME_HTML" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_ACTIVE_DIRECTORY").
				"<span class='invite-menu-sub'>".Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_SOON")."</span>",
			"ATTRIBUTES" => [
				"data-role" => "menu-active-directory",
				"data-action" => "active-directory"
			]
		];*/
	}

	private function prepareLinkRegisterData()
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

	public function executeComponent()
	{
		global $USER;

		$this->arResult["IS_CLOUD"] = Loader::includeModule("bitrix24");

		$this->arResult["IS_EXTRANET_INSTALLED"] = Loader::includeModule("extranet");
		$this->arResult["EXTRANET_SITE_ID"] = Option::get("extranet", "extranet_site", "");
		if (empty($this->arResult["EXTRANET_SITE_ID"]))
		{
			$this->arResult["IS_EXTRANET_INSTALLED"] = false;
		}

		if (
			(
				$this->arResult["IS_CLOUD"]
				&& !CBitrix24::isInvitingUsersAllowed()
			)
			|| (
				!$this->arResult["IS_CLOUD"]
				&& !$USER->CanDoOperation('edit_all_users')
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

		$this->prepareMenuItems();
		$this->arResult["IS_CREATOR_EMAIL_CONFIRMED"] = true;
		if ($this->arResult["IS_CLOUD"])
		{
			$this->prepareLinkRegisterData();
			$this->arResult["IS_CREATOR_EMAIL_CONFIRMED"] = \CBitrix24::isEmailConfirmed();
		}

		$this->includeComponentTemplate();
	}
}
?>
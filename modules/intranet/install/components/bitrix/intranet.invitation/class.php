<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Bitrix24\Service\PortalNotifications;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Bitrix24\Util;
use Bitrix\Intranet;
use Bitrix\Socialnetwork\Collab\CollabFeature;
use Bitrix\Socialnetwork\Internals\Registry\GroupRegistry;

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

		$isOnlyIntranetUsersInvite = (
			!isset($this->arParams['USER_OPTIONS']['intranetUsersOnly'])
			|| $this->arParams['USER_OPTIONS']['intranetUsersOnly'] !== true
		);
		$isExtranetInvitationAvailable = (
			!$this->arResult['IS_COLLAB_ENABLED']
			|| (
				isset($this->arParams['USER_OPTIONS']['groupId'])
				&& $this->isExtranetGroupById($this->arParams['USER_OPTIONS']['groupId'])
			)
		);

		if (
			$this->arResult["IS_EXTRANET_INSTALLED"]
			&& $isOnlyIntranetUsersInvite
			&& $isExtranetInvitationAvailable
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

	private function isExtranetGroupById($groupId): bool
	{
		$group = GroupRegistry::getInstance()->get($groupId);

		return (
			isset($group)
			&& $group->getSiteId() === $this->arResult['EXTRANET_SITE_ID']
			&& !$group->isCollab()
		);
	}

	private function prepareLinkRegisterData(): void
	{
		$this->arResult["REGISTER_SETTINGS"] = Intranet\Invitation::getRegisterSettings();
		$registerUri = Intranet\Invitation::getRegisterUri();

		$this->arResult["REGISTER_URL"] = $registerUri?->getUri();
		$this->arResult["REGISTER_URL_BASE"] = $registerUri?->addParams(['secret' => ''])->getUri();
	}

	private function prepareUserData(): void
	{
		if (!Loader::includeModule("bitrix24"))
		{
			return;
		}

		if (\CBitrix24BusinessTools::isAvailable())
		{
			$this->arResult["USER_MAX_COUNT"] = Application::getInstance()->getLicense()->getMaxUsers();
		}
		else
		{
			$this->arResult["USER_MAX_COUNT"] = CBitrix24::getMaxBitrix24UsersCount();
		}

		$this->arResult["USER_CURRENT_COUNT"] = \Bitrix\Bitrix24\License\User::getInstance()->getCount();
	}

	public function executeComponent()
	{
		$this->arResult["IS_CLOUD"] = Loader::includeModule("bitrix24");
		if ($this->arResult["IS_CLOUD"])
		{
			$this->arResult["LICENSE_ZONE"] = CBitrix24::getLicensePrefix();
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
		$this->arResult['IS_COLLAB_ENABLED'] = CollabFeature::isOn();

		$this->prepareMenuItems();
		$this->arResult["IS_CREATOR_EMAIL_CONFIRMED"] = true;
		if ($this->arResult["IS_CLOUD"])
		{
			$this->prepareLinkRegisterData();
			$this->prepareUserData();
			$this->arResult["IS_CREATOR_EMAIL_CONFIRMED"] = !\Bitrix\Bitrix24\Service\PortalSettings::getInstance()
				->getEmailConfirmationRequirements()
				->isRequiredByType(\Bitrix\Bitrix24\Portal\Settings\EmailConfirmationRequirements\Type::INVITE_USERS);
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

		if(isset($_GET['departments']) && is_array($_GET['departments']))
		{
			$this->arParams['USER_OPTIONS']['departmentsId'] = array_map(
				fn($departmentId) => (int)$departmentId,
				$_GET['departments']
			);
			$this->arParams['USER_OPTIONS']['departmentsId'] = array_filter(
				$this->arParams['USER_OPTIONS']['departmentsId'],
				fn($departmentId) => $departmentId > 0
			);
		}

		$this->includeComponentTemplate();
	}
}

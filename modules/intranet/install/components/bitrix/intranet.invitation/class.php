<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
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

			$this->arResult["MENU_ITEMS"]["self"] = [
					"NAME" => $this->arResult['USE_INVITE_LOCAL_EMAIL_PROGRAM']
						? Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_SELF_MSGVER_1")
						: Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_SELF"),
				"ATTRIBUTES" => [
					"data-role" => "menu-self",
					"data-action" => "self"
				],
				"ACTIVE" => true
			];

			if ($this->arResult['USE_INVITE_LOCAL_EMAIL_PROGRAM'])
			{
				$this->arResult['MENU_ITEMS']['invite-email'] = [
					'NAME' => Loc::getMessage('INTRANET_INVITE_DIALOG_MENU_INVITE_EMAIL_MSGVER_1'),
					'ATTRIBUTES' => [
						'data-role' => 'menu-invite-email',
						'data-action' => 'invite-email'
					],
					'ACTIVE' => $this->arResult['IS_CLOUD'] ? false : true
				];
			}

			$this->arResult['MENU_ITEMS']['invite'] = [
				'NAME' => Loc::getMessage('INTRANET_INVITE_DIALOG_MENU_INVITE_'.($this->arResult['IS_SMS_INVITATION_AVAILABLE'] ? ($this->arResult['USE_INVITE_LOCAL_EMAIL_PROGRAM'] ? 'PHONE' : 'EMAIL_AND_PHONE') : "EMAIL")),
				'ATTRIBUTES' => [
					'data-role' => "menu-invite",
					'data-action' => "invite"
				],
				'ACTIVE' => $this->arResult['IS_CLOUD'] ? false : true
			];

			$this->arResult["SUB_MENU_ITEMS"]["invite_with_group_dp"] = [
				"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_INVITE_WITH_GROUP"),
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
				$this->arResult["SUB_MENU_ITEMS"]["integrator"] = [
					"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_INTEGRATOR"),
					"ATTRIBUTES" => [
						"data-role" => "menu-integrator",
						"data-action" => "integrator"
					]
				];
			}

			if ($this->arResult["IS_CLOUD"] && \Bitrix\Main\Engine\CurrentUser::get()->isAdmin())
			{
				$userId = \Bitrix\Main\Engine\CurrentUser::get()?->getId() ?? null;

				$sliderCode = "BX.SidePanel.Instance.open('/company/personal/user/{$userId}/common_security/?page=sso',{ width: 1100 });";
				$infoHelperCode = "top.BX.UI.InfoHelper.show('limit_office_sso');";
				$isAllowSso = !\Bitrix\Bitrix24\Sso\Configuration::isSsoLocked();

				$this->arResult["SUB_MENU_ITEMS"]["sso_scim"] = [
					"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_SSO_SCIM"),
					"NAME_HTML" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_SSO_SCIM"),
					'SHOW_LOCKED' => !$isAllowSso,
					"ATTRIBUTES" => [
						'onclick' => $isAllowSso ? $sliderCode : $infoHelperCode,
					]
				];
			}

			if ($this->arResult["IS_CLOUD"] && in_array($this->arResult["LICENSE_ZONE"], ['ru']))
			{
				$this->arResult["SUB_MENU_ITEMS"]["active_directory"] = [
					"NAME" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_ACTIVE_DIRECTORY"),
					"NAME_HTML" => Loc::getMessage("INTRANET_INVITE_DIALOG_MENU_ACTIVE_DIRECTORY"),
					"ATTRIBUTES" => [
						"data-role" => "menu-active-directory",
						"data-action" => "active-directory",
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
			&& !$group->isCollab()
			&& in_array($this->arResult['EXTRANET_SITE_ID'], $group->getSiteIds())
		);
	}

	private function prepareLinkRegisterData(): void
	{
		$this->arResult["REGISTER_SETTINGS"] = Intranet\Invitation::getRegisterSettings();
		$registerUri = Intranet\Invitation::getRegisterUri();

		$departmentIds = array_map(
			fn($department) => $department['id'],
			$this->arResult['DEPARTMENTS']['DEPARTMENT_LIST'] ?? []
		);

		$linkGenerator = Intranet\Service\InviteLinkGenerator::createByDepartmentsIds($departmentIds);

		$this->arResult["REGISTER_URL"] = $linkGenerator?->getShortLink();
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
			\CUserOptions::SetOption('intranet.invitation', 'open_invitation_form_ts', time());
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

		$this->arResult['USE_INVITE_LOCAL_EMAIL_PROGRAM'] = $this->arResult["IS_CLOUD"]
			&& Option::get('intranet', 'useInviteLocalEmailProgram', 'N') === 'Y';

		$this->prepareMenuItems();
		$this->arResult["IS_CREATOR_EMAIL_CONFIRMED"] = true;

		$departmentsData = $this->prepareDepartments();

		$this->arParams['USER_OPTIONS']['departmentList'] = $departmentsData['DEPARTMENT_LIST'] ?? [];
		$this->arParams['USER_OPTIONS']['rootDepartment'] = $departmentsData['ROOT_DEPARTMENT'] ?? null;

		$this->prepareLinkRegisterData();

		if ($this->arResult["IS_CLOUD"])
		{
			$this->prepareUserData();
			$this->arResult["IS_CREATOR_EMAIL_CONFIRMED"] = !\Bitrix\Bitrix24\Service\PortalSettings::getInstance()
				->getEmailConfirmationRequirements()
				->isRequiredByType(\Bitrix\Bitrix24\Portal\Settings\EmailConfirmationRequirements\Type::INVITE_USERS);
		}

		if ($this->arResult['canCurrentUserInvite'])
		{
			$this->arResult['FIRST_INVITATION_BLOCK'] = 'self';
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

	/**
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function prepareDepartments(): array
	{
		$departmentRepository = new Intranet\Repository\HrDepartmentRepository();
		$departmentIds = \Bitrix\Main\Context::getCurrent()->getRequest()->get('departments');
		$result = [];

		$rootDepartment = Intranet\Integration\HumanResources\PermissionInvitation::createByCurrentUser()
			->findFirstPossibleAvailableDepartment();

		if (empty($departmentIds) || !is_array($departmentIds))
		{
			$departmentsCollection = $rootDepartment
				? new Intranet\Entity\Collection\DepartmentCollection($rootDepartment)
				: new Intranet\Entity\Collection\DepartmentCollection();
			$result['STATE'] = 'NOT_SELECTED';
		}
		else
		{
			$departmentsCollection = $departmentRepository->findAllByIds($departmentIds)->filter(function ($department) {
				return Intranet\Integration\HumanResources\PermissionInvitation::createByCurrentUser()
					->canInviteToDepartment($department)
					;
			});
			if ($departmentsCollection->empty() && $rootDepartment)
			{
				$departmentsCollection = new Intranet\Entity\Collection\DepartmentCollection($rootDepartment);
			}
			$result['STATE'] = 'SELECTED';
		}

		foreach ($departmentsCollection as $department)
		{
			$result['DEPARTMENT_LIST'][] = $this->prepareDepartmentStructure($department);
		}

		$result['ROOT_DEPARTMENT'] = $rootDepartment
			? $this->prepareDepartmentStructure($rootDepartment)
			: null
		;

		return $result;
	}

	private function prepareDepartmentStructure(Intranet\Entity\Department $department): array
	{
		return [
			'id' => $department->getId(),
			'name' => $department->getName(),
			'accessCode' => $department->getAccessCode(),
		];
	}
}
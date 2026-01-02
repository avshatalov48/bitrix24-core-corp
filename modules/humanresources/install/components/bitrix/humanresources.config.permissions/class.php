<?php

use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\HumanResources\Access\LeftMenuSection;
use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Internals\HumanResourcesBaseComponent;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Internals\Service\Container as InternalsContainer;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Bitrix\Main\Loader::includeModule('humanresources'))
{
	return;
}

class HumanResourcesConfigPermissionsComponent extends HumanResourcesBaseComponent implements Controllerable
{
	public function configureActions(): array
	{
		return [];
	}

		public function exec(): void
	{
		$categoryFromRequest = $this->request->get('category');
		$category = is_string($categoryFromRequest)
			? (RoleCategory::tryFrom($categoryFromRequest) ?? RoleCategory::Department)
			: RoleCategory::Department
		;

		$can = StructureAccessController::can(
			CurrentUser::get()->getId(),
			$category === RoleCategory::Department
				? StructureActionDictionary::ACTION_USERS_ACCESS_EDIT
				: StructureActionDictionary::ACTION_TEAM_ACCESS_EDIT,
		);

		if (!$can)
		{
			$this->setTemplatePage('access_denied');

			return;
		}

		$this->setResults();
	}

	private function setResults(): void
	{
		$title = Loc::getMessage('HUMAN_RESOURCES_CONFIG_PERMISSIONS_TITLE') ?? '';
		$this->setTemplateTitle($title);

		$categoryFromRequest = $this->request->get('category');
		$category = is_string($categoryFromRequest)
			? RoleCategory::tryFrom($categoryFromRequest) ?? RoleCategory::Department
			: RoleCategory::Department;

		$rolePermission = Container::getAccessRolePermissionService();
		$rolePermission->setCategory($category);

		$this->arResult['ACCESS_RIGHTS'] = $rolePermission->getAccessRights();
		$this->arResult['USER_GROUPS'] = $rolePermission->getUserGroups();
		$this->arResult['CATEGORY'] = $category->value;
		$this->arResult['CONFIG_PERMISSION_MENU_TITLE'] = htmlspecialcharsbx($title);
		$this->prepareLeftMenu($category);

		if (!\Bitrix\HumanResources\Config\Storage::canUsePermissionConfig())
		{
			$this->arResult['CANT_USE'] = true;
		}

		$this->arResult['ANALYTIC_CONTEXT'] = [
			'tool' => 'structure',
			'category' => 'roles',
		];
	}

	private function prepareLeftMenu(RoleCategory $activeCategory): void
	{
		/** @var LeftMenuSection\MenuSectionBaseClass[] $sections */
		$sections = [
			new LeftMenuSection\DepartmentSection(),
			new LeftMenuSection\TeamSection(),
		];

		$menuItems = [];
		foreach ($sections as $section)
		{
			if ($section->getCategory() === $activeCategory)
			{
				$this->arResult['MENU_ID'] = $section->getMenuId();
			}

			if (!$section->hasPermissionToEditRights())
			{
				continue;
			}

			$categoryParameters = $section->getCategoryParameters();
			$encodedParameters = htmlspecialcharsbx(Json::encode($categoryParameters));
			$analyticType = $section->getCategory() === RoleCategory::Team ? 'team' : 'dept';
			$menuItems[] = [
				'NAME' => $section->getTitle(),
				'ATTRIBUTES' => [
					'onclick' => "
						BX.UI.Analytics.sendData({
							tool: 'structure', 
							category: 'roles', 
							event: 'open_tab', 
							type: '{$analyticType}'
						});
						ConfigPerms.openPermission({$encodedParameters});
					",
					'data-menu-id' => $section->getMenuId(),
					'title' => htmlspecialcharsbx($section->getTitle()),
				],
				'CAN_BE_ACTIVE' => true,
				'ACTIVE' => $section->getCategory() === $activeCategory,
				'CHILDREN' => [],
			];
		}


		$this->arResult['LEFT_MENU'] = $menuItems;
	}

	public function getDataAction(?array $parameters = []): array
	{
		$category = RoleCategory::tryFrom($parameters['category'] ?? '');

		if (!$category)
		{
			return [];
		}

		$accessService = InternalsContainer::getAccessService();
		if (!$accessService->checkAccessToEditPermissions(category: $category, checkTariffRestriction: false))
		{
			return [];
		}

		$permissionService = Container::getAccessRolePermissionService();
		$permissionService->setCategory($category);

		return [
			'userGroups' => $permissionService->getUserGroups(),
			'accessRights' => $permissionService->getAccessRights(),
		];
	}
}
<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Security\Role\Manage\Manager\AllSelection;
use Bitrix\Crm\Integration\Analytics;
use Bitrix\Crm\Security\Role\Manage\RoleManagerSelectionFactory;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\AccessRightsDTO;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Queries\QueryRoles;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die;
}

if (!CModule::IncludeModule('crm'))
{
	ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

class CrmConfigPermsV2 extends Base implements Controllerable
{
	private ?string $criterion;
	private ?string $sectionCode = null;
	private bool $isAutomation = false;
	private ?RoleSelectionManager $manager = null;

	public function getDataAction(array $controllerData): ?array
	{
		$this->criterion = $controllerData['criterion'] ?? null;
		$this->sectionCode = $controllerData['sectionCode'] ?? null;
		$this->isAutomation = $controllerData['isAutomation'] === 'true';
		$this->manager = (new RoleManagerSelectionFactory())
			->setCustomSectionCode($this->sectionCode)
			->setAutomation($this->isAutomation)
			->create($this->criterion)
		;

		if ($this->manager === null)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		if (!$this->manager->hasPermissionsToEditRights())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$accessRightsDto = (new QueryRoles($this->manager))->execute();

		return [
			'accessRightsData' => $accessRightsDto,
			'maxVisibleUserGroups' => $this->getMaxVisibleUserGroups($accessRightsDto),
			'additionalSaveParams' => $this->getControllerData(),
		];
	}

	public function init(): void
	{
		parent::init();

		$this->criterion = $this->arParams['criterion'] ?? null;
		$this->sectionCode = $this->arParams['sectionCode'] ?? null;
		$this->isAutomation = $this->arParams['isAutomation'] ?? false;

		$this->manager = (new RoleManagerSelectionFactory())
			->setCustomSectionCode($this->sectionCode)
			->setAutomation($this->isAutomation)
			->create($this->criterion)
		;

		if ($this->manager === null)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return;
		}

		if (!$this->manager->hasPermissionsToEditRights())
		{
			$this->addError(ErrorCode::getAccessDeniedError());
		}
	}

	public function executeComponent(): void
	{
		$this->init();
		if ($this->getErrors())
		{
			$this->showFirstErrorViaInfoErrorUI();

			return;
		}

		if (!$this->manager->isAvailableTool())
		{
			$this->manager->printInaccessibilityContent();

			return;
		}

		$accessRightsDto = (new QueryRoles($this->manager))->execute();

		$this->arResult['accessRightsData'] = $accessRightsDto;
		$this->arResult['maxVisibleUserGroups'] = $this->getMaxVisibleUserGroups($accessRightsDto);
		$this->arResult['controllerData'] = $this->getControllerData();
		$this->arResult['isSharedCrmPermissionsSlider'] = $this->criterion === AllSelection::CRITERION;
		$this->arResult['analytics'] = $this->getAnalytics();

		$this->arResult['menuId'] = $this->manager->getMenuId();
		if ($this->arResult['menuId'])
		{
			$this->arResult['shouldDisplayLeftMenu'] = true;
			$this->prepareLeftMenu();
		}

		$this->IncludeComponentTemplate();
	}

	public function configureActions(): array
	{
		return [];
	}

	/**
	 * Limit max visible roles based on total cells estimate. Since CRM perms can have A LOT of content, browser
	 * can die if it renders everything at once.
	 */
	private function getMaxVisibleUserGroups(AccessRightsDTO $rolesData): ?int
	{
		$limitFromOptions = Option::get('crm', 'perms_v2_config_max_roles');
		if (is_numeric($limitFromOptions) && (int)$limitFromOptions > 0)
		{
			return (int)$limitFromOptions;
		}

		static $limitsTable = [
			1000 => 20, // 30 roles and 30 entities
			5000 => 15, // 70 roles and 70 entities
			10000 => 5, // 100 roles and 100 entities
			25000 => 1, // 160 roles and 160 entities
		];

		$estimatedNumberOfSectionColumns = count($rolesData->userGroups) * count($rolesData->accessRights);

		foreach (array_reverse($limitsTable, true) as $lowerThreshold => $limit)
		{
			if ($estimatedNumberOfSectionColumns >= $lowerThreshold)
			{
				return $limit;
			}
		}

		return null;
	}

	private function getControllerData(): array
	{
		return [
			'criterion' => $this->criterion,
			'sectionCode' => $this->sectionCode,
			'isAutomation' => $this->isAutomation,
		];
	}

	private function getAnalytics(): ?array
	{
		$builder = Analytics\Builder\Security\ViewEvent::createFromRequest($this->request);
		if (!$builder->validate()->isSuccess())
		{
			return null;
		}

		$data = $builder->buildData();
		unset($data['event']);

		return $data;
	}

	private function prepareLeftMenu()
	{
		$menuItems = [];
		$sections = [
			new \Bitrix\Crm\Security\Role\Manage\Manager\AllSelection(),
			new \Bitrix\Crm\Security\Role\Manage\Manager\WebFormSelection(),
			new \Bitrix\Crm\Security\Role\Manage\Manager\ButtonSelection(),
		];

		$automatedSolutionManager = new \Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager();
		foreach ($automatedSolutionManager->getExistingIntranetCustomSections() as $customSection)
		{
			$customSectionSelection = new \Bitrix\Crm\Security\Role\Manage\Manager\CustomSectionSelection($customSection);
			$sections[] = $customSectionSelection;
		}

		foreach ($sections as $section)
		{
			$this->appendMenuForSection($section, $menuItems);
		}

		$this->appendCustomSectionListMenu($menuItems);

		$this->arResult['leftMenu'] = $menuItems;
	}

	private function appendMenuForSection(\Bitrix\Crm\Security\Role\Manage\RoleSelectionManager $section, array &$menuItems)
	{
		if (!$section->hasPermissionsToEditRights())
		{
			return;
		}

		$controllerData = $section->getControllerData();
		$encodedControllerData = htmlspecialcharsbx(Json::encode($controllerData));
		$sectionMenu = [
			'NAME' => $section->getTitle(),
			'ATTRIBUTES' => [
				'onclick' => "ConfigPerms.openPermission({$encodedControllerData});",
				'data-menu-id' => $controllerData['menuId'],
				'title' => htmlspecialcharsbx($section->getTitle()),
			],
			'CAN_BE_ACTIVE' => true,
			'ACTIVE' => $this->manager->getMenuId() === $controllerData['menuId'],
			'SUBMENU_OPEN' => $this->manager->getMenuId() === $controllerData['menuId'],
			'CHILDREN' => [],
		];

		foreach ($section->buildModels() as $model)
		{
			$sectionMenu['CHILDREN'][] = [
				'NAME' => $model->name() . ' ' . $model->description(),
				'ATTRIBUTES' => [
					'onclick' => "ConfigPerms.AccessRights.scrollToSection('{$model->code()}');",
					'title' => htmlspecialcharsbx($model->name() . ' ' . $model->description()),
				],
				'CAN_BE_ACTIVE' => false,
			];
		}

		$menuItems[] = $sectionMenu;
	}

	private function appendCustomSectionListMenu(array &$menuItems): void
	{
		$customSectionListSelection = new \Bitrix\Crm\Security\Role\Manage\Manager\CustomSectionListSelection();
		if (!$customSectionListSelection->hasPermissionsToEditRights())
		{
			return;
		}

		$model = $customSectionListSelection->buildModels()[0];
		$controllerData = $customSectionListSelection->getControllerData();
		$encodedControllerData = htmlspecialcharsbx(Json::encode($controllerData));
		$buttonMenu = [
			'NAME' => $model->name(),
			'ATTRIBUTES' => [
				'onclick' => "ConfigPerms.openPermission({$encodedControllerData});",
				'data-menu-id' => $controllerData['menuId']
			],
			'CAN_BE_ACTIVE' => true,
			'ACTIVE' => $this->manager->getMenuId() === $controllerData['menuId'],
		];

		$menuItems[] = $buttonMenu;
	}
}

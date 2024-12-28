<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Security\Role\Manage\RoleManagerSelectionFactory;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\AccessRightsDTO;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Queries\QueryRoles;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;

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
}

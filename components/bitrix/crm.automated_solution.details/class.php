<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Feature\PermissionsLayoutV2;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

if (!Loader::includeModule('crm'))
{
	return;
}

class CrmAutomatedSolutionDetailsComponent extends Base
{
	private const TAB_IDS = [
		'common',
		'types',
	];

	private ?array $automatedSolution = null;

	public function onPrepareComponentParams($arParams)
	{
		$this->fillParameterFromRequest('id', $arParams);
		$this->fillParameterFromRequest('activeTabId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();

		if ($this->getErrors())
		{
			return;
		}

		if (!$this->userPermissions->canEditAutomatedSolutions())
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return;
		}

		$id = (int)($this->arParams['id'] ?? null);

		if ($id > 0)
		{
			$manager = Container::getInstance()->getAutomatedSolutionManager();

			$this->automatedSolution = $manager->getAutomatedSolution($id);
			if ($this->automatedSolution === null)
			{
				$this->addError(\Bitrix\Crm\Controller\ErrorCode::getNotFoundError());
			}
		}
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->arResult['isNew'] = $this->automatedSolution === null;
		$this->arResult['state'] = $this->prepareVuexState();

		$this->arResult['activeTabId'] = $this->arParams['activeTabId'] ?? null;
		if (!in_array($this->arResult['activeTabId'], self::TAB_IDS, true))
		{
			$this->arResult['activeTabId'] = current(self::TAB_IDS);
		}

		$this->includeComponentTemplate();
	}

	private function prepareVuexState(): array
	{
		$permissions = [
			'canMoveSmartProcessFromCrm' => $this->userPermissions->isCrmAdmin(),
			'canMoveSmartProcessFromAnotherAutomatedSolution' => $this->userPermissions->canEditAutomatedSolutions(),
		];

		if (!$this->automatedSolution)
		{
			return [
				'automatedSolution' => [],
				'dynamicTypesTitles' => [],
				'permissions' => $permissions,
				'isPermissionsLayoutV2Enabled' => Feature::enabled(PermissionsLayoutV2::class),
			];
		}

		return [
			'automatedSolution' => Container::getInstance()->getAutomatedSolutionConverter()->toJson($this->automatedSolution),
			'dynamicTypesTitles' => $this->getTitlesOfDynamicTypes($this->automatedSolution['TYPE_IDS']),
			'permissions' => $permissions,
			'isPermissionsLayoutV2Enabled' => Feature::enabled(PermissionsLayoutV2::class),
		];
	}

	private function getTitlesOfDynamicTypes(array $typeIds): array
	{
		$types = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		])->getBunchOfTypesByIds($typeIds);

		$result = [];
		foreach ($types as $type)
		{
			$result[$type->getId()] = $type->getTitle();
		}

		return $result;
	}
}

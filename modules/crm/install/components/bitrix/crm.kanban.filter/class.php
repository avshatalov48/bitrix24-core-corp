<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Crm\Filter\HeaderSections;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class CrmKanbanFilterComponent extends \CBitrixComponent
{
	protected function init(): bool
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}

		$type = $this->arParams['ENTITY_TYPE'] ?? '';
		$viewMode = ($this->arParams['VIEW_MODE'] ?? \Bitrix\Crm\Kanban\ViewMode::MODE_STAGES);
		$entity = \Bitrix\Crm\Kanban\Entity::getInstance($type, $viewMode);
		if(!$entity)
		{
			return false;
		}

		$categoryId = \Bitrix\Crm\Kanban\Helper::getCategoryId();
		if($categoryId >= -1  && $entity->isCategoriesSupported())
		{
			if ($categoryId < 0 && !$entity->canUseAllCategories())
			{
				$categoryId = 0;
			}
			$entity->setCategoryId($categoryId);
		}

		$customSectionCode = $this->arParams['CUSTOM_SECTION_CODE'] ?? null;
		if (
			IntranetManager::isCustomSectionExists($customSectionCode)
			&& $entity->isCustomSectionSupported()
		)
		{
			$entity->setCustomSectionCode($customSectionCode);
		}

		$showAutomationView = true;
		if (
			isset($this->arParams['VIEW_MODE'])
			&& $this->arParams['VIEW_MODE'] !== \Bitrix\Crm\Kanban\ViewMode::MODE_STAGES
		)
		{
			$showAutomationView = false;
		}

		$filterParams = [
			'LIMITS' => null,
			'SHOW_AUTOMATION_VIEW' => $showAutomationView,
			'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)(
				$this->arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? ModuleManager::isModuleInstalled('ui')
			),
			'RESTRICTED_FIELDS' => $entity->getFieldsRestrictions(),
		];

		$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
		$entityTypeID = $entity->getTypeId();
		if($searchRestriction->isExceeded($entityTypeID))
		{
			$filterParams['LIMITS'] = $searchRestriction->prepareStubInfo(
				['ENTITY_TYPE_ID' => $entityTypeID]
			);
		}

		$filter = $entity->getGridFilter();

		$filterParams['GRID_ID'] = $entity->getGridId();
		$filterParams['FILTER_ID'] = $entity->getGridId();
		$filterParams['FILTER'] = $filter;
		$filterParams['FILTER_FIELDS'] = $entity->getFilterOptions()->getFilter($filter);
		$filterParams['FILTER_PRESETS'] = $entity->getFilterPresets();
		$filterParams['ENABLE_LIVE_SEARCH'] = true;
		$filterParams['NAVIGATION_BAR'] = $this->arParams['NAVIGATION_BAR'] ?: [];
		$filterParams['LAZY_LOAD'] = $entity->getFilterLazyLoadParams() ?: false;

		$filterSections = $this->getFilterSections($entityTypeID);
		$filterParams['ENABLE_FIELDS_SEARCH'] = 'Y';
		$filterParams['HEADERS_SECTIONS'] = $filterSections;
		$filterParams['CONFIG'] = [
			'popupColumnsCount' => 4,
			'popupWidth' => 800,
			'showPopupInCenter' => true,
		];

		$this->arResult['filterParams'] = $filterParams;

		return true;
	}

	/**
	 * Base executable method.
	 */
	public function executeComponent()
	{
		if (!$this->init())
		{
			return;
		}

		$this->IncludeComponentTemplate();
	}

	protected function getFilterSections(int $entityTypeID): array
	{
		$factory = Container::getInstance()->getFactory($entityTypeID);
		if (!$factory)
		{
			Container::getInstance()->getLocalization()->loadMessages();

			$entityName = Loc::getMessage('CRM_COMMON_' . $this->arParams['ENTITY_TYPE'] . '_MSGVER_1')
				?? Loc::getMessage('CRM_COMMON_' . $this->arParams['ENTITY_TYPE']);

			return [
				[
					'id' => $this->arParams['ENTITY_TYPE'],
					'name' => $entityName,
					'default' => true,
					'selected' => true,
				],
			];
		}
		return HeaderSections::getInstance()->sections($factory);
	}
}

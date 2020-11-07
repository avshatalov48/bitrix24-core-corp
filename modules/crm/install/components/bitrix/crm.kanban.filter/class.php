<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

class CrmKanbanFilterComponent extends \CBitrixComponent
{
	protected function init(): bool
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}

		$type = $this->arParams['ENTITY_TYPE'] ?? '';
		$entity = \Bitrix\Crm\Kanban\Entity::getInstance($type);
		if(!$entity)
		{
			return false;
		}

		$categoryId = \Bitrix\Crm\Kanban\Helper::getCategoryId();
		if($categoryId > 0 && $entity->isCategoriesSupported())
		{
			$entity->setCategoryId($categoryId);
		}

		$filterParams = [
			'LIMITS' => null,
		];

		$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
		$entityTypeID = $entity->getTypeId();
		if($searchRestriction->isExceeded($entityTypeID))
		{
			$filterParams['LIMITS'] = $searchRestriction->prepareStubInfo(
				['LIMITS' => $entityTypeID]
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
}
<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Entity\EntityManager;
use Bitrix\Crm\Filter\Activity\CounterFilter;
use Bitrix\Crm\Filter\Activity\FilterByActivityResponsible;
use Bitrix\Crm\Filter\FieldsTransform\UserBasedField;
use Bitrix\Crm\Search\SearchContentBuilderFactory;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;

abstract class EntityDataProvider extends Main\Filter\EntityDataProvider
{
	public const QUERY_APPROACH_ORM = 'orm';
	public const QUERY_APPROACH_BUILDER = 'builder';

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$filterValue = parent::prepareFilterValue($rawFilterValue);

		$factory = Container::getInstance()->getFactory($this->getSettings()->getEntityTypeID());
		if (!$factory)
		{
			return $filterValue;
		}

		$this->applySearchString($factory->getEntityTypeId(), $filterValue);
		$this->applyParentFieldFilter($filterValue);

		if ($factory->isMultiFieldsEnabled())
		{
			$this->applyMultifieldFilter($filterValue);
		}

		$currentUser = CurrentUser::get()->getId();
		/** @var UserBasedField $userFieldPrepare */
		$userFieldPrepare = ServiceLocator::getInstance()->get('crm.filter.fieldsTransform.userBasedField');
		$userFieldPrepare->transformAll($filterValue, ['ASSIGNED_BY_ID', 'ACTIVITY_RESPONSIBLE_IDS'], $currentUser);

		if ($factory->isCountersEnabled())
		{
			$this->applyCounterFilter($factory->getEntityTypeId(), $filterValue);
		}

		$this->applySettingsDependantFilter($filterValue);

		return $filterValue;
	}

	protected function applyParentFieldFilter(array &$filterValue): void
	{
		foreach ($filterValue as $k=>$v)
		{
			if (\Bitrix\Crm\Service\ParentFieldManager::isParentFieldName($k))
			{
				$filterValue[$k] = \Bitrix\Crm\Service\ParentFieldManager::transformEncodedFilterValueIntoInteger($k, $v);
			}
		}
	}

	protected function applySearchString(int $entityTypeId, array &$filterValue): void
	{
		try
		{
			SearchContentBuilderFactory::create($entityTypeId)->convertEntityFilterValues($filterValue);
		}
		catch (\Bitrix\Main\NotSupportedException $e)
		{
			//  just do nothing if $entityTypeId is not supported by SearchContentBuilderFactory
		}
	}

	protected function applyMultifieldFilter(array &$filterValue): void
	{
		\CCrmEntityHelper::PrepareMultiFieldFilter($filterValue, [], '=%', false);
	}

	public function applyActivityResponsibleFilter(int $entityTypeId, array &$filterFields): void
	{
		$dataProviderQueryApproach = $this->getDataProviderQueryApproach($entityTypeId);

		if ($dataProviderQueryApproach === null)
		{
			unset($filterFields['ACTIVITY_RESPONSIBLE_IDS']);
			unset($filterFields['!ACTIVITY_RESPONSIBLE_IDS']);
			return;
		}

		$actResponsible = new FilterByActivityResponsible($dataProviderQueryApproach);
		$actResponsible->applyFilter($filterFields, $entityTypeId);
	}

	public function applyCounterFilter(int $entityTypeId, array &$filterFields, array $extras = []): void
	{

		$dataProviderQueryApproach = $this->getDataProviderQueryApproach($entityTypeId);

		if ($dataProviderQueryApproach === null)
		{
			unset($filterFields['ACTIVITY_COUNTER']);
			return;
		}

		$counterFilter = new CounterFilter($dataProviderQueryApproach);
		$counterExtras = array_merge($extras, $this->getCounterExtras());
		$counterFilter->applyCounterFilter($entityTypeId, $filterFields, $counterExtras);
	}

	private function getDataProviderQueryApproach(int $entityTypeId): ?string
	{
		if ($this instanceof ItemDataProvider)
		{
			return self::QUERY_APPROACH_ORM;
		}

		$entity = EntityManager::resolveByTypeID($entityTypeId);
		if (empty($entity))
		{
			return null;
		}

		if ($this instanceof FactoryOptionable && $this->isForceUseFactory())
		{
			return self::QUERY_APPROACH_ORM;
		}

		return self::QUERY_APPROACH_BUILDER;
	}

	protected function applySettingsDependantFilter(array &$filterFields): void
	{
	}

	protected function getCounterExtras(): array
	{
		return [];
	}

	protected function isActivityResponsibleEnabled(): bool
	{
		return CounterSettings::getInstance()->useActivityResponsible();
	}
}

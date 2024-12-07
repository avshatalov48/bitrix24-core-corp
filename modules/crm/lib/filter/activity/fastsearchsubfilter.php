<?php

namespace Bitrix\Crm\Filter\Activity;


use Bitrix\Crm\Activity\FastSearch\ActivityFastSearchTable;
use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\Entity\EntityManager;
use Bitrix\Crm\Filter\ActivityFastSearchDataProvider;
use Bitrix\Crm\Filter\ActivityFastSearchSettings;
use Bitrix\Crm\Filter\EntityDataProvider;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;

class FastSearchSubFilter
{
	private EntityDataProvider $parentEntityDataProvider;

	public function __construct(EntityDataProvider $parentEntityDataProvider)
	{
		$this->parentEntityDataProvider = $parentEntityDataProvider;
	}

	public function applyFilter(int $entityTypeId, array &$filterFields): void
	{
		$fastSearchFilter = $this->transformFilter($entityTypeId, $filterFields);

		if (empty($fastSearchFilter))
		{
			return;
		}

		$query = ActivityFastSearchTable::query()
			->addSelect('bind.OWNER_ID', 'OWNER_ID')
			->setFilter($fastSearchFilter)
			->registerRuntimeField('',
				new ReferenceField(
					'bind',
					ActivityBindingTable::getEntity(),
					['=this.ACTIVITY_ID' => 'ref.ACTIVITY_ID'],
				)
			)
			->where('bind.OWNER_TYPE_ID', $entityTypeId);

		$queryApproach = $this->parentEntityDataProvider->getDataProviderQueryApproach();

		if ($queryApproach === EntityDataProvider::QUERY_APPROACH_ORM)
		{
			$filterFields[] = ['@ID' => new SqlExpression($query->getQuery())];
		}
		elseif ($queryApproach === EntityDataProvider::QUERY_APPROACH_BUILDER)
		{
			$entity = EntityManager::resolveByTypeID($entityTypeId);
			$masterAlias = $entity->getDbTableAlias();
			$filterFields += ['__CONDITIONS' => [['SQL' => "{$masterAlias}.ID IN ({$query->getQuery()})"]]];
		}
	}

	public function transformFilter(int $entityTypeId, array &$filter): array
	{
		$afsDataProvider = new ActivityFastSearchDataProvider(
			new ActivityFastSearchSettings([
				'ID' => $entityTypeId,
				'PARENT_FILTER_ENTITY_TYPE_ID' => $entityTypeId,
				'PARENT_ENTITY_DATA_PROVIDER' => $this->parentEntityDataProvider,
			])
		);

		$fields = $afsDataProvider->prepareFields();

		$resFilter = [];
		foreach ($fields as $filedName => $fieldObj)
		{
			$dbFieldName = preg_replace('/[^a-zA-Z0-9_]/', '', $filedName);
			$dbFieldName = str_replace('ACTIVITY_FASTSEARCH_', '', $dbFieldName);

			$fieldsInFilter = array_filter($filter, function($key) use ($filedName) {
				$preparedKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);

				return str_starts_with($preparedKey, $filedName);
			}, ARRAY_FILTER_USE_KEY);

			if (empty($fieldsInFilter))
			{
				continue;
			}

			$this->unsetActivityFastSearchFieldsFromFilter($fieldsInFilter, $filter);

			if ($fieldObj->getId() === 'ACTIVITY_FASTSEARCH_CREATED')
			{
				$daysAgo = (int)$fieldsInFilter['ACTIVITY_FASTSEARCH_CREATED'] ?? 0;
				if ($daysAgo <= 0 || $daysAgo > 365)
				{
					continue;
				}
				$dt = \CCrmDateTimeHelper::getUserDate(
					(new DateTime())->add("-P{$daysAgo}D")
				);

				$resFilter['>=CREATED'] = $dt;
			}
			elseif ($fieldObj->getId() === 'ACTIVITY_FASTSEARCH_ACTIVITY_TYPE')
			{
				if (empty($fieldsInFilter['ACTIVITY_FASTSEARCH_ACTIVITY_TYPE']))
				{
					continue;
				}

				Task::transformTaskInFilter(
					$fieldsInFilter,
					'ACTIVITY_FASTSEARCH_ACTIVITY_TYPE',
					true
				);

				$resFilter[$dbFieldName] = $fieldsInFilter['ACTIVITY_FASTSEARCH_ACTIVITY_TYPE'];
			}
			elseif ($fieldObj->getType() === 'date')
			{
				$resFilter = array_merge($resFilter, $this->processDateField($filedName, $fieldsInFilter, $dbFieldName));
			}
			elseif (isset($fieldsInFilter[$filedName]))
			{
				$resFilter = array_merge_recursive($resFilter, $this->processOtherFiled($filedName, $fieldsInFilter, $dbFieldName));
			}
			elseif(isset($fieldsInFilter['!' . $filedName]) && $fieldsInFilter['!' . $filedName] === false)
			{
				$resFilter['!' . $dbFieldName] = $fieldsInFilter['!' . $filedName];
			}

		}

		return $resFilter;
	}

	private function processDateField(string $fieldName, array $filter, string $dbFieldName): array
	{
		$result = [];

		$from = $fieldName . '_from';
		$to = $fieldName . '_to';

		if(!empty($filter[$from]))
		{
			$strFrom  = $filter[$from];
			$strFrom .= str_ends_with($strFrom, ' 00:00:00') ? '' : ' 00:00:00';
			$result['>=' . $dbFieldName] = $strFrom;
		}
		if(!empty($filter[$to]))
		{
			$strTo = $filter[$to];
			$strTo .= str_ends_with($strTo, ' 23:59:59') ? '' : ' 23:59:00';
			$result['<=' . $dbFieldName] = $strTo;
		}
		if(isset($filter[$fieldName]) && $filter[$fieldName] === false)
		{
			$result[$dbFieldName] = $filter[$fieldName];
		}
		elseif(isset($filter['!' . $fieldName]) && $filter['!' . $fieldName] === false)
		{
			$result['!' . $dbFieldName] = $filter['!' . $fieldName];
		}

		if (isset($filter[">=$fieldName"]))
		{
			$result['>=' . $dbFieldName] = $filter[">=$fieldName"];
		}

		if (isset($filter["<=$fieldName"]))
		{
			$result['<=' . $dbFieldName] = $filter["<=$fieldName"];
		}

		return $result;
	}

	private function processOtherFiled(string $fieldName, array $filter, string $dbFieldName): array
	{
		$result = [];

		if((isset($filter[$fieldName]) && $filter[$fieldName] === false))
		{
			$result[$dbFieldName] = $filter[$fieldName];
		}
		elseif((isset($filter['!' . $fieldName]) && $filter['!' . $fieldName] === false))
		{
			$result['!' . $dbFieldName] = $filter['!' . $fieldName];
		}
		else
		{
			$result[$dbFieldName] = $filter[$fieldName];
		}
		return $result;
	}

	private function unsetActivityFastSearchFieldsFromFilter(array $fieldsInFilter, array &$filter): void
	{
		foreach ($fieldsInFilter as $k => $item)
		{
			unset($filter[$k]);
		}
	}
}
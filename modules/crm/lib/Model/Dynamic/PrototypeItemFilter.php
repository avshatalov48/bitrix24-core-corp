<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\Item;
use Bitrix\Crm\Model\AssignedTable;
use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Main\DB\SqlExpression;

class PrototypeItemFilter
{
	public static function replaceParameters(array $filter, int $entityTypeId): array
	{
		foreach ($filter as $index => $value)
		{
			if (mb_strpos($index, Item::FIELD_NAME_ASSIGNED) !== false)
			{
				$assignedSqlExpression = static::getAssignedSqlExpression($index, $entityTypeId, $value);

				unset($filter[$index]);
			}
			elseif (mb_strpos($index, Item::FIELD_NAME_OBSERVERS) !== false)
			{
				$observersSqlExpression = static::getObserversSqlExpression($index, $entityTypeId, $value);

				unset($filter[$index]);
			}
			elseif (is_array($value))
			{
				$filter[$index] = static::replaceParameters($value, $entityTypeId);
			}
		}


		$userFilter = [];
		if (isset($assignedSqlExpression))
		{
			$userFilter[] = ['@ID' => $assignedSqlExpression];
		}

		if (isset($observersSqlExpression))
		{
			$userFilter[] = ['@ID' => $observersSqlExpression];
		}

		if (!empty($userFilter))
		{
			$userFilter['LOGIC'] = 'AND';
			$filter[] = $userFilter;
		}

		return $filter;
	}

	private static function getAssignedSqlExpression(string $filterKey, int $entityTypeId, $filterValue): SqlExpression
	{
		preg_match('/([=%><@!]*)ASSIGNED_BY_ID/', $filterKey, $pregResult);
		$operation = $pregResult[1];

		$subQuery = AssignedTable::query()->addSelect('ENTITY_ID')
			->addFilter($operation.'ASSIGNED_BY', $filterValue)
			->addFilter('ENTITY_TYPE_ID', $entityTypeId);

		return new SqlExpression($subQuery->getQuery());
	}

	private static function getObserversSqlExpression(string $filterKey, int $entityTypeId, $filterValue): SqlExpression
	{
		preg_match('/([=%><@!]*)OBSERVERS/', $filterKey, $pregResult);
		$operation = $pregResult[1];

		$subQuery = ObserverTable::query()->addSelect('ENTITY_ID')
			->addFilter($operation.'USER_ID', $filterValue)
			->addFilter('ENTITY_TYPE_ID', $entityTypeId);

		return new SqlExpression($subQuery->getQuery());
	}
}

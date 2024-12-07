<?php

namespace Bitrix\Crm\Security\QueryBuilder\Result\Traits;

use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\Condition;
use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\ObserversCondition;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

trait UnionUtils
{
	/**
	 * @param Condition[] $conditions
	 * @return array
	 */
	protected function separateConditions(array $conditions): array
	{
		$observerCondition = null;
		$otherConditions = [];
		foreach ($conditions as $condition)
		{
			if ($condition instanceof ObserversCondition)
			{
				$observerCondition = $condition;
			}
			else
			{
				$otherConditions[] = $condition;
			}
		}

		return [$otherConditions, $observerCondition];
	}

	protected function filterConditionsByType(array $conditions, string $classString): array
	{
		$result = [];
		foreach ($conditions as $condition)
		{
			if ($condition instanceof $classString)
			{
				$result[] = $condition;
			}
		}

		return $result;
	}

	/**
	 * @param array $conditions
	 * @return ConditionTree
	 */
	protected function makeOrmConditions(array $conditions): ConditionTree
	{
		$ct = new ConditionTree();
		$ct->logic(ConditionTree::LOGIC_OR);

		foreach ($conditions as $condition)
		{
			$ct->addCondition($condition->toOrmCondition(false));
		}

		$joinCt = new ConditionTree();
		$joinCt->logic(ConditionTree::LOGIC_AND);
		$joinCt->where($ct);
		return $joinCt;
	}
}

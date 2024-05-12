<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\Conditions;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class RestrictedConditionsList
{
	/** @var Condition[] */
	private array $conditions;

	/**
	 * @param Condition[] $conditions
	 */
	public function __construct(array $conditions = [])
	{
		$this->conditions = $conditions;
	}

	public function add(Condition $condition): void
	{
		$this->conditions[] = $condition;
	}

	public function isEmpty(): bool
	{
		return empty($this->conditions);
	}

	public function toArray(): array
	{
		return array_map(fn($cond) => $cond->toArray(), $this->conditions);
	}

	public function makeOrmConditions(?UseJoin $useJoin = null): ConditionTree
	{
		$forJoin = $useJoin !== null;

		$ct = new ConditionTree();
		$ct->logic(ConditionTree::LOGIC_OR);

		foreach ($this->conditions as $condition)
		{
			$ct->addCondition($condition->toOrmCondition($forJoin));
		}

		if ($forJoin)
		{
			$joinCt = new ConditionTree();
			$joinCt->logic(ConditionTree::LOGIC_AND);
			$joinCt->whereColumn('this.' . $useJoin->getIdentityColumnName(), 'ref.ENTITY_ID');
			$joinCt->where($ct);
			return $joinCt;
		}

		return $ct;
	}

	/**
	 * @return Condition[]
	 */
	public function getConditions(): array
	{
		return $this->conditions;
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Client;

use Bitrix\Booking\Provider\Params\FilterInterface;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

class ClientFilter implements FilterInterface
{
	private array $filter;

	public function __construct(array $filter = [])
	{
		$this->filter = $filter;
	}

	public function prepareFilter(): ConditionTree
	{
		$filter = new ConditionTree();

		if (isset($this->filter['ENTITY_TYPE_IDS']))
		{
			$entityTypeIds = $this->filter['ENTITY_TYPE_IDS'];
			if (count($entityTypeIds) === 1)
			{
				$firstKey = array_key_first($entityTypeIds);

				return $this->createEntityTypeCondition($firstKey, $entityTypeIds[$firstKey]);
			}

			$condition = (new ConditionTree())->logic(ConditionTree::LOGIC_OR);
			foreach ($entityTypeIds as $entityType => $ids)
			{
				$condition->where($this->createEntityTypeCondition($entityType, $ids));
			}

			return $condition;
		}

		return $filter;
	}

	public function prepareQuery(Query $query): void
	{
	}

	/**
	 * @param int[] $entityIds
	 */
	private function createEntityTypeCondition(string $entityType, array $entityIds): ConditionTree
	{
		return (new ConditionTree())
			->logic(ConditionTree::LOGIC_AND)
			->where('ENTITY_TYPE', $entityType)
			->whereIn('ENTITY_ID', $entityIds)
		;
	}
}

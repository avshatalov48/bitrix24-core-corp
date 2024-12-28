<?php

namespace Bitrix\HumanResources\Item\Collection\HcmLink;

use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection\BaseCollection;

/**
 * @extends BaseCollection<Item\HcmLink\EmployeeMapping>
 * @deprecated TABLE DELETED
 */
class EmployeeMappingCollection extends BaseCollection
{
	public function orderMapByInclude(): static
	{
		$sorted = [];
		$nodes = $this->itemMap;
		$queue = new \SplQueue();
		$adjacencyList = [];

		foreach ($nodes as $node)
		{
			$parentId = (int)$node->parentId;
			if (!isset($adjacencyList[$parentId]))
			{
				$adjacencyList[$parentId] = [];
			}
			$adjacencyList[$parentId][] = $node->id;

			if ((int)$node->parentId === 0 || !isset($nodes[$node->parentId]))
			{
				$queue->enqueue($node->id);
			}
		}

		while (!$queue->isEmpty())
		{
			$nodeId = $queue->dequeue();
			$sorted[$nodeId] = $nodes[$nodeId];

			if (isset($adjacencyList[$nodeId]))
			{
				foreach ($adjacencyList[$nodeId] as $childId)
				{
					$queue->enqueue($childId);
				}
			}
		}

		$newNodeCollection = new static();
		$newNodeCollection->itemMap = $sorted;

		return $newNodeCollection;
	}
}
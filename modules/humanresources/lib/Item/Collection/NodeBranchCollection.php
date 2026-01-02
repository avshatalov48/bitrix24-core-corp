<?php

namespace Bitrix\HumanResources\Item\Collection;

use Bitrix\HumanResources\Item;

/**
 * @extends BaseCollection<Item\NodeBranch>
 */
class NodeBranchCollection extends BaseCollection
{
	/**
	 * @return array<int, Item\Node>
	 */
	public function toNodeDictionary(): array
	{
		$result = [];

		/** @var Item\NodeBranch $nodeBranch */
		foreach ($this as $nodeBranch)
		{
			foreach ($nodeBranch->nodeCollection as $node)
			{
				$result[$node->id] = $node;
			}
		}

		return $result;
	}

	public function toFlatNodeCollection(): Item\Collection\NodeCollection
	{
		$nodeCollection = new Item\Collection\NodeCollection();

		foreach ($this as $nodeBranch)
		{
			foreach ($nodeBranch->nodeCollection as $node)
			{
				if ($nodeCollection->getItemById($node->id))
				{
					continue;
				}

				$nodeCollection->add($node);
			}
		}

		return $nodeCollection;
	}

	/**
	 * @return list<int>
	 */
	public function getFromDepartmentIds(): array
	{
		$result = [];

		foreach ($this as $nodeBranch)
		{
			if ($nodeBranch->fromNodeId)
			{
				$result[] = $nodeBranch->fromNodeId;
			}
		}

		return $result;
	}
}
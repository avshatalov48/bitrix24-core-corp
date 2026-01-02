<?php

namespace Bitrix\HumanResources\Item\Collection;

use Bitrix\HumanResources\Item;

/**
 * @extends BaseCollection<Item\NodeMember>
 */
class NodeMemberCollection extends BaseCollection
{
	/**
	 * @return list<int>
	 */
	public function getNodeIds(): array
	{
		return array_values(
			$this->map(
				static fn(Item\NodeMember $member) => $member->nodeId,
			),
		);
	}

	/**
	 * @return list<int>
	 */
	public function getEntityIds(): array
	{
		return array_values(
			$this->map(
				static fn(Item\NodeMember $member) => $member->entityId,
			),
		);
	}
}

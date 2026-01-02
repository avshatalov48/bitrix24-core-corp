<?php

namespace Bitrix\HumanResources\Builder\Structure\SelectionCondition;

use Bitrix\HumanResources\Contract\Builder\Structure\SelectionCondition;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;

final class First implements SelectionCondition
{
	public function apply(NodeMemberCollection $nodeMemberCollection): NodeMemberCollection
	{
		$result = NodeMemberCollection::emptyList();
		$first = $nodeMemberCollection->getFirst();
		if (!$first)
		{
			return $result;
		}

		return $result->add($first);
	}
}

<?php

namespace Bitrix\HumanResources\Builder\Structure\SelectionCondition;

use Bitrix\HumanResources\Contract\Builder\Structure\SelectionCondition;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;

final class Last implements SelectionCondition
{
	public function apply(NodeMemberCollection $nodeMemberCollection): NodeMemberCollection
	{
		$result = NodeMemberCollection::emptyList();
		$last = $nodeMemberCollection->getLast();
		if (!$last)
		{
			return $result;
		}

		return $result->add($last);
	}
}

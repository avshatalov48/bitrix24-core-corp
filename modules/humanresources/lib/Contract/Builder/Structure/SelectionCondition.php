<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Contract\Builder\Structure;

use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;

interface SelectionCondition
{
 	public function apply(NodeMemberCollection $nodeMemberCollection): NodeMemberCollection;
}
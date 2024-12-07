<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Item;

interface NodeAccessCodeRepository
{
	public function createByNode(
		Item\Node $node
	): ?string;
}
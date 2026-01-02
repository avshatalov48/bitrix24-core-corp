<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Type\AccessCodeType;

class NodeAccessCodeRepository implements Contract\Repository\NodeAccessCodeRepository
{
	public function createByNode(Item\Node $node): ?string
	{
		$existed =
			NodeBackwardAccessCodeTable::query()
				->addSelect('ACCESS_CODE')
				->where('NODE_ID', $node->id)
				->setLimit(1)
				->fetch()
			;

		if ($existed)
		{
			return $existed['ACCESS_CODE'];
		}
		$node->accessCode = AccessCodeType::HrStructureNodeType->value . $node->id;

		return $node->accessCode;
	}
}
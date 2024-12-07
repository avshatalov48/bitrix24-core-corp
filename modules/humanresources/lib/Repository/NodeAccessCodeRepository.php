<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract;

class NodeAccessCodeRepository implements Contract\Repository\NodeAccessCodeRepository
{
	public const HUMAN_RESOURCES_PREFIX = 'HR';

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

		$node->accessCode = self::HUMAN_RESOURCES_PREFIX . $node->id;

		$nodeBackwardCode = NodeBackwardAccessCodeTable::getEntity()->createObject();
		$result = $nodeBackwardCode
			->setNodeId($node->id)
			->setAccessCode($node->accessCode)
			->save()
		;

		if ($result->isSuccess())
		{
			return $node->accessCode;
		}

		return null;
	}
}
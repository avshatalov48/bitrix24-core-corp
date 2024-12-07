<?php

namespace Bitrix\HumanResources\Rest\Factory\Item;

use Bitrix\HumanResources\Rest\Factory\ItemFactory;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main;

final class Node extends ItemFactory
{
	public function createFromRestFields(array $fields): Item\Node
	{
		$nodeType = isset($fields['NODE_TYPE'])
			? NodeEntityType::tryFrom($fields['NODE_TYPE'])
			: null
		;
		return new Item\Node(
			name: $fields['NAME'] ?? null,
			type: $nodeType,
			structureId: $fields['STRUCTURE_ID'] ?? null,
			accessCode: $fields['ACCESS_CODE'] ?? null,
			xmlId: $fields['XML_ID'] ?? null,
		);
	}

	public function validateRestFields(array $fields): Main\Result
	{
		$result = new Main\Result();
		if (isset($fields['TYPE']))
		{
			$entityType = $fields['TYPE'];
			if (NodeEntityType::isValid($entityType))
			{
				return $result->addError(new Main\Error('Invalid value for entityType'));
			}
		}

		return $result;
	}
}
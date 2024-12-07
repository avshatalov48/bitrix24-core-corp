<?php

namespace Bitrix\HumanResources\Rest\Factory\Item;

use Bitrix\HumanResources\Rest\Factory\ItemFactory;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main;

final class Member extends ItemFactory
{
	public function createFromRestFields(array $fields): Item\NodeMember
	{
		$entityType = isset($fields['ENTITY_TYPE'])
			? MemberEntityType::tryFrom($fields['ENTITY_TYPE'])
			: null
		;

		return new Item\NodeMember(
			entityType: $entityType,
			entityId: $fields['ENTITY_ID'] ?? null,
			nodeId: $fields['NODE_ID'] ?? null,
		);
	}

	public function validateRestFields(array $fields): Main\Result
	{
		$result = new Main\Result();
		if (isset($fields['ENTITY_TYPE']))
		{
			$entityType = $fields['ENTITY_TYPE'];
			if (!MemberEntityType::isValid($entityType))
			{
				return $result->addError(new Main\Error('Invalid value for entityType'));
			}
		}

		return $result;
	}
}
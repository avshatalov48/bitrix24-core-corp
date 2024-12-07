<?php

namespace Bitrix\HumanResources\Rest\Factory\Item;

use Bitrix\HumanResources\Rest\Factory\ItemFactory;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\RoleChildAffectionType;
use Bitrix\HumanResources\Type\RoleEntityType;
use Bitrix\Main;

final class Role extends ItemFactory
{
	public function createFromRestFields(array $fields): Item\Role
	{
		$entityType = isset($fields['ENTITY_TYPE'])
			? RoleEntityType::tryFrom($fields['ENTITY_TYPE'])
			: null;

		$childAffectionType = isset($fields['CHILD_AFFECTION_TYPE'])
			? RoleEntityType::tryFrom($fields['CHILD_AFFECTION_TYPE'])
			: null
		;

		return new Item\Role(
			name: $fields['NAME'] ?? null,
			xmlId: $fields['XML_ID'] ?? null,
			entityType: $entityType,
			childAffectionType: $childAffectionType,
			id: $fields['ID'] ?? null,
			priority: $fields['PRIORITY'] ?? null,
		);
	}

	public function validateRestFields(array $fields): Main\Result
	{
		$result = new Main\Result();
		if (isset($fields['ENTITY_TYPE']))
		{
			$entityType = $fields['ENTITY_TYPE'];
			if (RoleEntityType::isValid($entityType))
			{
				$result->addError(new Main\Error('Invalid value for ENTITY_TYPE'));
			}
		}

		if (isset($fields['CHILD_AFFECTION_TYPE']))
		{
			$entityType = $fields['CHILD_AFFECTION_TYPE'];
			if (RoleChildAffectionType::isValid($entityType))
			{
				$result->addError(new Main\Error('Invalid value for CHILD_AFFECTION_TYPE'));
			}
		}

		return $result;
	}
}
<?php

namespace Bitrix\HumanResources\Rest\Factory\Item;

use Bitrix\HumanResources\Rest\Factory\ItemFactory;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\RoleChildAffectionType;
use Bitrix\HumanResources\Type\RoleEntityType;
use Bitrix\HumanResources\Type\StructureType;
use Bitrix\Main;

final class Structure extends ItemFactory
{
	public function createFromRestFields(array $fields): Item\Structure
	{
		$type = isset($fields['TYPE'])
			? StructureType::tryFrom($fields['TYPE'])
			: null
		;

		return new Item\Structure(
			name: $fields['NAME'] ?? null,
			type: $type,
			xmlId: $fields['XML_ID'] ?? null,
		);
	}

	public function validateRestFields(array $fields): Main\Result
	{
		$result = new Main\Result();
		if (isset($fields['TYPE']))
		{
			$type = $fields['TYPE'];
			if (StructureType::isValid($type))
			{
				$result->addError(new Main\Error('Invalid value for TYPE'));
			}
		}

		return $result;
	}
}
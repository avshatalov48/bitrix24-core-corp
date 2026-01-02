<?php

namespace Bitrix\Sign\Operation\Member\Validation;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Result\Operation\Member\ValidateEntitySelectorMembersResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Item\Member\SelectorEntityCollection;
use Bitrix\Sign\Item\Member\SelectorEntity;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Hr\EntitySelector;

class ValidateEntitySelectorMembers implements Operation
{
	public function __construct(
		/** @var array{entityId: string, entityType: string, party: int, role: string} */
		private readonly array $members,
	) {}

	public function launch(): Main\Result|ValidateEntitySelectorMembersResult
	{
		$entities = new SelectorEntityCollection();
		foreach ($this->members as $member)
		{
			foreach ($this->getRequiredFields() as $requiredField)
			{
				if (!array_key_exists($requiredField, $member))
				{
					return Result::createByErrorMessage("Not all members contains `{$requiredField}`");
				}
			}

			// numeric or numeric:F (entity selector values)
			if (!preg_match('/^\d+$|^\d+:F$/', (string)$member['entityId']))
			{
				return Result::createByErrorMessage('Invalid `entityId` field value');
			}

			if (!is_int($member['party']))
			{
				return Result::createByErrorMessage('Invalid `party` field value');
			}

			if (isset($member['role']))
			{
				if (!is_string($member['role']) || !Role::isValid($member['role']))
				{
					return Result::createByErrorMessage('Invalid `role` field value');
				}
			}

			// entity selector types merged with sign types (company, etc.)
			$allEntityTypes = array_merge(EntityType::getAll(), EntitySelector\EntityType::getAll());
			if (!in_array($member['entityType'], $allEntityTypes))
			{
				return Result::createByErrorMessage('Invalid `EntityType` field value');
			}

			$entities->add(
				new SelectorEntity(
					entityType: (string)$member['entityType'],
					entityId: (string)$member['entityId'],
					role: $member['role'] ?? null,
					party: $member['party'],
				)
			);
		}

		return new ValidateEntitySelectorMembersResult($entities);
	}

	private function getRequiredFields(): array
	{
		return ['entityType', 'entityId', 'party'];
	}
}
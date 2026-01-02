<?php

namespace Bitrix\Sign\Operation\Member\Validation;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Member\SelectorEntity;
use Bitrix\Sign\Item\Member\SelectorEntityCollection;
use Bitrix\Sign\Result\Operation\Member\ValidateEntitySelectorMembersResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Type\Member\Role;

class ValidateEntitySelectorSigners implements Operation
{
	public function __construct(
		/** @var array{entityId: string, entityType: string} */
		private readonly array $signers,
	) {}

	public function launch(): Main\Result|ValidateEntitySelectorMembersResult
	{
		$entities = new SelectorEntityCollection();
		foreach ($this->signers as $member)
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

			$entities->add(
				new SelectorEntity(
					entityType: (string)$member['entityType'],
					entityId: (string)$member['entityId'],
					role: Role::SIGNER,
				)
			);
		}

		return new ValidateEntitySelectorMembersResult($entities);
	}

	private function getRequiredFields(): array
	{
		return ['entityType', 'entityId'];
	}
}
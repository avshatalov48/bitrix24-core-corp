<?php

namespace Bitrix\Crm\Integration\AI\Function\Category\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedCategoryIdentifier;
use Bitrix\Crm\Dto\Validator\EntityType\IsPossibleDynamicType;
use Bitrix\Crm\Dto\Validator\EnumField;
use Bitrix\Crm\Dto\Validator\Logic;
use CCrmOwnerType;

final class DeleteParameters extends Dto
{
	public int $entityTypeId;
	public int $categoryId;

	protected function getValidators(array $fields): array
	{
		return [
			Logic::or($this, [
				new EnumField($this, 'entityTypeId', [CCrmOwnerType::Deal]),
				new IsPossibleDynamicType($this, 'entityTypeId'),
			]),

			new DefinedCategoryIdentifier($this, 'entityTypeId', 'categoryId'),
		];
	}
}

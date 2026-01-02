<?php

namespace Bitrix\Crm\Integration\AI\Function\Category\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedCategoryIdentifier;
use Bitrix\Crm\Dto\Validator\EntityType\IsPossibleDynamicType;
use Bitrix\Crm\Dto\Validator\EnumField;
use Bitrix\Crm\Dto\Validator\IntegerField;
use Bitrix\Crm\Dto\Validator\Logic;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use CCrmOwnerType;

final class RenameParameters extends Dto
{
	public int $entityTypeId;
	public int $categoryId;
	public string $title;

	protected function getValidators(array $fields): array
	{
		return [
			Logic::or($this, [
				new EnumField($this, 'entityTypeId', [CCrmOwnerType::Deal]),
				new IsPossibleDynamicType($this, 'entityTypeId'),
			]),

			new DefinedCategoryIdentifier($this, 'entityTypeId', 'categoryId'),

			new NotEmptyField($this, 'title'),
		];
	}
}

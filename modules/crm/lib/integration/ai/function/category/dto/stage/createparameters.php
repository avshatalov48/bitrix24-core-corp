<?php

namespace Bitrix\Crm\Integration\AI\Function\Category\Dto\Stage;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedCategoryIdentifier;
use Bitrix\Crm\Dto\Validator\EntityType\IsPossibleDynamicType;
use Bitrix\Crm\Dto\Validator\EnumField;
use Bitrix\Crm\Dto\Validator\Logic;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\ObjectField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use Bitrix\Main\Error;
use CCrmOwnerType;

final class CreateParameters extends Dto
{
	public int $entityTypeId;
	public ?int $categoryId = null;

	public StageFields $fields;

	protected function getValidators(array $fields): array
	{
		return [
			Logic::or($this, [
				new EnumField($this, 'entityTypeId', [
					CCrmOwnerType::Lead,
					CCrmOwnerType::Deal,
					CCrmOwnerType::Quote,
					CCrmOwnerType::SmartInvoice,
				]),
				new IsPossibleDynamicType($this, 'entityTypeId'),
			]),

			new DefinedCategoryIdentifier($this, 'entityTypeId', 'categoryId'),

			new ObjectField($this, 'fields'),
			new NotEmptyField($this, 'fields'),
		];
	}
}

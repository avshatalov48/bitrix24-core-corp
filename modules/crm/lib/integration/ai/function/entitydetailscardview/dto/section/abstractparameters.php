<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedCategoryIdentifier;

abstract class AbstractParameters extends Dto
{
	public int $entityTypeId;
	public ?int $categoryId = null;

	protected function getValidators(array $fields): array
	{
		return [
			new DefinedCategoryIdentifier($this, 'entityTypeId', 'categoryId'),
		];
	}
}

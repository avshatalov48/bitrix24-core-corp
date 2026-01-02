<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\RequiredField;

class DeleteParameters extends AbstractParameters
{
	public string $name;

	protected bool $isCollectPropertiesFromParent = true;

	protected function getValidators(array $fields): array
	{
		return [
			...parent::getValidators($fields),

			new NotEmptyField($this, 'name'),
		];
	}
}

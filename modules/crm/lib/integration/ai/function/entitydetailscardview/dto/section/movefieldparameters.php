<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section;

use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\RequiredField;

class MoveFieldParameters extends AbstractParameters
{
	public string $sectionName;
	public string $fieldName;

	protected bool $isCollectPropertiesFromParent = true;

	protected function getValidators(array $fields): array
	{
		return [
			...parent::getValidators($fields),

			new NotEmptyField($this, 'sectionName'),
			new NotEmptyField($this, 'fieldName'),
		];
	}
}

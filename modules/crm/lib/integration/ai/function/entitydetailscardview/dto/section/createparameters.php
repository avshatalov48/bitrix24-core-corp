<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\ObjectCollectionField;
use Bitrix\Crm\Dto\Validator\ObjectField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Configuration\Section;

class CreateParameters extends AbstractParameters
{
	public Section $section;

	protected bool $isCollectPropertiesFromParent = true;

	protected function getValidators(array $fields): array
	{
		return [
			...parent::getValidators($fields),

			new ObjectField($this, 'section'),
			new NotEmptyField($this, 'section'),
		];
	}
}

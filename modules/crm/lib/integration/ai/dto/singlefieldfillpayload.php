<?php

namespace Bitrix\Crm\Integration\AI\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;

class SingleFieldFillPayload extends Dto
{
	public string $name;

	public string $aiValue;

	public bool $isApplied = false;
	public bool $isConflict = false;

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'name'),

			new NotEmptyField($this, 'aiValue'),
		];
	}
}

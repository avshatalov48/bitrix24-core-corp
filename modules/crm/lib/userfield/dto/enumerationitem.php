<?php

namespace Bitrix\Crm\UserField\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Main\Validation\Rule\NotEmpty;

class EnumerationItem extends Dto
{
	public int $id = 0;
	public bool $isDefault = false;
	public ?int $sort = null;
	public string $value;

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'value'),
		];
	}
}

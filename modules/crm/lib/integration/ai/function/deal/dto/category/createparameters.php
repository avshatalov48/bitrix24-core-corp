<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal\Dto\Category;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\StringField;
use Bitrix\Crm\Dto\Validator\NotEmptyField;

final class CreateParameters extends Dto
{
	public string $name;

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'name'),
			new StringField($this, 'name'),
		];
	}
}

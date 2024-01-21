<?php

namespace Bitrix\Crm\Activity\Provider\Sms;

use Bitrix\Crm\Dto\Validator\RequiredField;

final class TemplatePlaceholderDto extends \Bitrix\Crm\Dto\Dto
{
	public string $name;
	public string $value;

	protected function getValidators(array $fields): array
	{
		return [
			new RequiredField($this, 'name'),
			new RequiredField($this, 'value'),
		];
	}
}

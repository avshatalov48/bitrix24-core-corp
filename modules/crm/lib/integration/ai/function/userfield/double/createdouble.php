<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Double;

use Bitrix\Crm\Integration\AI\Function\UserField\AbstractCreateUserField;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;

abstract class CreateDouble extends AbstractCreateUserField
{
	protected const DEFAULT_PRECISION = 2;

	protected function getType(): UserFieldType
	{
		return UserFieldType::Double;
	}

	protected function settings(): array
	{
		return [
			'PRECISION' => static::DEFAULT_PRECISION,
		];
	}
}

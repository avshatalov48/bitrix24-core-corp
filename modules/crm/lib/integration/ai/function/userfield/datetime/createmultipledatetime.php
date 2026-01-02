<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\DateTime;

use Bitrix\Crm\Integration\AI\Function\UserField\AbstractCreateUserField;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;

final class CreateMultipleDateTime extends AbstractCreateUserField
{
	protected function isMultiple(): bool
	{
		return true;
	}

	protected function getType(): UserFieldType
	{
		return UserFieldType::DateTime;
	}
}

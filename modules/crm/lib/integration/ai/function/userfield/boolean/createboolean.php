<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Boolean;

use Bitrix\Crm\Integration\AI\Function\UserField\AbstractCreateUserField;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;

final class CreateBoolean extends AbstractCreateUserField
{
	protected function isMultiple(): bool
	{
		return false;
	}

	protected function getType(): UserFieldType
	{
		return UserFieldType::Boolean;
	}
}

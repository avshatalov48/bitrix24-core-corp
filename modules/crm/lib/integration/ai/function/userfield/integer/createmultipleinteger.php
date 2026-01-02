<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Integer;

use Bitrix\Crm\Integration\AI\Function\UserField\AbstractCreateUserField;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;

final class CreateMultipleInteger extends AbstractCreateUserField
{
	protected function isMultiple(): bool
	{
		return true;
	}

	protected function getType(): UserFieldType
	{
		return UserFieldType::Integer;
	}
}

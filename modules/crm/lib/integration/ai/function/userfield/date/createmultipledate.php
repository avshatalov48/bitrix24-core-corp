<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Date;

use Bitrix\Crm\Integration\AI\Function\UserField\AbstractCreateUserField;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;

final class CreateMultipleDate extends AbstractCreateUserField
{
	protected function isMultiple(): bool
	{
		return true;
	}

	protected function getType(): UserFieldType
	{
		return UserFieldType::Date;
	}
}

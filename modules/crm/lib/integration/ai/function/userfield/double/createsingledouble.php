<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Double;

use Bitrix\Crm\Integration\AI\Function\UserField\AbstractCreateUserField;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;

final class CreateSingleDouble extends CreateDouble
{
	protected function isMultiple(): bool
	{
		return false;
	}
}

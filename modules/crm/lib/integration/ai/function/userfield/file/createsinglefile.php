<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\File;

use Bitrix\Crm\Integration\AI\Function\UserField\AbstractCreateUserField;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;

final class CreateSingleFile extends AbstractCreateUserField
{
	protected function isMultiple(): bool
	{
		return false;
	}

	protected function getType(): UserFieldType
	{
		return UserFieldType::File;
	}
}

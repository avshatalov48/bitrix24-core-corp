<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Enumeration;

final class CreateMultipleEnumeration extends CreateEnumeration
{
	protected function isMultiple(): bool
	{
		return true;
	}
}

<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Enumeration;

final class CreateSingleEnumeration extends CreateEnumeration
{
	protected function isMultiple(): bool
	{
		return false;
	}
}

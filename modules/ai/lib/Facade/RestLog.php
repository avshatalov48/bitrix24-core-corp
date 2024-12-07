<?php

namespace Bitrix\AI\Facade;

use Bitrix\Main\Loader;
use Bitrix\Rest\UsageStatTable;

class RestLog
{
	/**
	 * Increment REST AI usage by type.
	 *
	 * @param string|null $applicationCode Application's code.
	 * @param string $type Log's item type.
	 * @return void
	 */
	public static function logUsage(?string $applicationCode, string $type): void
	{
		if (empty($applicationCode))
		{
			return;
		}

		// todo: remove is_callable after rest's release
		if (Loader::includeModule('rest') && is_callable(['Bitrix\Rest\UsageStatTable', 'logAI']))
		{
			$clientId = Rest::getApplicationClientId($applicationCode);
			if (!empty($clientId))
			{
				UsageStatTable::logAI($clientId, $type);
			}
		}
	}
}

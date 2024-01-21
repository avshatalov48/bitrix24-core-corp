<?php

namespace Bitrix\Disk\Integration\Calendar;

use Bitrix\Calendar\Integration\Disk\EventConnectorHelper;
use Bitrix\Main\Loader;

final class EventConnectorHelperHandler
{
	public static function getHandler(int $userId, string $forumXmlId): ?EventConnectorHelper
	{
		if (!Loader::includeModule('calendar'))
		{
			return null;
		}

		return new EventConnectorHelper($userId, $forumXmlId);
	}
}
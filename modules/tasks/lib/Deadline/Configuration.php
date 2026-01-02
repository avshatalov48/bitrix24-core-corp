<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline;

use Bitrix\Tasks\Integration\Extranet\User;

class Configuration
{
	public const MAX_DEFAULT_DEADLINE_IN_SECONDS = 2145398400;

	public static function isDeadlineNotificationAvailable(int $userId): bool
	{
		return !User::isExtranet($userId);
	}
}

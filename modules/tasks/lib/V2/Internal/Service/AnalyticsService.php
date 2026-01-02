<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Tasks\Integration\Intranet\User;

class AnalyticsService
{
	public function getUserTypeParameter(int $userId): string
	{
		if ($userId <= 0)
		{
			return '';
		}

		if (User::isIntranet($userId))
		{
			return 'user_intranet';
		}

		if (User::isCollaber($userId))
		{
			return 'user_collaber';
		}

		return 'user_extranet';
	}
}

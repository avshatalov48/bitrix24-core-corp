<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Config;

use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Onboarding\Internal\Type;

final class JobLimit
{
	public static function get(Type $type): int
	{
		[$name, $value] = match ($type)
		{
			Type::OneDayNotViewed => ['onboarding_tasks_one_day_not_viewed_limit', 5],
			Type::TwoDaysNotViewed =>['onboarding_tasks_two_days_not_viewed_limit', 5],
			Type::TooManyTasks => ['onboarding_tasks_too_many_tasks_limit', 1],
			Type::InviteToMobile => ['onboarding_tasks_invite_to_mobile_limit', 1],
			default => ['onboarding_default_limit', 1],
		};

		return (int)Option::get('tasks', $name, $value);
	}
}
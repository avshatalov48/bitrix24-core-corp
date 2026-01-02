<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Config;

use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Onboarding\Internal\Type;

final class JobOffset
{
	private const SECONDS_IN_DAY = 86400;

	public static function get(Type $type): int
	{
		[$name, $value] = match ($type)
		{
			Type::OneDayNotViewed => ['onboarding_tasks_one_day_not_viewed_offset', self::SECONDS_IN_DAY],
			Type::TwoDaysNotViewed =>['onboarding_tasks_two_days_not_viewed_offset', self::SECONDS_IN_DAY * 2],
			Type::TooManyTasks => ['onboarding_tasks_too_many_tasks_offset', 0],
			Type::ResponsibleInvitationNotAcceptedOneDay => ['onboarding_tasks_responsible_invitation_not_accepted_one_day_offset', self::SECONDS_IN_DAY],
			Type::InvitedResponsibleNotViewTaskTwoDays => ['onboarding_tasks_invited_responsible_not_view_task_two_days_offset', self::SECONDS_IN_DAY * 2],
			Type::ResponsibleInvitationAccepted => ['onboarding_tasks_responsible_invitation_accepted_offset', 0],
			Type::InviteToMobile => ['onboarding_tasks_invite_to_mobile_offset', 0],
			default => ['onboarding_default_offset', self::SECONDS_IN_DAY],
		};

		return (int)Option::get('tasks', $name, $value);
	}
}
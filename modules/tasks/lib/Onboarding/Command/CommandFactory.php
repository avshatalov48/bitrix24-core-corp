<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command;

use Bitrix\Tasks\Onboarding\Command\Type\InviteToMobile;
use Bitrix\Tasks\Onboarding\Command\Type\InvitedResponsibleNotViewTaskTwoDaysCommand;
use Bitrix\Tasks\Onboarding\Command\Type\ResponsibleInvitationAcceptedCommand;
use Bitrix\Tasks\Onboarding\Command\Type\ResponsibleInvitationNotAcceptedOneDay;
use Bitrix\Tasks\Onboarding\Command\Type\TaskNotViewedOneDay;
use Bitrix\Tasks\Onboarding\Command\Type\TaskNotViewedTwoDays;
use Bitrix\Tasks\Onboarding\Command\Type\TooManyTasks;
use Bitrix\Tasks\Onboarding\Internal\Type;

final class CommandFactory
{
	public static function createCommand(int $id, int $taskId, int $userId,  string $type, string $code): ?CommandInterface
	{
		if ($type === Type::OneDayNotViewed->value)
		{
			return new TaskNotViewedOneDay($id, $taskId, $userId, Type::OneDayNotViewed, $code);
		}

		if ($type === Type::TwoDaysNotViewed->value)
		{
			return new TaskNotViewedTwoDays($id, $taskId, $userId, Type::TwoDaysNotViewed, $code);
		}

		if ($type === Type::TooManyTasks->value)
		{
			return new TooManyTasks($id, $taskId, $userId, Type::TooManyTasks, $code);
		}

		if ($type === Type::ResponsibleInvitationNotAcceptedOneDay->value)
		{
			return new ResponsibleInvitationNotAcceptedOneDay($id, $taskId, $userId, Type::ResponsibleInvitationNotAcceptedOneDay, $code);
		}

		if ($type === Type::InvitedResponsibleNotViewTaskTwoDays->value)
		{
			return new InvitedResponsibleNotViewTaskTwoDaysCommand($id, $taskId, $userId, Type::InvitedResponsibleNotViewTaskTwoDays, $code);
		}

		if ($type === Type::ResponsibleInvitationAccepted->value)
		{
			return new ResponsibleInvitationAcceptedCommand($id, $taskId, $userId, Type::ResponsibleInvitationAccepted, $code);
		}

		if ($type === Type::InviteToMobile->value)
		{
			return new InviteToMobile($id, $taskId, $userId, Type::InviteToMobile, $code);
		}

		return null;
	}
}
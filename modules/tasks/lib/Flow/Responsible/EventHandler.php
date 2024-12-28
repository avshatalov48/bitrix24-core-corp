<?php

namespace Bitrix\Tasks\Flow\Responsible;

use Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveUserFromFlowResponsible;

final class EventHandler
{
	public function onAfterUserUpdate(int $userId): void
	{
		if ($userId <= 0)
		{
			return;
		}

		RemoveUserFromFlowResponsible::bindAgent($userId);
	}

	public function onAfterUserDelete(int $deletedUserId): void
	{
		if ($deletedUserId <= 0)
		{
			return;
		}

		RemoveUserFromFlowResponsible::bindAgent($deletedUserId);
	}
}
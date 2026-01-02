<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Exclusion;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Tasks\Flow\Migration\Exclusion\Agent\ExclusionFromFlowAgent;

final class EventHandler
{
	public function onAfterUserUpdate(int $userId): void
	{
		if ($userId <= 0)
		{
			return;
		}

		ExclusionFromFlowAgent::bindAgent("U{$userId}");
	}

	public function onAfterUserDelete(int $userId): void
	{
		if ($userId <= 0)
		{
			return;
		}

		ExclusionFromFlowAgent::bindAgent("U{$userId}");
	}

	public function onAfterDepartmentDelete(string $departmentAccessCode): void
	{
		if (!AccessCode::isValid($departmentAccessCode))
		{
			return;
		}

		ExclusionFromFlowAgent::bindAgent($departmentAccessCode);
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\EventResult;

class OnUserFiredListener extends AbstractEventListener
{
	public function onAfterUserFired(array $data): EventResult
	{
		$eventResult = new EventResult(EventResult::SUCCESS);

		$userId = (int)($data['ID'] ?? 0);
		if ($userId <= 0)
		{
			return $eventResult;
		}

		$isHired = ($data['ACTIVE'] ?? '') === 'N';

		if ($isHired)
		{
			$this->deleteByPair(userId: $userId);
		}

		return $eventResult;
	}

	public function onAfterUserDelete(int $userId): EventResult
	{
		$eventResult = new EventResult(EventResult::SUCCESS);

		if ($userId <= 0)
		{
			return $eventResult;
		}

		$this->deleteByPair(userId: $userId);

		return $eventResult;
	}
}
<?php

namespace Bitrix\Intranet\Integration\Im;

final class ChatProvider
{
	protected bool $isEnabled= false;

	public function __construct()
	{
		$this->isEnabled = \Bitrix\Main\Loader::includeModule('im');
	}

	public function isAvailable(): bool
	{
		return $this->isEnabled;
	}

	public function getUnreadMessagesCountForUser(int $userId): int
	{
		if (!$this->isAvailable())
		{
			return 0;
		}

		$counterService = new \Bitrix\Im\V2\Message\CounterService($userId);
		if (!$counterService)
		{
			return 0;
		}

		$counterData = $counterService->get();
		return isset($counterData['TYPE']['MESSENGER']) ? (int)$counterData['TYPE']['MESSENGER'] : 0;
	}

}
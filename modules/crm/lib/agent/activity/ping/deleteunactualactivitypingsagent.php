<?php

namespace Bitrix\Crm\Agent\Activity\Ping;

use Bitrix\Crm\Activity\Ping\PingQueueCleaner;
use Bitrix\Crm\Agent\AgentBase;

class DeleteUnactualActivityPingsAgent extends AgentBase
{
	public const AGENT_DONE_STOP_IT = false;
	public const RUN_LATER = true;

	public static function doRun(): bool
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$pingQueueCleaner = new PingQueueCleaner($connection);

		if ($pingQueueCleaner->hasUnattainableItems())
		{
			$pingQueueCleaner->deleteUnattainableItems();

			return self::RUN_LATER;
		}

		return self::AGENT_DONE_STOP_IT;
	}
}

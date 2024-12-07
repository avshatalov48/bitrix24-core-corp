<?php

namespace Bitrix\Tasks\Replication\Task\Regularity\Agent\Race;

use Bitrix\Tasks\Replication\AbstractMutex;

class Mutex extends AbstractMutex
{
	protected function getTTL(): int
	{
		return 1800;
	}

	protected function getCacheName(): string
	{
		return 'tasks_regularity_notification_mutex';
	}
}
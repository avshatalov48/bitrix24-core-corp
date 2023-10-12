<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Traits\Singleton;

class CounterManagerResetWrapper
{
	use Singleton;

	public function __construct()
	{
	}

	public function reset($codes, $responsibleIds): void
	{
		EntityCounterManager::reset($codes, $responsibleIds);
	}

	public function resetExcludeUsersCounters($codes, $responsibleIds): void
	{
		EntityCounterManager::resetExcludeUsersCounters($codes, $responsibleIds);
	}
}
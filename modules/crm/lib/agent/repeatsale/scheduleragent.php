<?php

namespace Bitrix\Crm\Agent\RepeatSale;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\RepeatSale\Schedule\Scheduler;
use Bitrix\Crm\Service\Container;

class SchedulerAgent extends AgentBase
{
	public const PERIODICAL_AGENT_RUN_LATER = true;

	public static function doRun(): bool
	{
		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();
		if ($availabilityChecker->isItemsCountsLessThenLimit())
		{
			Scheduler::getInstance()->execute();
		}

		$instance = new self();
		$instance->setExecutionPeriod(86400);

		return self::PERIODICAL_AGENT_RUN_LATER;
	}
}

<?php

namespace Bitrix\Crm\Agent\RepeatSale;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\RepeatSale\Queue\Controller\RepeatSaleQueueController;
use Bitrix\Crm\RepeatSale\Schedule\Scheduler;
use Bitrix\Crm\Service\Container;

class OnlyCalcSchedulerAgent extends AgentBase
{
	public const AGENT_DONE_STOP_IT = false;
	public const LIMIT_EXCEEDED_RUN_LATER = true;

	public static function doRun(): bool
	{
		$instance = new self();

		if (Container::getInstance()->getRepeatSaleAvailabilityChecker()->isItemsCountsLessThenLimit())
		{
			$instance->cleanQueue();

			Scheduler::getInstance()->setOnlyCalc()->execute();

			return self::AGENT_DONE_STOP_IT;
		}

		$instance->setExecutionPeriod(86400);

		return self::LIMIT_EXCEEDED_RUN_LATER;
	}

	private function cleanQueue(): void
	{
		$controller = RepeatSaleQueueController::getInstance();
		$controller->deleteOnlyCalcItems();
	}
}

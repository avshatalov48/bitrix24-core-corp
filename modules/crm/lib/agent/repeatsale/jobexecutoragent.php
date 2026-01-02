<?php

namespace Bitrix\Crm\Agent\RepeatSale;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\RepeatSale\AllowedAgentsTimeManager;
use Bitrix\Crm\RepeatSale\Queue\Controller\RepeatSaleQueueController;
use Bitrix\Crm\RepeatSale\Queue\Executor;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

class JobExecutorAgent extends AgentBase
{
	private const NEXT_DAY_INTERVAL = 3600 * 24; // 24 hours in seconds
	private const MIN_AGENT_INTERVAL = 30;
	private const CACHE_TTL = 3600 * 12;
	private const CACHE_ID = 'crm_repeat_sale_job_executor_deal_count';
	private const CACHE_DIR = '/crm/repeat_sale/';

	public const PERIODICAL_AGENT_RUN_LATER = true;

	public static function doRun(): bool
	{
		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();
		$instance = new self();

		if ($availabilityChecker->isItemsCountsLessThenLimit())
		{
			$allowedAgentsTimeManager = AllowedAgentsTimeManager::getInstance();
			$isAllowedTime = $allowedAgentsTimeManager->isAllowedTime();

			if ($isAllowedTime || $availabilityChecker->isSegmentsInitializationProgress())
			{
				Executor::getInstance()->execute();

				$stepInterval = $instance->getStepInterval();
				$instance->setExecutionPeriod($stepInterval);
			}
			else
			{
				$instance->setExecutionPeriod($instance->getNextDayInterval());
			}
		}
		else
		{
			$instance->setExecutionPeriod(86400);
		}

		return self::PERIODICAL_AGENT_RUN_LATER;
	}

	private function getStepInterval(): ?int
	{
		if (Container::getInstance()->getRepeatSaleAvailabilityChecker()->isSegmentsInitializationProgress())
		{
			return self::MIN_AGENT_INTERVAL;
		}

		$dealsCount = $this->getDealsCount();
		$limit = Executor::getInstance()->getLimit(false);
		$totalSegments = RepeatSaleSegmentController::getInstance()->getTotalCount(['IS_ENABLED' => 'Y']);

		if ($totalSegments <= 0 || $dealsCount <= 0)
		{
			return self::NEXT_DAY_INTERVAL;
		}

		$totalQueueItems = RepeatSaleQueueController::getInstance()->getList(['select' => ['ID']])->count();
		if ($totalQueueItems <= 0)
		{
			return $this->getNextDayInterval();
		}

		$allowedInterval = AllowedAgentsTimeManager::getInstance()->getAllowedIntervalInSeconds();
		$stepCount = ceil($dealsCount / $limit) * $totalSegments * 2; // 2 - for companies and contacts

		return max(ceil($allowedInterval / $stepCount), self::MIN_AGENT_INTERVAL); // 30 seconds is the minimum step interval
	}

	private function getDealsCount(): int
	{
		$cache = Application::getInstance()->getCache();

		if ($cache->initCache(self::CACHE_TTL, self::CACHE_ID, self::CACHE_DIR))
		{
			return (int)$cache->getVars()['dealTotalCount'];
		}

		$cache->startDataCache();
		$count = \CCrmDeal::GetTotalCount();
		$cache->endDataCache(['dealTotalCount' => $count]);

		return $count;
	}

	private function getNextDayInterval(): int
	{
		$allowedTime = AllowedAgentsTimeManager::getInstance()->getAllowedTime();
		$dateTime = (new DateTime())->disableUserTime();

		return $allowedTime->getTimestamp() - $dateTime->getTimestamp();
	}
}

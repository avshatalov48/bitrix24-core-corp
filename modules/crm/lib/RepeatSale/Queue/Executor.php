<?php

namespace Bitrix\Crm\RepeatSale\Queue;

use Bitrix\Crm\Copilot\AiQueueBuffer\Consumer;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\RepeatSale\AgentsManager;
use Bitrix\Crm\RepeatSale\AllowedAgentsTimeManager;
use Bitrix\Crm\RepeatSale\AvailabilityChecker;
use Bitrix\Crm\RepeatSale\Job\Controller\RepeatSaleJobController;
use Bitrix\Crm\RepeatSale\Logger;
use Bitrix\Crm\RepeatSale\Queue\Controller\RepeatSaleQueueController;
use Bitrix\Crm\RepeatSale\Queue\Entity\RepeatSaleQueue;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\SegmentManager;
use Bitrix\Crm\RepeatSale\Service\Context;
use Bitrix\Crm\RepeatSale\Service\Handler\Factory;
use Bitrix\Crm\RepeatSale\Service\Handler\Result;
use Bitrix\Crm\RepeatSale\Widget\StartBanner;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

final class Executor
{
	use Singleton;

	private const ENTITY_ITEMS_LIMIT = 300;
	private const ENTITY_ITEMS_ONLY_CALC_LIMIT = 500;
	private const MAX_RETRY_COUNT = 3;
	private ?RepeatSaleQueueController $controller = null;
	private ?AvailabilityChecker $availabilityChecker = null;

	public function execute(): void
	{
		$availabilityChecker = $this->getAvailabilityChecker();
		if (!$availabilityChecker->isEnabled() || !$availabilityChecker->isItemsCountsLessThenLimit())
		{
			return;
		}

		$itemEntity = $this->getSuitableQueueItemEntity();

		if ($itemEntity === null)
		{
			if (Option::get('crm', 'repeat-sale-wait-only-calc-scheduler', 'N') !== 'Y')
			{
				$this->tryUpdateFlow();
			}

			return;
		}

		$segmentId = $itemEntity['JOB']['SEGMENT_ID'] ?? 0;
		$item = QueueItem::createFromEntity($itemEntity);

		if (
			!$availabilityChecker->isSegmentsInitializationProgress() // first search clients in segment
			&& !$availabilityChecker->isEnablePending()
			&& !$this->checkSegment($segmentId)
		)
		{
			$this->deleteFromQueue($item);

			return;
		}

		$this->markQueueItemAsProgress($item);

		$result = $this->getQueueItemExecutionResult($item, $segmentId);
		if (!$result->isSuccess())
		{
			$this->processErrorQueueItem($item);

			return;
		}

		if ($this->isQueueItemCompleted($result))
		{
			$this->onQueueItemCompleted($item, $result);
			$this->deleteFromQueue($item);

			return;
		}

		$this->updateQueueItemForNextIteration($item, $result);
	}

	private function getController(): RepeatSaleQueueController
	{
		if ($this->controller === null)
		{
			$this->controller = RepeatSaleQueueController::getInstance();
		}

		return $this->controller;
	}

	private function getAvailabilityChecker(): AvailabilityChecker
	{
		if ($this->availabilityChecker === null)
		{
			$this->availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();
		}

		return $this->availabilityChecker;
	}

	private function getSuitableQueueItemEntity(): ?RepeatSaleQueue
	{
		$collection = $this->getController()->getList([
			'select' => ['*', 'JOB.SEGMENT_ID', 'JOB.SEGMENT.ENTITY_CATEGORY_ID'],
			'order' => ['CREATED_AT' => 'ASC'],
			'limit' => 1,
		]);

		if ($collection->isEmpty())
		{
			return null;
		}

		$item = $collection->current();
		if ($item->getStatus() === Status::Progress->value)
		{
			if ($item->getUpdatedAt() <= (new DateTime())->add('-1 hours'))
			{
				$this->processErrorQueueItem(QueueItem::createFromEntity($item));

				return $this->getSuitableQueueItemEntity();
			}

			return null;
		}

		return $item;
	}

	private function getQueueItemExecutionResult(QueueItem $item, int $segmentId): Result
	{
		$context = (new Context())
			->setJobId($item->getJobId())
			->setSegmentId($segmentId)
		;

		$handler = Factory::getInstance()->getHandler(
			$item->getHandlerType(),
			$item->getParams()['segmentCode'] ?? null,
			$context
		);

		if ($handler === null)
		{
			return (new Result())->addError(new Error('Unknown handler type id: ' . $item->getHandlerTypeId()));
		}

		// @todo for ConfigurableHandler will be set segmentId
		try
		{
			$limit = $this->getLimit($item->isOnlyCalc());
			$result = $handler
				->setEntityTypeId($item->getLastEntityTypeId() ?? \CCrmOwnerType::Contact)
				->setLastEntityId($item->getLastItemId())
				->setLastAssignmentId($item->getLastAssignmentId())
				->setLimit($limit)
				->setIsOnlyCalc($item->isOnlyCalc())
				->execute()
			;

			if (AllowedAgentsTimeManager::getInstance()->isUseTimeLimit())
			{
				Consumer::getInstance()->execute();
			}
		}
		catch (\Exception $e)
		{
			(new Logger())->error('Failed to execute queue item', [
				'segmentId' => $segmentId,
				'message' => $e->getMessage(),
			]);

			return (new Result())->addError(new Error('Failed to execute queue item: ' . $e->getMessage()));
		}

		return $result;
	}

	public function getLimit(bool $isOnlyCalc): int
	{
		if ($isOnlyCalc)
		{
			return \COption::GetOptionInt('crm', 'repeat_sale_limit_items_executor_calc', self::ENTITY_ITEMS_ONLY_CALC_LIMIT);
		}

		return \COption::GetOptionInt('crm', 'repeat_sale_limit_items_executor', self::ENTITY_ITEMS_LIMIT);
	}

	private function processErrorQueueItem(QueueItem $item): void
	{
		$this->deleteFromQueue($item);

		$loggerContext = [
			'jobId' => $item->getJobId(),
			'params' => $item->getParams(),
		];

		$logger = new Logger();

		if ($item->getRetryCount() > self::MAX_RETRY_COUNT)
		{
			$logger->error(
				'The number of attempts for the queue item has been exceeded. JobId: ' . $item->getJobId(),
				$loggerContext,
			);

			$this->sendItemExecuteAnalytics('rs-delete-failed-queue-item', $item);

			return;
		}

		$logger->error(
			'An attempt to process an item from the queue failed. JobId: ' . $item->getJobId(),
			$loggerContext,
		);

		$item
			->setStatus(Status::Waiting)
			->incRetryCount()
		;

		$this->addToQueue($item);

		$this->sendItemExecuteAnalytics('rs-fail-queue-item', $item);
	}

	private function checkSegment(int $segmentId): bool
	{
		if ($segmentId <= 0)
		{
			return false;
		}

		$segment = RepeatSaleSegmentController::getInstance()->getById($segmentId);

		return $segment?->getIsEnabled();
	}

	private function markQueueItemAsProgress(QueueItem $item): void
	{
		$item->setStatus(Status::Progress);

		$this->getController()->update($item->getId(), $item);
	}

	private function isQueueItemCompleted(Result $result): bool
	{
		$segmentData = $result->getSegmentData();

		if ($segmentData === null)
		{
			return false;
		}

		return (
			$segmentData->isLastDataForEntityTypeId()
			&& $segmentData->getEntityTypeId() === \CCrmOwnerType::Company
		);
	}

	private function onQueueItemCompleted(QueueItem $queueItem, Result $result): void
	{
		$itemsCount = $this->getItemsCount($queueItem, $result);

		$jobId = $queueItem->getJobId();
		$job = RepeatSaleJobController::getInstance()->getById($jobId);

		RepeatSaleSegmentController::getInstance()->updateClientCoverage($job?->getSegmentId(), $itemsCount);

		if ($itemsCount > 0)
		{
			$this->tryUpdateFlow(true, $itemsCount);
		}

		$this->sendItemExecuteAnalytics('rs-success-queue-item', $queueItem);
	}

	private function tryUpdateFlow(bool $isDropStartBannerStatistics = false, ?int $itemsCount = null): void
	{
		$availabilityChecker = $this->getAvailabilityChecker();

		if ($availabilityChecker->isSegmentsInitializationProgress())
		{
			(new SegmentManager())->updateFlowToPending();
		}

		if ($availabilityChecker->isEnablePending())
		{
			/**
			 * We'll repeat the search in a week
			 */
			(new Logger())->debug('The only calc search will be repeated in a week', []);

			AgentsManager::getInstance()->addOnlyCalcSchedulerAgent();

			if ($isDropStartBannerStatistics)
			{
				(new StartBanner())->dropShowedStatisticsData();
			}

			if ($itemsCount !== null)
			{
				$this->sendItemsCountAnalytics($itemsCount);
			}
		}
	}

	private function sendItemsCountAnalytics(int $count): void
	{
		$event = new AnalyticsEvent('banner_prepare', Dictionary::TOOL_CRM, Dictionary::CATEGORY_BANNERS);

		try
		{
			$event
				->setP1('count-deals-' . $count)
				->send()
			;
		}
		catch (\Exception $e)
		{

		}
	}

	private function sendItemExecuteAnalytics(string $eventName, QueueItem $item): void
	{
		$event = new AnalyticsEvent($eventName, Dictionary::TOOL_CRM, Dictionary::CATEGORY_SYSTEM_INFORM);

		$timestamp = $item->getParams()['date'] ?? null;
		$date = $timestamp ? Date::createFromTimestamp($timestamp) : null;

		try
		{
			$event->setType(Dictionary::TYPE_AGENT);
			if ($date)
			{
				$event->setP2($date->format('Y-m-d'));
			}

			$event->send();
		}
		catch (\Exception $e)
		{

		}
	}

	private function deleteFromQueue(QueueItem $item): void
	{
		$this->getController()->delete($item->getId());
	}

	private function addToQueue(QueueItem $item): void
	{
		$this->getController()->add($item);
	}

	private function updateQueueItemForNextIteration(QueueItem $item, Result $result): void
	{
		$item->setStatus(Status::Waiting);

		$segmentData = $result->getSegmentData();

		if ($segmentData->isLastDataForEntityTypeId())
		{
			$item
				->setLastItemId(null)
				->setLastEntityTypeId(\CCrmOwnerType::Company)
			;
		}
		else
		{
			$item
				->setLastEntityTypeId($segmentData->getEntityTypeId())
				->setLastItemId($segmentData->getLastEntityId())
				->setLastAssignmentId($segmentData->getLastAssignmentId())
			;
		}

		$item->setItemsCount($this->getItemsCount($item, $result));

		$this->getController()->update($item->getId(), $item);
	}

	private function getItemsCount(QueueItem $item, Result $result): int
	{
		return $item->getItemsCount() + $result->getSegmentData()?->getItemsCount();
	}
}

<?php

namespace Bitrix\Crm\Copilot\AiQueueBuffer;

use Bitrix\Crm\Copilot\AiQueueBuffer\Controller\AiQueueBufferController;
use Bitrix\Crm\Copilot\AiQueueBuffer\Entity\AiQueueBuffer;
use Bitrix\Crm\Copilot\AiQueueBuffer\Entity\AiQueueBufferItem;
use Bitrix\Crm\Copilot\AiQueueBuffer\Enum\Status;
use Bitrix\Crm\Copilot\Restriction\ExecutionDataManager;
use Bitrix\Crm\Copilot\Restriction\LimitManager;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Result;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Type\Date;
use COption;

final class Consumer
{
	use Singleton;

	private const LIMIT = 5;
	private const MAX_RETRY_COUNT = 5;
	private const MAX_PENDING_RECORDS = 20;

	private ?array $executionData = null;
	private ?AiQueueBufferController $controller = null;

	public function execute(): void
	{
		$this->init();

		if (!$this->canExecute())
		{
			return;
		}

		$items = $this->getItems();

		if ($items->isEmpty())
		{
			return;
		}

		$this->markItemsAsProgress($items);

		$this->processItems($items);
	}

	private function init(): void
	{
		$this->controller = AiQueueBufferController::getInstance();
	}

	private function canExecute(): bool
	{
		if ($this->isQueueHasManyPendingRecords())
		{
			return false;
		}

		$lastExecutionMonth = $this->getLastExecutionDate()->format('n');
		$currentMonth = (new Date())->format('n');

		if ($currentMonth !== $lastExecutionMonth)
		{
			$this->startNewMonth();

			return true;
		}

		$isPeriodLimitExceeded = LimitManager::getInstance()->isPeriodLimitExceeded();

		return !$isPeriodLimitExceeded;
	}

	private function isQueueHasManyPendingRecords(): bool
	{
		$count = (int)QueueTable::query()
			->where('EXECUTION_STATUS', QueueTable::EXECUTION_STATUS_PENDING)
			->queryCountTotal()
		;

		return $count >= self::MAX_PENDING_RECORDS;
	}

	private function getItems(): Entity\EO_AiQueueBuffer_Collection
	{
		return $this->controller->getList([
			'limit' => $this->getLimit(),
		]);
	}

	private function markItemsAsProgress(Entity\EO_AiQueueBuffer_Collection $items): void
	{
		/**
		 * @var $item AiQueueBuffer
		 */
		foreach ($items as $item)
		{
			if ($item->getStatus() !== Status::Progress->value)
			{
				$item->setStatus(Status::Progress->value);
			}
		}

		$items->save();
	}

	private function processItems(Entity\EO_AiQueueBuffer_Collection $items): Result
	{
		$successCount = 0;

		/**
		 * @var $item AiQueueBuffer
		 */
		foreach ($items as $item)
		{
			if ($item->getRetryCount() > self::MAX_RETRY_COUNT)
			{
				continue;
			}

			$provider = Factory::getProvider($item->getProviderId());
			if ($provider === null)
			{
				continue;
			}

			$result = $provider->process($item->getProviderData());
			if ($result->isSuccess())
			{
				$successCount++;
			}
			elseif ($this->isNeedMoveItemToEndOfQueue($result->getError()))
			{
				$this->moveItemToEndOfQueue($item);
			}
		}

		$this->controller->delete($items->getIdList());

		return (new Result())->setData(['successCount' => $successCount]);
	}

	private function isNeedMoveItemToEndOfQueue(?Error $error = null): bool
	{
		return (
			!in_array(
				$error?->getCode(),
				[
					ErrorCode::JOB_ALREADY_EXISTS,
					ErrorCode::NOT_SUITABLE_TARGET,
				],
			true
			)
		);
	}

	private function moveItemToEndOfQueue(AiQueueBuffer $item): void
	{
		$this->controller->delete([$item->getId()]);

		$aiQueueBufferItem = AiQueueBufferItem::createFromEntity($item);
		$aiQueueBufferItem->incrementRetryCount();

		$this->controller->add($aiQueueBufferItem);
	}

	private function getLimit(): int
	{
		return COption::GetOptionInt(
			'crm',
			'ai_queue_buffer_execute_limit',
			self::LIMIT
		);
	}

	private function getLastExecutionDate(): Date
	{
		return Date::createFromTimestamp($this->getCurrentPeriodTimestamp());
	}

	private function startNewMonth(): void
	{
		$this->truncateQueue();
		$this->saveNewPeriodTimestamp();

		ExecutionDataManager::getInstance()->clearExecutionData();
	}

	private function truncateQueue(): void
	{
		$this->controller->deleteAll();
	}

	private function getCurrentPeriodTimestamp(): int
	{
		return COption::GetOptionInt(
			'crm',
			'ai_queue_buffer_execution_period_date',
			(new Date())->getTimestamp()
		);
	}

	private function saveNewPeriodTimestamp(): void
	{
		COption::SetOptionInt(
			'crm',
			'ai_queue_buffer_execution_period_date',
			(new Date())->getTimestamp()
		);
	}
}

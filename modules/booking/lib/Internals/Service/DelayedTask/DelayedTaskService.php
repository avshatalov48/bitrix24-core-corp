<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask;

use Bitrix\Booking\Internals\Repository\ORM\DelayedTaskRepository;
use Bitrix\Booking\Internals\Service\DelayedTask\Data\DataInterface;
use Bitrix\Booking\Internals\Service\DelayedTask\Processor\ResourceCalendarDataChangedProcessor;
use Bitrix\Booking\Internals\Service\Logger\EventLogger;
use Bitrix\Booking\Internals\Service\Logger\EventTypeEnum;
use Bitrix\Booking\Internals\Service\Logger\LogLevelEnum;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;

class DelayedTaskService
{
	private const LOCK_KEY_TPL = 'booking.dt_proc_%s_%s';
	private const PROCESSING_BATCH = 10;
	private const LOCK_TIMEOUT_SEC = 10;
	private Connection $connection;
	private EventLogger $logger;

	public function __construct(
		private readonly DelayedTaskRepository $delayedTaskRepository,
	)
	{
		$this->connection = Application::getConnection();
		$this->logger = new EventLogger();
	}

	public function create(
		string $code,
		DataInterface $data,
	): void
	{
		$lockKey = $this->getLockKey($code, $data->getType());
		if (!$this->connection->lock($lockKey, self::LOCK_TIMEOUT_SEC))
		{
			return;
		}

		try
		{
			$oldDelayedTask = $this->delayedTaskRepository->getForUpdate(
				$code,
				$data->getType(),
				DelayedTaskStatus::Pending,
			);
			if ($oldDelayedTask)
			{
				$this->delayedTaskRepository->updateData($oldDelayedTask->getId(), $data);

				return;
			}
		}
		finally
		{
			$this->connection->unlock($lockKey);
		}

		$this->delayedTaskRepository->create($code, $data);
	}

	public function processPending(): void
	{
		$delayedTasks = $this->delayedTaskRepository->getPending(self::PROCESSING_BATCH);

		/** @var DelayedTask $delayedTask */
		foreach ($delayedTasks as $delayedTask)
		{
			$key = $this->getLockKey($delayedTask->getCode(), $delayedTask->getType());
			if (!$this->connection->lock($key, self::LOCK_TIMEOUT_SEC))
			{
				continue;
			}

			try
			{
				$this->delayedTaskRepository->setProcessing($delayedTask->getId());
				$delayedTask = $this->delayedTaskRepository->getById($delayedTask->getId());
			}
			finally
			{
				$this->connection->unlock($key);
			}

			if ($delayedTask->getStatus() !== DelayedTaskStatus::Processing)
			{
				continue;
			}

			try
			{
				$this->process($delayedTask);
			}
			catch (\Throwable $e)
			{
				$this->delayedTaskRepository->setStatus([$delayedTask->getId()], DelayedTaskStatus::Error);
				$this->logger->log(LogLevelEnum::Error, $e->getMessage(), EventTypeEnum::DelayedTask);
			}
		}
	}

	public function process(DelayedTask $delayedTask): void
	{
		$processorClass = $this->getProcessorClass($delayedTask->getType());
		(new $processorClass($delayedTask->getData()))();

		$this->delayedTaskRepository->setStatus([$delayedTask->getId()], DelayedTaskStatus::Processed);
	}

	private function getLockKey(string $code, DelayedTaskType $type): string
	{
		return sprintf(self::LOCK_KEY_TPL, $code, $type->value);
	}

	private function getProcessorClass(DelayedTaskType $type): string
	{
		return match ($type)
		{
			DelayedTaskType::ResourceCalendarDataChanged => ResourceCalendarDataChangedProcessor::class,
		};
	}
}

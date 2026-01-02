<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Queue;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Onboarding\Internal\Model\JobCountTable;
use Bitrix\Tasks\Onboarding\Internal\Model\QueueItem;
use Bitrix\Tasks\Onboarding\Internal\Model\QueueItemCollection;
use Bitrix\Tasks\Onboarding\Internal\Model\QueueTable;
use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\Transfer\QueueJobCollection;
use Throwable;

final class Queue implements QueueInterface
{
	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * @throws SystemException
	 */
	public function __wakeup()
	{
		throw new SystemException('Forbidden wakeup');
	}

	public static function getInstance(): self
	{
		return new self();
	}

	/**
	 * @throws SqlException
	 */
	public function save(QueueJobCollection $jobs): void
	{
		$jobItemCollection = new QueueItemCollection();
		$jobCountItems = [];

		foreach ($jobs as $job)
		{
			$jobItem = (new QueueItem())
				->setTaskId($job->taskId)
				->setUserId($job->userId)
				->setCode($job->code)
				->setType($job->type->value)
				->setNextExecution($job->nextExecution)
				->setCreatedDate($job->createdDate)
				->setIsProcessed($job->isProcessed);

			$jobItemCollection->add($jobItem);

			if ($job->isCountable && $job->jobCount === null)
			{
				$jobCountItems[] = $job->code;
			}
		}

		$connection = Application::getConnection();

		$connection->startTransaction();

		$jobResult = $jobItemCollection->save(true);

		if (!$jobResult->isSuccess())
		{
			$connection->rollbackTransaction();

			throw new SqlException($jobResult->getError()?->getMessage());
		}

		$counterResult = $this->saveCounters($jobCountItems);

		if (!$counterResult->isSuccess())
		{
			$connection->rollbackTransaction();

			throw new SqlException($counterResult->getError()?->getMessage());
		}

		$connection->commitTransaction();
	}

	/**
	 * @throws ArgumentException
	 */
	public function clearById(int ...$ids): void
	{
		if (empty($ids))
		{
			return;
		}

		QueueTable::deleteByFilter(['@ID' => $ids]);
	}

	public function clearByCode(string ...$codes): void
	{
		if (empty($codes))
		{
			return;
		}

		QueueTable::deleteByFilter(['@CODE' => $codes]);
	}

	public function clearByTaskAndUserId(int $taskId = 0, int $userId = 0): void
	{
		$filter = [];

		if ($userId > 0)
		{
			$filter['=USER_ID'] = $userId;
		}

		if ($taskId > 0)
		{
			$filter['=TASK_ID'] = $taskId;
		}

		if ($filter === [])
		{
			return;
		}

		QueueTable::deleteByFilter($filter);
	}

	/**
	 * @param Type[] $types
	 * @throws ArgumentException
	 */
	public function clearByUserJobParams(array $types, int $userId, int $taskId = 0): void
	{
		$filter = [];

		if ($userId > 0)
		{
			$filter['=USER_ID'] = $userId;
		}

		if ($taskId > 0)
		{
			$filter['=TASK_ID'] = $taskId;
		}

		if (!empty($types))
		{
			$filter['@TYPE'] = array_map(static fn(Type $type): string => $type->value, $types);
		}

		if (empty($filter))
		{
			return;
		}

		QueueTable::deleteByFilter($filter);
	}

	/**
	 * @throws SystemException
	 */
	public function process(int ...$ids): void
	{
		if (empty($ids))
		{
			return;
		}

		QueueTable::updateMulti($ids, ['IS_PROCESSED' => true, 'PROCESSED_DATE' => new DateTime()]);
	}

	/**
	 * @throws SystemException
	 */
	public function unprocess(int ...$ids): void
	{
		if (empty($ids))
		{
			return;
		}

		QueueTable::updateMulti($ids, ['IS_PROCESSED' => false]);
	}

	private function saveCounters(array $jobCodes): Result
	{
		$result = new Result();

		if (empty($jobCodes))
		{
			return $result;
		}

		$connection = Application::getConnection();

		$helper = $connection->getSqlHelper();

		$fields = "(CODE, JOB_COUNT)";
		$values = [];
		foreach ($jobCodes as $jobCode)
		{
			$jobCode = $helper->forSql($jobCode);
			$values[] = "('{$jobCode}', 0)";
		}

		$values = implode(', ', $values);

		$sql = $helper->getInsertIgnore(JobCountTable::getTableName(), " {$fields}", " VALUES {$values}");

		try
		{
			$connection->query($sql);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		JobCountTable::cleanCache();

		return $result;
	}
}
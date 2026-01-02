<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Queue\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Validation\ValidationService;
use Bitrix\Tasks\Integration\Calendar\Calendar;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Onboarding\DI\OnboardingContainer;
use Bitrix\Tasks\Onboarding\Internal\Factory\JobCodeFactory;
use Bitrix\Tasks\Onboarding\Internal\Config\JobLimit;
use Bitrix\Tasks\Onboarding\Internal\Config\JobOffset;
use Bitrix\Tasks\Onboarding\Internal\Queue\Queue;
use Bitrix\Tasks\Onboarding\Internal\Queue\QueueInterface;
use Bitrix\Tasks\Onboarding\Internal\Queue\QueueServiceInterface;
use Bitrix\Tasks\Onboarding\Transfer\JobCodes;
use Bitrix\Tasks\Onboarding\Transfer\Pair;
use Bitrix\Tasks\Onboarding\Transfer\CommandModel;
use Bitrix\Tasks\Onboarding\Transfer\CommandModelCollection;
use Bitrix\Tasks\Onboarding\Transfer\JobIds;
use Bitrix\Tasks\Onboarding\Transfer\QueueJob;
use Bitrix\Tasks\Onboarding\Transfer\QueueJobCollection;
use Bitrix\Tasks\Onboarding\Transfer\UserJob;
use CTimeZone;
use Throwable;

class QueueService implements QueueServiceInterface
{
	protected OnboardingContainer $container;
	protected QueueInterface $queue;
	protected ValidationService $validationService;

	public function __construct()
	{
		$this->init();
	}

	public function add(CommandModelCollection $commandModels): Result
	{
		$validationResult = $this->container->getValidationService()->validate($commandModels);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		$queueJobs = new QueueJobCollection();

		foreach ($commandModels as $commandModel)
		{
			$code = JobCodeFactory::createCodeByCommandModel($commandModel)->code;

			$count = null;
			if ($commandModel->isCountable)
			{
				$count = $this->container->getCounterRepository()->getByCode($code);
				if ($count >= JobLimit::get($commandModel->type))
				{
					continue;
				}
			}

			$executionTime = $this->getExecutionTime($commandModel);
			if ($executionTime === null)
			{
				continue;
			}

			$job = new QueueJob(
				type: $commandModel->type,
				taskId: $commandModel->taskId,
				userId: $commandModel->userId,
				code: $code,
				nextExecution: $executionTime,
				jobCount: $count,
				isCountable: $commandModel->isCountable
			);

			$queueJobs->add($job);
		}

		$result = new Result();

		if ($queueJobs->isEmpty())
		{
			return $result;
		}

		try
		{
			$this->queue->save($queueJobs);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		return $result;
	}

	public function deleteByIds(JobIds $jobIds): Result
	{
		$validationResult = $this->container->getValidationService()->validate($jobIds);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		$result = new Result();

		try
		{
			$this->queue->clearById(...$jobIds->jobIds);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		return $result;
	}

	public function deleteByCodes(JobCodes $jobCodes): Result
	{
		$validationResult = $this->container->getValidationService()->validate($jobCodes);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		$result = new Result();

		try
		{
			$this->queue->clearByCode(...$jobCodes->codes);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		return $result;
	}

	public function deleteByPair(Pair $pair): Result
	{
		$validationResult = $this->container->getValidationService()->validate($pair);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		$result = new Result();

		try
		{
			$this->queue->clearByTaskAndUserId($pair->taskId, $pair->userId);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		return $result;
	}

	public function deleteByUserJob(UserJob $userJob): Result
	{
		$validationResult = $this->container->getValidationService()->validate($userJob);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		$result = new Result();

		try
		{
			$this->queue->clearByUserJobParams($userJob->types, $userJob->userId, $userJob->taskId);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		return $result;
	}

	public function markAsProcessing(JobIds $jobIds): Result
	{
		$validationResult = $this->container->getValidationService()->validate($jobIds);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		$result = new Result();

		try
		{
			$this->queue->process(...$jobIds->jobIds);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		return $result;
	}

	public function unmarkAsProcessing(JobIds $jobIds): Result
	{
		$validationResult = $this->container->getValidationService()->validate($jobIds);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		$result = new Result();

		try
		{
			$this->queue->unprocess(...$jobIds->jobIds);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		return $result;
	}

	protected function getExecutionTime(CommandModel $commandModel): ?DateTime
	{
		$task = TaskRegistry::getInstance()->getObject($commandModel->taskId);
		if ($task === null)
		{
			return null;
		}

		$createdDate = $task->getCreatedDate();
		if ($createdDate === null)
		{
			return null;
		}

		$offset = JobOffset::get($commandModel->type);

		$closestDate =  Calendar::createFromPortalSchedule()->getClosestDate(
			(new DateTime())->toUserTime(),
			$offset,
			false,
			true,
		);

		$userTimeOffset = CTimeZone::GetOffset($commandModel->userId);
		$closestDate->add("-{$userTimeOffset} seconds");

		return $closestDate;
	}

	protected function init(): void
	{
		$this->container = OnboardingContainer::getInstance();
		$this->queue = Queue::getInstance();
	}
}
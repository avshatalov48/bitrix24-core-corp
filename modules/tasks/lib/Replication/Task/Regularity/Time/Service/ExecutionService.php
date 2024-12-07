<?php

namespace Bitrix\Tasks\Replication\Task\Regularity\Time\Service;

use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Replication\Template\AbstractParameter;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularityException;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularParameterException;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularTimeException;
use Bitrix\Tasks\Replication\Task\Regularity\Parameter\RegularParameter;
use Bitrix\Tasks\Replication\Template\Time\Enum\RepeatType;
use Bitrix\Tasks\Replication\Template\Time\Factory\ExecutionTimeFactory;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;
use Exception;

class ExecutionService
{
	private const MAX_ITERATION_COUNT = 100;
	private AbstractParameter $parameter;

	public function __construct(private RepositoryInterface $repository)
	{

	}

	/**
	 * @throws RegularParameterException
	 */
	private function getTaskStartTime(?DateTime $lastStartTime = null): DateTime
	{
		$this->parameter = new RegularParameter($this->repository);

		$timeZoneOffsetTS = $this->parameter->get('TIMEZONE_OFFSET');
		$creatorTimeZoneOffsetTS = $timeZoneOffsetTS ?? User::getTimeZoneOffset($this->repository->getEntity()->getCreatedBy());

		// prepare time to be forced to
		$regularityTimeTS = strtotime($this->parameter->get('TIME'));
		$creatorPreferredTimeTS = UI::parseTimeAmount(
			date("H:i", $regularityTimeTS - $creatorTimeZoneOffsetTS), 'HH:MI'
		);

		// prepare base time
		$baseExecutionTimeTS = time();

		if ($lastStartTime) // agent were found and had legal next_time
		{
			$lastExecutionTimeTS = $lastStartTime->getTimestamp();
			if ($lastExecutionTimeTS > 0)
			{
				// $agentTime is in current user`s time, but we want server time here
				$baseExecutionTimeTS = $lastExecutionTimeTS;
			}
		}

		$regularityStartTimeTS = 0;
		$regularityStartTime = $this->parameter->get('START_DATE');
		if ($regularityStartTime)
		{
			$regularityStartTimeTS = MakeTimeStamp($regularityStartTime);
			$regularityStartTimeTS -= $creatorTimeZoneOffsetTS;
		}

		$baseExecutionTimeTS = max($baseExecutionTimeTS, $regularityStartTimeTS);

		$startTimeTS = ExecutionTimeFactory::getNextExecutionTime(
			$baseExecutionTimeTS,
			$creatorPreferredTimeTS,
			$this->parameter
		);

		try
		{
			$startTime = DateTime::createFromTimestamp($startTimeTS);
		}
		catch (ObjectException $exception)
		{
			throw new RegularParameterException($exception->getMessage());
		}

		return $startTime;
	}

	/**
	 * @throws RegularityException
	 */
	public function getNextRegularityDateTime(?DateTime $lastStartTime = null): DateTime
	{
		$iterationCount = 0;
		$lastExecutionTimeTS = time();

		do
		{
			try
			{
				$startTime = $this->getTaskStartTime($lastStartTime);
			}
			catch (Exception $exception)
			{
				throw new RegularTimeException($exception->getMessage());
			}

			if ($startTime->getTimestamp() <= 0)
			{
				throw new RegularTimeException('Start time equals 0');
			}

			if (($lastExecutionTimeTS >= $startTime->getTimestamp()) || ($iterationCount > static::MAX_ITERATION_COUNT))
			{
				throw new RegularTimeException('Start time loop detected');
			}

			$lastStartTime = clone $startTime;
			$currentUserTimeTS = time();

			$iterationCount++;
		}
		while ($startTime->getTimestamp() < $currentUserTimeTS);

		return DateTime::createFromTimestamp($startTime->getTimestamp());
	}
}
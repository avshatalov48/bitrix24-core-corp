<?php

namespace Bitrix\HumanResources\Service\HcmLink;

use Bitrix\HumanResources\Contract\Repository\HcmLink\JobRepository;

use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\HcmLink\JobStatus;
use Bitrix\HumanResources\Item;
use Bitrix\Main\Type\DateTime;
use CAgent;

class JobKillerService
{
	private const NEXT_EXECUTION_OFFSET = 300;
	private const LIMIT = 50;
	private const TTL = '-2 hour';

	private const MAX_EVENT_COUNT_BEFORE_CANCEL = 2;

	private JobRepository $jobRepository;

	public function __construct(
		?JobRepository $jobRepository = null,
	)
	{
		$this->jobRepository = $jobRepository ?? Container::getHcmLinkJobRepository();
	}

	public function plan(): void
	{
		if (!$this->existsNotFinished())
		{
			return;
		}

		CAgent::AddAgent(
			name: self::getAgentName(),
			module: 'humanresources',
			interval: 300,
			next_exec: \ConvertTimeStamp(
				time() + \CTimeZone::GetOffset() + self::NEXT_EXECUTION_OFFSET, 'FULL',
			),
			existError: false,
		);
	}

	public static function kill(): string
	{
		$instance = (new self());

		return $instance->innerKill();
	}

	private function innerKill(): string
	{
		$jobCollection = $this->jobRepository->listByStatusListAndDate(
			statusList: array_map(
				static fn(\BackedEnum $status) => $status->value, JobStatus::getNotFinished(),
			),
			date: self::makeTtlDate(),
			limit: self::LIMIT,
		);

		$jobsToResend = $jobCollection->filter(
			static fn(Item\HcmLink\Job $job) => $job->eventCount < self::MAX_EVENT_COUNT_BEFORE_CANCEL,
		);

		$jobsToCancel = $jobCollection->filter(
			static fn(Item\HcmLink\Job $job) => $job->eventCount >= self::MAX_EVENT_COUNT_BEFORE_CANCEL,
		);

		$jobIdsToCancel = $jobsToCancel->map(
			static fn(Item\HcmLink\Job $job) => $job->id,
		);

		try
		{
			$this->jobRepository->updateStatusByIds($jobIdsToCancel, JobStatus::CANCELED);
		}
		catch (UpdateFailedException $exception)
		{
			return self::continue();
		}

		try
		{
			$this->resendJobCollection($jobsToResend);
		}
		catch (UpdateFailedException $exception)
		{
			return self::continue();
		}

		return self::existsNotFinished() ? self::continue() : self::finish();
	}

	private static function continue(): string
	{
		return self::getAgentName();
	}

	private static function finish(): string
	{
		return '';
	}

	private function resendJobCollection(Item\Collection\HcmLink\JobCollection $jobCollection): void
	{
		$jobIds = $jobCollection->map(
			static fn(Item\HcmLink\Job $job) => $job->id,
		);

		$this->jobRepository->increaseEventCountByIds($jobIds);

		$jobService = Container::getHcmLinkJobService();
		foreach ($jobCollection as $job)
		{
			$jobService->sendJob($job);
		}
	}

	private function existsNotFinished(): bool
	{
		$collection = $this->jobRepository->listByStatusListAndDate(
			statusList: JobStatus::getNotFinished(),
			limit: 1,
		);

		return !$collection->empty();
	}

	private function makeTtlDate(): DateTime
	{
		return (new DateTime())->add(self::TTL);
	}

	private static function getAgentName(): string
	{
		return sprintf('%s::%s();', self::class, 'kill');
	}
}
<?php

namespace Bitrix\HumanResources\Service\HcmLink;

use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Event\HcmLink\JobDoneEvent;
use Bitrix\HumanResources\Event\HcmLink\JobEvent;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item\HcmLink\Job;
use Bitrix\HumanResources\Result\Service\HcmLink\JobServiceResult;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\HcmLink\JobStatus;
use Bitrix\HumanResources\Type\HcmLink\JobType;
use Bitrix\HumanResources\Type\HcmLink\RestEventType;
use Bitrix\Main\Error;
use Bitrix\Main\EventManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Result;

class JobService implements Contract\Service\HcmLink\JobService
{
	private const MODULE_ID = 'humanresources';

	private Contract\Repository\HcmLink\JobRepository $jobRepository;
	protected Contract\Repository\HcmLink\CompanyRepository $companyRepository;

	public function __construct(
		?Contract\Repository\HcmLink\JobRepository $jobRepository = null,
		?Contract\Repository\HcmLink\CompanyRepository $companyRepository = null,
	)
	{
		$this->jobRepository = $jobRepository ?? new \Bitrix\HumanResources\Repository\HcmLink\JobRepository();
		$this->companyRepository = $companyRepository ?? new \Bitrix\HumanResources\Repository\HcmLink\CompanyRepository();
	}

	public function update(Job $job): ?Job
	{
		try
		{
			$this->jobRepository->update($job);
		}
		catch (UpdateFailedException $exception)
		{
			return null;
		}

		$this->fireEvents($job);

		return $job;
	}

	public function requestEmployeeList(int $companyId): Result|JobServiceResult
	{
		$company = $this->companyRepository->getById($companyId);
		if ($company === null)
		{
			return (new Result())->addError(new Error('Company not found'));
		}

		$data = [
			'company' => $company->code,
			'date' =>  (new DateTime())->format(\DateTimeInterface::ATOM),
		];

		try
		{
			$job = $this->addJob(
				new Job(
					companyId: $company->id,
					type: JobType::USER_LIST,
					outputData: $data
				)
			);
		}
		catch (CreationFailedException $exception)
		{
			return (new Result())->addError(new Error('Failed to create job'));
		}

		$this->riseRestEvents(RestEventType::onEmployeeListRequested, $job);

		return new JobServiceResult($job);
	}

	/**
	 * @param int $companyId \Bitrix\HumanResources\Item\HcmLink\Company::id
	 * @param string[] $employeeUids
	 * @param string[] $fieldUids
	 *
	 * @return JobServiceResult|Result
	 */
	public function requestFieldValue(
		int $companyId,
		array $employeeUids = [],
		array $fieldUids = [],
	): JobServiceResult|Result
	{
		$company = $this->companyRepository->getById($companyId);
		if($company === null)
		{
			return (new Result())->addError(new Error('Company not found'));
		}

		if (empty($employeeUids))
		{
			return (new Result())->addError(new Error('Employee ids is empty'));
		}

		if (empty($fieldUids))
		{
			return (new Result())->addError(new Error('Field ids is empty'));
		}

		$data = [
			'company' => $company->code,
			'employees' => $employeeUids,
			'fields' => $fieldUids,
			'date' =>  (new DateTime())->format(\DateTimeInterface::ATOM),
		];

		try
		{
			$job = $this->addJob(
				new Job(
					companyId: $company->id,
					type: JobType::FIELD_VALUES,
					outputData: $data
				)
			);
		}
		catch (CreationFailedException $exception)
		{
			return (new Result())->addError(new Error('Failed to create job'));
		}

		$this->riseRestEvents(RestEventType::onFieldValueRequested, $job);

		return new JobServiceResult($job);
	}

	protected function fireEvents(Job $job):void
	{
		(new JobEvent($job))->send();
		if ($job->status === JobStatus::DONE)
		{
			(new JobDoneEvent($job))->send();
		}
	}

	protected function riseRestEvents(RestEventType $event, Job $job): void
	{
		$eventManager = EventManager::getInstance();

		foreach ($eventManager->findEventHandlers(self::MODULE_ID, $event->name) as $event)
		{
			ExecuteModuleEventEx($event, ['jobId' => $job->id, ...$job->outputData]);
		}
	}

	public function completeMapping(int $companyId): Result|JobServiceResult
	{
		$company = $this->companyRepository->getById($companyId);
		if(!$company)
		{
			return (new Result())->addError(new Error('Company not found'));
		}

		$data = [
			'company' => $company->code,
			'date' =>  (new DateTime())->format(\DateTimeInterface::ATOM),
		];

		try
		{
			$job = $this->addJob(
				new Job(
					companyId: $company->id,
					type: JobType::COMPLETE_MAPPING,
					outputData: $data
				)
			);
		}
		catch (CreationFailedException $exception)
		{
			return (new Result())->addError(new Error('Failed to create job'));
		}

		$this->riseRestEvents(RestEventType::onEmployeeListMapped, $job);

		return new JobServiceResult($job);
	}

	/**
	 * @throws CreationFailedException
	 */
	private function addJob(Job $job): Job
	{
		$job = $this->jobRepository->add($job);
		Container::getHcmLinkJobKillerService()->plan();

		return $job;
	}
}

<?php

namespace Bitrix\HumanResources\Repository\HcmLink;

use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\HcmLink\Job;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Model\HcmLink\JobTable;
use Bitrix\HumanResources\Type\HcmLink\JobStatus;
use Bitrix\HumanResources\Type\HcmLink\JobType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

class JobRepository implements Contract\Repository\HcmLink\JobRepository
{
	/** @throws CreationFailedException */
	public function add(Item\HcmLink\Job $job): Item\HcmLink\Job
	{
		$model = $this->fillModelFromItem($job);
		$saveResult = $model->save();
		if ($saveResult->isSuccess() === false)
		{
			throw new CreationFailedException();
		}

		$job->id = (int)$saveResult->getId();

		return $job;
	}

	/**
	 * @param Job $job
	 * @return Job
	 * @throws UpdateFailedException
	 */
	public function update(Item\HcmLink\Job $job): Item\HcmLink\Job
	{
		$model = JobTable::getById($job->id)->fetchObject();
		if (!$model)
		{
			throw (new UpdateFailedException())
				->addError(new Error("Job #{$job->id} not found"))
			;
		}

		if ($job->status !== null)
		{
			$model->setStatus($job->status->value);
		}
		if ($job->done !== null)
		{
			$model->setProgressReceived($job->done);
		}
		if ($job->total !== null)
		{
			$model->setProgressTotal($job->total);
		}
		if (!empty($job->inputData))
		{
			$model->setInputData($model->getInputData() + $job->inputData);
		}

		if ($job->status->isFinished())
		{
			$model->setFinishedAt(new DateTime());
		}
		else
		{
			$model->setUpdatedAt(new DateTime());
		}

		$saveResult = $model->save();
		if ($saveResult->isSuccess() === false)
		{
			throw (new UpdateFailedException())
				->setErrors($saveResult->getErrorCollection())
			;
		}

		return $this->getItemFromModel($model);
	}

	protected function getItemFromModel(Model\HcmLink\Job $model): Item\HcmLink\Job
	{
		return new Item\HcmLink\Job(
			companyId: $model->getCompanyId(),
			type: JobType::tryFrom($model->getType()) ?? JobType::UNKNOWN,
			status: JobStatus::tryFrom($model->getStatus()) ?? JobStatus::UNKNOWN,
			done: $model->getProgressReceived(),
			total: $model->getProgressTotal(),
			createdAt: $model->getCreatedAt(),
			updatedAt: $model->getUpdatedAt(),
			finishedAt: $model->getFinishedAt(),
			inputData: $model->getInputData(),
			outputData: $model->getOutputData(),
			id: $model->hasId() ? $model->getId() : null,
		);
	}

	protected function getItemCollectionFromModelCollection(
		Model\HcmLink\JobCollection $modelCollection,
	): Item\Collection\HcmLink\JobCollection
	{
		$itemCollection = new Item\Collection\HcmLink\JobCollection();

		foreach ($modelCollection->getAll() as $model)
		{
			$itemCollection->add($this->getItemFromModel($model));
		}

		return $itemCollection;
	}

	protected function fillModelFromItem(
		Item\HcmLink\Job  $item,
		?Model\HcmLink\Job $model = null,
	): Model\HcmLink\Job
	{
		$model = $model ?? JobTable::createObject(true);
		$model
			->setCompanyId($item->companyId)
			->setType($item->type->value)
			->setStatus($item->status->value)
			->setProgressReceived($item->done)
			->setProgressTotal($item->total)
			->setInputData($item->inputData)
			->setOutputData($item->outputData)
		;

		if ($item->createdAt)
		{
			$model->setCreatedAt($item->createdAt);
		}
		if ($item->updatedAt)
		{
			$model->setUpdatedAt($item->updatedAt);
		}
		if ($item->finishedAt)
		{
			$model->setFinishedAt($item->finishedAt);
		}

		return $model;
	}

	public function getById(int $jobId): ?Item\HcmLink\Job
	{
		$model = JobTable::query()
			->where('ID', $jobId)
			->fetchObject()
		;

		return $model ? $this->getItemFromModel($model) : null;
	}

	public function checkIsDone(int $jobId): bool
	{
		$data = JobTable::query()
			->where('ID', $jobId)
			->where('STATUS', JobStatus::DONE)
			->fetch()
		;

		return $data !== false;
	}

	public function listIdsByDate(DateTime $olderThanDate, int $limit = 100): array
	{
		$modelCollection = JobTable::query()
			->setSelect(['ID'])
			->where('CREATED_AT', '<', $olderThanDate)
			->setLimit($limit)
			->fetchCollection()
		;

		$ids = [];
		foreach ($modelCollection->getAll() as $model)
		{
			$ids[] = $model->getId();
		}

		return $ids;
	}

	public function listByStatusListAndDate(array $statusList, DateTime $date, int $limit = 100): Item\Collection\HcmLink\JobCollection
	{
		if (empty($statusList))
		{
			return new Item\Collection\HcmLink\JobCollection();
		}

		$query = JobTable::query()
			->setSelect(['*'])
			->whereIn('STATUS', $statusList)
			->where('UPDATED_AT', '<', $date)
		;

		if ($limit)
		{
			$query->setLimit($limit);
		}

		$modelCollection = $query->fetchCollection();

		return $this->getItemCollectionFromModelCollection($modelCollection);
	}

	public function updateStatusByIds(array $ids, JobStatus $status): void
	{
		if (empty($ids))
		{
			return;
		}

		$result = JobTable::updateMulti($ids, ['STATUS' => $status->value]);
		if (!$result->isSuccess())
		{
			throw (new UpdateFailedException())->setErrors($result->getErrorCollection());
		}
	}

	public function removeByIds(array $ids): void
	{
		if (empty($ids))
		{
			return;
		}

		JobTable::deleteByFilter(['@ID' => $ids]);
	}
}

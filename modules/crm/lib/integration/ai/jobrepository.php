<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\Crm\Integration\AI\Dto\FillItemFieldsFromCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Dto\SummarizeCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Dto\TranscribeCallRecordingPayload;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Web\Json;

final class JobRepository
{
	use Singleton;

	/** @var Array<int, Result|null> */
	private array $transcribeCache = [];
	/** @var Array<int, Result|null> */
	private array $summarizeCache = [];
	/** @var Array<string, Result|null> */
	private array $fillCache = [];
	/** @var Array<int, Result|null> */
	private array $fillByIdCache = [];

	private \Bitrix\Main\ORM\EventManager $ormEventManager;
	private array $eventKeys = [
		DataManager::EVENT_ON_AFTER_ADD => true,
		DataManager::EVENT_ON_AFTER_UPDATE => true,
		DataManager::EVENT_ON_AFTER_DELETE => true,
	];

	private function __construct()
	{
		$this->ormEventManager = \Bitrix\Main\ORM\EventManager::getInstance();

		foreach ($this->eventKeys as $eventName => $doesntMatter)
		{
			$this->eventKeys[$eventName] = $this->ormEventManager->addEventHandler(
				QueueTable::class,
				$eventName,
				[$this, 'cleanRuntimeCache'],
			);
		}
	}

	public function __destruct()
	{
		foreach ($this->eventKeys as $eventName => $eventKey)
		{
			if (is_numeric($eventKey))
			{
				$this->ormEventManager->removeEventHandler(
					QueueTable::class,
					$eventName,
					$eventKey,
				);
			}
		}
	}

	/**
	 * @param int $activityId
	 *
	 * @return Result<TranscribeCallRecordingPayload>|null
	 */
	public function getTranscribeCallRecordingResultByActivity(int $activityId): ?Result
	{
		if (array_key_exists($activityId, $this->transcribeCache))
		{
			return is_object($this->transcribeCache[$activityId]) ? clone $this->transcribeCache[$activityId] : null;
		}

		if ($activityId > 0)
		{
			$job = QueueTable::query()
				->setSelect(['*'])
				->where('ENTITY_TYPE_ID', \CCrmOwnerType::Activity)
				->where('ENTITY_ID', $activityId)
				->where('TYPE_ID', TranscribeCallRecording::TYPE_ID)
				->setLimit(1)
				->fetchObject()
			;
		}
		else
		{
			$job = null;
		}

		$result = $job ? TranscribeCallRecording::constructResult($job) : null;

		$this->transcribeCache[$activityId] = is_object($result) ? clone $result : null;

		return $result;
	}

	/**
	 * @return Result<SummarizeCallTranscriptionPayload>|null
	 */
	public function getSummarizeCallTranscriptionResultByActivity(int $activityId): ?Result
	{
		if (array_key_exists($activityId, $this->summarizeCache))
		{
			return is_object($this->summarizeCache[$activityId]) ? clone $this->summarizeCache[$activityId] : null;
		}

		if ($activityId > 0)
		{
			$job = QueueTable::query()
				->setSelect(['*'])
				->where('ENTITY_TYPE_ID', \CCrmOwnerType::Activity)
				->where('ENTITY_ID', $activityId)
				->where('TYPE_ID', SummarizeCallTranscription::TYPE_ID)
				->setLimit(1)
				->fetchObject()
			;
		}
		else
		{
			$job = null;
		}

		$result = $job ? SummarizeCallTranscription::constructResult($job) : null;

		$this->summarizeCache[$activityId] = is_object($result) ? clone $result : null;

		return $result;
	}

	/**
	 * @param ItemIdentifier $targetItem
	 * @param int|null $activityId
	 *
	 * @return Result<FillItemFieldsFromCallTranscriptionPayload>|null
	 */
	public function getFillItemFieldsFromCallTranscriptionResult(
		ItemIdentifier $targetItem,
		?int $activityId = null
	): ?Result
	{
		$cacheKey = $targetItem->getHash() . $activityId;

		if (array_key_exists($cacheKey, $this->fillCache))
		{
			return is_object($this->fillCache[$cacheKey]) ? clone $this->fillCache[$cacheKey] : null;
		}

		$query = QueueTable::query()
			->setSelect(['*'])
			->where('ENTITY_TYPE_ID', $targetItem->getEntityTypeId())
			->where('ENTITY_ID', $targetItem->getEntityId())
			->where('TYPE_ID', FillItemFieldsFromCallTranscription::TYPE_ID)
			// select last job
			->addOrder('ID', 'DESC')
			->setLimit(1)
		;

		if ($activityId > 0)
		{
			$parentSubQuery = QueueTable::query()
				->setSelect(['ID'])
				->where('ENTITY_TYPE_ID', \CCrmOwnerType::Activity)
				->where('ENTITY_ID', $activityId)
				->where('TYPE_ID', SummarizeCallTranscription::TYPE_ID)
			;

			$query->whereIn('PARENT_ID', $parentSubQuery);
		}

		$job = $query->fetchObject();

		$result = $job ? FillItemFieldsFromCallTranscription::constructResult($job) : null;

		if (is_object($result))
		{
			$clone = clone $result;

			$this->fillCache[$cacheKey] = $clone;
			$this->fillByIdCache[$result->getJobId()] = $clone;
		}
		else
		{
			$this->fillCache[$cacheKey] = null;
		}

		return $result;
	}

	/**
	 * @return Result<FillItemFieldsFromCallTranscriptionPayload>|null
	 */
	public function getFillItemFieldsFromCallTranscriptionResultById(int $jobId): ?Result
	{
		if (array_key_exists($jobId, $this->fillByIdCache))
		{
			return is_object($this->fillByIdCache[$jobId]) ? clone $this->fillByIdCache[$jobId] : null;
		}

		if ($jobId > 0)
		{
			$job = QueueTable::query()
				->setSelect(['*'])
				->where('ID', $jobId)
				->where('TYPE_ID', FillItemFieldsFromCallTranscription::TYPE_ID)
				->fetchObject()
			;
		}
		else
		{
			$job = null;
		}

		$result = $job ? FillItemFieldsFromCallTranscription::constructResult($job) : null;

		$this->fillByIdCache[$jobId] = is_object($result) ? clone $result : null;

		return $result;
	}

	/**
	 * @param Result<FillItemFieldsFromCallTranscriptionPayload> $result
	 *
	 * @return Result
	 */
	public function updateFillItemFieldsFromCallTranscriptionResult(Result $result): Result
	{
		$updateResult = new Result(FillItemFieldsFromCallTranscription::TYPE_ID);

		if (
			$result->getJobId() <= 0
			|| !($result->getPayload() instanceof FillItemFieldsFromCallTranscriptionPayload)
			|| $result->getOperationStatus() === null
		)
		{
			return $updateResult->addError(
				new Error('Job id, payload and operation status are required for update', ErrorCode::REQUIRED_ARG_MISSING)
			);
		}

		$job = QueueTable::query()
			->setSelect(['RESULT', 'EXECUTION_STATUS', 'OPERATION_STATUS'])
			->where('ID', $result->getJobId())
			->fetchObject()
		;

		if (!$job)
		{
			return $updateResult->addError(ErrorCode::getNotFoundError());
		}

		if ($job->requireExecutionStatus() !== QueueTable::EXECUTION_STATUS_SUCCESS)
		{
			return $updateResult->addError(new Error(
				'Only successfully executed jobs can be updated', ErrorCode::JOB_IN_WRONG_STATUS,
			));
		}

		if (Result::isFinalOperationStatus($job->requireOperationStatus()))
		{
			return $updateResult->addError(ErrorCode::getOperationIsCompleteError());
		}

		$job->setOperationStatus($result->getOperationStatus());
		$job->setResult(Json::encode($result->getPayload()));

		$saveResult = $job->save();
		if (!$saveResult->isSuccess())
		{
			$updateResult->addErrors($saveResult->getErrors());
		}

		return $updateResult;
	}

	public function isJobOfSameTypeAlreadyExistsForTarget(ItemIdentifier $target, int $jobTypeId): bool
	{
		if (!in_array($jobTypeId, AIManager::getAllOperationTypes(), true))
		{
			return false;
		}

		$anotherJobOfSameTypeForThisTarget = QueueTable::query()
			->setSelect(['ID'])
			->where('ENTITY_TYPE_ID', $target->getEntityTypeId())
			->where('ENTITY_ID', $target->getEntityId())
			->where('TYPE_ID', $jobTypeId)
			->fetch()
		;

		return is_array($anotherJobOfSameTypeForThisTarget);
	}

	public function getFieldsFillingOperationStatusById(int $id): ?string
	{
		$query = QueueTable::query()
			->setSelect(['OPERATION_STATUS'])
			->where('ID', $id)
			->where('TYPE_ID', FillItemFieldsFromCallTranscription::TYPE_ID);

		return $query->fetchObject()?->getOperationStatus();
	}

	/**
	 * @internal
	 */
	public function cleanRuntimeCache(): void
	{
		$this->transcribeCache = [];
		$this->summarizeCache = [];
		$this->fillCache = [];
		$this->fillByIdCache = [];
	}
}

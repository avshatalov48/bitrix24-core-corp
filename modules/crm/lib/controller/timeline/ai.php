<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Category\EditorHelper;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Entity\FieldDataProvider;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Dto\FillItemFieldsFromCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Dto\MultipleFieldFillPayload;
use Bitrix\Crm\Integration\AI\Dto\SingleFieldFillPayload;
use Bitrix\Crm\Integration\AI\ErrorCode as AIErrorCode;
use Bitrix\Crm\Integration\AI\Feedback;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Timeline\Config;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\UserField\Dispatcher;
use CCrmOwnerType;

class AI extends Activity
{
	private UserPermissions $permissions;
	private JobRepository $jobRepository;
	private Dispatcher $dispatcher;

	protected function init(): void
	{
		parent::init();

		$this->permissions = Container::getInstance()->getUserPermissions();
		$this->jobRepository = JobRepository::getInstance();
		$this->dispatcher = Dispatcher::instance();
	}

	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();

		$filters[] = new ActionFilter\Scope(ActionFilter\Scope::NOT_REST);
		$filters[] = new class extends ActionFilter\Base {
			public function onBeforeAction(Event $event): ?EventResult
			{
				if (!AIManager::isAiCallProcessingEnabled())
				{
					$this->addError(ErrorCode::getAccessDeniedError());

					return new EventResult(EventResult::ERROR, null, 'crm', $this);
				}

				return null;
			}
		};

		return $filters;
	}

	/** @noinspection PhpUnused */
	public function launchRecordingTranscriptionAction(int $activityId, int $ownerTypeId, int $ownerId): void
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return;
		}

		if (!$this->isUpdateEnable($ownerTypeId, $ownerId))
		{
			return;
		}

		if (
			AIManager::isAiCallProcessingEnabled()
			&& in_array($ownerTypeId, AIManager::SUPPORTED_ENTITY_TYPE_IDS, true)
			&& !ComparerBase::isClosed(
				new ItemIdentifier($ownerTypeId, $ownerId)
			)
		)
		{
			$result = AIManager::launchFillItemFromCallRecordingScenario($activityId); // async start transcription
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
			}
		}
	}

	/** @noinspection PhpUnused */
	public function getCopilotSummaryAction(int $activityId, int $ownerTypeId, int $ownerId): ?array
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return null;
		}

		$summaryResult = JobRepository::getInstance()->getSummarizeCallTranscriptionResultByActivity($activityId);
		if (is_null($summaryResult))
		{
			$this->addError(new Error('CoPilot call summary not found'));

			return null;
		}

		if (!$summaryResult->isSuccess())
		{
			$this->addErrors($summaryResult->getErrors());

			return null;
		}

		$payload = $summaryResult->getPayload();
		if (is_null($payload))
		{
			$this->addError(AIErrorCode::getPayloadNotFoundError());

			return null;
		}

		return $payload->toArray();
	}

	/** @noinspection PhpUnused */
	public function getCopilotTranscriptAction(int $activityId, int $ownerTypeId, int $ownerId): ?array
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return null;
		}

		$transcriptResult = JobRepository::getInstance()->getTranscribeCallRecordingResultByActivity($activityId);
		if (is_null($transcriptResult))
		{
			$this->addError(new Error('CoPilot call transcription not found'));

			return null;
		}

		if (!$transcriptResult->isSuccess())
		{
			$this->addErrors($transcriptResult->getErrors());

			return null;
		}

		$payload = $transcriptResult->getPayload();
		if (is_null($payload))
		{
			$this->addError(AIErrorCode::getPayloadNotFoundError());

			return null;
		}

		return $payload->toArray();
	}

	public function fieldsFillingStatusAction(int $mergeId): array
	{
		$operationStatus = JobRepository::getInstance()->getFieldsFillingOperationStatusById($mergeId);

		return [
			'operationStatus' => $operationStatus,
		];
	}

	private function getCallRecord(int $activityId, int $ownerTypeId, int $ownerId): ?array
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return null;
		}

		$storageTypeId = $activity['STORAGE_TYPE_ID'];
		$elementIds = unserialize($activity['STORAGE_ELEMENT_IDS'], ['allowed_classes' => false,]);

		try
		{
			// pick first call
			$fileInfo = StorageManager::getFileInfo(
				$elementIds[0],
				$storageTypeId,
				true,
				['OWNER_ID' => $activity['ID'], 'OWNER_TYPE_ID' => \CCrmOwnerType::Activity,]
			);
		}
		catch (NotSupportedException $exception)
		{
			$this->addError(new Error($exception->getMessage()));

			return null;
		}

		if (
			!is_array($fileInfo)
			|| empty($fileInfo)
			|| !in_array(GetFileExtension(mb_strtolower($fileInfo['NAME'])), Config::ALLOWED_AUDIO_EXTENSIONS, true)
		)
		{
			$this->addError(new Error('Call record not found'));

			return null;
		}

		return [
			'src' => $fileInfo['VIEW_URL'],
			'id' => mb_substr($activity['ORIGIN_ID'], 3),
			'title' => CCrmOwnerType::GetCaption($ownerTypeId, $ownerId),
		];
	}

	public function getCopilotSummaryAndCallRecordAction(int $activityId, int $ownerTypeId, int $ownerId): ?array
	{
		$activityData = [$activityId, $ownerTypeId, $ownerId];
		$summary = $this->getCopilotSummaryAction(...$activityData);
		if (!$summary)
		{
			return null;
		}

		$callRecord = $this->getCallRecord(...$activityData);
		if (!$callRecord)
		{
			return null;
		}

		return [
			'aiJobResult' => $summary,
			'callRecord' => $callRecord,
		];
	}

	public function getCopilotTranscriptAndCallRecordAction(int $activityId, int $ownerTypeId, int $ownerId): ?array
	{
		$activityData = [$activityId, $ownerTypeId, $ownerId];

		$transcription = $this->getCopilotTranscriptAction(...$activityData);
		if (!$transcription)
		{
			return null;
		}

		$callRecord = $this->getCallRecord(...$activityData);
		if (!$callRecord)
		{
			return null;
		}

		return [
			'aiJobResult' => $transcription,
			'callRecord' => $callRecord,
		];
	}

	public function configureActions(): array
	{
		return [
			'mergeFields' => [
				'prefilters' => [
					new ActionFilter\HttpMethod(
						[
							ActionFilter\HttpMethod::METHOD_GET
						]
					),
					new ActionFilter\Csrf()
				]
			]
		];
	}

	/** @noinspection PhpUnused */
	public function mergeFieldsAction(int $mergeUuid): ?array
	{
		$result = $this->getAndCheckFillFieldsResult($mergeUuid);
		if (!$result)
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($result->getTarget()->getEntityTypeId());
		if (!$factory || !\CCrmOwnerType::isUseFactoryBasedApproach($factory->getEntityTypeId()))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($result->getTarget()->getEntityTypeId()));

			return null;
		}

		$item = $factory->getItem($result->getTarget()->getEntityId());
		if (!$item)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return null;
		}

		if (!$this->permissions->canUpdateItem($item))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		if ($result->isInFinalOperationStatus())
		{
			$this->addError(AIErrorCode::getOperationIsCompleteError());

			return null;
		}

		// actualize conflicts info
		FillItemFieldsFromCallTranscription::calculateConflicts(
			$result->getPayload(),
			$factory,
			$item,
		);

		$feedbackResult = $this->jobRepository->getFillItemFieldsFromCallTranscriptionResultById($mergeUuid);

		return [
			'fields' => $this->prepareMergeFields($result->getPayload(), $factory, $item),
			'target' =>
				[
					'entityTypeName' => $factory->getEntityName(),
					'editorId' => $this->makeEditorId(
						$result->getTarget()->getEntityTypeId(),
						$item->isCategoriesSupported() ? $item->getCategoryId() : null,
					),
					'feedbackWasSent' => Feedback::wasSent($feedbackResult),
				]
				+ $result->getTarget()->jsonSerialize()
			,
			'editMode' => true,
		];
	}

	private function prepareMergeFields(
		FillItemFieldsFromCallTranscriptionPayload $payload,
		Factory $factory,
		\Bitrix\Crm\Item $item
	): array
	{
		$json = [];

		//todo move to operation?
		$whitelist =
			(new FieldDataProvider($factory->getEntityTypeId(), Context::SCOPE_AI))
				->getDisplayedInEntityEditorFieldData($this->getCurrentUser()->getId())
		;

		foreach (array_merge($payload->singleFields, $payload->multipleFields) as $dtoField)
		{
			/** @var SingleFieldFillPayload|MultipleFieldFillPayload $dtoField */

			$field = $factory->getFieldsCollection()->getField($dtoField->name);
			if (
				!$field
				|| !$item->hasField($dtoField->name)
				|| !isset($whitelist[$dtoField->name]) // return only fields that are displayed in entity details
				|| $dtoField->isApplied // return only fields that were not automatically applied to item
			)
			{
				continue;
			}

			if ($dtoField instanceof SingleFieldFillPayload)
			{
				$newValue = $dtoField->aiValue;
			}
			elseif ($dtoField instanceof MultipleFieldFillPayload)
			{
				if (is_array($item->get($dtoField->name)))
				{
					$newValue = array_merge($dtoField->aiValues, $item->get($dtoField->name));
				}
				else
				{
					$newValue = $dtoField->aiValues;
				}
			}
			else
			{
				throw new NotSupportedException('Unknown payload field type');
			}

			$json[] = [
				'name' => $field->getName(),
				'title' => $field->getTitle(),
				'isMultiple' => $field->isMultiple(),
				'type' => $field->getType(),
				'isUserField' => $field->isUserField(),
				'aiModel' => [
					'IS_EMPTY' => $field->isValueEmpty($newValue),
					'SIGNATURE' => $this->dispatcher->getSignature([
						'ENTITY_ID' => $factory->getUserFieldEntityId(),
						'FIELD' => $field->getName(),
						'VALUE' => $newValue,
					]),
					'VALUE' => $newValue,
				],
			];
		}

		return $json;
	}

	private function makeEditorId(int $entityTypeId, ?int $categoryId = null): string
	{
		$sourceFormId = match ($entityTypeId)
		{
			CCrmOwnerType::Lead => 'lead_details',
			CCrmOwnerType::Deal => 'deal_details',
			default => throw new \Exception('Unknown entity type'),
		};

		return (new EditorHelper($entityTypeId))
			->getEditorConfigId($categoryId, $sourceFormId, false)
		;
	}

	/**
	 * @param string[] $fieldNamesToApply
	 *
	 * @noinspection PhpUnused
	 */
	public function applyMergeAction(int $mergeUuid, array $fieldNamesToApply): void
	{
		$result = $this->getAndCheckFillFieldsResult($mergeUuid);
		if (!$result)
		{
			return;
		}

		if ($result->isInFinalOperationStatus())
		{
			$this->addError(AIErrorCode::getOperationIsCompleteError());

			return;
		}

		$factory = Container::getInstance()->getFactory($result->getTarget()->getEntityTypeId());
		if (!$factory || !\CCrmOwnerType::isUseFactoryBasedApproach($factory->getEntityTypeId()))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($result->getTarget()->getEntityTypeId()));

			return;
		}

		$item = $factory->getItem($result->getTarget()->getEntityId());
		if (!$item)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return;
		}

		if (!$this->permissions->canUpdateItem($item))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return;
		}

		//todo move to operation?
		$whitelist =
			(new FieldDataProvider($factory->getEntityTypeId(), Context::SCOPE_AI))
				->getDisplayedInEntityEditorFieldData($this->getCurrentUser()->getId())
		;

		$payload = $result->getPayload();

		foreach ($payload->singleFields as $singleField)
		{
			if (
				isset($whitelist[$singleField->name])
				&& in_array($singleField->name, $fieldNamesToApply, true)
				&& !$singleField->isApplied
				&& $item->hasField($singleField->name)
			)
			{
				$item->set($singleField->name, $singleField->aiValue);
				$singleField->isApplied = true;
			}
		}

		foreach ($payload->multipleFields as $multipleField)
		{
			if (
				isset($whitelist[$multipleField->name])
				&& in_array($multipleField->name, $fieldNamesToApply, true)
				&& !$multipleField->isApplied
				&& $item->hasField($multipleField->name)
			)
			{
				$previousValue = $item->get($multipleField->name) ?? [];
				if (is_array($previousValue))
				{
					$item->set($multipleField->name, array_merge($previousValue, $multipleField->aiValues));
					$multipleField->isApplied = true;
				}
			}
		}

		$context =
			(new Context())
				->setUserId($this->getCurrentUser()->getId())
				->setScope(Context::SCOPE_AI)
		;

		$operation =
			$factory->getUpdateOperation($item, $context)
				// disable all checks except check access
				->disableAllChecks()
				->enableCheckAccess()
				->disableBizProc()
				->disableAutomation()
		;

		$updateResult = $operation->launch();
		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());

			return;
		}

		$result->setOperationStatus(Result::OPERATION_STATUS_APPLIED);

		$saveResult = $this->jobRepository->updateFillItemFieldsFromCallTranscriptionResult($result);
		if ($saveResult->isSuccess())
		{
			FillItemFieldsFromCallTranscription::onAfterConflictApply($result);
		}
		else
		{
			$this->addErrors($saveResult->getErrors());
		}
	}

	/** @noinspection PhpUnused */
	public function rejectMergeAction(int $mergeUuid): void
	{
		$result = $this->getAndCheckFillFieldsResult($mergeUuid);
		if (!$result)
		{
			return;
		}

		if ($result->isInFinalOperationStatus())
		{
			$this->addError(AIErrorCode::getOperationIsCompleteError());

			return;
		}

		$result->setOperationStatus(Result::OPERATION_STATUS_REJECTED);

		$updateResult = $this->jobRepository->updateFillItemFieldsFromCallTranscriptionResult($result);

		if ($updateResult->isSuccess())
		{
			FillItemFieldsFromCallTranscription::onAfterConflictReject($result);
		}
		else
		{
			$this->addErrors($updateResult->getErrors());
		}
	}

	/**
	 * @param int $mergeUuid
	 *
	 * @return Result<FillItemFieldsFromCallTranscriptionPayload>|null
	 */
	private function getAndCheckFillFieldsResult(int $mergeUuid): ?Result
	{
		$result = $this->jobRepository->getFillItemFieldsFromCallTranscriptionResultById($mergeUuid);
		if (!$result)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		if ($result->isPending() || !$result->isSuccess())
		{
			$this->addError(new Error('Only successful finished jobs are allowed', AIErrorCode::JOB_IN_WRONG_STATUS));

			return null;
		}

		if (
			!$result->getTarget()
			|| !$this->permissions->checkUpdatePermissions(
				$result->getTarget()->getEntityTypeId(),
				$result->getTarget()->getEntityId()
			)
		)
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public function sendFeedbackAction(int $mergeUuid): void
	{
		$result = $this->getAndCheckFillFieldsResult($mergeUuid);
		if (!$result)
		{
			return;
		}

		$consentResult = Feedback::grantConsent($result);
		if (!$consentResult->isSuccess())
		{
			$this->addErrors($consentResult->getErrors());

			return;
		}

		$enqueueResult = Feedback::addToSendQueue($result);
		if (!$enqueueResult->isSuccess())
		{
			$this->addErrors($enqueueResult->getErrors());
		}
	}

	/** @noinspection PhpUnused */
	public function wasFeedbackSentAction(int $mergeUuid): ?bool
	{
		$result = $this->getAndCheckFillFieldsResult($mergeUuid);
		if (!$result)
		{
			return null;
		}

		return Feedback::wasSent($result);
	}
}

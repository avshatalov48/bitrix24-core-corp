<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\Crm\Badge;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Entity\FieldDataProvider;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Config;
use Bitrix\Crm\Integration\AI\Dto\FillItemFieldsFromCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Dto\MultipleFieldFillPayload;
use Bitrix\Crm\Integration\AI\Dto\SingleFieldFillPayload;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Model\EO_Queue;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Integration\Analytics\Builder\AI\AIBaseEvent;
use Bitrix\Crm\Integration\Analytics\Builder\AI\ExtractFieldsEvent;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Timeline\AI\Call\Controller;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Random;
use Bitrix\Main\UserField\Types\DoubleType;
use Bitrix\Main\UserField\Types\IntegerType;
use Bitrix\Main\UserField\Types\StringType;
use Bitrix\Main\Web\Json;
use CCrmOwnerType;

class FillItemFieldsFromCallTranscription extends AbstractOperation
{
	public const TYPE_ID = 3;
	public const CONTEXT_ID = 'fill_item_fields_from_call_transcription';

	public const SUPPORTED_TARGET_ENTITY_TYPE_IDS = [
		CCrmOwnerType::Lead,
		CCrmOwnerType::Deal
	];

	protected const PAYLOAD_CLASS = FillItemFieldsFromCallTranscriptionPayload::class;

	public function __construct(
		ItemIdentifier $target, // item which fields will be filled
		private readonly string $summary, // summary of the call transcription
		?int $userId = null,
		?int $parentJobId = null,
	)
	{
		if ($parentJobId <= 0)
		{
			throw new ArgumentNullException('parentJobId');
		}

		parent::__construct($target, $userId, $parentJobId);
	}

	public static function isSuitableTarget(ItemIdentifier $target): bool
	{
		return in_array($target->getEntityTypeId(), self::SUPPORTED_TARGET_ENTITY_TYPE_IDS, true);
	}

	protected static function findDuplicateJob(ItemIdentifier $target, int $parentId): ?EO_Queue
	{
		if ($parentId <= 0)
		{
			// it's impossible to find previous job
			return null;
		}

		// we count as duplicates only jobs with same parent id
		return QueueTable::query()
			->setSelect(['ID', 'EXECUTION_STATUS', 'RETRY_COUNT'])
			->where('ENTITY_TYPE_ID', $target->getEntityTypeId())
			->where('ENTITY_ID', $target->getEntityId())
			->where('TYPE_ID', static::TYPE_ID)
			->where('PARENT_ID', $parentId)
			->setLimit(1)
			->fetchObject()
		;
	}

	protected function getAIPayload(): \Bitrix\Main\Result
	{
		$fields = [
			// unallocated data
			'comment' => 'list[string]',
		];

		// sent to AI all available fields, regardless of user
		foreach (self::getAllSuitableFields($this->target->getEntityTypeId()) as $fieldDescription)
		{
			if ($fieldDescription['MULTIPLE'])
			{
				$type = 'list[' . $fieldDescription['TYPE'] . ']';
			}
			else
			{
				$type = $fieldDescription['TYPE'];
			}

			$fields[$fieldDescription['NAME']] = "{$type} or null";
		}

		return (new \Bitrix\Main\Result())->setData([
			'payload' => (new \Bitrix\AI\Payload\Prompt('extract_form_fields'))
				->setMarkers(['original_message' => $this->summary, 'fields' => $fields])
			,
		]);
	}

	protected function getStubPayload(): mixed
	{
		$generateSingleStubValue = static function(string $type): mixed {
			return match ($type) {
				StringType::USER_TYPE_ID => Random::getString(4, true),
				IntegerType::USER_TYPE_ID => Random::getInt(0, 10_000),
				DoubleType::USER_TYPE_ID => Random::getInt(0, 100_000) * 0.1,
				default => null,
			};
		};

		$fields = [
			// unallocated data
			'comment' => [
				'This is stub of an unallocated data',
				'Imagine that it was returned by AI',
				'(Some supermagic info here)',
			],
		];
		foreach (self::getAllSuitableFields($this->target->getEntityTypeId()) as $fieldDescription)
		{
			if ($fieldDescription['MULTIPLE'])
			{
				$numberOfElements = Random::getInt(-1, 3);
				if ($numberOfElements < 0)
				{
					$value = null;
				}
				else
				{
					$value = [];
					while (count($value) < $numberOfElements)
					{
						$value[] = $generateSingleStubValue($fieldDescription['TYPE']);
					}
				}
			}
			else
			{
				$value = Random::getInt(0, 1) ? $generateSingleStubValue($fieldDescription['TYPE']) : null;
			}

			$fields[$fieldDescription['NAME']] = $value;
		}

		return Json::encode($fields);
	}

	final protected function getContextLanguageId(): string
	{
		$item = Container::getInstance()
			->getFactory($this->target->getEntityTypeId())
			?->getItem($this->target->getEntityId())
		;

		if ($item)
		{
			$categoryId = $item->isCategoriesSupported() ? $item->getCategoryId() : null;

			return Config::getLanguageId(
				$this->userId,
				$this->target->getEntityTypeId(),
				$categoryId
			);
		}

		return parent::getContextLanguageId();
	}

	protected static function extractPayloadFromAIResult(\Bitrix\AI\Result $result, EO_Queue $job): Dto
	{
		$json = self::extractPayloadPrettifiedData($result);
		if (empty($json))
		{
			return new FillItemFieldsFromCallTranscriptionPayload([]);
		}

		$map = [];
		foreach (self::getAllSuitableFields($job->requireEntityTypeId()) as $singleFieldDescription)
		{
			$map[$singleFieldDescription['NAME']] = $singleFieldDescription;
		}

		$singleFields = [];
		$multipleFields = [];
		foreach ($json as $caption => $value)
		{
			$fieldDescription = $map[$caption] ?? null;
			if (!is_array($fieldDescription))
			{
				continue;
			}

			if ($fieldDescription['MULTIPLE'])
			{
				$candidate = new MultipleFieldFillPayload([
					'name' => $fieldDescription['ID'],
					'aiValues' => $value,
				]);
				if (!$candidate->hasValidationErrors())
				{
					$multipleFields[] = $candidate->toArray();
				}
			}
			else
			{
				$candidate = new SingleFieldFillPayload([
					'name' => $fieldDescription['ID'],
					'aiValue' => $value,
				]);
				if (!$candidate->hasValidationErrors())
				{
					$singleFields[] = $candidate->toArray();
				}
			}
		}

		return new FillItemFieldsFromCallTranscriptionPayload([
			'singleFields' => $singleFields,
			'multipleFields' => $multipleFields,
			'unallocatedData' => self::extractPayloadString($json['comment'] ?? ''),
		]);
	}

	/**
	 * Checks all fields that were returned by AI and compares them to actual item fields to find conflicts.
	 * All fields are checked, event hidden and not displayed
	 */
	public static function calculateConflicts(
		FillItemFieldsFromCallTranscriptionPayload $payload,
		Factory $factory,
		Item $item
	): void
	{
		foreach (array_merge($payload->singleFields, $payload->multipleFields) as $field)
		{
			$field->isConflict = false;
		}

		// multiple never has conflicts, check only single ones
		foreach ($payload->singleFields as $fieldDto)
		{
			$field = $factory->getFieldsCollection()->getField($fieldDto->name);

			if ($field && $item->hasField($field->getName()))
			{
				$itemValue = $item->get($field->getName());
				$aiValue = $fieldDto->aiValue;

				//todo will it work for list fields?
				if (!$field->isValueEmpty($itemValue) && !$field->isValueEmpty($aiValue) && (string)$itemValue !== (string)$aiValue)
				{
					$fieldDto->isConflict = true;
				}
			}
		}
	}

	protected static function notifyTimelineAfterSuccessfulLaunch(Result $result): void
	{
		$activityId = self::getParentActivityId($result);
		if ($activityId > 0)
		{
			Controller::getInstance()->onStartFillingEntityFields(
				$result->getTarget(),
				$activityId,
				$result->getUserId(),
			);
		}
	}

	protected static function onAfterSuccessfulJobFinish(Result $result, ?\Bitrix\AI\Context $context = null): void
	{
		/** @var FillItemFieldsFromCallTranscriptionPayload $payload */
		$payload = $result->getPayload();
		if (!$payload || !$result->isSuccess())
		{
			AIManager::logger()->error(
				'{date}: {class}: Not updating item fields because of job error: {target}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
				],
			);

			return;
		}

		$factory = Container::getInstance()->getFactory($result->getTarget()?->getEntityTypeId());
		$item = $factory?->getItem($result->getTarget()?->getEntityId());
		if (!$factory || !$item || !CCrmOwnerType::isUseFactoryBasedApproach($result->getTarget()?->getEntityTypeId()))
		{
			AIManager::logger()->error(
				'{date}: {class}: Target not found or invalid when trying to update its fields: {target}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
				],
			);

			return;
		}

		self::calculateConflicts($payload, $factory, $item);

		$automaticallyHandledFields = self::getAutomaticallyHandledFields(
			$item,
			$result->getUserId(),
		);

		foreach ($payload->singleFields as $singleField)
		{
			if (
				isset($automaticallyHandledFields[$singleField->name])
				&& !$singleField->isConflict
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
				isset($automaticallyHandledFields[$multipleField->name])
				&& !$multipleField->isConflict
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

		if (!empty($payload->unallocatedData) && $item->hasField(Item::FIELD_NAME_COMMENTS))
		{
			$item->setComments(self::appendComment((string)$item->getComments(), $payload->unallocatedData));
		}

		$userContext = (new Context())
			->setUserId($result->getUserId())
			->setScope(Context::SCOPE_AI)
		;

		$operation =
			$factory->getUpdateOperation($item, $userContext)
				// disable all checks except check access
				->disableAllChecks()
				->enableCheckAccess()
				->disableBizProc()
				->disableAutomation()
		;

		$updateResult = $operation->launch();
		if ($updateResult->isSuccess())
		{
			if (self::isPayloadHasAutoHandledFieldsWithConflicts($payload, $automaticallyHandledFields))
			{
				$result->setOperationStatus(Result::OPERATION_STATUS_CONFLICT);
			}
			else
			{
				// we have just applied all fields, this is the end of operation
				$result->setOperationStatus(Result::OPERATION_STATUS_APPLIED);
			}

			$activityId = self::getParentActivityId($result);
			// send 'main scenario ended' event
			self::sendCallParsingAnalyticsEvent(
				$result,
				$activityId,
				JobRepository::getInstance()->getTotalFillItemFromCallRecordingScenarioDuration($result->getJobId()),
			);

			$saveResult = JobRepository::getInstance()->updateFillItemFieldsFromCallTranscriptionResult($result);
			if ($saveResult->isSuccess())
			{
				AIManager::logger()->info(
					'{date}: {class}: Updated item fields for target {target}' . PHP_EOL,
					[
						'class' => self::class,
						'target' => $result->getTarget(),
					],
				);
			}
			else
			{
				AIManager::logger()->critical(
					'{date}: {class}: Got error while trying to update operation status after item fields update for activity{activity}.'
					. ' Target {target}, errors {errors}, result {result}' . PHP_EOL,
					[
						'class' => self::class,
						'target' => $result->getTarget(),
						'errors' => $saveResult->getErrors(),
						'result' => $result,
						'activity' => self::getTargetRealId($result->getTarget(), $result->getParentJobId()),
					],
				);
			}
		}
		else
		{
			AIManager::logger()->error(
				'{date}: {class}: Got error while trying to update item fields: target {target}, errors {errors}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
					'errors' => $updateResult->getErrors(),
				],
			);
		}
	}

	private static function appendComment(string $oldComment, string $unallocatedData): string
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$copilotSuffix = Loc::getMessage('CRM_COMMON_COPILOT') . PHP_EOL . $unallocatedData;

		if (empty($oldComment))
		{
			return $copilotSuffix;
		}

		if (
			str_ends_with($oldComment, "\n\n")
			|| str_ends_with($oldComment, "\r\n\r\n")
			|| str_ends_with($oldComment, "\r\n\n")
			|| str_ends_with($oldComment, "\n\r\n")
		)
		{
			$numberOfLineBreaksToAdd = 0;
		}
		elseif (str_ends_with($oldComment, "\n") || str_ends_with($oldComment, "\r\n"))
		{
			$numberOfLineBreaksToAdd = 1;
		}
		else
		{
			$numberOfLineBreaksToAdd = 2;
		}

		while ($numberOfLineBreaksToAdd > 0)
		{
			$oldComment .= PHP_EOL;
			$numberOfLineBreaksToAdd--;
		}

		return $oldComment . $copilotSuffix;
	}

	private static function isPayloadHasAutoHandledFieldsWithConflicts(
		FillItemFieldsFromCallTranscriptionPayload $payload,
		array $automaticallyHandledFields
	): bool
	{
		foreach (array_merge($payload->singleFields, $payload->multipleFields) as $field)
		{
			if ($field->isConflict && isset($automaticallyHandledFields[$field->name]))
			{
				return true;
			}
		}

		return false;
	}

	protected static function notifyTimelineAfterSuccessfulJobFinish(Result $result): void
	{
		$activityId = self::getParentActivityId($result);
		if ($activityId > 0)
		{
			Controller::getInstance()->onFinishFillingEntityFields(
				$result->getTarget(),
				$activityId,
				[],
				$result->getUserId(),
			);

			$badgeType = $result->getOperationStatus() === Result::OPERATION_STATUS_CONFLICT
				? Badge\Type\AiCallFieldsFillingResult::CONFLICT_FIELDS_VALUE
				: Badge\Type\AiCallFieldsFillingResult::SUCCESS_FIELDS_VALUE
			;
			self::syncBadges($activityId, $badgeType);
			self::notifyTimelinesAboutActivityUpdate($activityId);
		}
	}

	protected static function notifyAboutJobError(
		Result $result,
		bool $withSyncBadges = true,
		bool $withSendAnalytics = true
	): void
	{
		$activityId = self::getParentActivityId($result);
		if ($activityId > 0)
		{
			if ($withSyncBadges)
			{
				Controller::getInstance()->onLaunchError(
					$result->getTarget(),
					$activityId,
					[
						'OPERATION_TYPE_ID' => self::TYPE_ID,
						'ENGINE_ID' => self::$engineId,
						'ERRORS' => $result->getErrorMessages(),
					],
					$result->getUserId(),
				);

				self::syncBadges(
					$activityId,
					self::$engineId === 0
						? Badge\Type\AiCallFieldsFillingResult::ERROR_PROCESS_VALUE
						: Badge\Type\AiCallFieldsFillingResult::ERROR_PROCESS_THIRDPARTY_VALUE
				);
			}

			self::notifyTimelinesAboutActivityUpdate($activityId);

			if ($withSendAnalytics)
			{
				self::sendCallParsingAnalyticsEvent(
					$result,
					$activityId
				);
			}
		}
	}

	protected static function notifyAboutLimitExceededError(Result $result): void
	{
		$activityId = self::getParentActivityId($result);
		if ($activityId > 0)
		{
			static::syncBadges($activityId, Badge\Type\AiCallFieldsFillingResult::ERROR_LIMIT_EXCEEDED);
			static::notifyTimelinesAboutAutomationLaunchError($result, $activityId);
		}
	}

	public static function getParentActivityId(Result $result): int
	{
		if ($result->getParentJobId() <= 0)
		{
			return 0;
		}

		return (int)QueueTable::query()
			->setSelect(['ENTITY_ID'])
			->where('ID', $result->getParentJobId())
			->where('ENTITY_TYPE_ID', CCrmOwnerType::Activity)
			->fetchObject()
			?->getEntityId()
		;
	}

	private static function getAllSuitableFields(int $entityTypeId): array
	{
		return (new FieldDataProvider($entityTypeId, Context::SCOPE_AI))->getFieldData();
	}

	private static function getAutomaticallyHandledFields(Item $item, int $userId): array
	{
		$categoryId = $item->isCategoriesSupported() ? $item->getCategoryId() : null;

		return (new FieldDataProvider($item->getEntityTypeId(), Context::SCOPE_AI))->getDisplayedInEntityEditorFieldData($userId, $categoryId);
	}

	public static function actualizeResult(Result $result, ?int $userId = null): Result
	{
		if ($result->getTypeId() !== self::TYPE_ID)
		{
			throw new ArgumentException('Only results of type ' . static::TYPE_ID . ' are supported');
		}

		$newResult = new Result(
			$result->getTypeId(),
			$result->getTarget(),
			$userId ?? $result->getUserId(),
			$result->getJobId(),
			$result->isPending(),
			$result->getPayload() ? clone $result->getPayload() : null,
			$result->getOperationStatus(),
			$result->getParentJobId(),
			$result->getRetryCount()
		);
		if ($result->getErrors())
		{
			$newResult->addErrors($result->getErrors());
		}

		if (
			$newResult->getOperationStatus() === Result::OPERATION_STATUS_APPLIED
			|| $newResult->getOperationStatus() === Result::OPERATION_STATUS_REJECTED
		)
		{
			return $newResult->addError(ErrorCode::getOperationIsCompleteError());
		}

		if (!$newResult->getPayload() || !$newResult->getTarget())
		{
			//nothing to change here
			return $newResult;
		}

		$factory = Container::getInstance()->getFactory($result->getTarget()?->getEntityTypeId());
		$item = $factory?->getItem($result->getTarget()?->getEntityId());
		if (!$factory || !$item || !CCrmOwnerType::isUseFactoryBasedApproach($factory->getEntityTypeId()))
		{
			AIManager::logger()->error(
				'{date}: {class}: Target not found or invalid when trying to actualize result: {target}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
				],
			);

			return $newResult->addError(ErrorCode::getNotFoundError());
		}

		self::calculateConflicts($newResult->getPayload(), $factory, $item);

		return $newResult;
	}

	public static function onAfterConflictApply(Result $result): void
	{
		$activityId = self::getParentActivityId($result);
		if ($activityId)
		{
			Controller::getInstance()->onFinishProcessingEntityFields(
				$result->getTarget(),
				$activityId,
			);

			self::syncBadges($activityId, Badge\Type\AiCallFieldsFillingResult::SUCCESS_FIELDS_VALUE);
			self::notifyTimelinesAboutActivityUpdate($activityId, true);
		}
	}

	public static function onAfterConflictReject(Result $result): void
	{
		$activityId = self::getParentActivityId($result);
		if ($activityId)
		{
			self::syncBadges($activityId);
			self::notifyTimelinesAboutActivityUpdate($activityId, true);
		}
	}

	protected static function getJobFinishEventBuilder(): AIBaseEvent
	{
		return new ExtractFieldsEvent();
	}
}

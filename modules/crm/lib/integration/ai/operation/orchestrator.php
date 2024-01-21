<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\Crm\Activity\IncomingChannel;
use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Analytics;
use Bitrix\Crm\Integration\AI\Dto\SummarizeCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Dto\TranscribeCallRecordingPayload;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use Psr\Log\LoggerInterface;

/**
 * The place for automatic AI flow - which operation should start after another is finished,
 * what are possible targets for them.
 */
final class Orchestrator
{
	private Container $container;
	private JobRepository $jobRepo;
	private IncomingChannel $incomingChannel;
	private LoggerInterface $logger;

	public function __construct()
	{
		$this->container = Container::getInstance();
		$this->jobRepo = JobRepository::getInstance();
		$this->incomingChannel = IncomingChannel::getInstance();
		$this->logger = AIManager::logger();
	}

	public function launchNextOperationIfNeeded(
		Result $previousJobResult,
		AutostartSettings $settings
	): void
	{
		$this->logger->info(
			'{date}: Trying to autostart next operation after finish of another operation.'
			. ' Autostart settings {autostartSettings}, previous job result {previousJobResult}' . PHP_EOL,
			['autostartSettings' => $settings, 'previousJobResult' => $previousJobResult],
		);

		if (
			$previousJobResult->getTypeId() === TranscribeCallRecording::TYPE_ID
			&& $previousJobResult->isSuccess()
			&& $previousJobResult->getPayload() instanceof TranscribeCallRecordingPayload
			&& !empty($previousJobResult->getPayload()->transcription)
			&& $settings->shouldAutostart(SummarizeCallTranscription::TYPE_ID, false)
		)
		{
			$this->logger->info(
				'{date}: Trying to autostart operation with type {operationType}' . PHP_EOL,
				['operationType' => SummarizeCallTranscription::TYPE_ID]
			);

			/** @var Result<TranscribeCallRecordingPayload> $previousJobResult */

			$summarizeCall = new SummarizeCallTranscription(
				$previousJobResult->getTarget(),
				$previousJobResult->getPayload()->transcription,
				$previousJobResult->getUserId(),
				$previousJobResult->getJobId(),
			);
			$summarizeCall->launch();
		}

		if (
			$previousJobResult->getTypeId() === SummarizeCallTranscription::TYPE_ID
			&& $previousJobResult->isSuccess()
			&& $previousJobResult->getPayload() instanceof SummarizeCallTranscriptionPayload
			&& !empty($previousJobResult->getPayload()->summary)
			&& $settings->shouldAutostart(FillItemFieldsFromCallTranscription::TYPE_ID, false)
		)
		{
			$this->logger->info(
				'{date}: Trying to autostart operation with type {operationType}' . PHP_EOL,
				['operationType' => FillItemFieldsFromCallTranscription::TYPE_ID]
			);

			/** @var Result<SummarizeCallTranscriptionPayload> $previousJobResult */

			$fillFieldsTarget = $this->findPossibleFillFieldsTarget(
				$previousJobResult->getTarget()?->getEntityId()
			);
			if ($fillFieldsTarget)
			{
				$fillItemFields = new FillItemFieldsFromCallTranscription(
					$fillFieldsTarget,
					$previousJobResult->getPayload()->summary,
					$previousJobResult->getUserId(),
					$previousJobResult->getJobId(),
				);
				$fillItemFields->launch();
			}
		}
	}

	public function launchOperationAfterCallActivityAddIfNeeded(
		array $activityFields,
		AutostartSettings $settings,
	): void
	{
		$this->logger->info(
			'{date}: Trying to autostart operation after call activity was added.'
			. ' Autostart settings {autostartSettings}, activity {activity}' . PHP_EOL,
			['autostartSettings' => $settings, 'activity' => $activityFields],
		);

		if (!$settings->shouldAutostart(TranscribeCallRecording::TYPE_ID))
		{
			return;
		}

		$activityId = (int)($activityFields['ID'] ?? null);

		$nextTarget = $this->findPossibleFillFieldsTargetByBindings($activityFields['BINDINGS'] ?? []);
		$userId = $nextTarget ? $this->findAssigned($nextTarget) : null;

		$storageTypeId = (int)($activityFields['STORAGE_TYPE_ID'] ?? null);

		$storageElementIds = (array)($activityFields['STORAGE_ELEMENT_IDS'] ?? []);
		$storageElementIds = array_map('intval', $storageElementIds);
		$storageElementIds = array_filter($storageElementIds, fn(int $id) => $id > 0);

		if (
			$activityId > 0
			&& $nextTarget
			&& $userId > 0
			&& StorageType::isDefined($storageTypeId)
			&& !empty($storageElementIds)
			&& ($activityFields['IS_INCOMING_CHANNEL'] ?? 'N') === 'Y'
			&& AIManager::checkForSuitableAudios((string)$activityFields['ORIGIN_ID'], $storageTypeId, serialize($storageElementIds))->isSuccess()
		)
		{
			if ($settings->isAutostartTranscriptionOnlyOnFirstCallWithRecording())
			{
				$shouldStart = $this->isFirstCallActivityWithFilesForItem($activityFields, $nextTarget);
			}
			else
			{
				$shouldStart = true;
			}

			if ($shouldStart)
			{
				$this->logger->info(
					'{date}: Trying to autostart operation with type {operationType}' . PHP_EOL,
					['operationType' => TranscribeCallRecording::TYPE_ID]
				);
				$result = AIManager::launchCallRecordingTranscription(
					$activityId,
					$userId,
					$storageTypeId,
					max($storageElementIds)
				);
				$this->sendAnalyticsWrapper($result, $activityFields);
			}
		}
	}

	public function launchOperationAfterCallActivityUpdateIfNeeded(
		array $activityFields,
		array $changedFields,
		AutostartSettings $settings,
	): void
	{
		$this->logger->info(
			'{date}: Trying to autostart operation after call activity was updated.'
			. ' Autostart settings {autostartSettings}, changed fields {changedFields}, new activity state {activity}' . PHP_EOL,
			['autostartSettings' => $settings, 'activity' => $activityFields, 'changedFields' => $changedFields],
		);

		if (!$settings->shouldAutostart(TranscribeCallRecording::TYPE_ID))
		{
			return;
		}

		$activityId = (int)($activityFields['ID'] ?? null);

		$nextTarget = $this->findPossibleFillFieldsTargetByBindings($activityFields['BINDINGS'] ?? []);
		$userId = $nextTarget ? $this->findAssigned($nextTarget) : null;

		$storageTypeId = (int)($changedFields['STORAGE_TYPE_ID'] ?? null);

		$storageElementIds = (array)($changedFields['STORAGE_ELEMENT_IDS'] ?? []);
		$storageElementIds = array_map('intval', $storageElementIds);
		$storageElementIds = array_filter($storageElementIds, fn(int $id) => $id > 0);

		if (
			$activityId > 0
			&& $nextTarget
			&& $userId > 0
			&& StorageType::isDefined($storageTypeId)
			&& !empty($storageElementIds)
			&& ($activityFields['IS_INCOMING_CHANNEL'] ?? 'N') === 'Y'
			&& !$this->jobRepo->isJobOfSameTypeAlreadyExistsForTarget(
				new ItemIdentifier(\CCrmOwnerType::Activity, $activityId),
				TranscribeCallRecording::TYPE_ID,
			)
			&& AIManager::checkForSuitableAudios((string)$activityFields['ORIGIN_ID'], $storageTypeId, serialize($storageElementIds))->isSuccess()
		)
		{
			if ($settings->isAutostartTranscriptionOnlyOnFirstCallWithRecording())
			{
				$shouldStart = $this->isFirstCallActivityWithFilesForItem($activityFields, $nextTarget);
			}
			else
			{
				$shouldStart = true;
			}

			if ($shouldStart)
			{
				$this->logger->info(
					'{date}: Trying to autostart operation with type {operationType}' . PHP_EOL,
					['operationType' => TranscribeCallRecording::TYPE_ID]
				);
				$result = AIManager::launchCallRecordingTranscription(
					$activityId,
					$userId,
					$storageTypeId,
					max($storageElementIds)
				);
				$this->sendAnalyticsWrapper($result, $activityFields);
			}
		}
	}

	public function findPossibleFillFieldsTarget(int $activityId): ?ItemIdentifier
	{
		return $this->findPossibleFillFieldsTargetByBindings(\CCrmActivity::GetBindings($activityId));
	}

	private function sendAnalyticsWrapper(Result $result, array $activityFields): void
	{
		$status = Analytics::STATUS_SUCCESS;
		if (!$result->isSuccess())
		{
			$status = Analytics::STATUS_ERROR_B24;
			$error = $result->getErrors()[0] ?? null;
			if ($error && $error->getCode() === ErrorCode::AI_ENGINE_LIMIT_EXCEEDED)
			{
				$status = Analytics::STATUS_ERROR_NO_LIMITS;
			}
		}

		Analytics::getInstance()->sendAnalytics(
			Analytics::CONTEXT_EVENT_CALL,
			Analytics::CONTEXT_TYPE_AUTO,
			Analytics::CONTEXT_ELEMENT_COPILOT_BTN,
			$status,
			(int)($activityFields['OWNER_TYPE_ID'] ?? 0),
			(string)($activityFields['ORIGIN_ID'] ?? ''),
		);
	}

	private function findPossibleFillFieldsTargetByBindings(array $bindings): ?ItemIdentifier
	{
		$this->logger->debug(
			'{date}: Trying to find possible fill item fields target by activity bindings {bindings}' . PHP_EOL,
			['bindings' => $bindings],
		);

		static $whitelist = [
			// sorted by priority - first found entity type is used
			\CCrmOwnerType::Deal => \CCrmOwnerType::Deal,
			\CCrmOwnerType::Lead => \CCrmOwnerType::Lead,
		];

		$filteredBindings = [];
		foreach ($bindings as $binding)
		{
			$ownerTypeId = (int)$binding['OWNER_TYPE_ID'];
			$ownerId = (int)$binding['OWNER_ID'];

			if (isset($whitelist[$ownerTypeId]) && $ownerId > 0)
			{
				$filteredBindings[$ownerTypeId][$ownerId] = $ownerId;
			}
		}

		if (empty($filteredBindings))
		{
			$this->logger->debug('{date}: All given bindings were filtered out, cant find target' . PHP_EOL);

			return null;
		}

		foreach ($whitelist as $entityTypeId)
		{
			$this->logger->debug('{date}: Checking type {entityTypeId}' . PHP_EOL, ['entityTypeId' => $entityTypeId]);

			$factory = $this->container->getFactory($entityTypeId);

			if (!$factory || empty($filteredBindings[$entityTypeId]))
			{
				$this->logger->debug(
					'{date}: No bindings or factory, skipping type {entityTypeId}' . PHP_EOL,
					['entityTypeId' => $entityTypeId],
				);

				continue;
			}

			if (count($filteredBindings[$entityTypeId]) === 1)
			{
				$id = reset($filteredBindings[$entityTypeId]);

				$target = new ItemIdentifier(
					$entityTypeId,
					$id,
					$factory->getItemCategoryId($id)
				);

				$this->logger->debug(
					'{date}: Exactly one binding exists, found target {target} for bindings' . PHP_EOL,
					['target' => $target, 'bindings' => $bindings]
				);

				return $target;
			}

			$items = $factory->getItems([
				'select' => array_filter([
					Item::FIELD_NAME_ID,
					$factory->isCategoriesSupported() ? Item::FIELD_NAME_CATEGORY_ID : null,
				]),
				'filter' => [
					'@' . Item::FIELD_NAME_ID => $filteredBindings[$entityTypeId],
				],
				'order' => [
					Item::FIELD_NAME_CREATED_TIME => 'DESC',
				],
				'limit' => 1,
			]);

			if (!empty($items))
			{
				$target = ItemIdentifier::createByItem(reset($items));

				$this->logger->debug(
					'{date}: Found target {target} for by filtering for bindings {bindings}' . PHP_EOL,
					['target' => $target, 'bindings' => $bindings]
				);

				return $target;
			}
		}

		$this->logger->debug('{date}: No target found for bindings {bindings}' . PHP_EOL, ['bindings' => $bindings]);

		return null;
	}

	private function findAssigned(ItemIdentifier $target): ?int
	{
		$factory = $this->container->getFactory($target->getEntityTypeId());
		if (!$factory || !\CCrmOwnerType::isUseFactoryBasedApproach($target->getEntityTypeId()))
		{
			return null;
		}

		$item = current($factory->getItems([
			'select' => [Item::FIELD_NAME_ASSIGNED],
			'filter' => ['=ID' => $target->getEntityId()]
		]));

		return $item ? $item->getAssignedById() : null;
	}

	private function isFirstCallActivityWithFilesForItem(array $activityFields, ItemIdentifier $possibleTarget): bool
	{
		$this->logger->debug(
			'{date}: Trying to determine if the activity is first call activity with files for item: {activity}' . PHP_EOL,
			['activity' => $activityFields],
		);

		$allOtherCallActivityIdsOfTarget = ActivityTable::query()
			->setSelect(['ID'])
			->where('PROVIDER_ID', Call::ACTIVITY_PROVIDER_ID)
			->whereNotNull('ORIGIN_ID') // check that it's a real call from voximplant or another telephony
			->where('BINDINGS.OWNER_TYPE_ID', $possibleTarget->getEntityTypeId())
			->where('BINDINGS.OWNER_ID', $possibleTarget->getEntityId())
			->setLimit(100)
			->fetchCollection()
			->getIdList()
		;

		// exclude activity that we are testing right now
		$allOtherCallActivityIdsOfTarget = array_diff($allOtherCallActivityIdsOfTarget, [(int)$activityFields['ID']]);
		if (empty($allOtherCallActivityIdsOfTarget))
		{
			$this->logger->debug(
				'{date}: No other call activities found for target {target} {activity}' . PHP_EOL,
				['target' => $possibleTarget, 'activity' => $activityFields],
			);

			return true;
		}

		$incomingCallsActivityIds = $this->incomingChannel->getIncomingChannelActivityIds(
			$allOtherCallActivityIdsOfTarget,
		);
		if (empty($incomingCallsActivityIds))
		{
			$this->logger->debug(
				'{date}: All call activities found for target {target} are not incoming {ids} {activity}' . PHP_EOL,
				['target' => $possibleTarget, 'ids' => $allOtherCallActivityIdsOfTarget, 'activity' => $activityFields],
			);

			return true;
		}

		$createdTime = $activityFields['CREATED'] ?? null;
		if (is_string($createdTime))
		{
			try
			{
				$createdTime = DateTime::createFromUserTime($createdTime);
			}
			catch (ObjectException)
			{
				$createdTime = null;
			}
		}
		if (!($createdTime instanceof DateTime))
		{
			$this->logger->error(
				'{date}: Didnt find valid CREATED time in activity fields: {activity}' . PHP_EOL,
				['activity' => $activityFields],
			);

			return false;
		}

		$previousCalls = ActivityTable::query()
			->setSelect(['ID', 'STORAGE_ELEMENT_IDS', 'STORAGE_TYPE_ID', 'ORIGIN_ID'])
			->whereIn('ID', $incomingCallsActivityIds)
			->where('CREATED', '<', $createdTime)
			->fetchCollection()
		;
		if (count($previousCalls) <= 0)
		{
			$this->logger->debug(
				'{date}: All previous calls were created after the given activity: {activity}' . PHP_EOL,
				['activity' => $activityFields]
			);

			return true;
		}

		$emptyArraySerializedString = serialize([]);
		foreach ($previousCalls as $previousCall)
		{
			// if a call has any files, we consider that it has recordings
			if (
				!empty($previousCall->requireStorageElementIds())
				&& $previousCall->requireStorageElementIds() !== $emptyArraySerializedString
				&& AIManager::checkForSuitableAudios(
					(string)$previousCall->requireOriginId(),
					(int)$previousCall->requireStorageTypeId(),
					(string)$previousCall->requireStorageElementIds()
				)->isSuccess()
			)
			{
				$this->logger->debug(
					'{date}: Found a call activity that was created before and has files: {id}' . PHP_EOL,
					['ID' => $previousCall->getId()]
				);

				return false;
			}
		}

		$this->logger->debug('{date}: No other call activity with files found' . PHP_EOL);

		return true;
	}

	public function getSettingsByActivity(array $activity): ?AutostartSettings
	{
		$target = null;
		if (isset($activity['BINDINGS']) && is_array($activity['BINDINGS']))
		{
			$target = $this->findPossibleFillFieldsTargetByBindings($activity['BINDINGS']);
		}
		if (isset($activity['ID']) && (int)$activity['ID'] > 0)
		{
			$target = $this->findPossibleFillFieldsTarget((int)$activity['ID']);
		}

		if (!$target)
		{
			return null;
		}

		return AutostartSettings::get($target->getEntityTypeId(), $target->getCategoryId());
	}

	public function getSettingsByPreviousJobResult(Result $result): ?AutostartSettings
	{
		$dummyActivity = match ($result->getTypeId()) {
			TranscribeCallRecording::TYPE_ID, SummarizeCallTranscription::TYPE_ID => [
				'ID' => $result->getTarget()?->getEntityId(),
			],
			default => [],
		};

		return $this->getSettingsByActivity($dummyActivity);
	}
}

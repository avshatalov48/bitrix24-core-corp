<?php

namespace Bitrix\Crm\Integration\AI\Operation\Autostart;

use Bitrix\Crm\Activity\IncomingChannel;
use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Operation\Orchestrator;
use Bitrix\Crm\Integration\AI\Operation\Scenario;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\Integration\AI\SuitableAudiosChecker;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use CCrmOwnerType;
use Psr\Log\LoggerInterface;

final class AutoLauncher
{
	public const OPERATION_ADD = 1;
	public const OPERATION_UPDATE = 2;

	private Orchestrator $orchestrator;
	private ?LoggerInterface $logger;

	private ?ItemIdentifier $nextTarget;
	private ?int $userId;

	public function __construct(readonly int $activityOperation, readonly array $activityFields)
	{
		$this->orchestrator = new Orchestrator();
		$this->logger = AIManager::logger();

		$this->nextTarget = $this->orchestrator->findPossibleTargetByBindings($activityFields['BINDINGS'] ?? []);
		$this->userId = $this->nextTarget
			? $this->findAssigned($this->nextTarget)
			: null
		;
	}

	public static function isEnabled(): bool
	{
		return AIManager::isAiCallAutomaticProcessingAllowed()
			&& AIManager::isBaasServiceHasPackage()
			&& (
				AIManager::isEnabledInGlobalSettings()
				|| AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment)
			)
		;
	}

	public function run(array $changedFields = []): void
	{
		if (!self::isEnabled())
		{
			$this->logger->debug('{date}: Unable to autostart operation: AI operations in CRM is disabled (see settings)' . PHP_EOL);

			return;
		}

		$fillFieldsSettings = AIManager::isEnabledInGlobalSettings()
			? $this->orchestrator->getFillFieldsSettingsByActivity($this->activityFields)
			: null
		;
		$scoreCallSettings = AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment)
			? $this->orchestrator->getScoreCallSettingsByActivity($this->activityFields)
			: null;

		if (
			$fillFieldsSettings === null
			&& $scoreCallSettings === null
		)
		{
			$this->logger->debug('{date}: Unable to autostart operation: launch options not found' . PHP_EOL);

			return;
		}

		$direction = (int)($this->activityFields['DIRECTION'] ?? 0);
		if (
			!$fillFieldsSettings?->shouldAutostart(TranscribeCallRecording::TYPE_ID, $direction)
			&& !$scoreCallSettings?->shouldAutostart(TranscribeCallRecording::TYPE_ID, $direction)
		)
		{
			return;
		}

		$activityId = (int)($this->activityFields['ID'] ?? null);
		$storageTypeId = 0;
		$storageElementIds = [];
		$isJobOfSameTypeNotExistsForTarget = true;

		if ($this->activityOperation === self::OPERATION_ADD)
		{
			$this->logger->info(
				'{date}: Trying to autostart operation after call activity was added.'
				. ' Autostart fill fields settings {fillFieldsSettings}, score call settings {scoreCallSettings}, activity {activity}' . PHP_EOL,
				[
					'fillFieldsSettings' => $fillFieldsSettings ?? null,
					'scoreCallSettings' => $scoreCallSettings ?? null,
					'activity' => $this->activityFields
				],
			);

			$storageTypeId = (int)($this->activityFields['STORAGE_TYPE_ID'] ?? null);
			$storageElementIds = $this->getStorageElementIds($this->activityFields);
		}
		elseif ($this->activityOperation === self::OPERATION_UPDATE)
		{
			$this->logger->info(
				'{date}: Trying to autostart operation after call activity was updated.'
				. ' Autostart fill fields settings {fillFieldsSettings}, score call settings {scoreCallSettings}, changed fields {changedFields}, new activity state {activity}' . PHP_EOL,
				[
					'fillFieldsSettings' => $fillFieldsSettings ?? null,
					'scoreCallSettings' => $scoreCallSettings ?? null,
					'activity' => $this->activityFields,
					'changedFields' => $changedFields,
				],
			);

			$storageTypeId = (int)($changedFields['STORAGE_TYPE_ID'] ?? null);
			$storageElementIds = $this->getStorageElementIds($changedFields);
			$isJobOfSameTypeNotExistsForTarget = !JobRepository::getInstance()->isJobOfSameTypeAlreadyExistsForTarget(
				new ItemIdentifier(CCrmOwnerType::Activity, $activityId),
				TranscribeCallRecording::TYPE_ID,
			);
		}

		$isLaunchPossible = $isJobOfSameTypeNotExistsForTarget
			&& $this->isLaunchPossible($activityId, $storageTypeId, $storageElementIds)
		;

		if (!$isLaunchPossible)
		{
			$this->logger->debug('{date}: Unable to autostart operation: AI operation in CRM is not possible' . PHP_EOL);

			return;
		}

		$scenario = $this->detectLaunchScenarioBySettings(
			$fillFieldsSettings,
			$scoreCallSettings
		);

		if ($scenario !== Scenario::UNDEFINED_SCENARIO)
		{
			$this->logger->info(
				'{date}: Trying to autostart operation with type {operationType} with scenario "{scenario}"' . PHP_EOL,
				[
					'operationType' => TranscribeCallRecording::TYPE_ID,
					'scenario' => $scenario,
				]
			);

			AIManager::launchCallRecordingTranscription(
				$activityId,
				$scenario,
				$this->userId,
				$storageTypeId,
				max($storageElementIds),
				false,
			);
		}
	}

	private function detectLaunchScenarioBySettings(
		?FillFieldsSettings $fillFieldsSettings,
		?ScoreCallSettings $scoreCallSettings
	): string
	{
		$shouldFillFieldsStart = false;
		$shouldScoreCallStart = false;
		$direction = (int)($this->activityFields['DIRECTION'] ?? 0);

		if ($fillFieldsSettings?->shouldAutostart(TranscribeCallRecording::TYPE_ID, $direction))
		{
			if ($fillFieldsSettings?->isAutostartTranscriptionOnlyOnFirstCallWithRecording())
			{
				$shouldFillFieldsStart = $this->isFirstCallActivityWithFilesForItem();
			}
			else
			{
				$shouldFillFieldsStart = true;
			}
		}

		if ($scoreCallSettings?->shouldAutostart(TranscribeCallRecording::TYPE_ID, $direction))
		{
			if ($scoreCallSettings?->isAutostartTranscriptionOnlyOnFirstCallWithRecording())
			{
				$shouldScoreCallStart = $this->isFirstCallActivityWithFilesForItem();
			}
			else
			{
				$shouldScoreCallStart = true;
			}
		}

		if ($shouldFillFieldsStart && $shouldScoreCallStart)
		{
			return Scenario::FULL_SCENARIO;
		}

		if ($shouldFillFieldsStart)
		{
			return Scenario::FILL_FIELDS_SCENARIO;
		}

		if ($shouldScoreCallStart)
		{
			return Scenario::CALL_SCORING_SCENARIO;
		}

		return Scenario::UNDEFINED_SCENARIO;
	}

	private function findAssigned(ItemIdentifier $target): ?int
	{
		if (!CCrmOwnerType::isUseFactoryBasedApproach($target->getEntityTypeId()))
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($target->getEntityTypeId());

		return $factory?->getItem($target->getEntityId(), [Item::FIELD_NAME_ASSIGNED])?->getAssignedById();
	}

	private function isFirstCallActivityWithFilesForItem(): bool
	{
		$activityFields = $this->activityFields;
		$possibleTarget = $this->nextTarget;

		$this->logger->debug(
			'{date}: Trying to determine if the activity is first call activity with files for item: {activity}' . PHP_EOL,
			['activity' => $activityFields],
		);

		$allOtherCallActivityIdsOfTarget = ActivityTable::query()
			->setSelect(['ID'])
			->where('PROVIDER_ID', Call::ACTIVITY_PROVIDER_ID)
			->whereNotNull('ORIGIN_ID') // check that it's a real call from voximplant
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

		$incomingCallsActivityIds = IncomingChannel::getInstance()->getIncomingChannelActivityIds(
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
				&& (new SuitableAudiosChecker($previousCall->requireOriginId(), $previousCall->requireStorageTypeId(), $previousCall->requireStorageElementIds()))
					->run()
					->isSuccess()
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

	private function isLaunchPossible(int $activityId, int $storageTypeId, array $storageElementIds): bool
	{
		$originId = (string)($this->activityFields['ORIGIN_ID'] ?? '');

		return $activityId > 0
			&& $this->nextTarget
			&& $this->userId > 0
			&& Call::hasRecordings($this->activityFields)
			&& (new SuitableAudiosChecker($originId, $storageTypeId, serialize($storageElementIds)))
				->run()
				->isSuccess()
			;
	}

	private function getStorageElementIds(array $activityFields): array
	{
		$storageElementIds = (array)($activityFields['STORAGE_ELEMENT_IDS'] ?? []);

		return array_filter(
			array_map('intval', $storageElementIds),
			static fn(int $id) => $id > 0
		);
	}
}

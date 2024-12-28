<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItemChecker;
use Bitrix\Crm\Copilot\CallAssessment\ItemFactory;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Dto\SummarizeCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Dto\TranscribeCallRecordingPayload;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;
use Bitrix\Crm\Integration\AI\ErrorCode as AIErrorCode;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Integration\AI\Operation\Autostart\FillFieldsSettings;
use Bitrix\Crm\Integration\AI\Operation\Autostart\ScoreCallSettings;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use CCrmActivity;
use CCrmOwnerType;
use Psr\Log\LoggerInterface;

/**
 * The place for automatic AI flow - which operation should start after another is finished,
 * what are possible targets for them.
 */
final class Orchestrator
{
	private Container $container;
	private LoggerInterface $logger;

	public function __construct()
	{
		$this->container = Container::getInstance();
		$this->logger = AIManager::logger();
	}

	public function launchNextOperationIfNeeded(Result $previousJobResult, FillFieldsSettings $settings): void
	{
		$this->logger->info(
			'{date}: Trying to autostart next operation after finish of another operation.'
			. ' Autostart settings {autostartSettings}, previous job result {previousJobResult}' . PHP_EOL,
			['autostartSettings' => $settings, 'previousJobResult' => $previousJobResult],
		);

		$activityId = $previousJobResult->getTarget()?->getEntityId();
		$activity = $this->container->getActivityBroker()->getById($activityId);

		if (
			$previousJobResult->getTypeId() === TranscribeCallRecording::TYPE_ID
			&& $previousJobResult->isSuccess()
			&& $previousJobResult->getPayload() instanceof TranscribeCallRecordingPayload
			&& !empty($previousJobResult->getPayload()->transcription)
			&& $settings->shouldAutostart(
				SummarizeCallTranscription::TYPE_ID,
				(int)($activity['DIRECTION'] ?? 0),
				false
			)
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

			$summarizeCall->setIsManualLaunch($previousJobResult->isManualLaunch());

			$summarizeCall->launch();
		}

		if (
			$previousJobResult->getTypeId() === SummarizeCallTranscription::TYPE_ID
			&& $previousJobResult->isSuccess()
			&& $previousJobResult->getPayload() instanceof SummarizeCallTranscriptionPayload
			&& !empty($previousJobResult->getPayload()->summary)
			&& $settings->shouldAutostart(
				FillItemFieldsFromCallTranscription::TYPE_ID,
				(int)($activity['DIRECTION'] ?? 0),
				false
			)
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

				$fillItemFields->setIsManualLaunch($previousJobResult->isManualLaunch());

				$fillItemFields->launch();
			}
		}
	}

	// @todo: rename
	public function findPossibleFillFieldsTarget(int $activityId): ?ItemIdentifier
	{
		$bindings = CCrmActivity::GetBindings($activityId);
		$bindings = is_array($bindings) ? $bindings : [];

		return $this->findPossibleTargetByBindings($bindings);
	}

	public function findPossibleTargetByBindings(array $bindings): ?ItemIdentifier
	{
		$this->logger->debug(
			'{date}: Trying to find possible fill item fields target by activity bindings {bindings}' . PHP_EOL,
			[
				'bindings' => $bindings,
			],
		);

		static $whitelist = [
			// sorted by priority - first found entity type is used
			CCrmOwnerType::Deal => CCrmOwnerType::Deal,
			CCrmOwnerType::Lead => CCrmOwnerType::Lead,
		];

		$filteredBindings = $this->filterBindings($bindings, $whitelist);
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
					Item::FIELD_NAME_ID => 'DESC',
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

		$this->logger->debug(
			'{date}: No target found for bindings {bindings}' . PHP_EOL,
			[
				'bindings' => $bindings,
			]
		);

		return null;
	}

	public function getFillFieldsSettingsByActivity(array $activity): ?FillFieldsSettings
	{
		$target = null;
		if (isset($activity['BINDINGS']) && is_array($activity['BINDINGS']))
		{
			$target = $this->findPossibleTargetByBindings($activity['BINDINGS']);
		}

		if (isset($activity['ID']) && (int)$activity['ID'] > 0)
		{
			$target = $this->findPossibleFillFieldsTarget((int)$activity['ID']);
		}

		if (!$target)
		{
			return null;
		}

		return FillFieldsSettings::get($target->getEntityTypeId(), $target->getCategoryId());
	}

	public function getFillFieldsSettingsByPreviousJobResult(Result $result): ?FillFieldsSettings
	{
		$dummyActivity = match ($result->getTypeId()) {
			TranscribeCallRecording::TYPE_ID, SummarizeCallTranscription::TYPE_ID => [
				'ID' => $result->getTarget()?->getEntityId(),
			],
			default => [],
		};

		return $this->getFillFieldsSettingsByActivity($dummyActivity);
	}

	public function getScoreCallSettingsByActivity(array $activity): ?ScoreCallSettings
	{
		$activityId = (int)($activity['ID'] ?? null);
		if ($activityId <= 0)
		{
			return null;
		}

		$callAssessmentItem = ItemFactory::getByActivityId($activityId);
		$checkerResult = CallAssessmentItemChecker::getInstance()
			->setItem(ItemFactory::getByActivityId($activityId))
			->run()
		;
		if (!$checkerResult->isSuccess())
		{
			return null;
		}

		return new ScoreCallSettings($callAssessmentItem?->getAutoCheckTypeId());
	}

	public function launchScoreCallOperationIfNeeded(Result $result, bool $checkExecuted = false): void
	{
		if (!AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment))
		{
			return;
		}

		$activityId = $result->getTarget()?->getEntityTypeId() === CCrmOwnerType::Activity
			? $result->getTarget()?->getEntityId()
			: FillItemFieldsFromCallTranscription::getParentActivityId($result);
		if ($activityId <= 0)
		{
			$this->logger->error(
				'{date}: Unable to autostart operation with type {operationType}: unable to find activity ID' . PHP_EOL,
				['operationType' => ScoreCall::TYPE_ID]
			);

			return;
		}

		$transcriptResult = JobRepository::getInstance()->getTranscribeCallRecordingResultByActivity($activityId);
		if (is_null($transcriptResult))
		{
			$this->logger->error(
				'{date}: Unable to autostart operation with type {operationType}: CoPilot call transcription not found' . PHP_EOL,
				['operationType' => ScoreCall::TYPE_ID]
			);

			return;
		}

		if (!$transcriptResult->isSuccess())
		{
			$this->logger->error(
				'{date}: Unable to autostart operation with type {operationType}: {errors} ' . PHP_EOL,
				[
					'operationType' => ScoreCall::TYPE_ID,
					'errors' => $transcriptResult->getErrors()
				]
			);

			return;
		}

		if ($transcriptResult->getNextTypeId() === SummarizeCallTranscription::TYPE_ID)
		{
			return;
		}

		$payload = $transcriptResult->getPayload();
		if (is_null($payload))
		{
			$this->logger->error(
				'{date}: Unable to autostart operation with type {operationType}: {errors} ' . PHP_EOL,
				[
					'operationType' => ScoreCall::TYPE_ID,
					'errors' => AIErrorCode::getPayloadNotFoundError()
				]
			);

			return;
		}

		/** @var Result<TranscribeCallRecordingPayload> $transcriptResult */
		$scoreCall = new ScoreCall(
			$transcriptResult->getTarget(),
			$transcriptResult->getPayload()->transcription,
			$transcriptResult->getUserId() ?? Container::getInstance()->getContext()->getUserId(),
			$transcriptResult->getJobId(),
		);

		if ($checkExecuted)
		{
			$executedJob = QueueTable::query()
				->setSelect(['ID'])
				->where('ENTITY_TYPE_ID', $transcriptResult->getTarget()?->getEntityTypeId())
				->where('ENTITY_ID', $transcriptResult->getTarget()?->getEntityId())
				->where('TYPE_ID', ScoreCall::TYPE_ID)
				->setLimit(1)
				->fetchObject()
			;
			if ($executedJob)
			{
				return;
			}
		}

		$this->logger->info(
			'{date}: Trying to autostart operation with type {operationType}' . PHP_EOL,
			['operationType' => ScoreCall::TYPE_ID]
		);

		$scoreCall->setIsManualLaunch($transcriptResult->isManualLaunch());
		$scoreCall->launch();
	}

	private function filterBindings(array $bindings, array $whitelist): array
	{
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

		return $filteredBindings;
	}
}

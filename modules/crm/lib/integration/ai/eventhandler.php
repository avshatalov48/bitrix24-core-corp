<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Quality;
use Bitrix\AI\Tuning;
use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Integration\AI\Model\EO_Queue;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Integration\AI\Operation\Autostart\AutoLauncher;
use Bitrix\Crm\Integration\AI\Operation\ExtractScoringCriteria;
use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\Orchestrator;
use Bitrix\Crm\Integration\AI\Operation\ScoreCall;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\Integration\Analytics\Builder\AI\CallActivityWithAudioRecordingEvent;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Integration\VoxImplantManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use CCrmOwnerType;

final class EventHandler
{
	public const SETTINGS_FILL_ITEM_FROM_CALL_ENABLED_CODE = 'crm_copilot_fill_item_from_call_enabled';
	public const SETTINGS_FILL_ITEM_FROM_CALL_ENGINE_AUDIO_CODE = 'crm_copilot_fill_item_from_call_engine_audio';
	public const SETTINGS_FILL_ITEM_FROM_CALL_ENGINE_TEXT_CODE = 'crm_copilot_fill_item_from_call_engine_text';
	public const SETTINGS_FILL_CRM_TEXT_ENABLED_CODE = 'crm_copilot_fill_crm_text_enabled';
	public const SETTINGS_CALL_ASSESSMENT_ENABLED_CODE = 'crm_copilot_call_assessment_enabled';
	public const SETTINGS_CALL_ASSESSMENT_ENGINE_CODE = 'crm_copilot_call_assessment_engine_code';

	public const ENGINE_CATEGORY = 'text';

	private const SETTINGS_GROUP_CODE = 'crm_copilot';

	// region Tuning
	public static function onTuningLoad(): EventResult
	{
		$result = new EventResult();

		$items = [];
		$groups = [];

		if (Engine::getByCategory(self::ENGINE_CATEGORY, Context::getFake()))
		{
			$items[self::SETTINGS_FILL_CRM_TEXT_ENABLED_CODE] = [
				'group' => Tuning\Defaults::GROUP_TEXT,
				'header' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_FILL_TODO_TEXT_HEADER'),
				'title' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_FILL_TODO_TEXT_TITLE'),
				'type' => Tuning\Type::BOOLEAN,
				'default' => true,
				'sort' => 600,
			];
		}

		if (AIManager::isAiCallProcessingEnabled())
		{
			$groups[self::SETTINGS_GROUP_CODE] = [
				'title' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_GROUP_TITLE'),
				'description' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_GROUP_DESCRIPTION'),
				'helpdesk' => 18799442
			];

			$items[self::SETTINGS_FILL_ITEM_FROM_CALL_ENABLED_CODE] = [
				'group' => self::SETTINGS_GROUP_CODE,
				'title' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_FILL_ITEM_FROM_CALL_TITLE'),
				'header' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_FILL_ITEM_FROM_CALL_HEADER'),
				'type' => Tuning\Type::BOOLEAN,
				'default' => true,
				'sort' => 10,
			];

			$quality = new Quality([
				Quality::QUALITIES['transcribe']
			]);
			$items[self::SETTINGS_FILL_ITEM_FROM_CALL_ENGINE_AUDIO_CODE] = array_merge(
				Tuning\Defaults::getProviderSelectFieldParams(Engine::CATEGORIES['audio'], $quality),
				[
					'group' => self::SETTINGS_GROUP_CODE,
					'title' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_ENGINE_AUDIO_TITLE'),
					'sort' => 20,
				],
			);

			$quality = new Quality([
				Quality::QUALITIES['fields_highlight'],
				Quality::QUALITIES['translate'],
			]);
			$items[self::SETTINGS_FILL_ITEM_FROM_CALL_ENGINE_TEXT_CODE] = array_merge(
				Tuning\Defaults::getProviderSelectFieldParams(Engine::CATEGORIES['text'], $quality),
				[
					'group' => self::SETTINGS_GROUP_CODE,
					'title' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_ENGINE_TEXT_TITLE'),
					'sort' => 30,
				],
			);

			$items[self::SETTINGS_CALL_ASSESSMENT_ENABLED_CODE] = [
				'group' => self::SETTINGS_GROUP_CODE,
				'title' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTING_CALL_ASSESSMENT_TITLE'),
				'header' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_CALL_ASSESSMENT_HEADER'),
				'type' => Tuning\Type::BOOLEAN,
				'default' => true,
				'sort' => 15,
			];

			$quality = new Quality([
				Quality::QUALITIES['translate'],
			]);
			$items[self::SETTINGS_CALL_ASSESSMENT_ENGINE_CODE] = [
				...Tuning\Defaults::getProviderSelectFieldParams(Engine::CATEGORIES['text'], $quality),
				'group' => self::SETTINGS_GROUP_CODE,
				'title' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_CALL_ASSESSMENT_ENGINE_TITLE'),
				'sort' => 50,
			];
		}

		$result->modifyFields([
			'groups' => $groups,
			'items' => $items,
		]);

		return $result;
	}
	// endregion

	// region Queue
	public static function onQueueJobExecute(Event $event): void
	{
		if (!AIManager::isAiCallProcessingEnabled())
		{
			return;
		}

		AIManager::logger()->info(
			'{date}: Received event {eventName}: {event}' . PHP_EOL,
			[
				'eventName' => __FUNCTION__,
				'event' => $event,
			],
		);

		$hash = self::getValidJobHash($event);
		if ($hash === null)
		{
			return;
		}

		$job = QueueTable::query()->setSelect(['*'])->where('HASH', $hash)->fetchObject();
		if (
			!$job
			|| $job->requireExecutionStatus() !== QueueTable::EXECUTION_STATUS_PENDING
			|| in_array($job->requireEntityTypeId(), CCrmOwnerType::getAllSuspended(), true)
		)
		{
			AIManager::logger()->debug(
				'{date}: Dont process event {eventName} because job dont exists or invalid: {job}' . PHP_EOL,
				[
					'eventName' => __FUNCTION__,
					'job' => $job?->collectValues(fieldsMask: FieldTypeMask::FLAT),
				],
			);

			return;
		}

		// ------------------------------ @todo: refactor block ------------------------------
		$result = self::getQueueJobExecuteResult($event, $job);
		if ($result)
		{
			$orchestrator = new Orchestrator();

			if (
				$result->getTypeId() === TranscribeCallRecording::TYPE_ID
				&& $result->getNextTypeId() === ScoreCall::TYPE_ID
			)
			{
				$orchestrator->launchScoreCallOperationIfNeeded($result);
			}
			elseif (AIManager::isEnabledInGlobalSettings())
			{
				$settings = $orchestrator->getFillFieldsSettingsByPreviousJobResult($result);
				if ($settings)
				{
					$orchestrator->launchNextOperationIfNeeded(
						$result,
						$settings,
					);
				}
				elseif (
					$result->getTypeId() === FillItemFieldsFromCallTranscription::TYPE_ID
					&& $result->isSuccess()
				)
				{
					$orchestrator->launchScoreCallOperationIfNeeded($result, true);
				}
			}
		}
		// ------------------------------------------------------------------------------------

		AIManager::logger()->debug(
			'{date}: Event {eventName} was processed with result {result}' . PHP_EOL,
			[
				'eventName' => __FUNCTION__,
				'result' => $result,
			]
		);
	}

	public static function onQueueJobFail(Event $event): void
	{
		if (!AIManager::isAiCallProcessingEnabled())
		{
			return;
		}

		AIManager::logger()->info(
			'{date}: Received event {eventName}: {event}' . PHP_EOL,
			[
				'eventName' => __FUNCTION__,
				'event' => $event,
			],
		);

		$hash = self::getValidJobHash($event);
		if ($hash === null)
		{
			return;
		}

		$job = QueueTable::query()->setSelect(['*'])->where('HASH', $hash)->fetchObject();
		if (
			!$job
			|| $job->requireExecutionStatus() !== QueueTable::EXECUTION_STATUS_PENDING
			|| in_array($job->requireEntityTypeId(), CCrmOwnerType::getAllSuspended(), true)
		)
		{
			AIManager::logger()->debug(
				'{date}: Dont process event {eventName} because job dont exists or invalid: {job}' . PHP_EOL,
				[
					'eventName' => __FUNCTION__,
					'job' => $job?->collectValues(fieldsMask: FieldTypeMask::FLAT),
				],
			);

			return;
		}

		if ((int)$job->requireTypeId() === TranscribeCallRecording::TYPE_ID)
		{
			TranscribeCallRecording::onQueueJobFail($event, $job);
		}
		elseif ((int)$job->requireTypeId() === SummarizeCallTranscription::TYPE_ID)
		{
			SummarizeCallTranscription::onQueueJobFail($event, $job);
		}
		elseif ((int)$job->requireTypeId() === FillItemFieldsFromCallTranscription::TYPE_ID)
		{
			FillItemFieldsFromCallTranscription::onQueueJobFail($event, $job);
		}
		elseif ((int)$job->requireTypeId() === ScoreCall::TYPE_ID)
		{
			ScoreCall::onQueueJobFail($event, $job);
		}
		elseif ((int)$job->requireTypeId() === ExtractScoringCriteria::TYPE_ID)
		{
			ExtractScoringCriteria::onQueueJobFail($event, $job);
		}
	}
	//endregion

	// region Activity
	public static function onAfterCallActivityAdd(array $activityFields): void
	{
		if (
			VoxImplantManager::isActivityBelongsToVoximplant($activityFields)
			&& Call::hasRecordings($activityFields)
		)
		{
			self::registerCallActivityWithAudioRecordingEvent($activityFields);
		}

		if (AutoLauncher::isEnabled())
		{
			(new AutoLauncher(AutoLauncher::OPERATION_ADD, $activityFields))->run();
		}
	}

	public static function onAfterCallActivityUpdate(array $changedFields, array $oldFields, array $newFields): void
	{
		if (
			VoxImplantManager::isActivityBelongsToVoximplant($newFields)
			// if records were added
			&& !Call::hasRecordings($oldFields)
			&& Call::hasRecordings($newFields)
		)
		{
			self::registerCallActivityWithAudioRecordingEvent($newFields);
		}

		if (AutoLauncher::isEnabled())
		{
			(new AutoLauncher(AutoLauncher::OPERATION_UPDATE, $newFields))->run($changedFields);
		}
	}
	//endregion

	//region Recycle bin
	public static function onItemMoveToBin(ItemIdentifier $target, ItemIdentifier $recycleBinItem): void
	{
		QueueTable::deletePending($target);

		QueueTable::rebind($target, $recycleBinItem);
	}

	public static function onItemDelete(ItemIdentifier $target): void
	{
		QueueTable::deleteByItem($target);
	}

	public static function onItemRestoreFromRecycleBin(ItemIdentifier $target, ItemIdentifier $recycleBinItem): void
	{
		QueueTable::rebind($recycleBinItem, $target);
	}
	// endregion

	private static function getValidJobHash(Event $event): ?string
	{
		$hash = $event->getParameter('queue');

		return is_string($hash) && !empty($hash)
			? $hash
			: null
		;
	}

	private static function getQueueJobExecuteResult(Event $event, EO_Queue $job): ?Result
	{
		if ((int)$job->requireTypeId() === TranscribeCallRecording::TYPE_ID)
		{
			return TranscribeCallRecording::onQueueJobExecute($event, $job);
		}

		if ((int)$job->requireTypeId() === SummarizeCallTranscription::TYPE_ID)
		{
			return SummarizeCallTranscription::onQueueJobExecute($event, $job);
		}

		if ((int)$job->requireTypeId() === FillItemFieldsFromCallTranscription::TYPE_ID)
		{
			return FillItemFieldsFromCallTranscription::onQueueJobExecute($event, $job);
		}

		if ((int)$job->requireTypeId() === ScoreCall::TYPE_ID)
		{
			return ScoreCall::onQueueJobExecute($event, $job);
		}

		if ((int)$job->requireTypeId() === ExtractScoringCriteria::TYPE_ID)
		{
			return ExtractScoringCriteria::onQueueJobExecute($event, $job);
		}

		return null;
	}

	private static function registerCallActivityWithAudioRecordingEvent(array $activityFields): void
	{
		$nullSafeInt = static fn(array $input, string $key) => (int)($input[$key] ?? null);

		$originId = $activityFields['ORIGIN_ID'] ?? '';
		$callId = VoxImplantManager::extractCallIdFromOriginId($originId);

		$builder = (new CallActivityWithAudioRecordingEvent())
			->setActivityOwnerTypeId($nullSafeInt($activityFields, 'OWNER_TYPE_ID'))
			->setActivityId($nullSafeInt($activityFields, 'ID'))
			->setActivityDirection($nullSafeInt($activityFields, 'DIRECTION'))
			->setCallDuration(VoxImplantManager::getCallDuration($callId) ?? 0)
		;
		$builder->buildEvent()->send();
		// send the same analytics only with different TOOL and CATEGORY
		$builder
			->setTool(Dictionary::TOOL_CRM)
			->setCategory(Dictionary::CATEGORY_AI_OPERATIONS)
			->buildEvent()
			->send()
		;
	}
}

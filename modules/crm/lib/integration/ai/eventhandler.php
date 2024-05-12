<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Tuning;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\Orchestrator;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use CCrmOwnerType;

final class EventHandler
{
	public const SETTINGS_FILL_ITEM_FROM_CALL_ENABLED_CODE = 'crm_copilot_fill_item_from_call_enabled';
	public const SETTINGS_FILL_CRM_TEXT_ENABLED_CODE = 'crm_copilot_fill_crm_text_enabled';

	public const ENGINE_CATEGORY = 'text';

	private const SETTINGS_GROUP_CODE = 'crm_copilot';

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
				'title' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_GROUP_TITLE'),
				'header' => Loc::getMessage('CRM_INTEGRATION_AI_EVENTHANDLER_SETTINGS_FILL_ITEM_FROM_CALL_HEADER'),
				'type' => Tuning\Type::BOOLEAN,
				'default' => true,
			];
		}

		$result->modifyFields([
			'items' => $items,
			'groups' => $groups,
		]);

		return $result;
	}

	public static function onQueueJobExecute(Event $event): void
	{
		if (!AIManager::isAiCallProcessingEnabled())
		{
			return;
		}

		AIManager::logger()->info(
			'{date}: Received event {eventName}: {event}' . PHP_EOL,
			['eventName' => __FUNCTION__, 'event' => $event],
		);

		$hash = $event->getParameter('queue');
		if (!is_string($hash) || empty($hash))
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
				['eventName' => __FUNCTION__, 'job' => $job?->collectValues(fieldsMask: FieldTypeMask::FLAT)],
			);

			return;
		}

		$result = null;
		if ((int)$job->requireTypeId() === TranscribeCallRecording::TYPE_ID)
		{
			$result = TranscribeCallRecording::onQueueJobExecute($event, $job);
		}
		elseif ((int)$job->requireTypeId() === SummarizeCallTranscription::TYPE_ID)
		{
			$result = SummarizeCallTranscription::onQueueJobExecute($event, $job);
		}
		elseif ((int)$job->requireTypeId() === FillItemFieldsFromCallTranscription::TYPE_ID)
		{
			$result = FillItemFieldsFromCallTranscription::onQueueJobExecute($event, $job);
		}

		if ($result && AIManager::isEnabledInGlobalSettings())
		{
			$orchestrator = new Orchestrator();
			$settings = $orchestrator->getSettingsByPreviousJobResult($result);
			if ($settings)
			{
				$orchestrator->launchNextOperationIfNeeded(
					$result,
					$settings,
				);
			}
		}

		AIManager::logger()->debug(
			'{date}: Event {eventName} was processed with result {result}' . PHP_EOL,
			['eventName' => __FUNCTION__, 'result' => $result]
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
			['eventName' => __FUNCTION__, 'event' => $event],
		);

		$hash = $event->getParameter('queue');
		if (!is_string($hash) || empty($hash))
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
				['eventName' => __FUNCTION__, 'job' => $job?->collectValues(fieldsMask: FieldTypeMask::FLAT)],
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
	}
	//endregion

	public static function onAfterCallActivityAdd(array $activityFields): void
	{
		if (!AIManager::isAiCallProcessingEnabled() || !AIManager::isEnabledInGlobalSettings())
		{
			return;
		}

		$orchestrator = new Orchestrator();

		$settings = $orchestrator->getSettingsByActivity($activityFields);
		if ($settings)
		{
			$orchestrator->launchOperationAfterCallActivityAddIfNeeded(
				$activityFields,
				$settings,
			);
		}
	}

	public static function onAfterCallActivityUpdate(array $changedFields, array $newFields): void
	{
		if (!AIManager::isAiCallProcessingEnabled() || !AIManager::isEnabledInGlobalSettings())
		{
			return;
		}

		$orchestrator = new Orchestrator();

		$settings = $orchestrator->getSettingsByActivity($newFields);
		if ($settings)
		{
			$orchestrator->launchOperationAfterCallActivityUpdateIfNeeded(
				$newFields,
				$changedFields,
				$settings,
			);
		}
	}

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
}

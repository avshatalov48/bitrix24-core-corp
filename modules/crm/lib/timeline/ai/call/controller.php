<?php

namespace Bitrix\Crm\Timeline\AI\Call;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Timeline\LogMessageController;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Crm\Timeline\TimelineEntry\Facade;
use CCrmOwnerType;

final class Controller extends Timeline\Controller
{
	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	// region Wrappers for LogMessageController and AI\Call\Controller
	public function onStartRecordTranscript(ItemIdentifier $identifier, int $activityId, int $userId = null): void
	{
		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
			],
			LogMessageType::AI_CALL_START_RECORD_TRANSCRIPT,
			$userId
		);
	}

	public function onFinishRecordTranscript(ItemIdentifier $identifier, int $activityId, array $settings = [], int $userId = null): void
	{
		$this->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'SETTINGS' => $settings,
			],
			CategoryType::RECORD_TRANSCRIPT_FINISHED,
			$userId
		);
	}

	public function onStartRecordTranscriptSummary(ItemIdentifier $identifier, int $activityId, int $userId = null): void
	{
		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
			],
			LogMessageType::AI_CALL_START_RECORD_TRANSCRIPT_SUMMARY,
			$userId
		);
	}

	public function onFinishRecordTranscriptSummary(ItemIdentifier $identifier, int $activityId, array $settings = [], int $userId = null): void
	{
		$this->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'SETTINGS' => $settings,
			],
			CategoryType::RECORD_TRANSCRIPT_SUMMARY_FINISHED,
			$userId
		);
	}

	public function onStartFillingEntityFields(ItemIdentifier $identifier, int $activityId, int $userId = null): void
	{
		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
			],
			LogMessageType::AI_CALL_START_FILLING_ENTITY_FIELDS,
			$userId
		);
	}

	public function onFinishFillingEntityFields(ItemIdentifier $identifier, int $activityId, array $settings = [], int $userId = null): void
	{
		$this->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'SETTINGS' => $settings,

			],
			CategoryType::FILLING_ENTITY_FIELDS_FINISHED,
			$userId
		);
	}

	public function onFinishProcessingEntityFields(ItemIdentifier $identifier, int $activityId, int $userId = null): void
	{
		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
			],
			LogMessageType::AI_CALL_FINISH_FILLING_ENTITY_FIELDS,
			$userId
		);
	}

	public function onStartCallScoring(ItemIdentifier $identifier, int $activityId, int $userId = null): void
	{
		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
			],
			LogMessageType::AI_CALL_START_SCORING,
			$userId
		);
	}

	public function onFinishCallScoring(ItemIdentifier $identifier, int $activityId, array $settings = [], int $userId = null): void
	{
		$this->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'SETTINGS' => $settings,
			],
			CategoryType::CALL_SCORING_FINISHED,
			$userId
		);
	}

	public function onCallScoringEmptyResult(ItemIdentifier $identifier, int $activityId, array $settings = [], int $userId = null): void
	{
		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'SETTINGS' => $settings,
			],
			LogMessageType::AI_CALL_SCORING_EMPTY,
			$userId
		);
	}

	public function onLaunchError(ItemIdentifier $identifier, int $activityId, array $settings = [], int $userId = null): void
	{
		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'SETTINGS' => $settings,
			],
			LogMessageType::AI_CALL_LAUNCH_ERROR,
			$userId
		);
	}

	public function onAutomationLaunchError(
		ItemIdentifier $identifier,
		int $activityId,
		array $settings,
		int $userId = null,
	): void
	{
		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'ENTITY_ID' => $identifier->getEntityId(),
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'SETTINGS' => $settings,
			],
			LogMessageType::AI_CALL_AUTOMATION_LAUNCH_ERROR,
			$userId,
		);
	}
	// endregion

	public function onCreate(array $input, int $typeCategoryId, ?int $authorId = null): void
	{
		if (empty($input))
		{
			return;
		}

		// LEAD or DEAL
		$entityTypeId = $input['ENTITY_TYPE_ID'] ?? null;
		$entityId = $input['ENTITY_ID'] ?? null;
		if (!isset($entityTypeId, $entityId))
		{
			return;
		}

		$bindings[] = [
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId
		];
		$params = [
			'TYPE_CATEGORY_ID' => $typeCategoryId,
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId,
			'AUTHOR_ID' => ($authorId > 0) ? $authorId : self::getCurrentOrDefaultAuthorId(),
			'SETTINGS' => $input['SETTINGS'] ?? [],
			'BINDINGS' => $bindings,
		];

		if ($input['ASSOCIATED_ENTITY_TYPE_ID'])
		{
			$params['ASSOCIATED_ENTITY_TYPE_ID'] = $input['ASSOCIATED_ENTITY_TYPE_ID'];
		}

		if ($input['ASSOCIATED_ENTITY_ID'])
		{
			$params['ASSOCIATED_ENTITY_ID'] = $input['ASSOCIATED_ENTITY_ID'];
		}

		if (isset($input['CREATED']) && $input['CREATED'])
		{
			$params['CREATED'] = $input['CREATED'];
		}

		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			Facade::AI_CALL_PROCESSING,
			$params
		);
		if ($timelineEntryId <= 0)
		{
			return;
		}

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}
	}

	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		return $data;
	}

	public function prepareSearchContent(array $params): string
	{
		return '';
	}
}

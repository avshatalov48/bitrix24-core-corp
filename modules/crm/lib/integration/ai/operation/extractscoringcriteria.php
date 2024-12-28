<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\AI\Context;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessmentTable;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Dto\ExtractScoringCriteriaPayload;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\Integration\AI\Model\EO_Queue;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Integration\Analytics\Builder\AI\AIBaseEvent;
use Bitrix\Crm\Integration\Analytics\Builder\AI\ExtractScoringCriteriaEvent;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Web\Json;
use CCrmOwnerType;

final class ExtractScoringCriteria extends AbstractOperation
{
	public const TYPE_ID = 5;
	public const CONTEXT_ID = 'extract_scoring_criteria';

	protected const PAYLOAD_CLASS = ExtractScoringCriteriaPayload::class;
	protected const ENGINE_CODE = EventHandler::SETTINGS_CALL_ASSESSMENT_ENGINE_CODE;

	public function __construct(
		ItemIdentifier $target,
		private readonly string $prompt,
		?int $userId = null,
		?int $parentJobId = null,
	)
	{
		parent::__construct($target, $userId, $parentJobId);
	}

	public static function isSuitableTarget(ItemIdentifier $target): bool
	{
		return $target->getEntityTypeId() === CCrmOwnerType::CopilotCallAssessment;
	}

	protected static function checkPreviousJobs(ItemIdentifier $target, int $parentId): \Bitrix\Main\Result
	{
		return new \Bitrix\Main\Result(); // no parent
	}

	protected function getAIPayload(): \Bitrix\Main\Result
	{
		return (new \Bitrix\Main\Result())->setData([
			'payload' => (new \Bitrix\AI\Payload\Prompt('scoring_criteria_extraction'))
				->setMarkers([
					'user_input' => $this->prompt,
				])
			,
		]);
	}

	protected function getStubPayload(): mixed
	{
		$fields = [
			'status' => true,
			'criteria' => [
				'Mention the name of the factory',
				'Call the client by name',
				'Specify the order',
				'Ask what the order is for',
				'Ask if the cake needs customization',
			],
		];

		return Json::encode($fields);
	}

	protected static function notifyTimelineAfterSuccessfulLaunch(Result $result): void
	{
		// operation is not used in the timeline
	}

	protected static function notifyTimelineAfterSuccessfulJobFinish(Result $result): void
	{
		// operation is not used in the timeline
	}

	protected static function notifyAboutLimitExceededError(Result $result): void
	{
		// not implemented yet
	}

	protected static function onAfterSuccessfulJobFinish(Result $result, ?Context $context = null): void
	{
		/** @var ExtractScoringCriteriaPayload $payload */
		$payload = $result->getPayload();
		if (!$payload || !$result->isSuccess())
		{
			AIManager::logger()->error(
				'{date}: {class}: Unable to update extracted scoring criteria because of job error: {target}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
				],
			);

			return;
		}

		$scriptId = $result->getTarget()?->getEntityId() ?? 0;
		$scriptData = CopilotCallAssessmentController::getInstance()->getById($scriptId);
		if (!$scriptData)
		{
			AIManager::logger()->error(
				'{date}: {class}: Target not found or invalid when trying to update extracted scoring criteria: {target}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
				],
			);

			return;
		}

		$updateResult = CopilotCallAssessmentTable::update($scriptId, [
			'GIST' => implode(PHP_EOL, $payload->criteria),
			'JOB_ID' => $result->getJobId(),
			'STATUS' => QueueTable::EXECUTION_STATUS_SUCCESS,
			'UPDATED_AT' => $scriptData->getUpdatedAt(),
			'UPDATED_BY_ID' => $scriptData->getUpdatedById(),
		]);
		if (!$updateResult->isSuccess())
		{
			AIManager::logger()->error(
				'{date}: {class}: Got error while trying to update call assessment item fields: target {target}, errors {errors}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
					'errors' => $updateResult->getErrors(),
				],
			);
		}
	}

	protected static function notifyAboutJobError(Result $result, bool $withSyncBadges = true, bool $withSendAnalytics = true): void
	{
		$scriptId = $result->getTarget()?->getEntityId() ?? 0;
		$scriptData = CopilotCallAssessmentController::getInstance()->getById($scriptId);
		if (!$scriptData)
		{
			AIManager::logger()->error(
				'{date}: {class}: Target not found or invalid when trying to update extracted scoring criteria: {target}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
				],
			);

			return;
		}

		$updateResult = CopilotCallAssessmentTable::update($scriptId, [
			'GIST' => null,
			'JOB_ID' => $result->getJobId(),
			'STATUS' => QueueTable::EXECUTION_STATUS_ERROR,
			'UPDATED_AT' => $scriptData->getUpdatedAt(),
			'UPDATED_BY_ID' => $scriptData->getUpdatedById(),
		]);
		if (!$updateResult->isSuccess())
		{
			AIManager::logger()->error(
				'{date}: {class}: Got error while trying to update call assessment item fields: target {target}, errors {errors}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
					'errors' => $updateResult->getErrors(),
				],
			);
		}

		if ($withSendAnalytics)
		{
			// @todo: not implemented yet
		}
	}

	protected static function extractPayloadFromAIResult(\Bitrix\AI\Result $result, EO_Queue $job): Dto
	{
		$json = self::extractPayloadPrettifiedData($result);
		if (empty($json))
		{
			return new ExtractScoringCriteriaPayload([]);
		}

		return new ExtractScoringCriteriaPayload([
			'status' => (boolean)$json['status'],
			'criteria' => $json['criteria'],
		]);
	}

	protected static function getJobFinishEventBuilder(): AIBaseEvent
	{
		return new ExtractScoringCriteriaEvent();
	}
}

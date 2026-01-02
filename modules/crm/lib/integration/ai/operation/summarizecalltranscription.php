<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\AI\Engine;
use Bitrix\AI\Quality;
use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Crm\Badge;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Integration\AI\Config;
use Bitrix\Crm\Integration\AI\Dto\SummarizeCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Model\EO_Queue;
use Bitrix\Crm\Integration\AI\Operation\Payload\PayloadFactory;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Integration\Analytics\Builder\AI\AIBaseEvent;
use Bitrix\Crm\Integration\Analytics\Builder\AI\SummaryEvent;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\AI\Call\Controller;
use CCrmActivity;
use CCrmOwnerType;

class SummarizeCallTranscription extends AbstractOperation
{
	public const TYPE_ID = 2;
	public const CONTEXT_ID = 'summarize_call_transcription';

	public const SUPPORTED_TARGET_ENTITY_TYPE_IDS = [
		CCrmOwnerType::Activity,
	];

	protected const PAYLOAD_CLASS = SummarizeCallTranscriptionPayload::class;

	public function __construct(
		ItemIdentifier $target,
		private string $transcription,
		?int $userId = null,
		?int $parentJobId = null,
	)
	{
		parent::__construct($target, $userId, $parentJobId);
	}

	public static function isAccessGranted(int $userId, ItemIdentifier $target): bool
	{
		return parent::isAccessGranted($userId, $target)
			&& CCrmActivity::CheckItemUpdatePermission(
				['ID' => $target->getEntityId()],
				Container::getInstance()->getUserPermissions($userId)->getCrmPermissions(),
			)
		;
	}

	public static function isSuitableTarget(ItemIdentifier $target): bool
	{
		if ($target->getEntityTypeId() === CCrmOwnerType::Activity)
		{
			$activity = Container::getInstance()->getActivityBroker()->getById($target->getEntityId());
			if (
				$activity
				&& isset($activity['PROVIDER_ID'])
				&& $activity['PROVIDER_ID'] === Call::ACTIVITY_PROVIDER_ID
			)
			{
				return true;
			}
		}

		return false;
	}

	protected function getAIPayload(): \Bitrix\Main\Result
	{
		return PayloadFactory::build(self::TYPE_ID, $this->userId, $this->target)
			->setMarkers([
				'original_message' => $this->transcription,
			])->getResult()
		;
	}

	final protected function getContextLanguageId(): string
	{
		$itemIdentifier = (new Orchestrator())->findPossibleFillFieldsTarget($this->target->getEntityId());
		if ($itemIdentifier)
		{
			return Config::getLanguageId(
				$this->userId,
				$itemIdentifier->getEntityTypeId(),
				$itemIdentifier->getCategoryId()
			);
		}

		return parent::getContextLanguageId();
	}

	protected static function notifyTimelineAfterSuccessfulLaunch(Result $result): void
	{
		$nextTarget = (new Orchestrator())->findPossibleFillFieldsTarget($result->getTarget()?->getEntityId());

		if ($nextTarget)
		{
			Controller::getInstance()->onStartRecordTranscriptSummary(
				$nextTarget,
				$result->getTarget()?->getEntityId(),
				$result->getUserId(),
			);
		}
	}

	protected static function notifyTimelineAfterSuccessfulJobFinish(Result $result): void
	{
		$nextTarget = (new Orchestrator())->findPossibleFillFieldsTarget($result->getTarget()?->getEntityId());
		if ($nextTarget)
		{
			Controller::getInstance()->onFinishRecordTranscriptSummary(
				$nextTarget,
				$result->getTarget()?->getEntityId(),
				[],
				$result->getUserId(),
			);
		}
	}

	protected static function notifyAboutJobError(
		Result $result,
		bool $withSyncBadges = true,
		bool $withSendAnalytics = true
	): void
	{
		$activityId = $result->getTarget()?->getEntityId();
		$nextTarget = (new Orchestrator())->findPossibleFillFieldsTarget($activityId);
		if ($nextTarget)
		{
			if ($withSyncBadges)
			{
				Controller::getInstance()->onLaunchError(
					$nextTarget,
					$activityId,
					[
						'OPERATION_TYPE_ID' => self::TYPE_ID,
						'ENGINE_ID' => self::$engineId,
						'ERRORS' => array_unique($result->getErrorMessages()),
					],
					$result->getUserId(),
				);

				self::syncBadges($activityId, Badge\Type\AiCallFieldsFillingResult::ERROR_PROCESS_VALUE);
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

	protected static function extractPayloadFromAIResult(\Bitrix\AI\Result $result, EO_Queue $job): Dto
	{
		return new SummarizeCallTranscriptionPayload([
			'summary' => $result->getPrettifiedData(),
		]);
	}

	protected static function getJobFinishEventBuilder(): AIBaseEvent
	{
		return new SummaryEvent();
	}

	protected static function setQuality(Engine $engine): void
	{
		if (isset(Quality::QUALITIES['summarize']) && method_exists($engine->getIEngine(), 'setQuality'))
		{
			$engine->getIEngine()->setQuality(Quality::QUALITIES['summarize']);
		}
	}
}

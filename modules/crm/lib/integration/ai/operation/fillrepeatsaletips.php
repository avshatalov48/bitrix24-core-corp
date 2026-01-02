<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\AI\Context;
use Bitrix\AI\Payload\IPayload;
use Bitrix\Crm\Activity\Analytics\Dictionary;
use Bitrix\Crm\Activity\Provider\RepeatSale;
use Bitrix\Crm\Badge;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Dto\RepeatSale\FillRepeatSaleTipsPayload;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\Integration\AI\Model\EO_Queue;
use Bitrix\Crm\Integration\AI\Operation\Payload\PayloadFactory;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Integration\Analytics\Builder\Activity\EditActivityEvent;
use Bitrix\Crm\Integration\Analytics\Builder\AI\AIBaseEvent;
use Bitrix\Crm\Integration\Analytics\Builder\AI\FillRepeatSaleTipsEvent;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\RepeatSale\CostManager;
use Bitrix\Crm\RepeatSale\DataCollector\CopilotMarkerLimitManager;
use Bitrix\Crm\RepeatSale\Logger;
use Bitrix\Crm\RepeatSale\Segment\SegmentItemChecker;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\AI\Call\Controller;
use Bitrix\Main;
use Bitrix\Main\Web\Json;
use CCrmActivity;
use CCrmOwnerType;

final class FillRepeatSaleTips extends AbstractOperation
{
	public const TYPE_ID = 6;
	public const CONTEXT_ID = 'fill_repeat_sale_tips';

	protected const PAYLOAD_CLASS = FillRepeatSaleTipsPayload::class;
	protected const ENGINE_CODE = EventHandler::SETTINGS_REPEAT_SALE_ENGINE_CODE;

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
			$activityData = Container::getInstance()->getActivityBroker()->getById($target->getEntityId());
			$providerId = $activityData['PROVIDER_ID'] ?? null;
			if ($providerId === RepeatSale::getId())
			{
				return true;
			}
		}

		return false;
	}

	protected function getAIPayload(): Main\Result
	{
		$activity = Container::getInstance()->getActivityBroker()->getById($this->target->getEntityId());
		$checkerResult = SegmentItemChecker::getInstance()
			->setItemByActivity($activity)
			->run()
		;
		if (!$checkerResult->isSuccess())
		{
			return (new Main\Result())->addError($checkerResult->getError());
		}

		$result = PayloadFactory::build(self::TYPE_ID, $this->userId, $this->target)
			->setEncodedMarkers(['segment_data', 'crm_data'])
			->setMarkers([])
			->getResult()
		;

		/** @var IPayload $payload */
		$payload = $result->getData()['payload'];
		if (!$this->isPayloadMarkersValid($payload->getMarkers()))
		{
			return (new Main\Result())->addError(
				ErrorCode::getInvalidPayloadMarkersForFillRepeatSaleTipsError()
			);
		}

		return $result;
	}

	private function isPayloadMarkersValid(array $markers): bool
	{
		if (empty($markers))
		{
			return false;
		}

		$crmData = Json::decode($markers['crm_data'] ?? '');
		if (empty($crmData))
		{
			return false;
		}

		$dealList = $crmData['deals_list'] ?? [];
		if (empty($dealList))
		{
			return false;
		}

		$limit = CopilotMarkerLimitManager::getInstance()->getDealFieldsMinLimit();
		$filtered = array_filter(
			$dealList,
			static function (array $item) use ($limit): bool
			{
				$dealFields = $item['deal_fields'] ?? [];
				$communicationData = $item['communication_data'] ?? [];

				return
					TextHelper::countCharactersInArrayFlexible($dealFields, true) > $limit
					|| TextHelper::countCharactersInArrayFlexible($communicationData) > $limit;
			},
		);

		return !empty($filtered);
	}

	protected static function isSponsoredOperation(): bool
	{
		return CostManager::isSponsoredOperation();
	}

	protected static function notifyTimelineAfterSuccessfulLaunch(Result $result): void
	{
		// operation is not used in the timeline
		// temporary use method for to collect information about tasks sent to the AI queue
		$activityId = $result->getTarget()?->getEntityId() ?? 0;
		$jobId = $result->getJobId() ?? 0;

		$logMarkAiStart = $result->isManualLaunch()
			? Logger::LOG_MARK_AI_MANUAL_LAUNCH
			: Logger::LOG_MARK_AI_AUTO_LAUNCH
		;
		$logMarkAiPaid = self::isSponsoredOperation()
			? Logger::LOG_MARK_AI_NOT_PAID
			: Logger::LOG_MARK_AI_PAID
		;

		(new Logger('RepeatSaleVsAI'))->info(
			'{date}: Task with job ID {jobId} for activity ID {activityId} has been sent to AI queue: {markAiStart} {markAiPaid}' . PHP_EOL,
			[
				'jobId' => $jobId,
				'activityId' => $activityId,
				'markAiStart' => $logMarkAiStart,
				'markAiPaid' => $logMarkAiPaid,
			],
		);
	}

	protected static function notifyTimelineAfterSuccessfulJobFinish(Result $result): void
	{
		// operation is not used in the timeline
	}

	protected static function notifyAboutLimitExceededError(Result $result): void
	{
		// not implemented yet
	}

	protected static function extractPayloadFromAIResult(\Bitrix\AI\Result $result, EO_Queue $job): Dto
	{
		$json = self::extractPayloadPrettifiedData($result);
		if (empty($json))
		{
			return new FillRepeatSaleTipsPayload([]);
		}

		return new FillRepeatSaleTipsPayload([
			'customerInfo' => self::underscoreToCamelCase($json['customer_info'] ?? []),
			'actionPlan' => self::underscoreToCamelCase($json['action_plan'] ?? []),
		]);
	}

	protected static function onAfterSuccessfulJobFinish(Result $result, ?Context $context = null): void
	{
		/** @var FillRepeatSaleTipsPayload $payload */
		$payload = $result->getPayload();
		if (!$payload || !$result->isSuccess())
		{
			AIManager::logger()->error(
				'{date}: {class}: Error while trying to save activity of job error: {target}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
				],
			);

			return;
		}

		$analyticsStatus = \Bitrix\Crm\Integration\Analytics\Dictionary::STATUS_ERROR;

		$activityId = $result->getTarget()?->getEntityId();
		$saveResult = CCrmActivity::Update($activityId, [
			'DESCRIPTION' => RepeatSale::createDescriptionFromPayload(
				$payload,
				false,
				$context?->getLanguage()?->getCode()
			),
		]);
		if ($saveResult)
		{
			$analyticsStatus = \Bitrix\Crm\Integration\Analytics\Dictionary::STATUS_SUCCESS;

			self::cleanBadgeByType($activityId, Badge\Badge::AI_CALL_FIELDS_FILLING_RESULT);
			self::notifyTimelinesAboutActivityUpdate($activityId);
		}
		else
		{
			AIManager::logger()->error(
				'{date}: {class}: Error while trying to save activity of job error: {target}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
				],
			);
		}

		EditActivityEvent::createDefault(CCrmOwnerType::Deal) // @todo: extent entity type ID in future
			->setType(Dictionary::REPEAT_SALE_TYPE)
			->setElement(Dictionary::REPEAT_SALE_ELEMENT_SYS)
			->setStatus($analyticsStatus)
			->setP5('segment', str_replace('_', '-', RepeatSale::getSegmentCodeByActivity($activityId)))
			->buildEvent()
			->send()
		;
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
		}
	}

	protected static function getJobFinishEventBuilder(): AIBaseEvent
	{
		return new FillRepeatSaleTipsEvent();
	}

	private static function underscoreToCamelCase(array $input): array
	{
		return array_combine(
			array_map(
				static fn(string $key) => lcfirst(str_replace('_', '', ucwords($key, '_'))),
				array_keys($input)
			),
			array_values($input)
		);
	}
}

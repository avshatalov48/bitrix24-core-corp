<?php

namespace Bitrix\Crm\RepeatSale\Service\Action;

use Bitrix\Crm\Activity\Analytics\Dictionary;
use Bitrix\Crm\Activity\Entity;
use Bitrix\Crm\Activity\Provider\RepeatSale;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;
use Bitrix\Crm\Integration\Analytics\Builder\Activity\AddActivityEvent;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\RepeatSale\CostManager;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Service\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

final class CreateActivityAction implements ActionInterface
{
	public function process(
		Item $clientItem,
		int $assignmentUserId,
		?Result $prevActionResult = null,
		?Context $context = null,
		?SegmentItem $segmentItem = null,
	): Result
	{
		if (!$prevActionResult?->isSuccess())
		{
			return $prevActionResult;
		}

		$item = $prevActionResult?->getData()['item'] ?? null;
		if ($item === null)
		{
			return $this->getErrorResult();
		}

		$activityId = $this->addActivity(
			$item,
			$clientItem,
			$assignmentUserId,
			$context,
			$segmentItem,
		);
		if ($activityId === null)
		{
			return $this->getErrorResult();
		}

		return $prevActionResult;
	}

	private function getErrorResult(): Result
	{
		return (new Result())->addError(new Error(Loc::getMessage('CRM_REPEAT_SALE_ACTION_CREATE_ACTIVITY_ERROR')));
	}

	private function addActivity(
		Item $item,
		Item $clientItem,
		int $assignmentUserId,
		?Context $context = null,
		?SegmentItem $segmentItem = null,
	)
	: ?int
	{
		$segmentId = $segmentItem?->getId() ?? 0;
		if ($segmentId <= 0)
		{
			return null;
		}

		$identifier = ItemIdentifier::createFromArray([
			'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
			'ENTITY_ID' => $item->getId(),
		]);

		if (!$identifier)
		{
			return null;
		}

		$deadline = (new DateTime())->add('+15 day');
		$isAiAutoStartEnabled = $this->isAutomaticProcessingAllowed() && $segmentItem?->isAiEnabled();
		$activity = new Entity\RepeatSale($identifier, new RepeatSale());
		$activity
			->setSubject(Loc::getMessage('CRM_REPEAT_SALE_ACTION_CREATE_ACTIVITY_TITLE', ['#ENTITY_ID#' => $item->getId()]))
			->setDescription($segmentItem?->getPrompt() ?? '')
			->setResponsibleId($item->getAssignedById())
			->setDeadline($deadline)
			->setAuthorId($assignmentUserId)
			->setAdditionalFields(
				[
					'PROVIDER_PARAMS' => [
						'JOB_ID' => $context?->getJobId(),
						'SEGMENT_ID' => $segmentId,
						'CLIENT_ENTITY_TYPE_ID' => $clientItem->getEntityTypeId(),
						'CLIENT_ENTITY_ID' => $clientItem->getId(),
						'IS_AI_AUTO_START_ENABLED' => $isAiAutoStartEnabled,
					],
					'IS_INCOMING_CHANNEL' => 'Y',
				]
			)
			->setCheckPermissions(false)
		;

		$activityId = null;
		$analyticsStatus = \Bitrix\Crm\Integration\Analytics\Dictionary::STATUS_ERROR;
		$saveResult = $activity->save();
		if ($saveResult->isSuccess())
		{
			$analyticsStatus = \Bitrix\Crm\Integration\Analytics\Dictionary::STATUS_SUCCESS;

			$activityId = $activity->getId();
		}

		AddActivityEvent::createDefault($identifier->getEntityTypeId())
			->setType(Dictionary::REPEAT_SALE_TYPE)
			->setElement(Dictionary::REPEAT_SALE_ELEMENT_SYS)
			->setStatus($analyticsStatus)
			->setP5('segment', str_replace('_', '-', $segmentItem?->getCode() ?? ''))
			->buildEvent()
			->send()
		;

		return $activityId;
	}

	private function isAutomaticProcessingAllowed(): bool
	{
		$isAiEnabled = AIManager::isAiCallProcessingEnabled()
			&& AIManager::isEnabledInGlobalSettings(GlobalSetting::RepeatSale)
		;

		if (!$isAiEnabled)
		{
			return false;
		}

		return CostManager::isSponsoredOperation() || AIManager::isBaasServiceHasPackage();
	}
}

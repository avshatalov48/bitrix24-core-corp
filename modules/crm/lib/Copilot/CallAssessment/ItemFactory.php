<?php

namespace Bitrix\Crm\Copilot\CallAssessment;

use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Crm\Copilot\CallAssessment\Enum\CallType;
use Bitrix\Crm\Copilot\CallAssessment\Enum\ClientType;
use Bitrix\Crm\Integration\VoxImplant\Call;
use Bitrix\Crm\Integration\VoxImplantManager;
use Bitrix\Crm\MultiValueStoreService;
use Bitrix\Crm\Service\Container;
use CCrmActivityDirection;
use CCrmActivityType;
use CCrmOwnerType;

final class ItemFactory
{
	public static function getByActivityId(int $activityId): ?CallAssessmentItem
	{
		$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
		if (!$activity)
		{
			return null;
		}

		if ((int)$activity['TYPE_ID'] !== CCrmActivityType::Call)
		{
			return null;
		}

		$originId = $activity['ORIGIN_ID'] ?? '';
		if (VoxImplantManager::isVoxImplantOriginId($originId))
		{
			$callId = VoxImplantManager::extractCallIdFromOriginId($originId);
			$savedAssessmentItemByCallId = self::getSavedAssessmentItemByCallId($callId);
			if ($savedAssessmentItemByCallId)
			{
				return $savedAssessmentItemByCallId;
			}
		}

		$clientType = (new AssessmentClientTypeResolver())->resolveByActivityId($activityId);
		if (!$clientType)
		{
			return null;
		}

		return self::getAssessmentByClientAndCallType($clientType, self::getCallType((int)$activity['DIRECTION']));
	}

	public static function getByCallId(string $callId): ?CallAssessmentItem
	{
		$savedAssessmentItemByCallId = self::getSavedAssessmentItemByCallId($callId);
		if ($savedAssessmentItemByCallId)
		{
			return $savedAssessmentItemByCallId;
		}

		$voximplantCall = new Call($callId);
		$callCrmEntities = $voximplantCall->getCrmEntities();
		if (!$callCrmEntities)
		{
			return null;
		}

		$callDirection = $voximplantCall->getDirection();
		if (!CCrmActivityDirection::IsDefined($callDirection))
		{
			return null;
		}

		foreach ($callCrmEntities as $callCrmEntity)
		{
			$assessmentClientTypeResolver = new AssessmentClientTypeResolver();
			$availableEntityTypeIds = [
				CCrmOwnerType::Lead,
				CCrmOwnerType::Contact,
				CCrmOwnerType::Company
			];
			if (in_array($callCrmEntity->getEntityTypeId(), $availableEntityTypeIds, true))
			{
				$clientType = $assessmentClientTypeResolver->resolveByIdentifier($callCrmEntity);
				if (!$clientType)
				{
					return null;
				}

				return self::getAssessmentByClientAndCallType($clientType, self::getCallType($callDirection));
			}
		}

		return null;
	}

	private static function getSavedAssessmentItemByCallId(string $callId): ?CallAssessmentItem
	{
		$originCallId = VoxImplantManager::insertPrefix($callId);
		$callAssessmentItemId = MultiValueStoreService::getInstance()->getFirstValue($originCallId);
		if (!$callAssessmentItemId)
		{
			return null;
		}

		$callAssessmentItem = CopilotCallAssessmentController::getInstance()->getById($callAssessmentItemId);
		if (!$callAssessmentItem)
		{
			return null;
		}

		if (!$callAssessmentItem->getIsEnabled())
		{
			return null;
		}

		return CallAssessmentItem::createFromEntity($callAssessmentItem);
	}

	private static function getAssessmentByClientAndCallType(ClientType $assessmentClientType, CallType $callType): ?CallAssessmentItem
	{
		$filter =  [
			'=CALL_TYPE' => [CallType::ALL->value, $callType->value],
			'=CLIENT_TYPES.CLIENT_TYPE_ID' => [$assessmentClientType->value],
			'=IS_ENABLED' => true,
		];

		$assessment = CopilotCallAssessmentController::getInstance()->getList([
			'filter' => $filter,
			'limit' => 1,
			'order' => ['UPDATED_AT' => 'DESC'],
		])->current();

		return $assessment ? CallAssessmentItem::createFromEntity($assessment) : null;
	}

	private static function getCallType(int $callDirection): CallType
	{
		return (
			$callDirection === CCrmActivityDirection::Incoming
				? CallType::INCOMING
				: CallType::OUTGOING
		);
	}
}

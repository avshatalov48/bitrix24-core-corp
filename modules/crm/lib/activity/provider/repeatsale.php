<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\Analytics\Dictionary;
use Bitrix\Crm\Integration\AI\Dto\RepeatSale\FillRepeatSaleTipsPayload;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\Integration\Analytics\Builder\Activity\CompleteActivityEvent;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CCrmActivityDirection;
use CCrmOwnerType;

Loc::loadMessages(__FILE__);

final class RepeatSale extends Base
{
	public const PROVIDER_ID = 'CRM_REPEAT_SALE';
	public const PROVIDER_TYPE_ID_DEFAULT = 'REPEAT_SALE';
	
	public static function getId(): string
	{
		return self::PROVIDER_ID;
	}
	
	public static function getTypeId(array $activity): string
	{
		return $activity['PROVIDER_TYPE_ID'] ?? self::PROVIDER_TYPE_ID_DEFAULT;
	}
	
	public static function getName(): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_REPEAT_SALE_NAME') ?? '';
	}
	
	public static function isActive(): bool
	{
		return Container::getInstance()->getRepeatSaleAvailabilityChecker()->isAvailable();
	}
	
	public static function hasPlanner(array $activity): bool
	{
		return false;
	}

	public static function isTypeEditable($providerTypeId = null, $direction = CCrmActivityDirection::Undefined): bool
	{
		return false;
	}

	public static function getTypesFilterPresets()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_REPEAT_SALE_NAME'),
			),
		);
	}
	
	public static function getTypes(): array
	{
		return [
			[
				'NAME' => self::getName(),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_DEFAULT
			]
		];
	}
	
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		if (isset($fields['END_TIME']) && $fields['END_TIME'] != '')
		{
			$fields['DEADLINE'] = $fields['END_TIME'];
		}
		
		return new Result();
	}
	
	public static function onAfterAdd($activityFields, array $params = null)
	{
		// @todo: add AddActivityEvent in future
		EventHandler::onAfterRepeatSaleActivityAdd($activityFields);
	}

	public static function onAfterUpdate(
		int $id,
		array $changedFields,
		array $oldFields,
		array $newFields,
		array $params = null
	): void
	{
		$prevIsCompleted = ($oldFields['COMPLETED'] ?? '') === 'Y';
		$curIsCompleted = ($newFields['COMPLETED'] ?? '')  === 'Y';
		$isCompleted = !$prevIsCompleted && $curIsCompleted;
		if ($isCompleted)
		{
			CompleteActivityEvent::createDefault(CCrmOwnerType::Deal) // @todo: extent entity type ID in future
				->setType(Dictionary::REPEAT_SALE_TYPE)
				->setElement(Dictionary::REPEAT_SALE_ELEMENT_SYS)
				->setStatus( \Bitrix\Crm\Integration\Analytics\Dictionary::STATUS_SUCCESS)
				->setP5('segment', str_replace('_', '-', self::getSegmentCodeByActivity($id)))
				->buildEvent()
				->send()
			;
		}
	}

	public static function createDescriptionFromPayload(
		FillRepeatSaleTipsPayload $payload,
		bool $actionPlanOnly = false,
		?string $languageId = null
	): string
	{
		$actionPlanResult = [];
		if ($payload->actionPlan)
		{
			$actionPlanResult[] = sprintf(
				"\r\n✅ %s:",
				Loc::getMessage('CRM_ACTIVITY_PROVIDER_REPEAT_SALE_ACTION', null, $languageId),
			);

			if (!self::isEmptyString($payload->actionPlan->bestWayToContact))
			{
				$actionPlanResult[] = sprintf(
					"\r\n📞 %s",
					$payload->actionPlan->bestWayToContact
				);
			}

			if (!self::isEmptyString($payload->actionPlan->salesOpportunity))
			{
				$actionPlanResult[] = sprintf(
					"\r\n💡 %s",
					$payload->actionPlan->salesOpportunity
				);
			}

			if (!self::isEmptyString($payload->actionPlan->serviceImprovementSuggestions))
			{
				$actionPlanResult[] = sprintf(
					"\r\n🎁 %s",
					$payload->actionPlan->serviceImprovementSuggestions
				);
			}
		}

		if ($actionPlanOnly)
		{
			return implode(PHP_EOL, $actionPlanResult);
		}

		$summaryResult = [];
		if ($payload->customerInfo)
		{
			$summaryResult[] = sprintf(
				"\r\n📘 %s:",
				Loc::getMessage('CRM_ACTIVITY_PROVIDER_REPEAT_SALE_INFO', null, $languageId),
			);

			if (
				!self::isEmptyString($payload->customerInfo->lastPurchaseDate)
				|| !self::isEmptyString($payload->customerInfo->lastPurchaseDetails)
			)
			{
				$summaryResult[] = sprintf(
					"\r\n📌 [b]%s[/b]: %s – %s",
					Loc::getMessage('CRM_ACTIVITY_PROVIDER_REPEAT_SALE_LAST_PURCHASE', null, $languageId),
					$payload->customerInfo->lastPurchaseDate ?? '',
					$payload->customerInfo->lastPurchaseDetails ?? ''
				);
			}

			if (!self::isEmptyString($payload->customerInfo->ordersOverview))
			{
				$summaryResult[] = sprintf(
					"\r\n📌 [b]%s[/b]: %s",
					Loc::getMessage('CRM_ACTIVITY_PROVIDER_REPEAT_SALE_ORDERS_OVERVIEW', null, $languageId),
					$payload->customerInfo->ordersOverview
				);
			}

			if (!self::isEmptyString($payload->customerInfo->detailedIssuesSummary))
			{
				$summaryResult[] = sprintf(
					"\r\n📌 [b]%s[/b]: %s",
					Loc::getMessage('CRM_ACTIVITY_PROVIDER_REPEAT_SALE_ISSUES', null, $languageId),
					$payload->customerInfo->detailedIssuesSummary
				);
			}
		}

		return implode(PHP_EOL, array_merge($actionPlanResult, $summaryResult));
	}

	public static function getSegmentCodeByActivity(int $activityId): string
	{
		if ($activityId <= 0)
		{
			return '';
		}

		$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
		$segmentId = (int)($activity['PROVIDER_PARAMS']['SEGMENT_ID'] ?? 0);
		if ($segmentId <= 0)
		{
			return '';
		}

		$entity = RepeatSaleSegmentController::getInstance()->getById($segmentId, true);

		return $entity
			?  SegmentItem::createFromEntity($entity)?->getCode() ?? ''
			: ''
		;
	}

	private static function isEmptyString(?string $input): bool
	{
		$input = trim($input ?? '');

		return empty($input)
			|| $input === 'null'
		;
	}
}

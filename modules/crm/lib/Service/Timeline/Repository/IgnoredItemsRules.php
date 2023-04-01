<?php

namespace Bitrix\Crm\Service\Timeline\Repository;

use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Timeline\OrderCategoryType;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Crm\Binding\OrderEntityTable;

class IgnoredItemsRules
{
	private Context $context;

	private const ORDER_RULE_ASSOCIATED_ENTITY_TYPE_ID = TimelineType::ORDER;
	private const ORDER_RULE_TYPE_ID = [
		TimelineType::MODIFICATION,
		TimelineType::ORDER,
	];
	private const ORDER_RULE_EXCEPT_TYPE_CATEGORY_ID = [
		OrderCategoryType::ENCOURAGE_BUY_PRODUCTS,
		TimelineType::MODIFICATION,
	];

	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	public function isTimelineItemIgnored(array $timelineRecord): bool
	{
		$associatedEntityTypeId = $timelineRecord['ASSOCIATED_ENTITY_TYPE_ID'];
		if ($associatedEntityTypeId && in_array($associatedEntityTypeId, TimelineManager::getIgnoredEntityTypeIDs(), false))
		{
			return true;
		}

		if ($this->context->getEntityTypeId() === \CCrmOwnerType::SmartDocument)
		{
			$typeId = $timelineRecord['TYPE_ID'];
			if ($typeId && in_array($typeId, $this->getExcludedSmartDocumentTypeIds(), true))
			{
				return true;
			}
		}

		if (
			$this->needApplyOrderFilter()
			&& $this->isTimelineOrderItemIgnored($timelineRecord)
		)
		{
			return true;
		}

		return false;
	}

	public function applyToQuery(\Bitrix\Main\ORM\Query\Query $query): void
	{
		$query->whereNotIn(
			'ASSOCIATED_ENTITY_TYPE_ID',
			TimelineManager::getIgnoredEntityTypeIDs()
		);

		if ($this->needApplyOrderFilter())
		{
			$orderFilter = $this->getExcludingOrderFilter(
				$this->context->getEntityId(),
				$this->context->getEntityTypeId()
			);
			$query->whereNot($orderFilter);
		}

		if ($this->context->getEntityTypeId() === \CCrmOwnerType::SmartDocument)
		{
			$smartDocumentFilter = $this->getExcludingForSmartDocumentFilter(
				$this->context->getEntityId(),
				$this->context->getEntityTypeId()
			);
			$query->where($smartDocumentFilter);
		}
	}

	private function getExcludingOrderFilter(int $ownerId, int $ownerTypeId): ConditionTree
	{
		$orderFilter = \Bitrix\Main\ORM\Query\Query::filter();

		// @todo needs further research: most likely we should get rid of the $orderList filter
		$orderList = OrderEntityTable::getOrderIdsByOwner($ownerId, $ownerTypeId);
		if (!$orderList)
		{
			return $orderFilter;
		}

		return $orderFilter
			->whereIn('ASSOCIATED_ENTITY_ID', $orderList)
			->where('ASSOCIATED_ENTITY_TYPE_ID', self::ORDER_RULE_ASSOCIATED_ENTITY_TYPE_ID)
			->whereIn('TYPE_ID', self::ORDER_RULE_TYPE_ID)
			->whereNotIn('TYPE_CATEGORY_ID', self::ORDER_RULE_EXCEPT_TYPE_CATEGORY_ID)
		;
	}

	private function isTimelineOrderItemIgnored(array $timelineRecord): bool
	{
		$typeId = (int)$timelineRecord['TYPE_ID'];
		$typeCategoryId = (int)$timelineRecord['TYPE_CATEGORY_ID'];
		$associatedEntityTypeId = (int)$timelineRecord['ASSOCIATED_ENTITY_TYPE_ID'];

		if (
			$associatedEntityTypeId === \CCrmOwnerType::Order
			&& in_array(
				$typeId,
				self::ORDER_RULE_TYPE_ID,
				true
			)
			&& !in_array($typeCategoryId, self::ORDER_RULE_EXCEPT_TYPE_CATEGORY_ID)
		)
		{
			return true;
		}

		return false;
	}

	private function needApplyOrderFilter(): bool
	{
		return (
			!\CCrmSaleHelper::isWithOrdersMode()
			&& (
				$this->context->getEntityTypeId() === \CCrmOwnerType::Deal
				|| \CCrmOwnerType::isPossibleDynamicTypeId($this->context->getEntityTypeId())
			)
		);
	}

	private function getExcludingForSmartDocumentFilter(int $ownerId, int $ownerTypeId): ConditionTree
	{
		$filter = \Bitrix\Main\ORM\Query\Query::filter();

		$filter->whereNotIn('TYPE_ID', $this->getExcludedSmartDocumentTypeIds());
		return $filter;
	}

	private function getExcludedSmartDocumentTypeIds(): array
	{
		return [
			TimelineType::LINK,
			TimelineType::UNLINK,
		];
	}
}

<?php

namespace Bitrix\Crm\Service\Timeline\Repository;

use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Crm\Timeline\TimelineType;

class IgnoredItemsRules
{
	private Context $context;

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
			if ($typeId && in_array($typeId, $this->getExcludedSmartDocumentTypeIds(), false))
			{
				return true;
			}
		}
		// @todo support $this->getExcludingOrderFilter

		return false;
	}

	public function applyToQuery(\Bitrix\Main\ORM\Query\Query $query): void
	{
		$query->whereNotIn(
			'ASSOCIATED_ENTITY_TYPE_ID',
			TimelineManager::getIgnoredEntityTypeIDs()
		);

		if (
			!\CCrmSaleHelper::isWithOrdersMode()
			&& (
				$this->context->getEntityTypeId() === \CCrmOwnerType::Deal
				|| \CCrmOwnerType::isPossibleDynamicTypeId($this->context->getEntityTypeId())
			)
		)
		{
			$orderFilter = $this->getExcludingOrderFilter($this->context->getEntityId(),
				$this->context->getEntityTypeId());
			$query->whereNot($orderFilter);
		}

		if ($this->context->getEntityTypeId() === \CCrmOwnerType::SmartDocument)
		{
			$smartDocumentFilter = $this->getExcludingForSmartDocumentFilter($this->context->getEntityId(),
				$this->context->getEntityTypeId());
			$query->where($smartDocumentFilter);
		}
	}

	private function getExcludingOrderFilter(int $ownerId, int $ownerTypeId): \Bitrix\Main\ORM\Query\Filter\ConditionTree
	{
		$orderFilter = \Bitrix\Main\ORM\Query\Query::filter();

		$orderList = \Bitrix\Crm\Binding\OrderEntityTable::getOrderIdsByOwner($ownerId, $ownerTypeId);
		if ($orderList)
		{
			$orderFilter->whereIn('ASSOCIATED_ENTITY_ID', $orderList);
			$orderFilter->where('ASSOCIATED_ENTITY_TYPE_ID', TimelineType::ORDER);
			$orderFilter->whereIn('TYPE_ID', [TimelineType::MODIFICATION, TimelineType::ORDER]);
			$orderFilter->whereNot('TYPE_CATEGORY_ID', \Bitrix\Crm\Timeline\OrderCategoryType::ENCOURAGE_BUY_PRODUCTS);
			$orderFilter->whereNotIn('TYPE_CATEGORY_ID', [
				\Bitrix\Crm\Timeline\OrderCategoryType::ENCOURAGE_BUY_PRODUCTS,
				TimelineType::MODIFICATION,
			]);
		}

		return $orderFilter;
	}

	private function getExcludingForSmartDocumentFilter(int $ownerId, int $ownerTypeId): \Bitrix\Main\ORM\Query\Filter\ConditionTree
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

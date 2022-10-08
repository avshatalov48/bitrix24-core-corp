<?php

namespace Bitrix\Crm\Service\Timeline;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Compatible\Wait;
use Bitrix\Crm\Service\Timeline\Repository\Query;
use Bitrix\Crm\Service\Timeline\Repository\Result;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;

class Repository
{
	protected Context $context;

	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	/**
	 * @return Result
	 */
	public function getScheduledItems(): Result
	{
		$filter = [
			'CHECK_PERMISSIONS' => 'N',
			'STATUS' => \CCrmActivityStatus::Waiting,
			'BINDINGS' => [
				[
					'OWNER_TYPE_ID' => $this->context->getEntityTypeId(),
					'OWNER_ID' => $this->context->getEntityId(),
				],
			],
		];
		if (!$this->context->canReadEntity())
		{
			return new Result();
		}

		$dbResult = \CCrmActivity::GetList(
			[
				'DEADLINE' => 'ASC',
			],
			$filter,
			false,
			false,
			[
				'ID',
				'OWNER_ID',
				'OWNER_TYPE_ID',
				'TYPE_ID',
				'PROVIDER_ID',
				'PROVIDER_TYPE_ID',
				'ASSOCIATED_ENTITY_ID',
				'DIRECTION',
				'SUBJECT',
				'STATUS',
				'DESCRIPTION',
				'DESCRIPTION_TYPE',
				'DEADLINE',
				'RESPONSIBLE_ID',
				'PROVIDER_PARAMS',
				'SETTINGS',
				'RESULT_MARK',
				'ORIGIN_ID',
				'LAST_UPDATED',
				'END_TIME',
				'STORAGE_TYPE_ID',
				'STORAGE_ELEMENT_IDS',
			],
			[
				'QUERY_OPTIONS' => [
					'LIMIT' => 100,
					'OFFSET' => 0,
				],
			]
		);

		$items = [];
		while ($fields = $dbResult->Fetch())
		{
			$items[$fields['ID']] = $fields;
		}
		\Bitrix\Crm\Timeline\EntityController::loadCommunicationsAndMultifields(
			$items,
			$this->context->getUserPermissions()->getCrmPermissions()
		);

		$items = array_values($items);
		foreach ($items as $key => $item)
		{
			$items[$key] = Container::getInstance()->getTimelineScheduledItemFactory()::createItem($this->context, $item);
		}

		$fields = \Bitrix\Crm\Pseudoactivity\WaitEntry::getRecentByOwner(
			$this->context->getEntityTypeId(),
			$this->context->getEntityId()
		);
		if (is_array($fields))
		{
			$items[] = new Wait(
				$this->context,
				(new Item\Compatible\Model())
					->setData($fields)
					->setId('WAIT_' . $fields['ID'])
					->setIsScheduled(true)
			);
		}

		return (new Result())
			->setItems($items)
		;
	}

	public function getHistoryItemsPage(Query $queryParams): Result
	{
		$items = [];
		$offsetTime = $queryParams->getOffsetTime();
		$offsetId = $queryParams->getOffsetId();
		$nextOffsetTime = null;
		$nextOffsetId = 0;

		do
		{
			if ($nextOffsetTime !== null)
			{
				$offsetTime = $nextOffsetTime;
			}

			if ($nextOffsetId > 0)
			{
				$offsetId = $nextOffsetId;
			}

			$items = array_merge(
				$items,
				$this->loadHistoryItems(
					$offsetTime,
					$nextOffsetTime,
					$offsetId,
					$nextOffsetId,
					[
						'limit' => $queryParams->getLimit(),
						'filter' => $queryParams->getFilter(),
						'onlyFixed' => $queryParams->isSearchForFixedItems(),
					]
				)
			);
		}
		while (count($items) < $queryParams->getLimit() && $nextOffsetTime !== null);

		return (new Result())
			->setItems($items)
			->setOffsetId($nextOffsetId)
			->setOffsetTime($nextOffsetTime)
		;
	}

	/**
	 * @return Item[]
	 */
	private function loadHistoryItems(
		?DateTime $offsetTime,
		?DateTime &$nextOffsetTime,
		int $offsetId,
		int &$nextOffsetId,
		array $params = []
	): array
	{
		$onlyFixed = isset($params['onlyFixed']) && $params['onlyFixed'] == true;
		$limit = (int)($params['limit'] ?? 0);
		$filter = (array)($params['filter'] ?? []);
		$isOffsetExist = isset($offsetTime) && $offsetId > 0;

		$bindingQuery = $this->prepareLoadHistoryBindingQuery($onlyFixed);
		$query = $this->prepareLoadHistoryQuery($limit, false, $bindingQuery, $filter, $offsetTime, $offsetId);
		$items = $this->fetchHistoryItems($offsetId, $query);

		$fetchDiff = $limit - count($items);
		if ($fetchDiff > 0 && $isOffsetExist)
		{
			$query = $this->prepareLoadHistoryQuery($fetchDiff, true, $bindingQuery, $filter, $offsetTime, $offsetId);
			$extraItems = $this->fetchHistoryItems($offsetId, $query);
			$items = array_merge($items, $extraItems);
		}

		$nextOffsetTime = null;
		if (!empty($items))
		{
			$item = $items[count($items) - 1];
			if (isset($item['CREATED']) && $item['CREATED'] instanceof DateTime)
			{
				$nextOffsetTime = $item['CREATED'];
				$nextOffsetId = (int)$item['ID'];
			}
		}

		$itemIDs = array_column($items, 'ID');
		$itemsMap = array_combine($itemIDs, $items);

		/*
		 * @todo reorganize TimelineManager::prepareDisplayData and do not use it here
		 */
		TimelineManager::prepareDisplayData($itemsMap);

		$itemsMap = array_values($itemsMap);

		foreach ($itemsMap as $key => $item)
		{
			$itemsMap[$key] = Container::getInstance()->getTimelineHistoryItemFactory()::createItem($this->context, $item);
		}

		return $itemsMap;
	}

	private function prepareLoadHistoryQuery(
		int $limit,
		bool $isExtraFetch,
		\Bitrix\Main\ORM\Query\Query $bindingQuery,
		array $filter,
		?DateTime $offsetTime,
		int $offsetId
	): \Bitrix\Main\ORM\Query\Query
	{
		$query = TimelineTable::query();
		$query->addSelect('*');
		$query->addSelect('bind.IS_FIXED', 'IS_FIXED');
		$query->registerRuntimeField('',
			new ReferenceField('bind',
				\Bitrix\Main\ORM\Entity::getInstanceByQuery($bindingQuery),
				['=this.ID' => 'ref.OWNER_ID'],
				['join_type' => 'INNER']
			)
		);

		if (isset($filter['CREATED_to']))
		{
			$filter['CREATED_to'] = DateTime::tryParse($filter['CREATED_to']);
		}

		if (isset($filter['CREATED_from']))
		{
			$filter['CREATED_from'] = DateTime::tryParse($filter['CREATED_from']);
		}

		if (
			$offsetTime instanceof DateTime
			&& (!isset($filter['CREATED_to']) || $offsetTime->getTimestamp() < $filter['CREATED_to']->getTimestamp())
		)
		{
			if ($isExtraFetch)
			{
				$query->addFilter('<CREATED', $offsetTime);
			}
			else
			{
				$query->addFilter('=CREATED', $offsetTime);
				$query->addFilter('<ID', $offsetId);
			}
		}

		if (!empty($filter))
		{
			\Bitrix\Crm\Filter\TimelineDataProvider::prepareQuery($query, $filter);
		}

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

		$query->setOrder(['CREATED' => 'DESC', 'ID' => 'DESC']);

		if ($limit > 0)
		{
			$query->setLimit($limit);
		}

		return $query;
	}

	private function prepareLoadHistoryBindingQuery(bool $onlyFixed): \Bitrix\Main\ORM\Query\Query
	{
		$bindingQuery = TimelineBindingTable::query();
		$bindingQuery->addSelect('OWNER_ID');
		$bindingQuery->addFilter('=ENTITY_TYPE_ID', $this->context->getEntityTypeId());
		$bindingQuery->addFilter('=ENTITY_ID', $this->context->getEntityId());

		if ($onlyFixed)
		{
			$bindingQuery->addFilter('=IS_FIXED', 'Y');
		}

		$bindingQuery->addSelect('IS_FIXED');

		return $bindingQuery;
	}

	private function getExcludingOrderFilter(int $ownerId, int $ownerTypeId)
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

	private function getExcludingForSmartDocumentFilter(int $ownerId, int $ownerTypeId)
	{
		$filter = \Bitrix\Main\ORM\Query\Query::filter();

		$filter->whereNotIn('TYPE_ID', [
			TimelineType::ACTIVITY,
			TimelineType::LINK,
			TimelineType::UNLINK,
		]);
		return $filter;
	}

	private function fetchHistoryItems(int $offsetID, \Bitrix\Main\ORM\Query\Query $query): array
	{
		$items = [];
		$offsetIndex = -1;
		$dbResult = $query->exec();
		while ($fields = $dbResult->fetch())
		{
			$itemID = (int)$fields['ID'];
			$items[] = $fields;
			if ($offsetID > 0 && $itemID === $offsetID)
			{
				$offsetIndex = count($items) - 1;
			}
		}

		if ($offsetIndex >= 0)
		{
			$items = array_splice($items, $offsetIndex + 1);
		}

		return $items;
	}
}

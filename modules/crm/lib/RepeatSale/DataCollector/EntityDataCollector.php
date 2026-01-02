<?php

namespace Bitrix\Crm\RepeatSale\DataCollector;

use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\ActivityDataCollector;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\StrategyFactory;
use Bitrix\Crm\RepeatSale\DataCollector\Mapper\SystemFieldsMapper;
use Bitrix\Crm\RepeatSale\DataCollector\Mapper\UserFieldsMapper;
use CCrmOwnerType;

final class EntityDataCollector extends BaseDataCollector
{
	protected const DEFAULT_LIMIT = 5;
	protected const SUPPORTED_ENTITY_TYPES = [
		CCrmOwnerType::Deal,
	];

	public function getMarkers(array $parameters = []): array
	{
		$entityId = (int)($parameters['entityId'] ?? 0);
		if ($entityId <= 0)
		{
			return [];
		}

		$clientEntityTypeId = (int)($parameters['clientEntityTypeId'] ?? 0);
		$clientEntityId = (int)($parameters['clientEntityId'] ?? 0);
		$filter = $this->prepareCopilotMarkersFilter(
			$entityId,
			$clientEntityTypeId,
			$clientEntityId
		);
		if (empty($filter))
		{
			return [];
		}

		$items = $this->getData([
			'filter' => $filter,
			'order' => [
				Item::FIELD_NAME_CREATED_TIME => 'DESC',
			],
			'limit' => self::DEFAULT_LIMIT,
		]);
		$items = array_map(
			static fn (Item $item) => array_filter($item->getCompatibleData()),
			$items
		);
		if (empty($items))
		{
			return [];
		}

		return [
			$this->getEntityList($items),
			$this->getOrdersSummary($items),
		];
	}

	private function getEntityList(array $items): array
	{
		$systemFieldsMapper = new SystemFieldsMapper($this->entityTypeId);
		$userFieldsMapper = new UserFieldsMapper($this->entityTypeId);

		// by default create StrategyFactory with all supported strategies,
		// but you can create your own StrategyFactory with only needed strategies
		$activityCollector = new ActivityDataCollector($this->entityTypeId, new StrategyFactory());

		$result = [];
		foreach ($items as $item)
		{
			$result[$item['ID']] = [
				'deal_fields' => [
					'system_fields' => $systemFieldsMapper->map($item),
					'user_fields' => $userFieldsMapper->map($item),
				],
				'communication_data' => $this->isSupportedEntityType()
					? $activityCollector->getMarkers(['entityId' => $item['ID']])
					: []
				,
			];
		}

		return $result;
	}

	private function getOrdersSummary(array $items): array
	{
		if (!$this->isSupportedEntityType())
		{
			return [];
		}

		$latestItemFound = false;
		$latestItem = null;
		$successDealSum = 0;
		$successDealCnt = 0;
		$failedDealCnt = 0;

		foreach ($items as $item)
		{
			if (
				$latestItemFound === false
				&& $item[Item::FIELD_NAME_STAGE_SEMANTIC_ID] !== PhaseSemantics::FAILURE
			)
			{
				$latestItem = $item;
				$latestItemFound = true;
			}

			if ($item[Item::FIELD_NAME_STAGE_SEMANTIC_ID] === PhaseSemantics::SUCCESS)
			{
				$successDealSum += $item[Item::FIELD_NAME_OPPORTUNITY];
				$successDealCnt++;
			}

			if ($item[Item::FIELD_NAME_STAGE_SEMANTIC_ID] === PhaseSemantics::FAILURE)
			{
				$failedDealCnt++;
			}
		}

		if ($latestItem === null)
		{
			$latestItem = reset($items);
		}

		return [
			'latest_purchase_deal_id' => $latestItem[Item::FIELD_NAME_ID],
			'latest_purchase_date' => $latestItem['DATE_CREATE'],
			'successful_deals_sum' => $successDealSum,
			'successful_deals_count' => $successDealCnt,
			'failed_deals_count' => $failedDealCnt,
		];
	}

	private function prepareCopilotMarkersFilter(int $entityId, int $clientEntityTypeId, int $clientEntityId): array
	{
		$filter = [];

		if ($clientEntityTypeId === CCrmOwnerType::Contact)
		{
			$filter = [
				'=' . Item::FIELD_NAME_CONTACT_BINDINGS . '.CONTACT_ID' => $clientEntityId,
			];
		}

		if ($clientEntityTypeId === CCrmOwnerType::Company)
		{
			$filter = [
				'=' . Item::FIELD_NAME_COMPANY_ID => $clientEntityId,
			];
		}

		return empty($filter)
			? []
			: array_merge($filter, [
				'=' . Item::FIELD_NAME_IS_RECURRING => 'N',
				'!=' .Item::FIELD_NAME_ID => $entityId,
			])
		;
	}
}

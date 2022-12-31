<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Crm\Binding;
use Bitrix\Crm\Order;
use Bitrix\Main\DB;
use Bitrix\Main;
use Bitrix\Sale\Delivery;
use Bitrix\Main\Entity;

/**
 * Class provides several methods to fetch shipments, related to deals
 * @package Bitrix\Crm\Deal
 */
final class ShipmentsRepository
{
	/**
	 * @throws Main\LoaderException
	 */
	public function __construct()
	{
		Main\Loader::includeModule('sale');
	}

	/**
	 * Returns map [dealId => stage of latest related shipment]

	 * @param array $dealIds
	 * @return array<int, string>
	 */
	public function getShipmentStages(array $dealIds): array
	{
		if (empty($dealIds))
		{
			return [];
		}

		static $result = [];

		$dealIdsForLoadingStages = [];
		foreach ($dealIds as $dealId)
		{
			if (!isset($result[$dealId]))
			{
				$dealIdsForLoadingStages[] = $dealId;
			}
		}

		if ($dealIdsForLoadingStages)
		{
			$result += $this->loadShipmentStages($dealIdsForLoadingStages);
		}

		$dealIdsAsKey = array_fill_keys($dealIds, true);

		return array_filter(
			$result,
			static function ($key) use ($dealIdsAsKey)
			{
				return isset($dealIdsAsKey[$key]);
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	protected function loadShipmentStages(array $dealIds) : array
	{
		$result = [];

		$dbRes = Order\ShipmentCollection::getList([
			'select' => ['DEDUCTED', 'DEAL_ID' => 'DEAL_BINDING.OWNER_ID'],
			'filter' => [
				'!SYSTEM' => 'Y',
				'!DELIVERY_ID' => Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId(),
				'@DEAL_ID' => $dealIds,
			],
			'order' => ['ORDER_ID' => 'desc', 'ID' => 'desc'],
			'runtime' => [
				new Entity\ReferenceField(
					'DEAL_BINDING',
					Binding\OrderEntityTable::class,
					[
						'=this.ORDER_ID' => 'ref.ORDER_ID',
						'=ref.OWNER_TYPE_ID' => new DB\SqlExpression(\CCrmOwnerType::Deal)
					],
					['join_type' => 'inner']
				)
			]
		]);

		while ($shipment = $dbRes->fetch())
		{
			if (isset($result[$shipment['DEAL_ID']]))
			{
				continue;
			}

			$result[$shipment['DEAL_ID']] = ($shipment['DEDUCTED'] === 'Y')
				? Order\DeliveryStage::SHIPPED
				: Order\DeliveryStage::NO_SHIPPED;
		}

		return $result;
	}
}

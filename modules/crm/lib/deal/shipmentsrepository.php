<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Crm\Order\DeliveryStage;
use Bitrix\Crm\Order\ShipmentCollection;
use Bitrix\Sale\Delivery;

/**
 * Class provides several methods to fetch shipments, related to deals
 * @package Bitrix\Crm\Deal
 */
final class ShipmentsRepository
{
	use OrdersMapMixin;

	/**
	 * Returns map [dealId => stage of latest related shipment]
	 * @param array $dealIds
	 * @return array<int, string>
	 */
	public function getShipmentStages(array $dealIds): array
	{
		if (count($dealIds) === 0)
		{
			return [];
		}

		$orderToDealMap = $this->getOrderToDealMap($dealIds);
		$orderIds = array_keys($orderToDealMap);

		if (count($orderIds) === 0)
		{
			return [];
		}

		$result = [];

		$select = ['ID', 'ORDER_ID', 'DEDUCTED', 'DELIVERY_CLASS_NAME' => 'DELIVERY.CLASS_NAME'];
		$where = ['=ORDER_ID' => $orderIds, '!SYSTEM' => 'Y'];
		$orderBy = ['ORDER_ID' => 'desc', 'ID' => 'desc'];

		$shipments = ShipmentCollection::getList([
			'select' => $select,
			'filter' => $where,
			'order' => $orderBy,
		]);
		while ($shipment = $shipments->fetch())
		{
			$isEmptyDeliveryService = (
				$shipment['DELIVERY_CLASS_NAME'] === '\\' . Delivery\Services\EmptyDeliveryService::class
				|| is_subclass_of($shipment['DELIVERY_CLASS_NAME'], Delivery\Services\EmptyDeliveryService::class)
			);
			if ($isEmptyDeliveryService)
			{
				continue;
			}

			$orderId = (int)$shipment['ORDER_ID'];
			$dealId = $orderToDealMap[$orderId];
			if ($dealId && !isset($result[$dealId]))
			{
				$result[$dealId] = ($shipment['DEDUCTED'] === 'Y')
					? DeliveryStage::SHIPPED
					: DeliveryStage::NO_SHIPPED;
			}
		}

		return $result;
	}
}

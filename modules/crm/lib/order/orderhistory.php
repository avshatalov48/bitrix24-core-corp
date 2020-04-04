<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class OrderHistory
 * @package Bitrix\Crm\Order
 */
class OrderHistory extends Sale\OrderHistory
{
	/**
	 * @param $entityName
	 * @param $orderId
	 * @param $type
	 * @param null $id
	 * @param null $entity
	 * @param array $data
	 * @throws Main\ArgumentNullException
	 */
	protected static function addRecord($entityName, $orderId, $type, $id = null, $entity = null, array $data = array())
	{
		parent::addRecord($entityName, $orderId, $type, $id, $entity, $data);

		$operationType = static::getOperationType($entityName, $type);
		if (empty($operationType))
		{
			return;
		}

		if ($entity instanceof BasketItem)
		{
			$entityType = \CCrmOwnerType::OrderName;
			$entityId = $entity->getField('ORDER_ID');
		}
		elseif ($entity instanceof Order)
		{
			$entityType = \CCrmOwnerType::OrderName;
			$entityId = $entity->getId();
		}
		elseif ($entity instanceof ShipmentItem)
		{
			$entityType = \CCrmOwnerType::OrderShipmentName;
			$entityId = $entity->getField('ORDER_DELIVERY_ID');
			$basketItem = $entity->getBasketItem();
			if ($basketItem)
			{
				$data['NAME'] = $basketItem->getField('NAME');
				$data['PRODUCT_ID'] = $basketItem->getField('PRODUCT_ID');
			}
		}
		elseif ($entity instanceof Payment)
		{
			$entityType = \CCrmOwnerType::OrderPaymentName;
			$entityId = $entity->getId();
		}
		else
		{
			return;
		}

		$orderChange = new \CSaleOrderChange();
		$operationResult = $orderChange->GetRecordDescription($type, serialize($data));

		global $USER;

		$crmEventData = [
			'ENTITY_TYPE' => $entityType,
			'ENTITY_ID' => $entityId,
			'EVENT_TYPE' => \CCrmEvent::TYPE_CHANGE,
			'USER_ID' => (is_object($USER)) ? intval($USER->GetID()) : 0,
			'ENTITY_FIELD' => is_array($operationType['TRIGGER_FIELDS']) ? current($operationType['TRIGGER_FIELDS']) : "",
			'EVENT_NAME' => $operationResult['NAME'],
			'EVENT_TEXT_1' => $operationResult['INFO']
		];

		$event = new \CCrmEvent();
		$event->Add($crmEventData, false);
	}
}
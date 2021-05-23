<?php

namespace Bitrix\Sale\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Services\Taxi\ITaxiDeliveryService;
use Bitrix\Sale\Internals\ShipmentTable;
use Bitrix\Sale\Shipment;
use Bitrix\Sale\Order;

/**
 * Class TaxiDelivery
 * @package Bitrix\Sale\Controller
 */
class TaxiDelivery extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param int $shipmentId
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function sendRequestAction(int $shipmentId)
	{
		$shipment = $this->getShipmentById($shipmentId);
		if (!$shipment)
		{
			return null;
		}

		/** @var ITaxiDeliveryService $deliveryService */
		$deliveryService = $shipment->getDelivery();

		$result = $deliveryService->createTaxiRequest($shipment);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return [];
	}

	/**
	 * @param int $shipmentId
	 * @param int $requestId
	 * @return array|null
	 */
	public function cancelRequestAction(int $shipmentId, int $requestId)
	{
		$shipment = $this->getShipmentById($shipmentId);
		if (!$shipment)
		{
			return null;
		}

		/** @var ITaxiDeliveryService $deliveryService */
		$deliveryService = $shipment->getDelivery();

		$result = $deliveryService->cancelTaxiRequest($requestId);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return [];
	}

	/**
	 * @param int $shipmentId
	 * @return Shipment|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getShipmentById(int $shipmentId)
	{
		/**
		 * Get shipment object
		 */
		$shipmentRecord = ShipmentTable::getById($shipmentId)->fetch();
		if (!$shipmentRecord)
		{
			$this->addError(new Error('shipment_not_found'));
			return null;
		}

		/** @var Order $order */
		$order = Order::load($shipmentRecord['ORDER_ID']);
		if (!$order)
		{
			$this->addError(new Error('order_not_found'));
			return null;
		}

		$shipment = null;
		$shipmentCollection = $order->getShipmentCollection()->getNotSystemItems();
		/** @var Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->getId() == $shipmentId)
			{
				break;
			}
		}

		if (!$shipment)
		{
			$this->addError(new Error('shipment_not_found'));
			return null;
		}

		if (!$shipment->getDelivery() instanceof ITaxiDeliveryService)
		{
			$this->addError(new Error(Loc::getMessage('CONTROLLER_ERROR_CODE_NOT_OF_TAXI_TYPE')));
			return null;
		}

		return $shipment;
	}

	/**
	 * Workaround action needed only to run Yandex Go journal processor agent:
	 * \Sale\Handlers\Delivery\YandexTaxi\EventJournal\JournalProcessor::processJournal
	 *
	 * Needs to be removed after the order auto-confirmation feature is released on Yandex' side
	 *
	 * @return array
	 */
	public function checkRequestStatusAction()
	{
		return [];
	}
}

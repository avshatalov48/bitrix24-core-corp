<?php

namespace Bitrix\Crm\Controller\Item\Payment;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

class Delivery extends Base
{
	public function listAction(int $paymentId, array $filter = [], array $order = []): array
	{
		$paymentItemList = parent::listAction($paymentId, $filter, $order);

		$result = [];

		foreach ($paymentItemList as $item)
		{
			$result[] = [
				'ID' => $item['ID'],
				'PAYMENT_ID' => $item['PAYMENT_ID'],
				'QUANTITY' => $item['QUANTITY'],
				'DELIVERY_ID' => $item['ENTITY_ID'],
			];
		}

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @param int $paymentId
	 * @param int $deliveryId
	 * @return int|null
	 */
	public function addAction(int $paymentId, int $deliveryId): ?int
	{
		/** @var Crm\Order\Payment $payment */
		$payment = $this->getPaymentById($paymentId);
		if (!$payment)
		{
			return null;
		}

		if (!$this->canEditPayment($payment))
		{
			return null;
		}

		$order = $payment->getOrder();

		/** @var Sale\Shipment $shipment */
		$shipment = $order->getShipmentCollection()->getItemById($deliveryId);
		if (!$shipment)
		{
			$this->addError(
				new Main\Error(
					Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_DELIVERY_ADD_NOT_FOUND')
				)
			);

			return null;
		}

		$payableItem = $payment->getPayableItemCollection()->createItemByShipment($shipment);

		$this->recalculatePaymentSum($payment);

		$result = $order->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $payableItem->getId();
	}

	/**
	 * @param int $id - ID from b_sale_order_payment_item
	 * @param int $deliveryId - ID from b_sale_order_delivery
	 * @return bool|null
	 */
	public function setDeliveryAction(int $id, int $deliveryId): ?bool
	{
		$payment = $this->getPaymentByPayableId($id);
		if (!$payment)
		{
			return false;
		}

		if (!$this->canEditPayment($payment))
		{
			return false;
		}

		$order = $payment->getOrder();

		$payableItem = $payment->getPayableItemCollection()->getItemById($id);

		$payableItem->setField('ENTITY_ID', $deliveryId);

		$this->recalculatePaymentSum($payment);

		$result = $order->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	protected function getEntityType(): string
	{
		return Sale\Registry::ENTITY_SHIPMENT;
	}
}
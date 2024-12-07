<?php

namespace Bitrix\Crm\Controller\Item\Payment;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

class Delivery extends Base
{
	public function listAction(int $paymentId, array $filter = [], array $order = []): ?array
	{
		$paymentItemList = parent::listAction($paymentId, $filter, $order);
		if (is_null($paymentItemList))
		{
			return null;
		}

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

	public function addAction(int $paymentId, int $deliveryId): ?int
	{
		/** @var Crm\Order\Payment $payment */
		$payment = $this->getPaymentById($paymentId);
		if (!$payment)
		{
			$this->addError(new Error('Payment has not been found'));

			return null;
		}

		if (!$this->canEditPayment($payment))
		{
			$this->setAccessDenied();

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
			$this->addError(new Error('Payable item has not been found'));

			return null;
		}

		if (!$this->canEditPayment($payment))
		{
			$this->setAccessDenied();

			return null;
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

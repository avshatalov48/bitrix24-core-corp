<?php

namespace Bitrix\CrmMobile\Controller\Document;

use Bitrix\CrmMobile\Integration\Sale\Check\GetPaymentChecksQuery;
use Bitrix\CrmMobile\Integration\Sale\Payment\GetPaymentQuery;
use Bitrix\Sale\Registry;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\Sale\PayableShipmentItem;
use Bitrix\Crm\Order\Permissions;

class Payment extends Base
{
	public function setPaidAction(int $documentId, string $value)
	{
		return $this->forward(
			\Bitrix\Crm\Controller\Order\Payment::class,
			'setPaid',
			[
				'id' => $documentId,
				'value' => $value,
			]
		);
	}

	public function deleteAction(int $documentId)
	{
		return $this->forward(
			\Bitrix\Crm\Controller\Order\Payment::class,
			'delete',
			[
				'id' => $documentId,
			]
		);
	}

	public function getDocumentDataAction(int $documentId): array
	{
		$hasReadPermission = Permissions\Payment::checkReadPermission($documentId);
		if (!$hasReadPermission)
		{
			return [];
		}

		/** @var \Bitrix\Crm\Order\Payment $payment */
		$payment = PaymentRepository::getInstance()->getById($documentId);
		if (!$payment)
		{
			return [];
		}

		$result = parent::getDocumentDataAction($documentId);

		$result['payment'] = (new GetPaymentQuery($payment))->execute();
		$result['checks'] = (new GetPaymentChecksQuery($payment))->execute();

		$binding = $payment->getOrder()->getEntityBinding();
		if ($binding)
		{
			$result['entity'] = [
				'id' => $binding->getOwnerId(),
				'typeId' => $binding->getOwnerTypeId(),
			];
		}

		$shipmentData = $this->getShipmentData($documentId);
		if (!empty($shipmentData))
		{
			$result['shipment'] = $shipmentData;
		}

		return $result;
	}

	private function getShipmentData(int $paymentId): array
	{
		$payment = PaymentRepository::getInstance()->getById($paymentId);
		if (!$payment)
		{
			return [];
		}
		$orderId = $payment->getOrderId();
		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);

		/** @var \Bitrix\Crm\Order\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();
		$order = $orderClassName::load($orderId);
		if (!$order)
		{
			return [];
		}

		$shipmentId = 0;
		/** @var PayableShipmentItem $payableItem */
		foreach ($payment->getPayableItemCollection()->getShipments() as $payableItem)
		{
			$shipmentId = $payableItem->getField('ENTITY_ID');
		}

		if (!$shipmentId)
		{
			return [];
		}

		$shipment = $order->getShipmentCollection()->getItemById($shipmentId);
		if (!$shipment)
		{
			return [];
		}

		return $shipment->getFieldValues();
	}
}

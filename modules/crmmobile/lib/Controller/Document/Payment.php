<?php

namespace Bitrix\CrmMobile\Controller\Document;

use Bitrix\Crm\Workflow\EntityStageTable;
use Bitrix\Crm\Workflow\PaymentWorkflow;
use Bitrix\CrmMobile\Controller\ReceivePayment\SalescenterControllerWrapper;
use Bitrix\Sale\Registry;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\Sale\PayableShipmentItem;

class Payment extends Base
{
	use SalescenterControllerWrapper;

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
		$result = parent::getDocumentDataAction($documentId);
		$result['payment'] = $this->getPaymentData($documentId);

		$shipmentData = $this->getShipmentData($documentId);
		if (!empty($shipmentData))
		{
			$result['shipment'] = $shipmentData;
		}

		return $result;
	}

	private function getPaymentData(int $paymentId): array
	{
		$payment = PaymentRepository::getInstance()->getById($paymentId);
		if (!$payment)
		{
			return [];
		}
		$result = $payment->getFieldValues();
		if (isset($result['DATE_PAID']))
		{
			$result['FORMATTED_DATE_PAID'] = ConvertTimeStamp($result['DATE_PAID']->getTimestamp(), 'FULL');
		}

		$paymentStages = EntityStageTable::getRow([
			'select' => ['ENTITY_ID', 'STAGE'],
			'filter' => ['=ENTITY_ID' => $payment->getId(), '=WORKFLOW_CODE' => PaymentWorkflow::getWorkflowCode()],
		]);
		if (isset($paymentStages['STAGE']))
		{
			$result['STAGE'] = $paymentStages['STAGE'];
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
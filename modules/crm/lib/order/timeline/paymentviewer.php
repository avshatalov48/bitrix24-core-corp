<?php

namespace Bitrix\Crm\Order\Timeline;

use Bitrix\Crm\Order\BindingsMaker\TimelineBindingsMaker;
use Bitrix\Crm\Order\OrderStage;
use Bitrix\Crm\Order\Payment;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\OrderPaymentController;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Workflow\PaymentStage;
use Bitrix\Crm\Workflow\PaymentWorkflow;
use Bitrix\Main\Loader;

class PaymentViewer
{
	public function view(Payment $payment, string $viewedWay): void
	{
		$needEmitOrderViewedEvent = false;
		$paymentWorkflow = PaymentWorkflow::createFrom($payment);

		if ($paymentWorkflow->setStage(PaymentStage::VIEWED_NO_PAID))
		{
			$needEmitOrderViewedEvent = true;
		}

		if ($this->needAddTimelineEntityOnOpen($payment, $viewedWay))
		{
			$needEmitOrderViewedEvent = true;
			$this->addTimelineEntityOnView($payment, $viewedWay);

			if (!$payment->isPaid())
			{
				$binding = $payment->getOrder()->getEntityBinding();
				if (
					$binding
					&& $binding->getOwnerTypeId() === \CCrmOwnerType::Deal
				)
				{
					$this->changeOrderStageDealOnViewedNoPaid(
						$binding->getOwnerId()
					);
				}
			}
		}

		if ($needEmitOrderViewedEvent)
		{
			$this->emitOrderViewedEvent($payment);
		}
	}

	private function emitOrderViewedEvent(Payment $payment): void
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}

		$orderId = $payment->getOrder()->getId();
		if ($orderId <= 0)
		{
			return;
		}

		\CPullWatch::AddToStack(
			'SALESCENTER_ORDER_PAYMENT_VIEWED_' . $orderId,
			[
				'module_id' => 'salescenter',
				'command' => 'onOrderPaymentViewed',
				'params' => [
					'ORDER_ID' => $orderId,
					'PAYMENT_ID' => $payment->getId(),
				],
			]
		);
	}

	private function needAddTimelineEntityOnOpen(Payment $payment, string $viewedWay): bool
	{
		$dbRes = TimelineTable::getList([
			'select' => ['ID', 'SETTINGS'],
			'order' => ['ID' => 'ASC'],
			'filter' => [
				'=TYPE_ID' => TimelineType::ORDER,
				'=ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'=ASSOCIATED_ENTITY_ID' => $payment->getId(),
			]
		]);

		while ($item = $dbRes->fetch())
		{
			if (
				isset($item['SETTINGS']['FIELDS'][$viewedWay])
				&& $item['SETTINGS']['FIELDS'][$viewedWay] === 'Y'
			)
			{
				return false;
			}
		}

		return true;
	}

	private function addTimelineEntityOnView(Payment $payment, string $viewedWay): void
	{
		OrderPaymentController::getInstance()->onView(
			$payment->getId(),
			[
				'ORDER_FIELDS' => $payment->getOrder()->getFieldValues(),
				'SETTINGS' => [
					'FIELDS' => [
						'ORDER_ID' => $payment->getOrderId(),
						'PAYMENT_ID' => $payment->getId(),
					]
				],
				'BINDINGS' => TimelineBindingsMaker::makeByPayment($payment),
				'FIELDS' => $payment->getFieldValues(),
			],
			$viewedWay
		);
	}

	private function changeOrderStageDealOnViewedNoPaid(int $dealId): void
	{
		$fields = ['ORDER_STAGE' => OrderStage::VIEWED_NO_PAID];

		$deal = new \CCrmDeal(false);
		$deal->Update($dealId, $fields);
	}
}

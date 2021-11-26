<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm;

final class SaleOrderCheckout
{
	public static function onPaymentPayAction(Main\Event $event): void
	{
		$order = $event->getParameter('ORDER');
		if ($order instanceof Crm\Order\Order)
		{
			$orderId = $order->getId();

			if (!self::needAddTimelineRecordOnPaymentPay($orderId))
			{
				return;
			}

			$timelineParams = [
				'SETTINGS' => [
					'CHANGED_ENTITY' => \CCrmOwnerType::OrderName,
					'FIELDS' => [
						'ORDER_ID' => $orderId,
					],
				],
				'ORDER_FIELDS' => $order->getFieldValues(),
				'BINDINGS' => Crm\Order\BindingsMaker\TimelineBindingsMaker::makeByOrder($order),
			];

			Crm\Timeline\OrderController::getInstance()->onManualContinuePay($orderId, $timelineParams);
		}
	}

	private static function needAddTimelineRecordOnPaymentPay(int $orderId): bool
	{
		$timelineIterator = Crm\Timeline\Entity\TimelineTable::getList([
			'select' => ['ID', 'SETTINGS'],
			'filter' => [
				'=ASSOCIATED_ENTITY_ID' => $orderId ,
				'=ASSOCIATED_ENTITY_TYPE_ID' => Crm\Timeline\TimelineType::ORDER,
				'=TYPE_ID' => Crm\Timeline\TimelineType::ORDER,
			],
		]);
		while ($timelineData = $timelineIterator->fetch())
		{
			$timelineSettings = $timelineData['SETTINGS'];
			$isManualContinuePay = $timelineSettings['FIELDS']['MANUAL_CONTINUE_PAY'] ?? null;
			if ($isManualContinuePay === 'Y')
			{
				return false;
			}
		}

		return true;
	}
}
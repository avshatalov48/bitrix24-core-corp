<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Crm\Order;
use Bitrix\Crm\Timeline;
use Bitrix\Main;
use Bitrix\Sale;

Main\Loader::includeModule('crm');

final class PaySystem
{
	public static function onSalePsBeforeInitiatePay(Main\Event $event): void
	{
		/** @var Order\Payment $payment */
		$payment = $event->getParameter('payment');
		if (!$payment instanceof Order\Payment)
		{
			return;
		}

		/** @var Sale\PaySystem\Service $service */
		$service = $event->getParameter('service');

		if (!self::needAddTimelineRecord($payment, $service))
		{
			return;
		}

		$params =  [
			'FIELDS' => $payment->getFieldValues(),
			'SETTINGS' => [
				'FIELDS' => [
					'PAY_SYSTEM_ID' => $service->getField('ID'),
					'PAY_SYSTEM_NAME' => $service->getField('NAME'),
				],
			],
			'BINDINGS' => Order\BindingsMaker\TimelineBindingsMaker::makeByPayment($payment),
		];

		TimeLine\OrderPaymentController::getInstance()->onClick($payment->getId(), $params);
	}


	private static function needAddTimelineRecord(Order\Payment $payment, Sale\PaySystem\Service $service) : bool
	{
		$dbRes = Timeline\Entity\TimelineTable::getList([
			'select' => ['ID', 'SETTINGS'],
			'filter' => [
				'=ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'=ASSOCIATED_ENTITY_ID' => $payment->getId(),
				'%=SETTINGS' => '%PAY_SYSTEM_CLICK%'
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1
		]);

		if ($data = $dbRes->fetch())
		{
			$paySystemId = $data['SETTINGS']['FIELDS']['PAY_SYSTEM_ID'] ?? 0;

			return (int)$paySystemId !== (int)$service->getField('ID');
		}

		return true;
	}

	/**
	 * @param Main\Event $event
	 * @throws Main\ArgumentException
	 */
	public static function onSalePsInitiatePayError(Main\Event $event): void
	{
		/** @var Order\Payment $payment */
		$payment = $event->getParameter('payment');
		if (!$payment instanceof Order\Payment)
		{
			return;
		}

		$timelineParams = [
			'FIELDS' => $payment->getFieldValues(),
			'SETTINGS' => [
				'FIELDS' => [
					'PAY_SYSTEM_NAME' => $payment->getPaymentSystemName(),
					'STATUS_CODE' => 'ERROR',
					'STATUS_DESCRIPTION' => implode("\n", $event->getParameter('errors')),
				]
			],
			'BINDINGS' => Order\BindingsMaker\TimelineBindingsMaker::makeByPayment($payment),
		];

		Timeline\OrderPaymentController::getInstance()->onPaid($payment->getId(), $timelineParams);
	}
}
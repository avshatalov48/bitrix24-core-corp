<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Application;
use CCrmContact;
use CCrmFieldMulti;
use CCrmOwnerType;

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

	public static function onPrepareJsonData(array &$jsonData): void
	{
		$properties = $jsonData['SCHEME']['PROPERTIES'] ?? null;
		if (!$properties)
		{
			return;
		}

		$session = Application::getInstance()->getSession();
		$compilationDealId = $session->get('CATALOG_CURRENT_COMPILATION_DEAL_ID');
		if (!$compilationDealId)
		{
			return;
		}

		$currentDealContactData = [];

		$contactBindings = DealContactTable::getDealBindings($compilationDealId);
		$primaryDealContactId = EntityBinding::getPrimaryEntityID(
			CCrmOwnerType::Contact,
			$contactBindings
		);

		$contactData = CCrmContact::GetByID($primaryDealContactId, false);
		$contactPhoneData = CCrmFieldMulti::GetEntityFirstField(
			CCrmOwnerType::ContactName,
			$primaryDealContactId,
			CCrmFieldMulti::PHONE
		);
		$contactEmailData = CCrmFieldMulti::GetEntityFirstField(
			CCrmOwnerType::ContactName,
			$primaryDealContactId,
			CCrmFieldMulti::EMAIL
		);

		$currentDealContactData['NAME'] = $contactData['FULL_NAME'];
		$currentDealContactData['PHONE'] = $contactPhoneData['VALUE'];
		$currentDealContactData['EMAIL'] = $contactEmailData['VALUE'];

		foreach ($properties as $propertyKey => $property)
		{
			$propertyType = $property['TYPE'] ?? null;
			$currentDealContactValue = $currentDealContactData[$propertyType] ?? null;

			if ($currentDealContactValue !== null)
			{
				$properties[$propertyKey]['VALUE'] = $currentDealContactValue;
			}
		}

		$jsonData['SCHEME']['PROPERTIES'] = $properties;
	}
}
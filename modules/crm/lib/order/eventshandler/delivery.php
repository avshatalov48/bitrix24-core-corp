<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Crm\Order\Order;
use Bitrix\Main\Event;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Main;
use Bitrix\Sale;

Main\Loader::includeModule('sale');

/**
 * Class Delivery
 * @package Bitrix\Crm\Order\EventsHandler
 * @internal
 */
class Delivery
{
	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onNeedRecipientContactData(Event $event) : Main\EventResult
	{
		/** @var \Bitrix\Crm\Order\Shipment $order */
		$shipment = $event->getParameter('SHIPMENT');

		/** @var Order $order */
		$order = $shipment->getOrder();

		$contactId = null;

		$binding = $order->getEntityBinding();
		if (
			$binding
			&& $binding->getOwnerTypeId() === \CCrmOwnerType::Deal
			&& ($dealId = $binding->getOwnerId())
			&& ($deal = DealTable::getById($dealId)->fetch())
		)
		{
			$contactId = $deal['CONTACT_ID'];
		}
		elseif (
			($contactCompanyCollection = $order->getContactCompanyCollection())
			&& ($orderContact = $contactCompanyCollection->getPrimaryContact())
		)
		{
			$contactId = (int)$orderContact->getField('ENTITY_ID');
		}

		if (!$contactId || !($contactRow = ContactTable::getById($contactId)->fetch()))
		{
			return new Main\EventResult(Main\EventResult::ERROR);
		}

		$contact = (new Sale\Delivery\Services\Contact())->setName(
			\CCrmContact::PrepareFormattedName([
					'HONORIFIC' => $contactRow['HONORIFIC'],
					'NAME' => $contactRow['NAME'],
					'LAST_NAME' => $contactRow['LAST_NAME'],
					'SECOND_NAME' => $contactRow['SECOND_NAME']
				]
		));

		/**
		 * Phones
		 */
		if ($contactRow['HAS_PHONE'] === 'Y')
		{
			$phoneResults = \CCrmFieldMulti::GetEntityFields(
				'CONTACT',
				$contactRow['ID'],
				'PHONE',
				true
			);
			if (!empty($phoneResults))
			{
				foreach ($phoneResults as $phoneResult)
				{
					if (!(isset($phoneResult['VALUE']) && !empty($phoneResult['VALUE'])))
					{
						continue;
					}

					$contact->addPhone(
						new Sale\Delivery\Services\Phone($phoneResult['VALUE_TYPE'], $phoneResult['VALUE'])
					);
				}
			}
		}

		return new Main\EventResult(Main\EventResult::SUCCESS, $contact);
	}
}

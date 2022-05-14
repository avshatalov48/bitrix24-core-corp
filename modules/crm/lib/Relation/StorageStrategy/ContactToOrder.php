<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\Contact;
use Bitrix\Crm\Order\ContactCompanyBinding;
use Bitrix\Crm\Order\ContactCompanyEntity;
use Bitrix\Crm\Order\Order;
use Bitrix\Main\Result;

class ContactToOrder extends ContactCompanyToOrder
{
	/**
	 * Returns a contact entity
	 * Always creates a new entity, even if other contacts are bound to this order
	 *
	 * @param Order $order
	 *
	 * @return Contact
	 */
	protected function getEntity(Order $order): ContactCompanyEntity
	{
		/** @var Contact $contact */
		$contact = $order->getContactCompanyCollection()->createContact();
		if (!$order->getContactCompanyCollection()->isPrimaryItemExists(\CCrmOwnerType::Contact))
		{
			$contact->setField('IS_PRIMARY', 'Y');
		}

		return $contact;
	}

	protected function afterBindingDeletion(Order $order): void
	{
		$collection = $order->getContactCompanyCollection();
		if (!$collection->isPrimaryItemExists(\CCrmOwnerType::Contact))
		{
			/** @var Contact[] $contacts */
			$contacts = iterator_to_array($collection->getContacts());
			$firstNotDeletedContact = array_shift($contacts);
			if ($firstNotDeletedContact)
			{
				$firstNotDeletedContact->setField('IS_PRIMARY', 'Y');
			}
		}
	}

	protected function replaceBindings(ItemIdentifier $fromItem, ItemIdentifier $toItem): Result
	{
		(new ContactCompanyBinding(\CCrmOwnerType::Contact))->rebind(
			$fromItem->getEntityId(),
			$toItem->getEntityId()
		);

		return new Result();
	}
}

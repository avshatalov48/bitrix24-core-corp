<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm\Service\Container;

/**
 * Class Contact
 * @package Bitrix\Crm\Order
 */
class Contact extends ContactCompanyEntity
{
	/**
	 * @return string
	 */
	public static function getEntityType()
	{
		return \CCrmOwnerType::Contact;
	}

	/**
	 * @return string
	 */
	public static function getEntityTypeName()
	{
		return \CCrmOwnerType::ContactName;
	}

	/**
	 * @return null|string
	 * @internal
	 *
	 */
	public static function getEntityEventName()
	{
		return 'CrmOrderContact';
	}

	/**
	 * @return string|void
	 */
	public static function getRegistryEntity()
	{
		return ENTITY_CRM_CONTACT;
	}

	/**
	 * @inheritDoc
	 */
	public function getCustomerName(): ?string
	{
		$factory = Container::getInstance()->getFactory(static::getEntityType());

		if (!$factory)
		{
			return null;
		}

		$contactItem = $factory->getItem($this->getField('ENTITY_ID'), ['NAME', 'FULL_NAME']);

		if (!$contactItem)
		{
			return null;
		}

		return $contactItem->getName() ?: $contactItem->getFullName();
	}
}

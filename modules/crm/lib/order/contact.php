<?php

namespace Bitrix\Crm\Order;

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
}
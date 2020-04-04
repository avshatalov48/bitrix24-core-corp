<?php

namespace Bitrix\Crm\Order;

/**
 * Class Contact
 * @package Bitrix\Crm\Order
 */
class Contact extends ContactCompanyEntity
{
	const REGISTRY_ENTITY_NAME = ENTITY_CRM_CONTACT;

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
}
<?php

namespace Bitrix\Crm\Order;

/**
 * Class Company
 * @package Bitrix\Crm\Order
 */
class Company extends ContactCompanyEntity
{
	const REGISTRY_ENTITY_NAME = ENTITY_CRM_COMPANY;

	/**
	 * @return string
	 */
	public static function getEntityType()
	{
		return \CCrmOwnerType::Company;
	}

	/**
	 * @return string
	 */
	public static function getEntityTypeName()
	{
		return \CCrmOwnerType::CompanyName;
	}
}
<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm\Service\Container;

/**
 * Class Company
 * @package Bitrix\Crm\Order
 */
class Company extends ContactCompanyEntity
{
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

	/**
	 * @return null|string
	 * @internal
	 *
	 */
	public static function getEntityEventName()
	{
		return 'CrmOrderCompany';
	}

	/**
	 * @return string
	 */
	public static function getRegistryEntity()
	{
		return ENTITY_CRM_COMPANY;
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

		$companyItem = $factory->getItem($this->getField('ENTITY_ID'), ['TITLE']);

		if (!$companyItem)
		{
			return null;
		}

		return $companyItem->getTitle() ?? null;
	}
}

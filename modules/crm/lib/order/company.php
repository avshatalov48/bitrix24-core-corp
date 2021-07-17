<?php

namespace Bitrix\Crm\Order;

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
		$company = \CAllCrmCompany::GetByID($this->getField('ENTITY_ID'), false);

		return $company ? (string)$company['TITLE'] : null;
	}
}

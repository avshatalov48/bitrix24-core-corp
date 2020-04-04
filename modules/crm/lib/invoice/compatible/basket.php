<?php
namespace Bitrix\Crm\Invoice\Compatible;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class Basket extends Sale\Compatible\BasketCompatibility
{
	const ENTITY_ORDER_TABLE = 'b_crm_invoice';
	const ENTITY_PAYMENT_TABLE = 'b_crm_invoice_payment';

	/**
	 * @return string
	 */
	protected static function getOrderCompatibilityClassName()
	{
		return Invoice::class;
	}


	/**
	 * @return string
	 */
	protected static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @return Main\Entity\Base
	 *
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected static function getEntity()
	{
		return Crm\Invoice\Internals\BasketTable::getEntity();
	}
}

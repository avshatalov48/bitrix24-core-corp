<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentPropertyValueCollection
 * @package Bitrix\Crm\Invoice
 *
 * We suggest, that invoices don't really need  ShipmentPropertyValueCollection yet
 */
class ShipmentPropertyValueCollection extends Sale\ShipmentPropertyValueCollection
{
	/** @inherit  */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/** @inherit  */
	protected static function deleteInternal($primary)
	{
		return new Main\ORM\Data\DeleteResult();
	}

	/** @inherit  */
	public static function getList(array $parameters = [])
	{
		return new Main\DB\ArrayResult([]);
	}

	/** @inherit */
	protected static function getAllItemsFromDb(int $entityId): array
	{
		return [];
	}

	/** @inherit  */
	protected function getOriginalItemsValues()
	{
		return [];
	}
}
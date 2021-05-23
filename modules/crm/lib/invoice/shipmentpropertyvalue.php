<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;
use Bitrix\Sale\Internals\Entity;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentPropertyValue
 * @package Bitrix\Crm\Invoice
 * We suggest, that invoices don't really need  ShipmentPropertyValue yet
 */
class ShipmentPropertyValue extends Sale\ShipmentPropertyValue
{
	/** @inherit  */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/** @inherit  */
	protected static function getFieldsMap()
	{
		return Internals\InvoicePropsValueTable::getMap();
	}

	/** @inherit  */
	protected function addInternal(array $data)
	{
		return new Main\ORM\Data\AddResult();
	}

	/** @inherit  */
	protected function updateInternal($primary, array $data)
	{
		return new Main\ORM\Data\UpdateResult();
	}

	/** @inherit  */
	public static function getList(array $parameters = [])
	{
		return new Main\DB\ArrayResult([]);
	}

	/** @inherit  */
	public static function getTableEntity()
	{
		return Internals\InvoicePropsValueTable::getEntity();
	}

	/** @inherit  */
	protected static function loadFromDb(Entity $entity): array
	{
		return [[], [], [], []];
	}
}
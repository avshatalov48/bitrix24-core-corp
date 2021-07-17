<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class ShipmentCollection
 * @package Bitrix\Crm\Order
 */
class ShipmentCollection extends Sale\ShipmentCollection
{
	protected function isAllowAutoEdit(Sale\BasketItem $basketItem)
	{
		if (!\CCrmSaleHelper::isWithOrdersMode())
		{
			return false;
		}

		return parent::isAllowAutoEdit($basketItem);
	}
}
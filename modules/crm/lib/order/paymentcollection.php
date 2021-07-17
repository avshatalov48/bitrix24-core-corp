<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class PaymentCollection
 * @package Bitrix\Crm\Order
 */
class PaymentCollection extends Sale\PaymentCollection
{
	protected function isAllowAutoEdit()
	{
		if (!\CCrmSaleHelper::isWithOrdersMode())
		{
			return false;
		}

		return parent::isAllowAutoEdit();
	}
}
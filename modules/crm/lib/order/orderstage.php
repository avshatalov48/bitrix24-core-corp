<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main\Localization;


Localization\Loc::loadMessages(__FILE__);

/**
 * Class OrderStage
 * @package Bitrix\Crm\Order
 */
final class OrderStage
{
	public const PAID = 'PAID';
	public const SENT_NO_VIEWED = 'SENT_NO_VIEWED';
	public const VIEWED_NO_PAID = 'VIEWED_NO_PAID';
	public const PAYMENT_CANCEL = 'PAYMENT_CANCEL';
	public const REFUND = 'REFUND';

	public static function getList() : array
	{
		$result = [];

		$reflection = new \ReflectionClass(static::class);

		$constantList = $reflection->getConstants();
		foreach ($constantList as $name => $value)
		{
			$result[$value] = Localization\Loc::getMessage('CRM_ORDER_STAGE_'.$name);
		}

		return $result;
	}
}
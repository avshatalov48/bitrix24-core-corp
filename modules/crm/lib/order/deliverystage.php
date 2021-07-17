<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main\Localization;


Localization\Loc::loadMessages(__FILE__);

/**
 * Class DeliveryStage
 * @package Bitrix\Crm\Order
 */
final class DeliveryStage
{
	public const SHIPPED = 'SHIPPED';
	public const NO_SHIPPED = 'NO_SHIPPED';

	public static function getList() : array
	{
		$result = [];

		$reflection = new \ReflectionClass(static::class);

		$constantList = $reflection->getConstants();
		foreach ($constantList as $name => $value)
		{
			$result[$value] = Localization\Loc::getMessage('CRM_DELIVERY_STAGE_'.$name);
		}

		return $result;
	}
}

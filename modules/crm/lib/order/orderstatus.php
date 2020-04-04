<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class OrderStatus
 * @package Bitrix\Crm\Order
 */
class OrderStatus extends Sale\OrderStatus
{
	use StatusTrait;

	const NAME = 'ORDER_STATUS';

	/**
	 * @return string
	 */
	public static function getFinalUnsuccessfulStatus()
	{
		return 'D';
	}

	/**
	 * @return array
	 */
	public static function getDefaultStatuses()
	{
		return [
			'N' => [
				'NAME' => GetMessage('CRM_ORDER_STATUS_INITIAL'),
				'STATUS_ID' => 'N',
				'SORT' => 100,
				'SYSTEM' => 'Y'
			],
			'P' => [
				'NAME' => GetMessage('CRM_ORDER_STATUS_PAID'),
				'STATUS_ID' => 'P',
				'SORT' => 150,
				'SYSTEM' => 'Y'
			],
			'S' => [
				'NAME' => GetMessage('CRM_ORDER_STATUS_SEND'),
				'STATUS_ID' => 'S',
				'SORT' => 175,
				'SYSTEM' => 'Y'
			],
			'F' => [
				'NAME' => GetMessage('CRM_ORDER_STATUS_FINISHED'),
				'STATUS_ID' => 'F',
				'SORT' => 200,
				'SYSTEM' => 'Y'
			],
			'D' => [
				'NAME' => GetMessage('CRM_ORDER_STATUS_REFUSED'),
				'STATUS_ID' => 'D',
				'SORT' => 250,
				'SYSTEM' => 'Y'
			]
		];
	}
}
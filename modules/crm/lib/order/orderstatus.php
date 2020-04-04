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
		return array(
			'N' => array(
				'NAME' => GetMessage('CRM_ORDER_STATUS_INITIAL'),
				'STATUS_ID' => 'N',
				'SORT' => 100,
				'SYSTEM' => 'Y'
			),
			'P' => array(
				'NAME' => GetMessage('CRM_ORDER_STATUS_PAID'),
				'STATUS_ID' => 'P',
				'SORT' => 150,
				'SYSTEM' => 'Y'
			),
			'F' => array(
				'NAME' => GetMessage('CRM_ORDER_STATUS_FINISHED'),
				'STATUS_ID' => 'F',
				'SORT' => 200,
				'SYSTEM' => 'Y'
			),
			'D' => array(
				'NAME' => GetMessage('CRM_ORDER_STATUS_REFUSED'),
				'STATUS_ID' => 'D',
				'SORT' => 250,
				'SYSTEM' => 'Y'
			)
		);
	}
}
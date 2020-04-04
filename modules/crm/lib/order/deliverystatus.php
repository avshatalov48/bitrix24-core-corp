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
 * Class DeliveryStatus
 * @package Bitrix\Crm\Order
 */
class DeliveryStatus extends Sale\DeliveryStatus
{
	use StatusTrait;

	const NAME = 'ORDER_SHIPMENT_STATUS';

	/**
	 * @return string
	 */
	public static function getFinalUnsuccessfulStatus()
	{
		return 'DD';
	}

	/**
	 * @return array
	 */
	public static function getDefaultStatuses()
	{
		return [
			'DN' => [
				'NAME' => GetMessage('CRM_ORDER_SHIPMENT_STATUS_DN'),
				'STATUS_ID' => 'DN',
				'SORT' => 300,
				'SYSTEM' => 'Y'
			],
			'DF' => [
				'NAME' => GetMessage('CRM_ORDER_SHIPMENT_STATUS_DF'),
				'STATUS_ID' => 'DF',
				'SORT' => 400,
				'SYSTEM' => 'Y'
			],
			'DD' => [
				'NAME' => GetMessage('CRM_ORDER_SHIPMENT_STATUS_DD'),
				'STATUS_ID' => 'DD',
				'SORT' => 500,
				'SYSTEM' => 'Y'
			]
		];
	}
}

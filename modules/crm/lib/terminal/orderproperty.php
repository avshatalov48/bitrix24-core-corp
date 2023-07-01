<?php

namespace Bitrix\Crm\Terminal;

use Bitrix\Main;
use Bitrix\Crm;

class OrderProperty
{
	public const TERMINAL_PHONE = 'TERMINAL_PHONE';

	public static function getTerminalProperty(): array
	{
		return [
			'TYPE' => 'STRING',
			'NAME' => Main\Localization\Loc::getMessage('CRM_TERMINAL_ORDER_PROPERTY_PHONE'),
			'CODE' => self::TERMINAL_PHONE,
		];
	}

	public static function getTerminalPhoneValue(Crm\Order\Order $order): ?string
	{
		$collection = $order->getPropertyCollection();
		$propertyValues = $collection->getItemsByOrderPropertyCode(self::TERMINAL_PHONE);

		$propertyValues = array_filter(
			$propertyValues,
			static function($propertyValue)
			{
				return empty($propertyValue->getField('ORDER_PROPS_ID'));
			}
		);

		return empty($propertyValues) ? null : current($propertyValues)->getValue();
	}
}

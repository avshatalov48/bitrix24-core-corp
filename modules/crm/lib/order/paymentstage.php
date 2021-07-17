<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main\Localization;

Localization\Loc::loadMessages(__FILE__);

/**
 * Class PaymentStage contains available payment stages
 * @package Bitrix\Crm\Order
 */
final class PaymentStage
{
	/**
	 * Initial payment stage
	 */
	public const NOT_PAID = 'NOT_PAID';

	/**
	 * Payment sent to customer, but no viewed yet
	 */
	public const SENT_NO_VIEWED = 'SENT_NO_VIEWED';

	/**
	 * Customer receive payment, but not pay yet
	 */
	public const VIEWED_NO_PAID = 'VIEWED_NO_PAID';

	/**
	 * Payment successfully paid
	 */
	public const PAID = 'PAID';

	/**
	 * Payment was canceled
	 */
	public const CANCEL = 'CANCEL';

	/**
	 * Payment was refunded
	 */
	public const REFUND = 'REFUND';

	/**
	 * Returns stages with descriptions
	 * @return array<string, string> stage code => public description
	 */
	public static function getList(): array
	{
		$result = [];

		$reflection = new \ReflectionClass(static::class);

		$constantList = $reflection->getConstants();
		foreach ($constantList as $name => $value)
		{
			$result[$value] = Localization\Loc::getMessage('CRM_PAYMENT_STAGE_'.$name);
		}

		return $result;
	}

	/**
	 * Returns available stage codes
	 * @return string[]
	 */
	public static function getValues(): array
	{
		$result = [];

		$reflection = new \ReflectionClass(static::class);

		$constantList = $reflection->getConstants();
		foreach ($constantList as $name => $value)
		{
			$result[] = $value;
		}

		return $result;
	}
}

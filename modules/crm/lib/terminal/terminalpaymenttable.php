<?php

namespace Bitrix\Crm\Terminal;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class TerminalPaymentTable
 *
 * Fields:
 * <ul>
 * <li> PAYMENT_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Crm\Terminal
 */
class TerminalPaymentTable extends DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_crm_terminal_payment';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return [
			(new IntegerField('PAYMENT_ID'))
				->configurePrimary(),
		];
	}
}

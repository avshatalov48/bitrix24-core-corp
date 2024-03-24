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
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TerminalPayment_Query query()
 * @method static EO_TerminalPayment_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TerminalPayment_Result getById($id)
 * @method static EO_TerminalPayment_Result getList(array $parameters = [])
 * @method static EO_TerminalPayment_Entity getEntity()
 * @method static \Bitrix\Crm\Terminal\EO_TerminalPayment createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Terminal\EO_TerminalPayment_Collection createCollection()
 * @method static \Bitrix\Crm\Terminal\EO_TerminalPayment wakeUpObject($row)
 * @method static \Bitrix\Crm\Terminal\EO_TerminalPayment_Collection wakeUpCollection($rows)
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

<?php

namespace Bitrix\Crm\Invoice\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class TaxTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Tax_Query query()
 * @method static EO_Tax_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Tax_Result getById($id)
 * @method static EO_Tax_Result getList(array $parameters = [])
 * @method static EO_Tax_Entity getEntity()
 * @method static \Bitrix\Crm\Invoice\Internals\EO_Tax createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Invoice\Internals\EO_Tax_Collection createCollection()
 * @method static \Bitrix\Crm\Invoice\Internals\EO_Tax wakeUpObject($row)
 * @method static \Bitrix\Crm\Invoice\Internals\EO_Tax_Collection wakeUpCollection($rows)
 */
class TaxTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_invoice_tax';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_ID_FIELD'),
			),
			'ORDER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_ORDER_ID_FIELD'),
			),
			'TAX_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTaxName'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_STATUS_CODE_FIELD'),
			),
			'VALUE' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_SUM_FIELD'),
			),
			'VALUE_MONEY' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_SUM_FIELD'),
			),
			'APPLY_ORDER' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_RESPONSIBLE_ID_FIELD')
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_STATUS_CODE_FIELD'),
			),

			new Main\Entity\BooleanField(
				'IS_PERCENT', array('values' => array('N', 'Y'))
			),

			new Main\Entity\BooleanField(
				'IS_IN_PRICE', array('values' => array('N', 'Y'))
			),
		);
	}

	/**
	 * Returns validators for PAID field.
	 *
	 * @return array
	 */
	public static function validateTaxName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for PS_STATUS field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
}

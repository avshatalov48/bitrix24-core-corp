<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class PaymentTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Payment_Query query()
 * @method static EO_Payment_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Payment_Result getById($id)
 * @method static EO_Payment_Result getList(array $parameters = [])
 * @method static EO_Payment_Entity getEntity()
 * @method static \Bitrix\Sale\Internals\EO_Payment createObject($setDefaultValues = true)
 * @method static \Bitrix\Sale\Internals\EO_Payment_Collection createCollection()
 * @method static \Bitrix\Sale\Internals\EO_Payment wakeUpObject($row)
 * @method static \Bitrix\Sale\Internals\EO_Payment_Collection wakeUpCollection($rows)
 */
class PaymentTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_order_payment';
	}

	public static function getUfId()
	{
		return 'PAYMENT';
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
			new Main\Entity\StringField(
					'ACCOUNT_NUMBER',
					array(
							'size' => 100
					)
			),
			'ORDER' => array(
				'data_type' => 'Order',
				'reference' => array(
					'=this.ORDER_ID' => 'ref.ID'
				)
			),

			new Main\Entity\BooleanField(
				'PAID',
				array(
					'values' => array('N','Y'),
					'default_value' => 'N'
				)
			),
			'DATE_PAID' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_DATE_PAID_FIELD'),
			),
			'EMP_PAID_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_EMP_PAID_ID_FIELD'),
			),
			'EMP_PAID_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
							'=this.EMP_PAID_ID' => 'ref.ID'
				)
			),
			'PAY_SYSTEM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PAY_SYSTEM_ID_FIELD'),
			),
			'PAY_SYSTEM' => array(
				'data_type' => 'Bitrix\Sale\Internals\PaySystemAction',
				'reference' => array(
					'=this.PAY_SYSTEM_ID' => 'ref.ID'
				)
			),
			'PS_STATUS' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'validation' => array(__CLASS__, 'validatePsStatus'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_STATUS_FIELD'),
			),
			'PS_STATUS_CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePsStatusCode'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_STATUS_CODE_FIELD'),
			),
			'PS_INVOICE_ID' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_INVOICE_ID_FIELD'),
			),
			'PS_STATUS_DESCRIPTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePsStatusDescription'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_STATUS_DESCRIPTION_FIELD'),
			),
			'PS_STATUS_MESSAGE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePsStatusMessage'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_STATUS_MESSAGE_FIELD'),
			),
			'PS_SUM' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_SUM_FIELD'),
			),
			'PS_CURRENCY' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePsCurrency'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_CURRENCY_FIELD'),
			),
			'PS_RESPONSE_DATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_RESPONSE_DATE_FIELD'),
			),
			'PS_RECURRING_TOKEN' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePsRecurringToken'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_RECURRING_TOKEN_FIELD'),
			),
			'PS_CARD_NUMBER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePsCardNumber'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PS_CARD_NUMBER_FIELD'),
			),
			'PAY_VOUCHER_NUM' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePayVoucherNum'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PAY_VOUCHER_NUM_FIELD'),
			),
			'PAY_VOUCHER_DATE' => array(
				'data_type' => 'date',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PAY_VOUCHER_DATE_FIELD'),
			),
			'DATE_PAY_BEFORE' => array(
				'data_type' => 'date',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_DATE_PAY_BEFORE_FIELD'),
			),
			'DATE_BILL' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_DATE_BILL_FIELD'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_XML_ID_FIELD'),
			),
			new Main\Entity\FloatField(
				'SUM',
				array(
					'default_value' => '0.0000',
					'required' => true,
				)
			),
			'PRICE_COD' => array(
				'data_type' => 'float',
				'required' => false,
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PRICE_COD_FIELD'),
			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_CURRENCY_FIELD'),
			),
			'PAY_SYSTEM_NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validatePaySystemName'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PAY_SYSTEM_NAME_FIELD'),
			),
			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_RESPONSIBLE_ID_FIELD')
			),
			'RESPONSIBLE_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
							'=this.RESPONSIBLE_ID' => 'ref.ID'
				)
			),
			'EMP_RESPONSIBLE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_EMP_RESPONSIBLE_ID_FIELD')
			),
			'EMP_RESPONSIBLE_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_RESPONSIBLE_ID' => 'ref.ID'
				)
			),
			'DATE_RESPONSIBLE_ID' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_DATE_RESPONSIBLE_ID_FIELD')
			),
			'COMMENTS' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_COMMENTS_FIELD')
			),
			'COMPANY_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_COMPANY_ID_FIELD')
			),
			'COMPANY_BY' => array(
				'data_type' => 'Bitrix\Sale\Internals\Company',
				'reference' => array(
					'=this.COMPANY_ID' => 'ref.ID'
				)
			),
			'PAY_RETURN_NUM' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePayVoucherNum'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PAY_RETURN_NUM_FIELD'),
			),
			'PAY_RETURN_DATE' => array(
				'data_type' => 'date',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PAY_RETURN_DATE_FIELD'),
			),
			new Main\Entity\IntegerField('EMP_RETURN_ID'),

			new Main\Entity\ReferenceField(
				'EMP_RETURN_BY',
				'\Bitrix\Main\User',
				array('=this.USER_ID' => 'ref.EMP_RETURN_ID'),
				array('join_type' => 'INNER')
			),

			'PAY_RETURN_COMMENT' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_PAY_RETURN_COMMENT_FIELD'),
			),
			new Main\Entity\EnumField(
				'IS_RETURN',
				array(
					'values' => array('N','Y','P'),
					'default_value' => 'N'
				)
			),

			new Main\Entity\BooleanField(
				'MARKED',
				array(
					'values' => array('N','Y'),
					'default_value' => 'N'
				)
			),

			new Main\Entity\DatetimeField('DATE_MARKED'),

			new Main\Entity\IntegerField('EMP_MARKED_ID'),

			new Main\Entity\ReferenceField(
				'EMP_MARKED_BY',
				'\Bitrix\Main\User',
				array('=this.EMP_MARKED_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),

			'REASON_MARKED' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateReasonMarked'),
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_REASON_MARKED_FIELD'),
			),

			new Main\Entity\BooleanField(
				'UPDATED_1C',
				array(
					'values' => array('N', 'Y')
				)
			),

			new Main\Entity\StringField('ID_1C'),

			new Main\Entity\StringField('VERSION_1C'),

			new Main\Entity\EnumField(
				'EXTERNAL_PAYMENT',
				array(
					'values' => array('N', 'Y', 'F')
				)
			),
		);
	}

	/**
	 * Returns validators for PAID field.
	 *
	 * @return array
	 */
	public static function validatePaid()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for PS_STATUS field.
	 *
	 * @return array
	 */
	public static function validatePsStatus()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for PS_STATUS_CODE field.
	 *
	 * @return array
	 */
	public static function validatePsStatusCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for PS_STATUS_DESCRIPTION field.
	 *
	 * @return array
	 */
	public static function validatePsStatusDescription()
	{
		return array(
			new Main\Entity\Validator\Length(null, 512),
		);
	}
	/**
	 * Returns validators for PS_STATUS_MESSAGE field.
	 *
	 * @return array
	 */
	public static function validatePsStatusMessage()
	{
		return array(
			new Main\Entity\Validator\Length(null, 250),
		);
	}
	/**
	 * Returns validators for PS_CURRENCY field.
	 *
	 * @return array
	 */
	public static function validatePsCurrency()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}
	/**
	 * Returns validators for PAY_VOUCHER_NUM field.
	 *
	 * @return array
	 */
	public static function validatePayVoucherNum()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20),
		);
	}
	/**
	 * Returns validators for PAY_RETURN_NUM field.
	 *
	 * @return array
	 */
	public static function validatePayReturnNum()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20),
		);
	}
	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for CURRENCY field.
	 *
	 * @return array
	 */
	public static function validateCurrency()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}
	/**
	 * Returns validators for PAY_SYSTEM_NAME field.
	 *
	 * @return array
	 */
	public static function validatePaySystemName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 128),
		);
	}
	/**
	 * Returns validators for REASON_MARKED field.
	 *
	 * @return array
	 */
	public static function validateReasonMarked()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for PS_RECURRING_TOKEN field.
	 *
	 * @return array
	 */
	public static function validatePsRecurringToken()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	
	/**
	 * Returns validators for PS_CARD_NUMBER field.
	 *
	 * @return array
	 */
	public static function validatePsCardNumber()
	{
		return array(
			new Main\Entity\Validator\Length(null, 64),
		);
	}
}

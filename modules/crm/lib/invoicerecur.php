<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2013-2013 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main,
	Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Crm\Recurring,
	Bitrix\Crm\Activity\Provider,
	Bitrix\Main\Entity\Field;

Loc::loadMessages(__FILE__);

/**
 * Class InvoiceRecurTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_InvoiceRecur_Query query()
 * @method static EO_InvoiceRecur_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_InvoiceRecur_Result getById($id)
 * @method static EO_InvoiceRecur_Result getList(array $parameters = [])
 * @method static EO_InvoiceRecur_Entity getEntity()
 * @method static \Bitrix\Crm\EO_InvoiceRecur createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_InvoiceRecur_Collection createCollection()
 * @method static \Bitrix\Crm\EO_InvoiceRecur wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_InvoiceRecur_Collection wakeUpCollection($rows)
 */
class InvoiceRecurTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_invoice_recur';
	}

	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField(
				'ID',
				array(
					'autocomplete' => true,
					'primary' => true,
				)
			),
			new Main\Entity\IntegerField(
				'INVOICE_ID',
				array(
					'required' => true
				)
			),
			new Main\Entity\BooleanField(
				'ACTIVE',
				array(
					'values' => array('N', 'Y'),
					'default_value' => 'N'
				)
			),
			/**
			 * value 'N' isn't limit;
			 * value 'D' is limit by date;
			 * value 'T' is limit by times;
			*/
			new Main\Entity\StringField(
				'IS_LIMIT',
				array(
					'values' => array('N', 'D', 'T'),
					'default_value' => 'N'
				)
			),
			new Main\Entity\BooleanField(
				'SEND_BILL',
				array(
					'values' => array('N', 'Y'),
					'default_value' => 'N'
				)
			),
			new Main\Entity\IntegerField('EMAIL_ID'),
			new Main\Entity\IntegerField('COUNTER_REPEAT'),
			new Main\Entity\IntegerField('LIMIT_REPEAT'),
			new Main\Entity\DateField('LIMIT_DATE'),
			new Main\Entity\DateField('START_DATE'),

			new Main\Entity\DateField('NEXT_EXECUTION'),
			new Main\Entity\DateField('LAST_EXECUTION'),

			new Main\Entity\StringField(
				'PARAMS',
				array(
					'serialized' => 'Y'
				)
			)
		);
	}

	/**
	 * @return array
	 */
	public static function getFieldNames()
	{
		$recurringFields = array();		
		$map = static::getMap();

		/** @var  Field $entity */
		foreach ($map as $entity)
		{
			$recurringFields[] = $entity->getName();
		}
		
		return $recurringFields;
	}
	
	/**
	 * @param mixed $primary
	 *
	 * @return Entity\DeleteResult
	 * @throws \Exception
	 */
	public static function delete($primary)
	{
		$primary = (int)$primary;
		$data = static::getById($primary)->fetch();
		if ((int)$data['INVOICE_ID'])
		{
			$invoice = \CCrmInvoice::GetByID((int)$data['INVOICE_ID']);
			if ($invoice)
			{
				throw new Main\InvalidOperationException('Deleting is impossible. Connected recurring invoice exists.');
			}
		}
		return parent::delete($primary);
	}

	public static function getFieldCaption($fieldName)
	{
		$result = Loc::getMessage("CRM_INVOICE_RECURRING_ENTITY_{$fieldName}_FIELD");
		return is_string($result) ? $result : '';
	}
}

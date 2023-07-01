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
	Bitrix\Crm\DealTable,
	Bitrix\Crm\Activity\Provider,
	Bitrix\Main\Entity\Field;

Loc::loadMessages(__FILE__);

/**
 * Class DealRecurTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DealRecur_Query query()
 * @method static EO_DealRecur_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DealRecur_Result getById($id)
 * @method static EO_DealRecur_Result getList(array $parameters = [])
 * @method static EO_DealRecur_Entity getEntity()
 * @method static \Bitrix\Crm\EO_DealRecur createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_DealRecur_Collection createCollection()
 * @method static \Bitrix\Crm\EO_DealRecur wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_DealRecur_Collection wakeUpCollection($rows)
 */
class DealRecurTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_deal_recur';
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
				'DEAL_ID',
				array(
					'required' => true
				)
			),
			new Main\Entity\IntegerField('BASED_ID'),
			new Main\Entity\BooleanField(
				'ACTIVE',
				array(
					'values' => array('N', 'Y'),
					'default_value' => 'N'
				)
			),
			new Main\Entity\IntegerField('CATEGORY_ID'),
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
		if ((int)$data['DEAL_ID'])
		{
			$deal = DealTable::getById((int)$data['DEAL_ID']);
			if ($deal->fetch())
			{
				throw new Main\InvalidOperationException('Deleting is impossible. Connected recurring deal exists.');
			}
		}
		return parent::delete($primary);
	}

	public static function getFieldCaption($fieldName)
	{
		$result = Loc::getMessage("CRM_DEAL_RECURRING_ENTITY_{$fieldName}_FIELD");
		return is_string($result) ? $result : '';
	}
}

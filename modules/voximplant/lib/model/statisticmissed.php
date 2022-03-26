<?php
namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class StatisticMissedTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CALL_START_DATE datetime mandatory
 * <li> PHONE_NUMBER string(20) mandatory
 * <li> PORTAL_USER_ID int optional
 * <li> CALLBACK_ID int optional
 * <li> CALLBACK_CALL_START_DATE datetime optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_StatisticMissed_Query query()
 * @method static EO_StatisticMissed_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_StatisticMissed_Result getById($id)
 * @method static EO_StatisticMissed_Result getList(array $parameters = array())
 * @method static EO_StatisticMissed_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_StatisticMissed createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_StatisticMissed_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_StatisticMissed wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_StatisticMissed_Collection wakeUpCollection($rows)
 */

class StatisticMissedTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_voximplant_statistic_missed';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'title' => Loc::getMessage('STATISTIC_MISSED_ENTITY_ID_FIELD')
				]
			),
			new DatetimeField(
				'CALL_START_DATE',
				[
					'required' => true,
					'title' => Loc::getMessage('STATISTIC_MISSED_ENTITY_CALL_START_DATE_FIELD')
				]
			),
			new StringField(
				'PHONE_NUMBER',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validatePhoneNumber'],
					'title' => Loc::getMessage('STATISTIC_MISSED_ENTITY_PHONE_NUMBER_FIELD')
				]
			),
			new IntegerField(
				'PORTAL_USER_ID',
				[
					'title' => Loc::getMessage('STATISTIC_MISSED_ENTITY_PORTAL_USER_ID_FIELD')
				]
			),
			new IntegerField(
				'CALLBACK_ID',
				[
					'title' => Loc::getMessage('STATISTIC_MISSED_ENTITY_CALLBACK_ID_FIELD')
				]
			),
			new DatetimeField(
				'CALLBACK_CALL_START_DATE',
				[
					'title' => Loc::getMessage('STATISTIC_MISSED_ENTITY_CALLBACK_CALL_START_DATE_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for PHONE_NUMBER field.
	 *
	 * @return array
	 */
	public static function validatePhoneNumber()
	{
		return [
			new LengthValidator(null, 20),
		];
	}
}
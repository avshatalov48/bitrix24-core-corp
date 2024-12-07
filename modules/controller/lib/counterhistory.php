<?php
namespace Bitrix\Controller;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

Loc::loadMessages(__FILE__);

/**
 * Class CounterHistoryTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> COUNTER_ID int optional
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> USER_ID int optional
 * <li> NAME string(255) mandatory
 * <li> COMMAND_FROM string mandatory
 * <li> COMMAND_TO string mandatory
 * <li> USER reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Controller
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CounterHistory_Query query()
 * @method static EO_CounterHistory_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_CounterHistory_Result getById($id)
 * @method static EO_CounterHistory_Result getList(array $parameters = array())
 * @method static EO_CounterHistory_Entity getEntity()
 * @method static \Bitrix\Controller\EO_CounterHistory createObject($setDefaultValues = true)
 * @method static \Bitrix\Controller\EO_CounterHistory_Collection createCollection()
 * @method static \Bitrix\Controller\EO_CounterHistory wakeUpObject($row)
 * @method static \Bitrix\Controller\EO_CounterHistory_Collection wakeUpCollection($rows)
 */

class CounterHistoryTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_counter_history';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Fields\IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_ID_FIELD'),
				]
			),
			new Fields\IntegerField(
				'COUNTER_ID',
				[
					'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_COUNTER_ID_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'TIMESTAMP_X',
				[
					'required' => true,
					'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_TIMESTAMP_X_FIELD'),
				]
			),
			new Fields\IntegerField(
				'USER_ID',
				[
					'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_USER_ID_FIELD'),
				]
			),
			new Fields\StringField(
				'NAME',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateName'],
					'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_NAME_FIELD'),
				]
			),
			new Fields\TextField(
				'COMMAND_FROM',
				[
					'required' => true,
					'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_COMMAND_FROM_FIELD'),
				]
			),
			new Fields\TextField(
				'COMMAND_TO',
				[
					'required' => true,
					'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_COMMAND_TO_FIELD'),
				]
			),
			new Fields\Relations\Reference(
				'USER',
				'Bitrix\Main\UserTable',
				['=this.USER_ID' => 'ref.ID']
			),
		];
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return [
			new Fields\Validators\LengthValidator(null, 255),
		];
	}
}

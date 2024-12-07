<?php

namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Type\DateTime;

/**
 * Class CounterTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> MUTE_STATUS int optional default 0
 * <li> MUTE_UNTIL datetime optional default current datetime
 * </ul>
 *
 * @package Bitrix\Stafftrack
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Counter_Query query()
 * @method static EO_Counter_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Counter_Result getById($id)
 * @method static EO_Counter_Result getList(array $parameters = [])
 * @method static EO_Counter_Entity getEntity()
 * @method static \Bitrix\StaffTrack\Model\Counter createObject($setDefaultValues = true)
 * @method static \Bitrix\StaffTrack\Model\EO_Counter_Collection createCollection()
 * @method static \Bitrix\StaffTrack\Model\Counter wakeUpObject($row)
 * @method static \Bitrix\StaffTrack\Model\EO_Counter_Collection wakeUpCollection($rows)
 */
class CounterTable extends DataManager
{
	use MergeTrait;

	/**
	 * @return string
	 */
	public static function getObjectClass()
	{
		return Counter::class;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_stafftrack_counter';
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
					'autocomplete' => true,
					'title' => Loc::getMessage('COUNTER_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('COUNTER_ENTITY_USER_ID_FIELD'),
				]
			),
			new IntegerField(
				'MUTE_STATUS',
				[
					'default' => 0,
					'title' => Loc::getMessage('COUNTER_ENTITY_MUTE_STATUS_FIELD'),
				]
			),
			new DatetimeField(
				'MUTE_UNTIL',
				[
					'default' => function ()
					{
						return new DateTime();
					},
					'title' => Loc::getMessage('COUNTER_ENTITY_MUTE_UNTIL_FIELD'),
				]
			),
		];
	}
}

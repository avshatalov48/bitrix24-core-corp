<?php

namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Type\DateTime;

/**
 * Class ShiftCancellationTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SHIFT_ID int mandatory
 * <li> REASON text mandatory
 * <li> DATE_CANCEL datetime optional default current datetime
 * </ul>
 *
 * @package Bitrix\Stafftrack
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ShiftCancellation_Query query()
 * @method static EO_ShiftCancellation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ShiftCancellation_Result getById($id)
 * @method static EO_ShiftCancellation_Result getList(array $parameters = [])
 * @method static EO_ShiftCancellation_Entity getEntity()
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftCancellation createObject($setDefaultValues = true)
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection createCollection()
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftCancellation wakeUpObject($row)
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftCancellation_Collection wakeUpCollection($rows)
 */
class ShiftCancellationTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_stafftrack_shift_cancellation';
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
					'title' => Loc::getMessage('SHIFT_CANCELLATION_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'SHIFT_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_CANCELLATION_ENTITY_SHIFT_ID_FIELD'),
				]
			),
			new TextField(
				'REASON',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_CANCELLATION_ENTITY_REASON_FIELD'),
				]
			),
			new DatetimeField(
				'DATE_CANCEL',
				[
					'default' => function ()
					{
						return new DateTime();
					},
					'title' => Loc::getMessage('SHIFT_CANCELLATION_ENTITY_DATE_CANCEL_FIELD'),
				]
			),
		];
	}
}
<?php

namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class ShiftGeoTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SHIFT_ID int mandatory
 * <li> IMAGE_URL text mandatory
 * <li> ADDRESS text mandatory
 * </ul>
 *
 * @package Bitrix\Stafftrack
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ShiftGeo_Query query()
 * @method static EO_ShiftGeo_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ShiftGeo_Result getById($id)
 * @method static EO_ShiftGeo_Result getList(array $parameters = [])
 * @method static EO_ShiftGeo_Entity getEntity()
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftGeo createObject($setDefaultValues = true)
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection createCollection()
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftGeo wakeUpObject($row)
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftGeo_Collection wakeUpCollection($rows)
 */

class ShiftGeoTable extends DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_stafftrack_shift_geo';
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
					'title' => Loc::getMessage('SHIFT_GEO_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'SHIFT_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_GEO_ENTITY_SHIFT_ID_FIELD'),
				]
			),
			new TextField(
				'IMAGE_URL',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_GEO_ENTITY_IMAGE_URL_FIELD'),
				]
			),
			new TextField(
				'ADDRESS',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_GEO_ENTITY_ADDRESS_FIELD'),
				]
			),
		];
	}
}

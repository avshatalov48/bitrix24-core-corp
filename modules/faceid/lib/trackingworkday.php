<?php

namespace Bitrix\Faceid;

use Bitrix\Main;

/**
 * Class TrackingWorkdayTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TrackingWorkday_Query query()
 * @method static EO_TrackingWorkday_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_TrackingWorkday_Result getById($id)
 * @method static EO_TrackingWorkday_Result getList(array $parameters = array())
 * @method static EO_TrackingWorkday_Entity getEntity()
 * @method static \Bitrix\Faceid\EO_TrackingWorkday createObject($setDefaultValues = true)
 * @method static \Bitrix\Faceid\EO_TrackingWorkday_Collection createCollection()
 * @method static \Bitrix\Faceid\EO_TrackingWorkday wakeUpObject($row)
 * @method static \Bitrix\Faceid\EO_TrackingWorkday_Collection wakeUpCollection($rows)
 */
class TrackingWorkdayTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_faceid_tracking_workday';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new Main\Entity\IntegerField('USER_ID', array(
				'required' => true
			)),
			new Main\Entity\DatetimeField('DATE', array(
				'required' => true,
				'default_value' => function() {
					return new Main\Type\DateTime;
				}
			)),
			new Main\Entity\EnumField('ACTION', array(
				'required' => true,
				'values' => array('START', 'PAUSE', 'STOP')
			)),
			new Main\Entity\IntegerField('SNAPSHOT_ID')
		);
	}
}
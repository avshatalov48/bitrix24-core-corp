<?php

namespace Bitrix\Faceid;

use Bitrix\Main;

/**
 * Class TrackingWorkdayTable
 **/
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
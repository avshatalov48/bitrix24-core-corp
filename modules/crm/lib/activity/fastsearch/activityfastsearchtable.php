<?php

namespace Bitrix\Crm\Activity\FastSearch;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields;

/**
 * Class ActivityFastSearchTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ActivityFastSearch_Query query()
 * @method static EO_ActivityFastSearch_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ActivityFastSearch_Result getById($id)
 * @method static EO_ActivityFastSearch_Result getList(array $parameters = [])
 * @method static EO_ActivityFastSearch_Entity getEntity()
 * @method static \Bitrix\Crm\Activity\FastSearch\EO_ActivityFastSearch createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Activity\FastSearch\EO_ActivityFastSearch_Collection createCollection()
 * @method static \Bitrix\Crm\Activity\FastSearch\EO_ActivityFastSearch wakeUpObject($row)
 * @method static \Bitrix\Crm\Activity\FastSearch\EO_ActivityFastSearch_Collection wakeUpCollection($rows)
 */
class ActivityFastSearchTable extends Entity\DataManager
{
	// Maximum number of days to sync
	public const CREATED_THRESHOLD_DAYS = 365;

	/**
	 * @inheritdoc
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_act_fastsearch';
	}

	/**
	 * @inheritdoc
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new Fields\IntegerField('ACTIVITY_ID'))
				->configurePrimary(),
			(new Fields\DatetimeField('CREATED'))
				->configureRequired(),
			(new Fields\DatetimeField('DEADLINE'))
				->configureRequired(),
			(new Fields\IntegerField('RESPONSIBLE_ID'))
				->configureRequired(),
			(new Fields\BooleanField('COMPLETED'))
				->configureStorageValues('N', 'Y')
				->configureRequired(),
			(new Fields\StringField('ACTIVITY_TYPE'))
				->configureRequired(),
			(new Fields\IntegerField('ACTIVITY_KIND'))
				->configureRequired(),
			(new Fields\IntegerField('AUTHOR_ID')),
		];
	}
}
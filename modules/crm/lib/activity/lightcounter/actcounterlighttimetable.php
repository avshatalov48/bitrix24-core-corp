<?php

namespace Bitrix\Crm\Activity\LightCounter;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class ActCounterLightTimeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ActCounterLightTime_Query query()
 * @method static EO_ActCounterLightTime_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ActCounterLightTime_Result getById($id)
 * @method static EO_ActCounterLightTime_Result getList(array $parameters = [])
 * @method static EO_ActCounterLightTime_Entity getEntity()
 * @method static \Bitrix\Crm\Activity\LightCounter\EO_ActCounterLightTime createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Activity\LightCounter\EO_ActCounterLightTime_Collection createCollection()
 * @method static \Bitrix\Crm\Activity\LightCounter\EO_ActCounterLightTime wakeUpObject($row)
 * @method static \Bitrix\Crm\Activity\LightCounter\EO_ActCounterLightTime_Collection wakeUpCollection($rows)
 */
class ActCounterLightTimeTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act_counter_light';
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ACTIVITY_ID'))
				->configurePrimary(),
			(new DatetimeField('LIGHT_COUNTER_AT'))
				->configureRequired(),
			(new BooleanField('IS_LIGHT_COUNTER_NOTIFIED'))
				->configureStorageValues('N', 'Y')
				->configureRequired(),
		];
	}

	public static function deleteByIds(array $ids): void
	{
		$ids = array_filter($ids, 'is_numeric');

		if (empty($ids))
		{
			return;
		}

		$ids = array_map(fn($val) => (int)$val, $ids);
		$sql = 'delete from b_crm_act_counter_light where ACTIVITY_ID in ('. implode(',', $ids) . ')';

		Application::getConnection()->query($sql);
		self::cleanCache();
	}

}

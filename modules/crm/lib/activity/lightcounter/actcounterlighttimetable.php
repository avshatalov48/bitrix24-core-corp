<?php

namespace Bitrix\Crm\Activity\LightCounter;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;

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

}

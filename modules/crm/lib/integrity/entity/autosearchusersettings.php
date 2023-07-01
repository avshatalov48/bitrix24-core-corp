<?php

namespace Bitrix\Crm\Integrity\Entity;

use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class AutosearchUserSettingsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AutosearchUserSettings_Query query()
 * @method static EO_AutosearchUserSettings_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_AutosearchUserSettings_Result getById($id)
 * @method static EO_AutosearchUserSettings_Result getList(array $parameters = [])
 * @method static EO_AutosearchUserSettings_Entity getEntity()
 * @method static \Bitrix\Crm\Integrity\AutoSearchUserSettings createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_AutosearchUserSettings_Collection createCollection()
 * @method static \Bitrix\Crm\Integrity\AutoSearchUserSettings wakeUpObject($row)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_AutosearchUserSettings_Collection wakeUpCollection($rows)
 */
class AutosearchUserSettingsTable extends DataManager
{

	public static function getObjectClass()
	{
		return \Bitrix\Crm\Integrity\AutoSearchUserSettings::class;
	}

	public static function getTableName()
	{
		return 'b_crm_dp_autosearch_user_settings';
	}

	public static function getMap()
	{
		return [
			new IntegerField(
				'USER_ID',
				[
					'primary' => true
				]
			),
			new IntegerField(
				'ENTITY_TYPE_ID',
				[
					'primary' => true
				]
			),
			new IntegerField(
				'STATUS_ID',
				[
					'required' => true,
					'default' => 0
				]
			),
			new IntegerField(
				'EXEC_INTERVAL',
				[
					'required' => true
				]
			),
			new DatetimeField(
				'LAST_EXEC_TIME'
			),
			new DatetimeField(
				'NEXT_EXEC_TIME'
			),
			new ArrayField(
				'PROGRESS_DATA'
			),
			new BooleanField(
				'IS_MERGE_ENABLED',
				[
					'values' => ['N', 'Y'],
					'default_value' => 'N'
				]
			),
			new BooleanField(
				'CHECK_CHANGED_ONLY',
				[
					'values' => ['N', 'Y'],
					'default_value' => 'N'
				]
			),
			new StringField(
				'MERGE_ID',
				[
					'size' => 8
				]
			),
			new DatetimeField(
				'MERGE_ACTIVITY_DATE'
			),
		];
	}
}
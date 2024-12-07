<?php

namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class UserStatisticsHashTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UserStatisticsHash_Query query()
 * @method static EO_UserStatisticsHash_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_UserStatisticsHash_Result getById($id)
 * @method static EO_UserStatisticsHash_Result getList(array $parameters = [])
 * @method static EO_UserStatisticsHash_Entity getEntity()
 * @method static \Bitrix\StaffTrack\Model\EO_UserStatisticsHash createObject($setDefaultValues = true)
 * @method static \Bitrix\StaffTrack\Model\EO_UserStatisticsHash_Collection createCollection()
 * @method static \Bitrix\StaffTrack\Model\EO_UserStatisticsHash wakeUpObject($row)
 * @method static \Bitrix\StaffTrack\Model\EO_UserStatisticsHash_Collection wakeUpCollection($rows)
 */
class UserStatisticsHashTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_stafftrack_user_statistics_hash';
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('USER_ID'))
				->configureRequired()
			,
			(new StringField('HASH'))
				->configureRequired()
				->addValidator(new LengthValidator(null, 64))
			,
		];
	}
}
<?php

namespace Bitrix\Sign\Internal\ServiceUser;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class ServiceUserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ServiceUser_Query query()
 * @method static EO_ServiceUser_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ServiceUser_Result getById($id)
 * @method static EO_ServiceUser_Result getList(array $parameters = [])
 * @method static EO_ServiceUser_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\ServiceUser\ServiceUser createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\ServiceUser\ServiceUserCollection createCollection()
 * @method static \Bitrix\Sign\Internal\ServiceUser\ServiceUser wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\ServiceUser\ServiceUserCollection wakeUpCollection($rows)
 */
class ServiceUserTable extends Entity\DataManager
{
	public static function getTableName(): string
	{
		return 'b_sign_service_user';
	}

	public static function getObjectClass(): string
	{
		return ServiceUser::class;
	}

	public static function getCollectionClass(): string
	{
		return ServiceUserCollection::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('USER_ID'))
				->configureRequired()
				->configurePrimary()
			,
			(new StringField('UID'))
				->configureRequired()
				->addValidator(new Entity\Validator\Length(32, 32))
			,
			(new DatetimeField('DATE_CREATE'))
				->configureRequired()
			,
		];
	}
}

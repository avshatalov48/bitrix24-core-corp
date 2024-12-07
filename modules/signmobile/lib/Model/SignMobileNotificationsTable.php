<?php

namespace Bitrix\SignMobile\Model;

use Bitrix\Main\Entity;
use Bitrix\SignMobile\Type\NotificationType;

/**
 * Class SignMobileNotificationsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SignMobileNotifications_Query query()
 * @method static EO_SignMobileNotifications_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SignMobileNotifications_Result getById($id)
 * @method static EO_SignMobileNotifications_Result getList(array $parameters = [])
 * @method static EO_SignMobileNotifications_Entity getEntity()
 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotifications createObject($setDefaultValues = true)
 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotifications_Collection createCollection()
 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotifications wakeUpObject($row)
 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotifications_Collection wakeUpCollection($rows)
 */
class SignMobileNotificationsTable extends Entity\DataManager
{
	public static function getFilePath(): string
	{
		return __FILE__;
	}

	public static function getTableName(): string
	{
		return 'b_signmobile_notifications';
	}

	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
			],
			'SIGN_MEMBER_ID' => [
				'data_type' => 'integer',
				'required'  => true,
			],
			'TYPE' => [
				'data_type' => 'integer',
				'values' => NotificationType::getAll(),
				'required'  => true,
			],
			'DATE_UPDATE' => [
				'data_type' => 'datetime',
			],
		];
	}
}
<?php

namespace Bitrix\SignMobile\Model;

use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\DB\Result;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\SignMobile\Type\NotificationType;
use Bitrix\Main\Entity\Query;

/**
 * Class SignMobileNotificationQueueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SignMobileNotificationQueue_Query query()
 * @method static EO_SignMobileNotificationQueue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SignMobileNotificationQueue_Result getById($id)
 * @method static EO_SignMobileNotificationQueue_Result getList(array $parameters = [])
 * @method static EO_SignMobileNotificationQueue_Entity getEntity()
 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue createObject($setDefaultValues = true)
 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue_Collection createCollection()
 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue wakeUpObject($row)
 * @method static \Bitrix\SignMobile\Model\EO_SignMobileNotificationQueue_Collection wakeUpCollection($rows)
 */
class SignMobileNotificationQueueTable extends Entity\DataManager
{
	public static function getFilePath(): string
	{
		return __FILE__;
	}

	public static function deleteOlderThan(int $userId, DateTime $dateCreate): Result
	{
		return self::deleteList([
			'=USER_ID' => $userId,
			'<DATE_CREATE' => $dateCreate,
		]);
	}

	public static function deleteBy(int $userId, int $type, int $signMemberId): DeleteResult
	{
		$row = self::getRow(
			[
				'select' => [
					'ID',
				],
				'filter' => [
					'=USER_ID' => $userId,
					'=TYPE' => $type,
					'=SIGN_MEMBER_ID' => $signMemberId,
				],
			]
		);

		if (!is_null($row) && isset($row['ID']))
		{
			return self::delete([
				'ID' => (int)$row['ID'],
			]);
		}

		return (new DeleteResult())->addError(new Error('Row not found', 'ROW_NOT_FOUND'));
	}

	private static function deleteList(array $filter): Result
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();

		return $connection->query(sprintf(
			'DELETE FROM %s WHERE %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			Query::buildFilterSql($entity, $filter)
		));
	}

	public static function getTableName(): string
	{
		return 'b_signmobile_notification_queue';
	}

	public static function add($data)
	{
		try {
			return parent::add($data);
		} catch (\Exception $exception)
		{
			/*
			 	In order not to throw an error in the logs when trying to add an existing notification.
				In our case, trying to add an element again is not an error.
			*/
		}
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
			'DATE_CREATE' => [
				'data_type' => 'datetime',
			],
		];
	}
}
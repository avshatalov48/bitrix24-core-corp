<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\DB\MysqlCommonConnection;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Disk;

/**
 * Class TrackedObjectTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TrackedObject_Query query()
 * @method static EO_TrackedObject_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_TrackedObject_Result getById($id)
 * @method static EO_TrackedObject_Result getList(array $parameters = array())
 * @method static EO_TrackedObject_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_TrackedObject createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_TrackedObject_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_TrackedObject wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_TrackedObject_Collection wakeUpCollection($rows)
 */
final class TrackedObjectTable extends DataManager
{
	private const MAX_COUNT_FOR_USER = 100;

	public static function getTableName()
	{
		return 'b_disk_tracked_object';
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
			new Entity\IntegerField('USER_ID', ['required' => true]),
			new Entity\IntegerField('OBJECT_ID', ['required' => true]),
			new Entity\IntegerField('REAL_OBJECT_ID', ['required' => true]),
			new Entity\IntegerField('ATTACHED_OBJECT_ID', ['required' => false]),
			new Entity\DatetimeField('CREATE_TIME', ['default_value' => function(){return new DateTime();}]),
			new Entity\DatetimeField('UPDATE_TIME', ['default_value' => function(){return new DateTime();}]),
		];
	}

	public static function updateBatch(array $fields, array $filter)
	{
		parent::updateBatch($fields, $filter);
	}

	public static function deleteBatch(array $filter)
	{
		parent::deleteBatch($filter);
	}

	/**
	 * Deletes old objects from recently used log by user.
	 * @param int $userId User id.
	 * @return void
	 */
	public static function deleteOldObjects($userId)
	{
		$offset = self::MAX_COUNT_FOR_USER - 1;
		$connection = Application::getConnection();
		if($connection instanceof MysqlCommonConnection)
		{
			$connection->queryExecute("
				DELETE t
				FROM
					b_disk_tracked_object AS t
				JOIN
				( SELECT ID
					FROM b_disk_tracked_object
					WHERE USER_ID = {$userId}
					ORDER BY ID DESC
					LIMIT 1 OFFSET {$offset}
				) tlimit ON t.ID < tlimit.ID AND t.USER_ID = {$userId}
			");
		}
		else
		{
			$id = static::getList(array(
				'select' => array('ID'),
				'filter' => array(
					'USER_ID' => $userId,
				),
				'order' => array('ID' => 'DESC'),
				'limit' => 1,
				'offset' => $offset,
			))->fetch();
			$id = !empty($id['ID'])? (int)$id['ID'] : null;
			if($id)
			{
				$connection->queryExecute("
					DELETE FROM b_disk_tracked_object WHERE ID < {$id} AND USER_ID = {$userId}
				");
			}
		}
	}
}
<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\DB\MysqlCommonConnection;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class RecentlyUsedTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> CREATE_TIME datetime mandatory
 * </ul>
 *
 * @package Bitrix\Disk\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RecentlyUsed_Query query()
 * @method static EO_RecentlyUsed_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RecentlyUsed_Result getById($id)
 * @method static EO_RecentlyUsed_Result getList(array $parameters = [])
 * @method static EO_RecentlyUsed_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_RecentlyUsed createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_RecentlyUsed wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_RecentlyUsed_Collection wakeUpCollection($rows)
 */

final class RecentlyUsedTable extends DataManager
{
	const MAX_COUNT_FOR_USER = 50;

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_recently_used';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'OBJECT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CREATE_TIME' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function() {
					return new DateTime();
				},
			),
		);
	}

	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter does not look like filter in getList. It depends by current implementation.
	 * @internal
	 * @return void
	 */
	public static function deleteBatch(array $filter)
	{
		parent::deleteBatch($filter);
	}

	/**
	 * Adds rows to table.
	 * @param array $items Items.
	 * @internal
	 * @return void
	 */
	public static function insertBatch(array $items)
	{
		parent::insertBatch($items);
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
				    b_disk_recently_used AS t
			    JOIN
				( SELECT ID
				  FROM b_disk_recently_used
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
					DELETE FROM b_disk_recently_used WHERE ID < {$id} AND USER_ID = {$userId}
				");
			}
		}
	}
}

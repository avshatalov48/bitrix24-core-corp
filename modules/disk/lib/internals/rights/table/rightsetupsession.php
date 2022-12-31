<?php
namespace Bitrix\Disk\Internals\Rights\Table;

use Bitrix\Disk\Internals\DataManager;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

/**
 * Class RightSetupSessionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RightSetupSession_Query query()
 * @method static EO_RightSetupSession_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RightSetupSession_Result getById($id)
 * @method static EO_RightSetupSession_Result getList(array $parameters = [])
 * @method static EO_RightSetupSession_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\Rights\Table\EO_RightSetupSession_Collection wakeUpCollection($rows)
 */
final class RightSetupSessionTable extends DataManager
{
	const STATUS_STARTED      = 2;
	const STATUS_FINISHED     = 3;
	const STATUS_FORKED       = 4;
	const STATUS_BAD          = 5;
	const STATUS_DUPLICATE    = 6;
	const STATUS_BAD_PURIFIED = 7;

	/**
	 * In 5 minutes we decide to restart setup session and try again.
	 */
	const LIFETIME_SECONDS = 300;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_right_setup_session';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$deathTime = $sqlHelper->addSecondsToDateTime(self::LIFETIME_SECONDS, 'CREATE_TIME');
		$now = $sqlHelper->getCurrentDateTimeFunction();

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
			),
			'PARENT' => array(
				'data_type' => 'Bitrix\Disk\Internals\Rights\Table\RightSetupSessionTable',
				'reference' => array(
					'=this.PARENT_ID' => 'ref.ID'
				),
			),
			'OBJECT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'OBJECT' => array(
				'data_type' => 'Bitrix\Disk\Internals\ObjectTable',
				'reference' => array(
					'=this.OBJECT_ID' => 'ref.ID'
				),
				'join_type' => 'INNER',
			),
			'STATUS' => array(
				'data_type' => 'integer',
				'default_value' => self::STATUS_STARTED,
			),
			'CREATE_TIME' => array(
				'data_type' => 'datetime',
				'default_value' => function() {
					return new DateTime();
				},
				'required' => true,
			),
			'IS_EXPIRED' => array(
				'data_type' => 'boolean',
				'expression' => array(
					"CASE WHEN ({$now} > {$deathTime}) THEN 1 ELSE 0 END"
				),
				'values' => array(0, 1),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
			),
		);
	}

	/**
	 * Marks as bad sessions which were calculated without success many times and
	 * deletes tmp simple rights by these sessions.
	 *
	 * @return void
	 */
	public static function markAsBad()
	{
		$badStatus = self::STATUS_BAD;
		$purifiedStatus = self::STATUS_BAD_PURIFIED;
		$startedStatus = self::STATUS_STARTED;

		$connection = Application::getConnection();
		$connection->queryExecute("
			UPDATE b_disk_right_setup_session s
				INNER JOIN b_disk_right_setup_session s1 ON s1.PARENT_ID=s.ID
				INNER JOIN b_disk_right_setup_session s2 ON s2.PARENT_ID=s1.ID
			SET s2.STATUS = {$badStatus}
			WHERE s2.STATUS = {$startedStatus} 		
		");

		$badIds = $connection->query(
			"SELECT ID FROM b_disk_right_setup_session WHERE STATUS = {$badStatus} ORDER BY CREATE_TIME LIMIT 50"
		);

		foreach ($badIds as $badId)
		{
			$badId = $badId['ID'];

			$connection->queryExecute("
				DELETE FROM b_disk_tmp_simple_right WHERE SESSION_ID = {$badId}
			");
			$connection->queryExecute("
				UPDATE b_disk_right_setup_session SET STATUS = {$purifiedStatus} WHERE ID = {$badId}
			");
		}
	}
}
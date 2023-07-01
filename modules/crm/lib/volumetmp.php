<?php

namespace Bitrix\Crm;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Crm\Volume;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity;

/**
 * Class VolumeTableTmp
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> INDICATOR_TYPE string(255) mandatory
 * <li> OWNER_ID int mandatory
 * <li> TIMESTAMP_X datetime optional
 * <li> DATE_CREATE date optional
 * <li> STAGE_SEMANTIC_ID string(3) optional
 * <li> ENTITY_SIZE float optional
 * <li> ENTITY_COUNT float optional
 * <li> FILE_SIZE float optional
 * <li> FILE_COUNT float optional
 * <li> DISK_SIZE float optional
 * <li> DISK_COUNT float optional
 * <li> EVENT_SIZE float optional
 * <li> EVENT_COUNT float optional
 * <li> ACTIVITY_SIZE float optional
 * <li> ACTIVITY_COUNT float optional
 * <li> AGENT_LOCK int optional
 * <li> DROP_ENTITY int optional
 * <li> DROP_FILE int optional
 * <li> DROP_EVENT int optional
 * <li> DROP_ACTIVITY int optional
 * <li> DROPPED_ENTITY_COUNT float optional
 * <li> DROPPED_FILE_COUNT float optional
 * <li> DROPPED_EVENT_COUNT float optional
 * <li> DROPPED_ACTIVITY_COUNT float optional
 * <li> LAST_ID int optional
 * <li> FAIL_COUNT int optional
 * <li> LAST_ERROR string(255) optional
 * <li> FILTER string(255) optional
 * </ul>
 *
 * @package Bitrix\Crm
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_VolumeTmp_Query query()
 * @method static EO_VolumeTmp_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_VolumeTmp_Result getById($id)
 * @method static EO_VolumeTmp_Result getList(array $parameters = [])
 * @method static EO_VolumeTmp_Entity getEntity()
 * @method static \Bitrix\Crm\EO_VolumeTmp createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_VolumeTmp_Collection createCollection()
 * @method static \Bitrix\Crm\EO_VolumeTmp wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_VolumeTmp_Collection wakeUpCollection($rows)
 */


class VolumeTmpTable extends Crm\VolumeTable
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_volume_tmp';
	}

	/**
	 * Drops Temporally table
	 * @return void
	 */
	public static function dropTemporally()
	{
		$connection = Main\Application::getConnection();

		$tmpName = self::getTableName();

		if ($connection->isTableExists($tmpName))
		{
			$connection->query('DROP TABLE '. $tmpName);
		}
	}

	/**
	 * Creates database structure
	 * @return void
	 */
	public static function createTemporally()
	{
		$connection = Main\Application::getConnection();
		$tmpName = self::getTableName();
		if (!$connection->isTableExists($tmpName))
		{
			$connection->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$tmpName.' LIKE '.Crm\VolumeTable::getTableName());
		}
	}

	/**
	 * Checks data base structure
	 * @return bool
	 */
	public static function checkTemporally()
	{
		$connection = Main\Application::getConnection();
		return $connection->isTableExists(self::getTableName());
	}

	/**
	 * Removes all data
	 * @return void
	 */
	public static function clearTemporally()
	{
		$connection = Main\Application::getConnection();
		$tmpName = self::getTableName();
		if ($connection->isTableExists($tmpName))
		{
			$connection->truncateTable($tmpName);
		}
	}
}

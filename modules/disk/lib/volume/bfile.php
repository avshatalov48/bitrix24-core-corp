<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\ObjectTable;

/**
 * Calculate total count at b_file table/
 * @package Bitrix\Disk\Volume
 */
class Bfile extends Volume\Base
{
	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return $this
	 */
	public function measure($collectData = array())
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$indicatorType = $connection->getSqlHelper()->forSql(static::className());
		$ownerId = (string)$this->getOwner();

		$querySql = "
			SELECT 
				'{$indicatorType}' as INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
				bfile.FILE_SIZE,
				bfile.FILE_COUNT,
				disk_file.DISK_SIZE,
				disk_file.DISK_COUNT,
				disk_file.VERSION_COUNT
			FROM
			(
				SELECT 
					SUM(FILE_SIZE) as FILE_SIZE,
					COUNT(*) as FILE_COUNT
				FROM 
					b_file
			) bfile,
			(
				SELECT 
					SUM(f.FILE_SIZE) as DISK_SIZE,
					COUNT(DISTINCT files.ID) as DISK_COUNT,
					COUNT(ver.ID) as VERSION_COUNT
				FROM 
					b_file f
					INNER JOIN b_disk_version ver 
						ON f.ID = ver.FILE_ID 
					INNER JOIN b_disk_object files 
						ON files.ID = ver.OBJECT_ID 
						AND files.ID = files.REAL_OBJECT_ID
						AND files.TYPE = '". ObjectTable::TYPE_FILE. "'
			) disk_file
		";
		$columnList = Volume\QueryHelper::prepareInsert(
			array(
				'INDICATOR_TYPE',
				'OWNER_ID',
				'CREATE_TIME',
				'FILE_SIZE',
				'FILE_COUNT',
				'DISK_SIZE',
				'DISK_COUNT',
				'VERSION_COUNT',
			),
			$this->getSelect()
		);

		$tableName = \Bitrix\Disk\Internals\VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}

	/**
	 * Returns title of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return string
	 */
	public static function getTitle(Volume\Fragment $fragment)
	{
		return '';
	}
}


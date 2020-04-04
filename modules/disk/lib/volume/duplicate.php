<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main\Application;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Volume;


/**
 * Count file duplicates/
 * @package Bitrix\Disk\Volume
 */
class Duplicate extends Volume\Base
{
	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return $this
	 */
	public function measure($collectData = array(self::DISK_FILE))
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$selectSql = Volume\QueryHelper::prepareSelect($this->getSelect());

		$whereSql = Volume\QueryHelper::prepareWhere(
			$this->getFilter(array(
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE
			)),
			array(
				'DELETED_TYPE' => 'files.DELETED_TYPE'
			)
		);

		$indicatorType = $sqlHelper->forSql(static::className());
		$ownerId = (string)$this->getOwner();

		$querySql = "
			SELECT
				'{$indicatorType}' AS INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				CNT_FILES.FILE_SIZE,
				CNT_FILES.FILE_COUNT,
				CNT_FILES.FILE_SIZE as DISK_SIZE,
				CNT_FILES.FILE_COUNT as DISK_COUNT,
				CNT_FILES.VERSION_COUNT,
				IFNULL(CNT_ATTACH.ATTACHED_COUNT, 0) AS ATTACHED_COUNT,
				IFNULL(CNT_LINK.LINK_COUNT, 0) AS LINK_COUNT,
				IFNULL(CNT_SHARING.SHARING_COUNT, 0) AS SHARING_COUNT,
				IFNULL(CNT_FREE.UNNECESSARY_VERSION_SIZE, 0) AS UNNECESSARY_VERSION_SIZE,
				IFNULL(CNT_FREE.UNNECESSARY_VERSION_COUNT, 0) AS UNNECESSARY_VERSION_COUNT,
				CNT_FILES.NAME,
				CNT_FILES.SIZE
			FROM (
				SELECT 
					SUM(IFNULL(ver.SIZE, files.SIZE)) AS FILE_SIZE,
					COUNT(DISTINCT files.ID) AS FILE_COUNT,
					COUNT(DISTINCT ver.ID) AS VERSION_COUNT,
					CRC32(CONCAT(files.NAME,files.SIZE)) as name_hash,
					files.NAME,
					files.SIZE
					{$selectSql}
				FROM 
					b_disk_object files 
					LEFT JOIN b_disk_version ver ON files.ID = ver.OBJECT_ID
					INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
				WHERE 
					files.TYPE = '". ObjectTable::TYPE_FILE. "'
					AND files.ID = files.REAL_OBJECT_ID
					{$whereSql}
				GROUP BY 
					files.NAME,
					files.SIZE
				HAVING 
					FILE_COUNT > 1
				ORDER BY 
					FILE_COUNT  DESC
			) CNT_FILES

			/* attached */
			LEFT JOIN (
				SELECT
					COUNT(DISTINCT files.ID) AS FILE_COUNT,
					COUNT(DISTINCT attached.ID) AS ATTACHED_COUNT,
					CRC32(CONCAT(files.NAME,files.SIZE)) as name_hash
				FROM
					b_disk_object files
					INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
					INNER JOIN b_disk_attached_object attached on attached.OBJECT_ID = files.ID
				WHERE
					files.TYPE = '". ObjectTable::TYPE_FILE. "'
					AND files.ID = files.REAL_OBJECT_ID
					{$whereSql}
				GROUP BY 
					files.NAME,
					files.SIZE
				HAVING 
					FILE_COUNT > 1
				ORDER BY 
					FILE_COUNT  DESC
			) CNT_ATTACH
				ON CNT_FILES.name_hash = CNT_ATTACH.name_hash

			/* external_link */
			LEFT JOIN (
				SELECT
					COUNT(DISTINCT files.ID) AS FILE_COUNT,
					COUNT(DISTINCT link.ID) AS LINK_COUNT,
					CRC32(CONCAT(files.NAME,files.SIZE)) as name_hash
				FROM
					b_disk_object files
					INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID 
					INNER JOIN b_disk_external_link link on link.OBJECT_ID = files.ID
				WHERE
					files.TYPE = '". ObjectTable::TYPE_FILE. "'
					AND link.TYPE != ". \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_AUTO. "
					AND files.ID = files.REAL_OBJECT_ID
					{$whereSql}
				GROUP BY 
					files.NAME,
					files.SIZE
				HAVING 
					FILE_COUNT > 1
				ORDER BY 
					FILE_COUNT  DESC
			) CNT_LINK
				ON CNT_FILES.name_hash = CNT_LINK.name_hash

			/* sharing */
			LEFT JOIN 
			(
				SELECT
					COUNT(DISTINCT files.ID) AS FILE_COUNT,
					COUNT(DISTINCT sharing.ID) AS SHARING_COUNT,
					CRC32(CONCAT(files.NAME,files.SIZE)) as name_hash
				FROM
					b_disk_object files
					INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID 
					INNER JOIN b_disk_sharing sharing on sharing.REAL_OBJECT_ID = files.ID
				WHERE
					files.TYPE = '". ObjectTable::TYPE_FILE. "'
					AND sharing.STATUS = '". SharingTable::STATUS_IS_APPROVED. "'
					AND files.ID = files.REAL_OBJECT_ID
					{$whereSql}
				GROUP BY 
					files.NAME,
					files.SIZE
				HAVING 
					FILE_COUNT > 1
				ORDER BY 
					FILE_COUNT DESC
			) CNT_SHARING
				ON CNT_FILES.name_hash = CNT_SHARING.name_hash

			/* may drop */
			LEFT JOIN
			(
				SELECT
					SUM(src.SIZE) AS UNNECESSARY_VERSION_SIZE,
					SUM(src.CNT) AS UNNECESSARY_VERSION_COUNT,
					src.name_hash
				FROM
				(
					SELECT
						files.ID,
						SUM(IFNULL(ver.SIZE, files.SIZE)) - CAST(GROUP_CONCAT(ver.SIZE ORDER BY ver.ID DESC) as UNSIGNED) AS SIZE,
						COUNT(ver.ID) - 1 AS CNT,
						CRC32(CONCAT(files.NAME,files.SIZE)) as name_hash
					FROM 
						b_disk_object files 
						LEFT JOIN b_disk_version ver ON files.ID = ver.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
				
						LEFT JOIN b_disk_attached_object attached 
							on attached.OBJECT_ID = files.ID 
							AND (attached.VERSION_ID IS NULL OR attached.VERSION_ID = ver.ID)
				
						LEFT JOIN b_disk_external_link link 
							ON link.OBJECT_ID = files.ID
							AND (link.VERSION_ID IS NULL OR link.VERSION_ID = ver.ID)
					WHERE 
						files.TYPE = '". ObjectTable::TYPE_FILE. "'
						AND files.ID = files.REAL_OBJECT_ID
						AND (link.ID IS NULL OR link.TYPE = ". \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_AUTO. ")
						AND attached.ID IS NULL
						{$whereSql}
					GROUP BY 
						files.ID,
						name_hash
					HAVING 
						COUNT(ver.ID) > 1
				) src
				GROUP BY
					src.name_hash
			) CNT_FREE
				ON CNT_FILES.name_hash = CNT_FREE.name_hash

			ORDER BY 
				CNT_FILES.FILE_COUNT DESC
		";

		/*$columnList = Volume\QueryHelper::prepareInsert(
			array(
				'INDICATOR_TYPE',
				'ATTACHED_COUNT',
				'LINK_COUNT',
				'FILE_SIZE',
				'FILE_COUNT',
				'VERSION_COUNT',
				'DATA',
			),
			$this->>getSelect()
		);

		$tableName = VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");*/

		$result = $connection->query($querySql);

		while($row = $result->fetch())
		{
			VolumeTable::add(array(
				'INDICATOR_TYPE' => static::className(),
				'OWNER_ID'  => $ownerId,
				'FILE_SIZE'      => $row['FILE_SIZE'],
				'FILE_COUNT'     => $row['FILE_COUNT'],
				'DISK_SIZE'      => $row['DISK_SIZE'],
				'DISK_COUNT'     => $row['DISK_COUNT'],
				'VERSION_COUNT'  => $row['VERSION_COUNT'],
				'ATTACHED_COUNT' => $row['ATTACHED_COUNT'],
				'LINK_COUNT'     => $row['LINK_COUNT'],
				'SHARING_COUNT'  => $row['SHARING_COUNT'],
				'UNNECESSARY_VERSION_SIZE'      => $row['UNNECESSARY_VERSION_SIZE'],
				'UNNECESSARY_VERSION_COUNT'     => $row['UNNECESSARY_VERSION_COUNT'],
				'DATA'           => serialize(array('TITLE' => $row['NAME'], 'SIZE' => $row['SIZE']))
			));
		}

		return $this;
	}

	/**
	 * @param string[] $filter Row from VolumeTable as a filter.
	 * @return Volume\Fragment
	 */
	public static function getFragment(array $filter)
	{
		$filter['SPECIFIC'] = unserialize($filter['DATA']);
		return parent::getFragment($filter);
	}

	/**
	 * @param Volume\Fragment $fragment File type data set.
	 * @return string
	 */
	public static function getTitle(Volume\Fragment $fragment)
	{
		$specific = $fragment->getSpecific();
		return $specific['TITLE']. ' ('.\CFile::formatSize($specific['SIZE']).')';
	}
}


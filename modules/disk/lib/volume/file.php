<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Application;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\SharingTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class File extends Volume\Base implements Volume\IVolumeIndicatorParent, Volume\IVolumeIndicatorLink
{
	/** @var array */
	protected $order = ['VERSION_COUNT' => 'DESC'];

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 */
	public function measure(array $collectData = [self::DISK_FILE]): self
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$storageId = $this->getFilterValue('STORAGE_ID', '=@');
		if (!empty($storageId))
		{
			$this->addSelect('STORAGE_ID', "'$storageId'");
		}

		$moduleId = $this->getFilterValue('MODULE_ID', '=@');
		if (!empty($moduleId))
		{
			$this->addSelect('MODULE_ID', "'$moduleId'");
		}

		$folderId = $this->getFilterValue('FOLDER_ID', '=@');
		if (!empty($folderId))
		{
			$this->addSelect('FOLDER_ID', "'$folderId'");
		}

		$parentFolderId = $this->getFilterValue('PARENT_ID', '=@!');
		if (!empty($parentFolderId))
		{
			$this
				->addSelect('PARENT_ID', "'$parentFolderId'")
				->unsetFilter('PARENT_ID')
				->addFilter('@PARENT_ID', Volume\QueryHelper::prepareFolderTreeQuery($parentFolderId));
		}


		/**
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @return void
		 */
		$buildDiskSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '')
		{
			/* CNT_FILES.FILE_SIZE as DISK_SIZE, */
			/* CNT_FILES.FILE_COUNT as DISK_COUNT, */
			$selectSql .= "
				, CNT_FILES.FILE_SIZE
				, CNT_FILES.FILE_COUNT
				, CNT_FILES.VERSION_COUNT
			";
			$columns = array_merge($columns, [
				'FILE_SIZE',
				'FILE_COUNT',
				'VERSION_COUNT',
				// 'DISK_SIZE',
				// 'DISK_COUNT',
			]);
			if ($subSelectSql != '')
			{
				$sqlStatements = explode(',', $subSelectSql);
				foreach ($sqlStatements as $statement)
				{
					if (preg_match("/([a-z0-9_\.\']+)[ \t\n]+as[ \t\n]+([a-z0-9_\.\']+)/i", $statement, $parts))
					{
						$selectSql .= ", CNT_FILES.". $parts[2];
						$columns[] = $parts[2];
					}
				}
			}
			// language=SQL
			$fromSql .= "
				(
					SELECT
						SUM(IFNULL(ver.SIZE, files.SIZE)) AS FILE_SIZE,
						COUNT(DISTINCT files.ID) AS FILE_COUNT,
						COUNT(DISTINCT ver.ID) AS VERSION_COUNT
						{$subSelectSql}
					FROM
						b_disk_object files
						LEFT JOIN b_disk_version ver ON files.ID = ver.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
				) CNT_FILES
			";
		};


		/**
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @return void
		 */
		$buildPreviewSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '')
		{
			$selectSql .= "
				, CNT_PREVIEW.PREVIEW_SIZE AS PREVIEW_SIZE
				, CNT_PREVIEW.PREVIEW_COUNT AS PREVIEW_COUNT
			";
			$columns = array_merge($columns, [
				'PREVIEW_SIZE',
				'PREVIEW_COUNT',
			]);
			// language=SQL
			$fromSql .= "
				/* preview */
				, (
					SELECT
						SUM(IFNULL(preview_file.FILE_SIZE, 0)) + SUM(IFNULL(view_file.FILE_SIZE, 0)) AS PREVIEW_SIZE,
						COUNT(DISTINCT preview_file.ID) + COUNT(DISTINCT view_file.ID) AS PREVIEW_COUNT
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
						LEFT JOIN b_file preview_file ON preview_file.ID = files.PREVIEW_ID
						LEFT JOIN b_file view_file ON view_file.ID = files.VIEW_ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
				) CNT_PREVIEW
			";
		};

		/**
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @return void
		 */
		$buildAttachedSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '')
		{
			$selectSql .= "
				, IFNULL(CNT_ATTACH.ATTACHED_COUNT, 0) AS ATTACHED_COUNT
			";
			$columns = array_merge($columns, [
				'ATTACHED_COUNT',
			]);
			// language=SQL
			$fromSql .= "
				/* attached */
				, (
					SELECT
						COUNT(DISTINCT attached.ID) AS ATTACHED_COUNT
					FROM
						b_disk_object files
						LEFT JOIN b_disk_attached_object attached ON files.ID = attached.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
				) CNT_ATTACH
			";
		};

		/**
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @return void
		 */
		$buildExternalSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '')
		{
			$selectSql .= "
				, IFNULL(CNT_LINK.LINK_COUNT, 0) AS LINK_COUNT
			";
			$columns = array_merge($columns, [
				'LINK_COUNT',
			]);
			// language=SQL
			$fromSql .= "
				/* external_link */
				, (
					SELECT
						COUNT(DISTINCT link.ID) AS LINK_COUNT
					FROM
						b_disk_object files
						LEFT JOIN b_disk_external_link link ON files.ID = link.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND link.TYPE != ". Disk\Internals\ExternalLinkTable::TYPE_AUTO. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
				) CNT_LINK
			";
		};

		/**
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @return void
		 */
		$buildSharingSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '')
		{
			$selectSql .= "
				, IFNULL(CNT_SHARING.SHARING_COUNT, 0) AS SHARING_COUNT
			";
			$columns = array_merge($columns, [
				'SHARING_COUNT',
			]);
			// language=SQL
			$fromSql .= "
				/* sharing */
				(
					SELECT
						COUNT(DISTINCT sharing.ID) AS SHARING_COUNT
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
						INNER JOIN b_disk_sharing sharing on sharing.REAL_OBJECT_ID = files.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND sharing.STATUS = ". SharingTable::STATUS_IS_APPROVED. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
				) CNT_SHARING
			";
		};

		/**
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @return void
		 */
		$buildUnnecessarySql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '')
		{
			$selectSql .= "
				, IFNULL(CNT_FREE.UNNECESSARY_VERSION_SIZE, 0) AS UNNECESSARY_VERSION_SIZE
				, IFNULL(CNT_FREE.UNNECESSARY_VERSION_COUNT, 0) AS UNNECESSARY_VERSION_COUNT
			";
			$columns = array_merge($columns, [
				'UNNECESSARY_VERSION_SIZE',
				'UNNECESSARY_VERSION_COUNT',
			]);
			// language=SQL
			$fromSql .= "
				/* may drop */
				, (
					SELECT
						SUM(src.SIZE) AS UNNECESSARY_VERSION_SIZE,
						SUM(src.CNT) AS UNNECESSARY_VERSION_COUNT
					FROM
					(
						SELECT
							files.ID,
							SUM(ver.SIZE) AS SIZE,
							COUNT(ver.ID) AS CNT

						FROM
							b_disk_version ver
							INNER JOIN b_disk_object files ON ver.OBJECT_ID = files.ID and ver.FILE_ID != files.FILE_ID
							INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID

							/* head */
							INNER JOIN (
								SELECT  object_id, max(id) as id
								FROM b_disk_version
								GROUP BY object_id
								ORDER BY NULL
							) head ON head.OBJECT_ID = files.ID

							LEFT JOIN b_disk_attached_object  attached
								ON attached.OBJECT_ID  = ver.OBJECT_ID
								AND attached.VERSION_ID = ver.ID
								AND attached.VERSION_ID != head.ID

							LEFT JOIN b_disk_external_link link
								ON link.OBJECT_ID  = ver.OBJECT_ID
								AND link.VERSION_ID = ver.ID
								AND link.VERSION_ID != head.ID
								AND ifnull(link.TYPE,-1) != ". Disk\Internals\ExternalLinkTable::TYPE_AUTO. "

						WHERE
							files.TYPE = ". ObjectTable::TYPE_FILE. "
							AND files.ID = files.REAL_OBJECT_ID
							AND attached.VERSION_ID is null /* no attach */
							AND link.VERSION_ID is null /*no link */
							{$subWhereSql}

						GROUP BY
							files.ID
						ORDER BY NULL
					) src
				) CNT_FREE
			";
		};


		$subSelectSql = Volume\QueryHelper::prepareSelect($this->getSelect());

		$subWhereSql = Volume\QueryHelper::prepareWhere(
			$this->getFilter([
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			]),
			[
				'MODULE_ID' => 'storage.MODULE_ID',
				'STORAGE_ID' => 'files.STORAGE_ID',
				'FOLDER_ID' => 'files.PARENT_ID',
				'PARENT_ID' => 'files.PARENT_ID',
				'TITLE' => 'files.NAME',
				'TYPE' => 'files.TYPE_FILE',
				'DELETED_TYPE' => 'files.DELETED_TYPE',
			]
		);
		if ($subWhereSql != '')
		{
			$subWhereSql = " AND {$subWhereSql} ";
		}

		$selectSql = '';
		$fromSql = '';
		$whereSql = '';

		$columns = [
			'INDICATOR_TYPE',
			'OWNER_ID',
			'CREATE_TIME',
		];

		$buildDiskSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);

		if (in_array(self::PREVIEW_FILE, $collectData))
		{
			$buildPreviewSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
		}

		if (in_array(self::ATTACHED_OBJECT, $collectData))
		{
			$buildAttachedSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
		}

		if (in_array(self::EXTERNAL_LINK, $collectData))
		{
			$buildExternalSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
		}

		if (in_array(self::SHARING_OBJECT, $collectData))
		{
			$buildSharingSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
		}

		if (in_array(self::UNNECESSARY_VERSION, $collectData))
		{
			$buildUnnecessarySql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
		}


		$indicatorType = $sqlHelper->forSql(static::className());
		$ownerId = (string)$this->getOwner();

		$querySql = "
			SELECT
				'{$indicatorType}' AS INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME
				{$selectSql}
			FROM
				{$fromSql}
		";

		VolumeTable::createTemporally();
		VolumeTable::clearTemporally();
		$tableName = VolumeTable::getTableName();
		$temporallyTableName = VolumeTable::getTemporallyName();

		$columnList = Volume\QueryHelper::prepareInsert($columns, $this->getSelect());
		$connection->queryExecute("INSERT INTO {$temporallyTableName} ({$columnList}) {$querySql}");

		$temporallyDataSource = "SELECT {$columnList} FROM {$temporallyTableName}";

		if ($this->getFilterId() > 0)
		{
			$filterId = $this->getFilterId();
			$columnList = Volume\QueryHelper::prepareUpdateOnSelect($columns, $this->getSelect(), 'destinationTbl', 'sourceQuery');
			$querySql = "
				UPDATE
					{$tableName} destinationTbl,
					({$temporallyDataSource}) sourceQuery
				SET {$columnList}
				WHERE destinationTbl.ID = {$filterId}
			";
		}
		else
		{
			$querySql = "INSERT INTO {$tableName} ({$columnList}) {$temporallyDataSource}";
		}

		if (!$connection->lock(self::$lockName, self::$lockTimeout))
		{
			throw new Main\SystemException('Cannot get table lock for '.$indicatorType, self::ERROR_LOCK_TIMEOUT);
		}

		$connection->queryExecute($querySql);

		$connection->unlock(self::$lockName);

		VolumeTable::clearTemporally();

		return $this;
	}

	/**
	 * Returns result set of file list corresponding to filter.
	 * @param array $collectedData List types of collected data to return: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return DB\Result
	 */
	public function getMeasurementResult(array $collectedData = [self::DISK_FILE, self::ATTACHED_OBJECT, self::EXTERNAL_LINK, self::UNNECESSARY_VERSION, self::CRM_OBJECT]): DB\Result
	{
		$connection = Application::getConnection();

		$parentFolderId = $this->getFilterValue('PARENT_ID', '=@!');
		if (!empty($parentFolderId))
		{
			$this
				->unsetFilter('PARENT_ID')
				->addFilter('@PARENT_ID', Volume\QueryHelper::prepareFolderTreeQuery($parentFolderId));
		}

		$this->unsetFilter('FILES_LEFT');

		$whereSql = Volume\QueryHelper::prepareWhere(
			$this->getFilter([
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			]),
			[
				'MODULE_ID' => 'storage.MODULE_ID',
				'STORAGE_ID' => 'files.STORAGE_ID',
				'FOLDER_ID' => 'files.PARENT_ID',
				'PARENT_ID' => 'files.PARENT_ID',
				'TITLE' => 'files.NAME',
				'TYPE' => 'files.TYPE_FILE',
				'DELETED_TYPE' => 'files.DELETED_TYPE',
				'ENTITY_TYPE' => 'storage.ENTITY_TYPE',
			]
		);
		if ($whereSql != '')
		{
			$whereSql = " AND {$whereSql} ";
		}

		$orderSql = Volume\QueryHelper::prepareOrder(
			$this->getOrder([
				'VERSION_COUNT' => 'DESC'
			]),
			[
				'TITLE' => 'CNT_FILES.TITLE',
				'SIZE_FILE' => 'CNT_FILES.SIZE_FILE',
				'UPDATE_TIME' => 'CNT_FILES.UPDATE_TIME',
				'VERSION_SIZE' => 'CNT_FILES.VERSION_SIZE',
				'VERSION_COUNT' => 'CNT_FILES.VERSION_COUNT',
				'UNNECESSARY_VERSION_SIZE' => 'CNT_FREE.UNNECESSARY_VERSION_SIZE',
				'UNNECESSARY_VERSION_COUNT' => 'CNT_FREE.UNNECESSARY_VERSION_COUNT',
			]
		);

		$orderKeys = array_keys($this->getOrder());
		$orderKey = array_shift($orderKeys);


		$sqlHint = '';
		if($connection instanceof DB\MysqlCommonConnection)
		{
			$sqlHint = 'SQL_CALC_FOUND_ROWS';
		}

		$indicatorType = $connection->getSqlHelper()->forSql(static::className());

		/**
		 * @param string $whereSql
		 * @return string
		 */
		$buildQueryFiles = function($whereSql = '')
		{
			// language=SQL
			$querySql = "
				/* files */
				(
					SELECT
						SUM(IFNULL(ver.SIZE, 0)) AS VERSION_SIZE,
						COUNT(distinct ver.ID) AS VERSION_COUNT,
						files.NAME as TITLE,
						files.SIZE as SIZE_FILE,
						files.UPDATE_TIME as UPDATE_TIME,
						files.ID AS FID,
						files.PARENT_ID AS PARENT_ID,
						files.STORAGE_ID AS STORAGE_ID,
						storage.ENTITY_TYPE AS ENTITY_TYPE,
						storage.ENTITY_ID AS ENTITY_ID
					FROM
						b_disk_object files
						LEFT JOIN b_disk_version ver ON files.ID = ver.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE
						files.TYPE = ".ObjectTable::TYPE_FILE."
						AND files.ID = files.REAL_OBJECT_ID
						{$whereSql}
					GROUP BY
						files.PARENT_ID,
						files.STORAGE_ID,
						storage.ENTITY_ID,
						storage.ENTITY_TYPE,
						files.ID,
						files.NAME,
						files.SIZE,
						files.UPDATE_TIME
					ORDER BY NULL
				) CNT_FILES
			";
			return $querySql;
		};

		/**
		 * @param string $whereSql
		 * @return string
		 */
		$buildPreviewSql = function($whereSql = '')
		{
			// language=SQL
			$querySql = "
				/* preview */
				LEFT JOIN
				(
					SELECT
						SUM(IFNULL(preview_file.FILE_SIZE, 0)) + SUM(IFNULL(view_file.FILE_SIZE, 0)) AS PREVIEW_SIZE,
						COUNT(DISTINCT preview_file.ID) + COUNT(DISTINCT view_file.ID) AS PREVIEW_COUNT,
						files.ID AS FID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
						LEFT JOIN b_file preview_file ON preview_file.ID = files.PREVIEW_ID
						LEFT JOIN b_file view_file ON view_file.ID = files.VIEW_ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$whereSql}
					GROUP BY
						files.STORAGE_ID,
						storage.ENTITY_ID,
						storage.ENTITY_TYPE
					ORDER BY NULL
				) CNT_PREVIEW
					ON CNT_PREVIEW.FID = CNT_FILES.FID
			";
			return $querySql;
		};

		/**
		 * @param string $whereSql
		 * @return string
		 */
		$buildQueryAttached = function($whereSql = '')
		{
			// language=SQL
			$querySql = "
				/* attached */
				LEFT JOIN
				(
					SELECT
						COUNT(distinct attached.ID) AS ATTACHED_COUNT,
						files.ID AS FID
					FROM
						b_disk_object files
						LEFT JOIN b_disk_attached_object attached ON files.ID = attached.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE
						files.TYPE = ".ObjectTable::TYPE_FILE."
						AND files.ID = files.REAL_OBJECT_ID
						{$whereSql}
					GROUP BY
						files.ID
					ORDER BY NULL
				) CNT_ATTACH
					ON CNT_ATTACH.FID = CNT_FILES.FID
			";
			return $querySql;
		};

		/**
		 * @param string $whereSql
		 * @return string
		 */
		$buildQueryExternal = function($whereSql = '')
		{
			// language=SQL
			$querySql = "
				/* external_link */
				LEFT JOIN
				(
					SELECT
						COUNT(distinct link.ID) AS LINK_COUNT,
						files.ID AS FID
					FROM
						b_disk_object files
						LEFT JOIN b_disk_external_link link ON files.ID = link.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND link.TYPE != ". Disk\Internals\ExternalLinkTable::TYPE_AUTO. "
						AND files.ID = files.REAL_OBJECT_ID
						{$whereSql}
					GROUP BY
						files.ID
					ORDER BY NULL
				) CNT_LINK
					ON CNT_LINK.FID = CNT_FILES.FID
			";
			return $querySql;
		};

		/**
		 * @param string $whereSql
		 * @return string
		 */
		$buildQuerySharing = function($whereSql = '')
		{
			// language=SQL
			$querySql = "
				/* sharing */
				LEFT JOIN
				(
					SELECT
						COUNT(DISTINCT sharing.ID) AS SHARING_COUNT,
						files.ID AS FID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
						INNER JOIN b_disk_sharing sharing on sharing.REAL_OBJECT_ID = files.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND sharing.STATUS = ". SharingTable::STATUS_IS_APPROVED. "
						AND files.ID = files.REAL_OBJECT_ID
						{$whereSql}
					GROUP BY
						files.ID
					ORDER BY NULL
				) CNT_SHARING
					ON CNT_FILES.FID = CNT_SHARING.FID
			";
			return $querySql;
		};

		/**
		 * @param string $whereSql
		 * @return string
		 */
		$buildQueryCrm = function($whereSql = '')
		{
			// language=SQL
			$querySql = "
				/* crm */
				LEFT JOIN
				(
					SELECT
						COUNT(DISTINCT act_elem.ELEMENT_ID) AS ACT_COUNT,
						files.ID AS FID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
						INNER JOIN b_crm_act_elem act_elem on act_elem.ELEMENT_ID = files.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND act_elem.STORAGE_TYPE_ID = ". \Bitrix\Crm\Integration\StorageType::Disk. "
						AND files.ID = files.REAL_OBJECT_ID
						{$whereSql}
					GROUP BY
						files.ID
					ORDER BY NULL
				) CNT_CRM
					ON CNT_FILES.FID = CNT_CRM.FID
			";
			return $querySql;
		};

		/**
		 * @param string $whereSql
		 * @return string
		 */
		$buildQueryUnnecessary = function($whereSql = '')
		{
			// language=SQL
			$querySql = "
				/* may drop */
				LEFT JOIN
				(
					SELECT
						files.ID AS FID,
						SUM(ver.SIZE) AS UNNECESSARY_VERSION_SIZE,
						COUNT(ver.ID) AS UNNECESSARY_VERSION_COUNT

					FROM
						b_disk_version ver
						INNER JOIN b_disk_object files ON ver.OBJECT_ID = files.ID and ver.FILE_ID != files.FILE_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID

						/* head */
						INNER JOIN (
							SELECT  object_id, max(id) as id
							FROM b_disk_version
							GROUP BY object_id
							ORDER BY NULL
						) head ON head.OBJECT_ID = files.ID

						LEFT JOIN b_disk_attached_object  attached
							ON attached.OBJECT_ID  = ver.OBJECT_ID
							AND attached.VERSION_ID = ver.ID
							AND attached.VERSION_ID != head.ID

						LEFT JOIN b_disk_external_link link
							ON link.OBJECT_ID  = ver.OBJECT_ID
							AND link.VERSION_ID = ver.ID
							AND link.VERSION_ID != head.ID
							AND ifnull(link.TYPE,-1) != ". Disk\Internals\ExternalLinkTable::TYPE_AUTO. "

					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.ID = files.REAL_OBJECT_ID
						AND attached.VERSION_ID is null /* no attach */
						AND link.VERSION_ID is null /*no link */
						{$whereSql}

					GROUP BY
						files.ID
					ORDER BY NULL
				) CNT_FREE
					ON CNT_FREE.FID = CNT_FILES.FID
			";
			return $querySql;
		};


		$fromSql = $buildQueryFiles($whereSql);

		if ($orderKey == 'UNNECESSARY_VERSION_SIZE' || $orderKey == 'UNNECESSARY_VERSION_COUNT')
		{
			$fromSql .= $buildQueryUnnecessary($whereSql);
		}

		// query to count rows
		$queryIdsSql = "
			SELECT {$sqlHint}
				CNT_FILES.FID as FID
			FROM
				{$fromSql}
			ORDER BY
				{$orderSql}
		";

		if ($this->getLimit() > 0)
		{
			$helper = Application::getConnection()->getSqlHelper();
			$queryIdsSql = $helper->getTopSql($queryIdsSql, $this->getLimit(), $this->getOffset());
		}

		$cursor = $connection->query($queryIdsSql);

		if($connection instanceof DB\MysqlCommonConnection)
		{
			$count = $connection->queryScalar('SELECT FOUND_ROWS() as CNT');
		}
		else
		{
			$queryIdsSql = "SELECT COUNT(cntholder) AS CNT FROM (SELECT 1 cntholder FROM {$fromSql}) xxx";

			$count = $connection->queryScalar($queryIdsSql);
		}

		if ($count > 0)
		{
			$ids = [];
			foreach ($cursor as $row)
			{
				$ids[] = $row['FID'];
			}

			$selectSql = '';
			$usingSql = '';
			$fromSql = $buildQueryFiles($whereSql. ' AND files.ID IN('. implode(',', $ids). ') ');

			if (in_array(self::PREVIEW_FILE, $collectedData))
			{
				$selectSql .=
					', IFNULL(CNT_PREVIEW.PREVIEW_SIZE, 0) as PREVIEW_SIZE '.
					', IFNULL(CNT_PREVIEW.PREVIEW_COUNT, 0) as PREVIEW_COUNT';
				$fromSql .= $buildPreviewSql($whereSql);
			}

			if (in_array(self::ATTACHED_OBJECT, $collectedData))
			{
				$selectSql .= ', IFNULL(CNT_ATTACH.ATTACHED_COUNT, 0) as ATTACHED_COUNT';
				$usingSql .= '+ IFNULL(CNT_ATTACH.ATTACHED_COUNT, 0)';
				$fromSql .= $buildQueryAttached($whereSql);
			}

			if (in_array(self::EXTERNAL_LINK, $collectedData))
			{
				$selectSql .= ', IFNULL(CNT_LINK.LINK_COUNT, 0) as LINK_COUNT';
				$usingSql .= '+ IFNULL(CNT_LINK.LINK_COUNT, 0)';
				$fromSql .= $buildQueryExternal($whereSql);
			}

			if (in_array(self::SHARING_OBJECT, $collectedData))
			{
				$selectSql .= ', IFNULL(CNT_SHARING.SHARING_COUNT, 0) as SHARING_COUNT';
				$fromSql .= $buildQuerySharing($whereSql);
			}

			if (in_array(self::UNNECESSARY_VERSION, $collectedData))
			{
				$selectSql .=
					', IFNULL(CNT_FREE.UNNECESSARY_VERSION_SIZE, 0) as UNNECESSARY_VERSION_SIZE '.
					', IFNULL(CNT_FREE.UNNECESSARY_VERSION_COUNT, 0) as UNNECESSARY_VERSION_COUNT';

				$fromSql .= $buildQueryUnnecessary($whereSql);
			}

			if (in_array(self::CRM_OBJECT, $collectedData))
			{
				$crmIndicator = new Volume\Module\Crm();
				if ($crmIndicator->isMeasureAvailable())
				{
					$selectSql .= ', IFNULL(CNT_CRM.ACT_COUNT, 0) as ACT_COUNT';
					$usingSql .= '+ IFNULL(CNT_CRM.ACT_COUNT, 0)';
					$fromSql .= $buildQueryCrm($whereSql);
				}
			}

			// main query
			$querySql = "
				SELECT
					'{$indicatorType}' as INDICATOR_TYPE,
					CNT_FILES.FID as ID,
					CNT_FILES.PARENT_ID as FOLDER_ID,
					CNT_FILES.*,
					0 {$usingSql} as USING_COUNT
					{$selectSql}
				FROM
					{$fromSql}
				ORDER BY
					{$orderSql}
			";

			$cursor = $connection->query($querySql);
			$cursor->setCount($count);
		}

		return $cursor;
	}

	/**
	 * @param string[] $filter Row from VolumeTable as a filter.
	 * @return Volume\Fragment
	 * @throws ObjectException
	 */
	public static function getFragment(array $filter): Volume\Fragment
	{
		$filter['FILE_ID'] = $filter['FID'];
		return parent::getFragment($filter);
	}

	/**
	 * @param Volume\Fragment $fragment File entity.
	 * @return string|null
	 * @throws ArgumentTypeException
	 */
	public static function getTitle(Volume\Fragment $fragment): ?string
	{
		$file = $fragment->getFile();
		if (!$file instanceof Disk\File)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\File::className());
		}

		return $file->getName();
	}

	/**
	 * Returns last update time of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return \Bitrix\Main\Type\DateTime|null
	 * @throws ArgumentTypeException
	 */
	public static function getUpdateTime(Volume\Fragment $fragment): ?\Bitrix\Main\Type\DateTime
	{
		$file = $fragment->getFile();
		if (!$file instanceof Disk\File)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\File::className());
		}

		return $file->getUpdateTime();
	}

	/**
	 * @param Volume\Fragment $fragment File entity.
	 * @return string[]
	 * @throws ArgumentTypeException
	 */
	public static function getParents(Volume\Fragment $fragment): array
	{
		$file = $fragment->getFile();
		if (!$file instanceof Disk\File)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\File::className());
		}

		$parents = [];

		// Im
		if (in_array($fragment->getEntityType(), Volume\Module\Im::getEntityType()))
		{
			$imFragment = Volume\Module\Im::getFragment([
				'INDICATOR_TYPE' => Volume\Folder::className(),
				'FOLDER_ID' => $fragment->getFolderId(),
			]);
			$parents[] = Volume\Module\Im::getTitle($imFragment);
		}
		elseif ($parent = Disk\Folder::loadById($file->getParentId()))
		{
			$parents = Disk\CrumbStorage::getInstance()->getByObject($parent, true);
		}

		return $parents;
	}

	/**
	 * @param Volume\Fragment $fragment File entity.
	 * @return string|null
	 * @throws ArgumentTypeException
	 */
	public static function getUrl(Volume\Fragment $fragment): ?string
	{
		$file = $fragment->getFile();
		if (!$file instanceof Disk\File)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\File::className());
		}

		// Im
		if (in_array($fragment->getEntityType(), Volume\Module\Im::getEntityType()))
		{
			return null;
		}
		// Mail
		if (in_array($fragment->getEntityType(), Volume\Module\Mail::getEntityType()))
		{
			return null;
		}
		// Documentgenerator
		if (in_array($fragment->getEntityType(), Volume\Module\Documentgenerator::getEntityType()))
		{
			return null;
		}

		$urlManager = Disk\Driver::getInstance()->getUrlManager();

		if ($file->isDeleted())
		{
			$url = $urlManager->getUrlFocusController('openTrashcanFileDetail', ['fileId' => $file->getId()]);
		}
		else
		{
			$url = $urlManager->getUrlFocusController('openFileDetail', ['fileId' => $file->getId()]);
		}

		return $url;
	}

	/**
	 * @param Volume\Fragment $fragment File entity.
	 * @param int $userId User id for permission check.
	 * @return array
	 * @throws ArgumentTypeException
	 */
	public static function getAttachedList(Volume\Fragment $fragment, $userId)
	{
		$file = $fragment->getFile();
		if (!$file instanceof Disk\File)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\File::className());
		}

		$attached = [];

		if ($fragment->getAttachedCount() > 0)
		{
			$attachedObjects = $file->getAttachedObjects();
			if($attachedObjects)
			{
				foreach($attachedObjects as $attachedObject)
				{
					if ($attachedObject instanceof Disk\AttachedObject)
					{
						try
						{
							$connector = $attachedObject->getConnector();

							$dataToShow = $connector->getDataToShow();
							if ($dataToShow)
							{
								$attached[$attachedObject->getEntityId()] = [
									'title' => $dataToShow['TITLE'],
								];
								if ($attachedObject->canRead($userId))
								{
									if (!empty($dataToShow['DETAIL_URL']))
									{
										$attached[$attachedObject->getEntityId()]['url'] = $dataToShow['DETAIL_URL'];
									}
								}
							}
						}
						catch (\Bitrix\Main\SystemException $exception)
						{
						}
					}
				}
			}
		}

		return $attached;
	}
}



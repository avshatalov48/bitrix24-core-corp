<?php

namespace Bitrix\Disk\Volume\Storage;

use Bitrix\Main\Application;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\ProxyType;


/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Storage
	extends Volume\Base
	implements Volume\IVolumeIndicatorStorage, Volume\IVolumeIndicatorLink, Volume\IVolumeTimeLimit
{
	/** @implements Volume\IVolumeTimeLimit */
	use Volume\TimeLimit;

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType(): array
	{
		return [];
	}

	/**
	 * Returns measure process stages list.
	 * List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION or PREVIEW_FILE.
	 * @return string[]
	 */
	public function getMeasureStages()
	{
		return [
			self::DISK_FILE,
			self::UNNECESSARY_VERSION,
			'recalculatePercent'
		];
	}


	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 */
	public function measure(array $collectData = [self::DISK_FILE, self::UNNECESSARY_VERSION]): self
	{
		$collectData[] = 'recalculatePercent';

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		/**
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @return void
		 */
		$build0Sql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$query = VolumeTable::query();
			$query
				->addFilter('=OWNER_ID', $this->getOwner())
				->addFilter('=INDICATOR_TYPE', static::className())
			;

			$query
				->addSelect('STORAGE_ID')
				->addSelect('ENTITY_ID')
				->addSelect('ENTITY_TYPE')
			;
			$selectSql .= "
				, CNT_FILES.STORAGE_ID
				, CNT_FILES.ENTITY_TYPE
				, CNT_FILES.ENTITY_ID
			";
			$columns = array_merge($columns, [
				'STORAGE_ID',
				'ENTITY_TYPE',
				'ENTITY_ID',
			]);

			if ($this instanceof Volume\Storage\Group)
			{
				$query->addSelect('GROUP_ID');
				$columns[] = 'GROUP_ID';
				$selectSql .= " , CNT_FILES.GROUP_ID ";
			}
			if ($this instanceof Volume\Storage\User)
			{
				$query->addSelect('USER_ID');
				$columns[] = 'USER_ID';
				$selectSql .= " , CNT_FILES.USER_ID ";
			}

			if ($this->getFilterId() > 0)
			{
				$query->addFilter('=ID', $this->getFilterId());
			}
			if ($this->getFilterValue('STORAGE_ID') !== null)
			{
				$query->addFilter('=STORAGE_ID', $this->getFilterValue('STORAGE_ID'));
			}
			if ($this->getFilterValue('ENTITY_TYPE') !== null)
			{
				$query->addFilter('=ENTITY_TYPE', $this->getFilterValue('ENTITY_TYPE'));
			}
			if ($this->getFilterValue('ENTITY_ID') !== null)
			{
				$query->addFilter('=ENTITY_ID', $this->getFilterValue('ENTITY_ID'));
			}

			$querySql = $query->getQuery();


			// language=SQL
			$fromSql .= "
				(
					{$querySql}
					ORDER BY NULL
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
		$buildDiskSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$selectSql .= "
				, CNT_FILES.FILE_SIZE
				, CNT_FILES.FILE_COUNT
				, CNT_FILES.FILE_SIZE as DISK_SIZE
				, CNT_FILES.FILE_COUNT as DISK_COUNT
				, CNT_FILES.VERSION_COUNT
				, CNT_FILES.STORAGE_ID
				, CNT_FILES.ENTITY_TYPE
				, CNT_FILES.ENTITY_ID
				, CNT_FILES.TITLE
			";
			$columns = array_merge($columns, [
				'FILE_SIZE',
				'FILE_COUNT',
				'DISK_SIZE',
				'DISK_COUNT',
				'VERSION_COUNT',
				'STORAGE_ID',
				'ENTITY_TYPE',
				'ENTITY_ID',
				'TITLE',
			]);
			if ($subSelectSql != '')
			{
				$sqlStatements = explode(',', $subSelectSql);
				foreach ($sqlStatements as $statement)
				{
					if (preg_match("/([a-z0-9_\.\']+)[ \t\n]+as[ \t\n]+([a-z0-9_\.\']+)/i", $statement, $parts))
					{
						$selectSql .= ', CNT_FILES.'. $parts[2];
						$columns[] = $parts[2];
						$subGroupSql .= ', '. $parts[1];
					}
				}
			}

			$realFileSize = 'ver.SIZE';
			$realFileCount = 'ver.ID';
			$realFileFilter = 'AND files.ID = files.REAL_OBJECT_ID';
			if ($this instanceof Volume\Storage\TrashCan)
			{
				$realFileSize = 'CASE WHEN files.ID = files.REAL_OBJECT_ID THEN ver.SIZE ELSE 0 END';
				$realFileCount = 'CASE WHEN files.ID = files.REAL_OBJECT_ID THEN ver.ID ELSE 1 END';
				$realFileFilter = '';
			}

			// language=SQL
			$fromSql .= "
				(
					SELECT 
						SUM( {$realFileSize} ) AS FILE_SIZE,
						COUNT( {$realFileCount} ) AS FILE_COUNT,
						COUNT( {$realFileCount} ) AS VERSION_COUNT,
						storage.ID AS STORAGE_ID,
						storage.ENTITY_TYPE AS ENTITY_TYPE,
						storage.ENTITY_ID AS ENTITY_ID,
						storage.NAME AS TITLE
						{$subSelectSql}
					FROM 
						b_disk_object files 
						LEFT JOIN b_disk_version ver ON files.ID = ver.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE 
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						{$realFileFilter}
						{$subWhereSql}
					GROUP BY 
						storage.ID, 
						storage.ENTITY_ID, 
						storage.ENTITY_TYPE,
						storage.NAME
						{$subGroupSql}
					ORDER BY NULL
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
				INNER JOIN
				(
					SELECT
						SUM(IFNULL(preview_file.FILE_SIZE, 0)) + SUM(IFNULL(view_file.FILE_SIZE, 0)) AS PREVIEW_SIZE,
						COUNT(DISTINCT preview_file.ID) + COUNT(DISTINCT view_file.ID) AS PREVIEW_COUNT,
						storage.ID AS STORAGE_ID,
						storage.ENTITY_TYPE AS ENTITY_TYPE,
						storage.ENTITY_ID AS ENTITY_ID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
						LEFT JOIN b_file preview_file ON preview_file.ID = files.PREVIEW_ID
						LEFT JOIN b_file view_file ON view_file.ID = files.VIEW_ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						storage.ID,
						storage.ENTITY_ID,
						storage.ENTITY_TYPE
					ORDER BY NULL
				) CNT_PREVIEW
					ON CNT_FILES.STORAGE_ID = CNT_PREVIEW.STORAGE_ID
					AND CNT_FILES.ENTITY_ID = CNT_PREVIEW.ENTITY_ID
					AND CNT_FILES.ENTITY_TYPE = CNT_PREVIEW.ENTITY_TYPE
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
				INNER JOIN
				(
					SELECT
						COUNT(DISTINCT attached.ID) AS ATTACHED_COUNT,
						storage.ID AS STORAGE_ID,
						storage.ENTITY_TYPE AS ENTITY_TYPE,
						storage.ENTITY_ID AS ENTITY_ID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
						INNER JOIN b_disk_attached_object attached on attached.OBJECT_ID = files.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						storage.ID,
						storage.ENTITY_ID,
						storage.ENTITY_TYPE
					ORDER BY NULL
				) CNT_ATTACH
					ON CNT_FILES.STORAGE_ID = CNT_ATTACH.STORAGE_ID
					AND CNT_FILES.ENTITY_ID = CNT_ATTACH.ENTITY_ID
					AND CNT_FILES.ENTITY_TYPE = CNT_ATTACH.ENTITY_TYPE
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
				INNER JOIN
				(
					SELECT
						COUNT(DISTINCT link.ID) AS LINK_COUNT,
						storage.ID AS STORAGE_ID,
						storage.ENTITY_TYPE AS ENTITY_TYPE,
						storage.ENTITY_ID AS ENTITY_ID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID 
						INNER JOIN b_disk_external_link link on link.OBJECT_ID = files.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND link.TYPE != ". Disk\Internals\ExternalLinkTable::TYPE_AUTO. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						storage.ID,
						storage.ENTITY_ID,
						storage.ENTITY_TYPE
					ORDER BY NULL
				) CNT_LINK
					ON CNT_FILES.STORAGE_ID = CNT_LINK.STORAGE_ID
					AND CNT_FILES.ENTITY_ID = CNT_LINK.ENTITY_ID
					AND CNT_FILES.ENTITY_TYPE = CNT_LINK.ENTITY_TYPE
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
				INNER JOIN
				(
					SELECT
						COUNT(DISTINCT sharing.ID) AS SHARING_COUNT,
						storage.ID AS STORAGE_ID,
						storage.ENTITY_TYPE AS ENTITY_TYPE,
						storage.ENTITY_ID AS ENTITY_ID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID 
						INNER JOIN b_disk_sharing sharing on sharing.REAL_OBJECT_ID = files.ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND sharing.STATUS = ". SharingTable::STATUS_IS_APPROVED. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						storage.ID,
						storage.ENTITY_ID,
						storage.ENTITY_TYPE
					ORDER BY NULL
				) CNT_SHARING
					ON CNT_FILES.STORAGE_ID = CNT_SHARING.STORAGE_ID
					AND CNT_FILES.ENTITY_ID = CNT_SHARING.ENTITY_ID
					AND CNT_FILES.ENTITY_TYPE = CNT_SHARING.ENTITY_TYPE
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
				INNER JOIN
				(
					SELECT
						SUM(src.SIZE) AS UNNECESSARY_VERSION_SIZE,
						SUM(src.CNT) AS UNNECESSARY_VERSION_COUNT,
						src.STORAGE_ID,
						src.ENTITY_ID,
						src.ENTITY_TYPE
					FROM
					(
						SELECT
							files.ID,
							SUM(ver.SIZE) AS SIZE,
							COUNT(ver.ID) AS CNT,
							storage.ID AS STORAGE_ID,
							storage.ENTITY_TYPE AS ENTITY_TYPE,
							storage.ENTITY_ID AS ENTITY_ID
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
							files.ID,
							storage.ID, 
							storage.ENTITY_ID, 
							storage.ENTITY_TYPE
						ORDER BY NULL
					) src
					GROUP BY
						src.STORAGE_ID,
						src.ENTITY_ID,
						src.ENTITY_TYPE
					ORDER BY NULL
				) CNT_FREE
					ON CNT_FILES.STORAGE_ID = CNT_FREE.STORAGE_ID
					AND CNT_FILES.ENTITY_ID = CNT_FREE.ENTITY_ID
					AND CNT_FILES.ENTITY_TYPE = CNT_FREE.ENTITY_TYPE
			";
		};

		$stageId = $this->getStage();
		if (empty($stageId))
		{
			$stageId = self::DISK_FILE;
		}

		$allTestTypes = [
			self::DISK_FILE,
			//self::ATTACHED_OBJECT,
			//self::EXTERNAL_LINK,
			//self::SHARING_OBJECT,
			self::UNNECESSARY_VERSION,
			'recalculatePercent',
		];
		$executeTests = [];
		$needApply = false;
		foreach ($allTestTypes as $testTypeId)
		{
			if (in_array($testTypeId, $collectData))
			{
				$needApply = $needApply || ($testTypeId == $stageId);
				if ($needApply)
				{
					$executeTests[] = $testTypeId;
				}
			}
		}

		VolumeTable::createTemporally();
		$temporallyTableName = VolumeTable::getTemporallyName();

		$indicatorType = $sqlHelper->forSql(static::className());
		$ownerId = (string)$this->getOwner();
		$createTime = $connection->getSqlHelper()->getCurrentDateTimeFunction();
		$tableName = VolumeTable::getTableName();

		foreach ($executeTests as $testTypeId)
		{
			$this->setStage($testTypeId);

			if (!$this->checkTimeEnd())
			{
				break;
			}

			if ($testTypeId == 'recalculatePercent')
			{
				$this->recalculatePercent();
				$this->setStage(null);
				continue;
			}

			VolumeTable::clearTemporally();

			$subSelectSql = Volume\QueryHelper::prepareSelect($this->getSelect());

			$subWhereSql = Volume\QueryHelper::prepareWhere(
				$this->getFilter([
					'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE
				]),
				[
					'ENTITY_TYPE' => 'storage.ENTITY_TYPE',
					'ENTITY_ID' => 'storage.ENTITY_ID',
					'USER_ID' => 'storage.ENTITY_ID',
					'GROUP_ID' => 'storage.ENTITY_ID',
					'DELETED_TYPE' => 'files.DELETED_TYPE',
					'STORAGE_ID' => 'storage.ID',
					'TITLE' => 'storage.NAME',
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

			if ($testTypeId != self::DISK_FILE)
			{
				$build0Sql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
			}
			switch ($testTypeId)
			{
				case self::DISK_FILE:
					$buildDiskSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
					break;

				case self::PREVIEW_FILE:
					$buildPreviewSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
					break;

				case self::ATTACHED_OBJECT:
					$buildAttachedSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
					break;

				case self::EXTERNAL_LINK:
					$buildExternalSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
					break;

				case self::SHARING_OBJECT:
					$buildSharingSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
					break;

				case self::UNNECESSARY_VERSION:
					$buildUnnecessarySql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql);
					break;
			}

			$querySql = "
				SELECT
					'{$indicatorType}' AS INDICATOR_TYPE,
					{$ownerId} as OWNER_ID,
					{$createTime} as CREATE_TIME
					{$selectSql}
				FROM 
					{$fromSql}
				WHERE
					1 = 1
					{$whereSql}
			";

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
			elseif ($testTypeId != self::DISK_FILE)
			{
				$columnList = Volume\QueryHelper::prepareUpdateOnSelect($columns, $this->getSelect(), 'destinationTbl', 'sourceQuery');
				$querySql = "
					UPDATE 
						{$tableName} destinationTbl, 
						({$temporallyDataSource}) sourceQuery 
					SET {$columnList} 
					WHERE 
						destinationTbl.INDICATOR_TYPE = '{$indicatorType}'
						AND destinationTbl.OWNER_ID = {$ownerId} 
						AND destinationTbl.STORAGE_ID  = sourceQuery.STORAGE_ID  
						AND destinationTbl.ENTITY_ID  = sourceQuery.ENTITY_ID  
						AND destinationTbl.ENTITY_TYPE  = sourceQuery.ENTITY_TYPE 
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
		}

		VolumeTable::clearTemporally();

		return $this;
	}

	/**
	 * Recalculates percent from total file size per row selected by filter.
	 * @param string|Volume\IVolumeIndicator $totalSizeIndicator Use this indicator as total volume.
	 * @param string|Volume\IVolumeIndicator $excludeSizeIndicator Exclude indicator's volume from total volume.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return static
	 */
	public function recalculatePercent($totalSizeIndicator = '\\Bitrix\\Disk\\Volume\\Module\\Disk', $excludeSizeIndicator = ''): self
	{
		if (is_string($totalSizeIndicator) && !empty($totalSizeIndicator) && class_exists($totalSizeIndicator))
		{
			/** @var Volume\Module\Disk $totalSizeIndicator */
			$totalSizeIndicator = new $totalSizeIndicator();
		}
		if (!($totalSizeIndicator instanceof Volume\IVolumeIndicator))
		{
			throw new \Bitrix\Main\ArgumentException('Wrong parameter totalSizeIndicator');
		}
		$totalSizeIndicator->setOwner($this->getOwner());
		$totalSizeIndicator->loadTotals();
		$total = $totalSizeIndicator->getTotalSize() + $totalSizeIndicator->getPreviewSize();

		if (is_string($excludeSizeIndicator) && !empty($excludeSizeIndicator) && class_exists($excludeSizeIndicator))
		{
			/** @var Volume\Module\DiskTrashcan $excludeSizeIndicator */
			$excludeSizeIndicator = new $excludeSizeIndicator();
		}
		if ($excludeSizeIndicator instanceof Volume\IVolumeIndicator)
		{
			/** @var string|Volume\IVolumeIndicator $excludeSizeIndicator */
			$excludeSizeIndicator->setOwner($this->getOwner());
			$excludeSizeIndicator->loadTotals();
			$total -= $excludeSizeIndicator->getTotalSize();
			$total -= $excludeSizeIndicator->getPreviewSize();
		}

		if ($total > 0)
		{
			$tableName = VolumeTable::getTableName();
			$connection = Application::getConnection();

			$ownerId = $this->getOwner();
			$classStorage = $connection->getSqlHelper()->forSql(static::className());
			$classTrashcan = $connection->getSqlHelper()->forSql(Volume\Storage\TrashCan::className());

			$sql = "
				UPDATE 
					{$tableName} destinationTbl, 
					(
						SELECT 
							Storage.ID,
							Storage.STORAGE_ID,
							ifnull(Storage.FILE_SIZE, 0) + ifnull(Trashcan.FILE_SIZE, 0) as FILE_SIZE 
						FROM
						(
							SELECT ID, STORAGE_ID, FILE_SIZE + ifnull(PREVIEW_SIZE, 0) as FILE_SIZE
							FROM 
								{$tableName} 
							WHERE 
								OWNER_ID = {$ownerId}
								AND INDICATOR_TYPE = '{$classStorage}'
						) Storage
						LEFT JOIN
						(
							SELECT STORAGE_ID, FILE_SIZE + ifnull(PREVIEW_SIZE, 0) as FILE_SIZE
							FROM 
								{$tableName} 
							WHERE 
								OWNER_ID = {$ownerId}
								AND INDICATOR_TYPE = '{$classTrashcan}'
						) Trashcan
						ON Storage.STORAGE_ID = Trashcan.STORAGE_ID
					) sourceQuery 
				SET 
					destinationTbl.PERCENT = ROUND(sourceQuery.FILE_SIZE * 100 / {$total}, 4)  
				WHERE 
					destinationTbl.ID = sourceQuery.ID
					AND destinationTbl.storage_id = sourceQuery.storage_id
			";

			if ($connection->lock(self::$lockName, self::$lockTimeout))
			{
				$connection->queryExecute($sql);
				$connection->unlock(self::$lockName);
			}
		}

		return $this;
	}

	/**
	 * Returns calculation result set.
	 * @param array $collectedData List types of collected data to return.
	 * @return DB\Result
	 */
	public function getMeasurementResult(array $collectedData = []): DB\Result
	{
		$storageId = $this->getFilterValue('STORAGE_ID', '=@');
		if ($storageId === null)
		{
			$this->addFilter('!STORAGE_ID', null);
		}
		return parent::getMeasurementResult($collectedData);
	}

	/**
	 * @param Volume\Fragment $fragment Storage entity object.
	 * @return string|null
	 */
	public static function getTitle(Volume\Fragment $fragment): ?string
	{
		$title = $fragment->getTitle();

		if ($fragment->getEntityType() == ProxyType\User::className())
		{
			$storage = $fragment->getStorage();

			if ($storage instanceof Disk\Storage)
			{
				$proxy = $storage->getProxyType();
				if ($proxy instanceof ProxyType\User)
				{
					$title = $proxy->getEntityTitle();
				}
				else
				{
					$title = $storage->getName();
				}
			}

			$user = Disk\User::loadById($fragment->getEntityId());
			if ($user instanceof Disk\User && $user->getActive() !== 'Y')
			{
				// user fired
				Loc::loadMessages(__DIR__. '/../module/socialnetwork.php');

				if($user->getPersonalGender() === 'F')
				{
					$title = Loc::getMessage('DISK_VOLUME_MODULE_SONET_FIRED_F', ['#USER_NAME#' => $title]);
				}
				else
				{
					$title = Loc::getMessage('DISK_VOLUME_MODULE_SONET_FIRED_M', ['#USER_NAME#' => $title]);
				}
			}
		}
		elseif ($fragment->getEntityType())
		{
			$storage = $fragment->getStorage();

			if ($storage instanceof Disk\Storage)
			{
				$proxy = $storage->getProxyType();
				if (
					$storage->getName() == $proxy->getEntityTitle()
					|| mb_stripos($proxy->getEntityTitle(), $storage->getName()) !== false
				)
				{
					$title = $storage->getName();
				}
				else
				{
					$title = $proxy->getEntityTitle();
				}
			}
		}

		return $title;
	}

	/**
	 * @param Volume\Fragment $fragment Storage entity object.
	 * @return string|null
	 * @throws ArgumentTypeException
	 */
	public static function getUrl(Volume\Fragment $fragment): ?string
	{
		$storage = $fragment->getStorage();
		if (!$storage instanceof Disk\Storage)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Storage::className());
		}

		if (
			in_array($storage->getEntityType(), Volume\Module\Im::getEntityType()) ||
			in_array($storage->getEntityType(), Volume\Module\Mail::getEntityType()) ||
			in_array($storage->getEntityType(), Volume\Module\Documentgenerator::getEntityType())
		)
		{
			$url = $storage->getProxyType()->getStorageBaseUrl();
		}
		else
		{
			$url = $storage->getProxyType()->getBaseUrlFolderList();
		}

		$testUrl = trim($url, '/');
		if (
			$testUrl == '' ||
			$testUrl == Disk\ProxyType\Base::SUFFIX_FOLDER_LIST ||
			$testUrl == Disk\ProxyType\Base::SUFFIX_DISK
		)
		{
			return null;
		}

		return $url;
	}


	/**
	 * Gets available disk space. Units ara bytes.
	 * @param Disk\Storage|null $storage Storage entity object.
	 * @return int
	 */
	public static function getAvailableSpace(Disk\Storage $storage = null)
	{
		$diskSpace = -1;
		if ($storage instanceof Disk\Storage)
		{
			if ($storage->isEnabledSizeLimitRestriction())
			{
				$diskSpace = $storage->getSizeLimit();
			}
		}
		else
		{
			$diskSpace = (float)\Bitrix\Main\Config\Option::get('main', 'disk_space', -1);
			if ($diskSpace > 0)
			{
				$diskSpace *= 1024 * 1024;
			}
		}

		/*
		$diskQuota = new \CDiskQuota();
		$freeSpace = $diskQuota->getDiskQuota();
		*/

		return ($diskSpace > 0 ? $diskSpace : -1);
	}
}


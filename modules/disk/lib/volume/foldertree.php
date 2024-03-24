<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk;
use Bitrix\Disk\Volume;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class FolderTree extends Volume\Folder
{
	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 * @throws Main\ArgumentException
	 */
	public function measure(array $collectData = [self::DISK_FILE, self::UNNECESSARY_VERSION]): self
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$folderIndicatorType = $sqlHelper->forSql(Volume\Folder::className());
		$ownerId = (string)$this->getOwner();

		$storageId = $this->getFilterValue('STORAGE_ID', '=@');
		$folderId = $this->getFilterValue('FOLDER_ID', '=@');


		if (is_null($storageId))
		{
			throw new Main\ArgumentException('Undefined filter parameter: STORAGE_ID');
		}
		if (is_null($folderId))
		{
			throw new Main\ArgumentException('Undefined filter parameters: FOLDER_ID');
		}

		// minimize data collected on special storages
		$minimizeDataIndicator = [
			Volume\Module\Im::class,
			Volume\Module\Mail::class,
			Volume\Module\Documentgenerator::class
		];
		foreach ($minimizeDataIndicator as $excludeInd)
		{
			$storageList = (new $excludeInd)->getStorageList();
			if (
				($storageList[0] instanceof Disk\Storage)
				&& (int)$storageId === (int)$storageList[0]->getId()
			)
			{
				$collectData = [self::DISK_FILE];
				break;
			}
		}

		$prefSql = '';
		if ($connection instanceof DB\MysqlCommonConnection)
		{
			$prefSql = 'ORDER BY NULL';
		}

		/**
		 * with path structure
		 */
		$buildDiskPathSql = function(
			array &$select,
			array &$from,
			array &$where,
			array &$columns,
			string $subSelectSql = '',
			string $subWhereSql = '',
			string $subGroupSql = ''
		)
		use ($prefSql)
		: void
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$subWhereSql .= ' AND files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$subWhereSql .= ' AND files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$select = array_merge($select, [
				'CNT_FILES.FILE_SIZE',
				'CNT_FILES.FILE_COUNT',
				'CNT_FILES.DISK_SIZE',
				'CNT_FILES.DISK_COUNT',
				'CNT_FILES.VERSION_COUNT',
				'folder.STORAGE_ID',
				'path.OBJECT_ID as FOLDER_ID',
				'folder.PARENT_ID as PARENT_ID',
			]);
			$columns = array_merge($columns, [
				'FILE_SIZE',
				'FILE_COUNT',
				'DISK_SIZE',
				'DISK_COUNT',
				'VERSION_COUNT',
				'STORAGE_ID',
				'FOLDER_ID',
				'PARENT_ID',
			]);

			$isRemeasure = ($this->getFilterId() > 0);
			if (!$isRemeasure)
			{
				$select = array_merge($select, [
					'folder.NAME  as TITLE',
					'storage.ENTITY_TYPE',
					'storage.ENTITY_ID',
				]);
				$columns = array_merge($columns, [
					'TITLE',
					'ENTITY_TYPE',
					'ENTITY_ID',
				]);
			}

			// language=SQL
			$from[] = "
				(
					SELECT
						pth.PARENT_ID,
						SUM(versions.SIZE) AS FILE_SIZE,
						COUNT(DISTINCT versions.ID) AS FILE_COUNT,
						SUM(versions.SIZE) AS DISK_SIZE,
						COUNT(DISTINCT files.ID) AS DISK_COUNT,
						COUNT(DISTINCT versions.ID) AS VERSION_COUNT
						{$subSelectSql}
					FROM
						b_disk_object_path pth
						INNER JOIN b_disk_object files ON pth.OBJECT_ID = files.ID
						LEFT JOIN b_disk_version versions ON files.ID = versions.OBJECT_ID
					WHERE
						files.TYPE = ".ObjectTable::TYPE_FILE."
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						pth.PARENT_ID
						{$subGroupSql}
					{$prefSql}
				) CNT_FILES

				INNER JOIN b_disk_object_path path ON CNT_FILES.PARENT_ID = path.OBJECT_ID

				INNER JOIN b_disk_object top_folder ON top_folder.ID = path.PARENT_ID

				INNER JOIN b_disk_object folder ON folder.ID = CNT_FILES.PARENT_ID

				INNER JOIN b_disk_storage storage ON storage.ID = folder.STORAGE_ID
			";

			$where[] = 'folder.TYPE = '. ObjectTable::TYPE_FOLDER;
		};


		/**
		 * preview path structure
		 */
		$buildPreviewPathSql = function(
			array &$select,
			array &$from,
			array &$where,
			array &$columns,
			string $subSelectSql = '',
			string $subWhereSql = '',
			string $subGroupSql = ''
		)
		use ($prefSql)
		: void
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$subWhereSql .= ' AND files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$subWhereSql .= ' AND files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$select[] = 'CNT_PREVIEW.PREVIEW_SIZE AS PREVIEW_SIZE';
			$select[] = 'CNT_PREVIEW.PREVIEW_COUNT AS PREVIEW_COUNT';
			$columns[] = 'PREVIEW_SIZE';
			$columns[] = 'PREVIEW_COUNT';
			// language=SQL
			$from[] = "
				/* preview */
				LEFT JOIN
				(
					SELECT
						SUM(COALESCE(preview_file.FILE_SIZE, 0)) + SUM(COALESCE(view_file.FILE_SIZE, 0)) AS PREVIEW_SIZE,
						COUNT(DISTINCT preview_file.ID) + COUNT(DISTINCT view_file.ID) AS PREVIEW_COUNT,
						pth.PARENT_ID
					FROM
						b_disk_object_path pth
						INNER JOIN b_disk_object files ON pth.OBJECT_ID = files.ID
						LEFT JOIN b_file preview_file ON preview_file.ID = files.PREVIEW_ID
						LEFT JOIN b_file view_file ON view_file.ID = files.VIEW_ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						pth.PARENT_ID
						{$subGroupSql}
					{$prefSql}
				) CNT_PREVIEW
					ON CNT_PREVIEW.PARENT_ID = CNT_FILES.PARENT_ID
			";
		};

		/**
		 * attach
		 */
		$buildAttachedSql = function(
			array &$select,
			array &$from,
			array &$where,
			array &$columns,
			string $subSelectSql = '',
			string $subWhereSql = '',
			string $subGroupSql = ''
		)
		use ($prefSql)
		: void
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$subWhereSql .= ' AND files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$subWhereSql .= ' AND files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$select[] = 'COALESCE(CNT_ATTACH.ATTACHED_COUNT, 0) AS ATTACHED_COUNT';
			$columns[] = 'ATTACHED_COUNT';
			// language=SQL
			$from[] = "
				/* attached */
				LEFT JOIN (
					SELECT
						pth.PARENT_ID,
						COUNT(DISTINCT attached.ID) AS ATTACHED_COUNT
						{$subSelectSql}
					FROM
						b_disk_object_path pth
						INNER JOIN b_disk_object files ON pth.OBJECT_ID = files.ID
						LEFT JOIN b_disk_attached_object attached ON files.ID = attached.OBJECT_ID
					WHERE
						files.TYPE = ".ObjectTable::TYPE_FILE."
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						pth.PARENT_ID
						{$subGroupSql}
					{$prefSql}
				) CNT_ATTACH
					ON CNT_ATTACH.PARENT_ID = CNT_FILES.PARENT_ID
			";
		};

		/**
		 * external
		 */
		$buildExternalSql = function(
			array &$select,
			array &$from,
			array &$where,
			array &$columns,
			string $subSelectSql = '',
			string $subWhereSql = '',
			string $subGroupSql = ''
		)
		use ($prefSql)
		: void
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$subWhereSql .= ' AND files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$subWhereSql .= ' AND files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$select[] = 'COALESCE(CNT_LINK.LINK_COUNT, 0) AS LINK_COUNT';
			$columns[] = 'LINK_COUNT';
			// language=SQL
			$from[] = "
				/* external_link */
				LEFT JOIN (
					SELECT
						pth.PARENT_ID,
						COUNT(DISTINCT link.ID) AS LINK_COUNT
						{$subSelectSql}
					FROM
						b_disk_object_path pth
						INNER JOIN b_disk_object files ON pth.OBJECT_ID = files.ID
						LEFT JOIN b_disk_external_link link ON files.ID = link.OBJECT_ID
					WHERE
						files.TYPE = ".ObjectTable::TYPE_FILE."
						AND link.TYPE != ".Disk\Internals\ExternalLinkTable::TYPE_AUTO."
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						pth.PARENT_ID
						{$subGroupSql}
					{$prefSql}
				) CNT_LINK
					ON CNT_LINK.PARENT_ID = CNT_FILES.PARENT_ID
			";
		};

		/**
		 * sharing
		 */
		$buildSharingSql = function(
			array &$select,
			array &$from,
			array &$where,
			array &$columns,
			string $subSelectSql = '',
			string $subWhereSql = '',
			string $subGroupSql = ''
		)
		use ($prefSql)
		: void
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$subWhereSql .= ' AND files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$subWhereSql .= ' AND files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$select[] = 'COALESCE(CNT_SHARING.SHARING_COUNT, 0) AS SHARING_COUNT';
			$columns[] = 'SHARING_COUNT';
			// language=SQL
			$from[] = "
				/* sharing */
				LEFT JOIN
				(
					SELECT
						pth.PARENT_ID,
						COUNT(DISTINCT sharing.ID) AS SHARING_COUNT
						{$subSelectSql}
					FROM
						b_disk_object_path pth
						INNER JOIN b_disk_object files ON pth.OBJECT_ID = files.ID
						LEFT JOIN b_disk_sharing sharing on files.ID = sharing.REAL_OBJECT_ID
					WHERE
						files.TYPE = ".ObjectTable::TYPE_FILE."
						AND sharing.STATUS = ".SharingTable::STATUS_IS_APPROVED."
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						pth.PARENT_ID
						{$subGroupSql}
					{$prefSql}
				) CNT_SHARING
					ON CNT_FILES.PARENT_ID = CNT_SHARING.PARENT_ID
			";
		};


		/**
		 * with path structure
		 */
		$buildUnnecessaryPathSql = function(
			array &$select,
			array &$from,
			array &$where,
			array &$columns,
			string $subSelectSql = '',
			string $subWhereSql = '',
			string $subGroupSql = ''
		)
		use ($prefSql)
		: void
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$subWhereSql .= ' AND files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$subWhereSql .= ' AND files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$select[] = 'COALESCE(CNT_FREE.UNNECESSARY_VERSION_SIZE, 0) AS UNNECESSARY_VERSION_SIZE';
			$select[] = 'COALESCE(CNT_FREE.UNNECESSARY_VERSION_COUNT, 0) AS UNNECESSARY_VERSION_COUNT';
			$columns[] = 'UNNECESSARY_VERSION_SIZE';
			$columns[] = 'UNNECESSARY_VERSION_COUNT';
			// language=SQL
			$from[] = "
				/* may drop */
				LEFT JOIN
				(
					SELECT
						SUM(src.SIZE) AS UNNECESSARY_VERSION_SIZE,
						SUM(src.CNT) AS UNNECESSARY_VERSION_COUNT,
						src.PARENT_ID
					FROM
					(
						SELECT
							files.ID,
							SUM(ver.SIZE) AS SIZE,
							COUNT(ver.ID) AS CNT,
							pth.PARENT_ID

						FROM
							b_disk_object_path pth
							INNER JOIN b_disk_version ver ON pth.OBJECT_ID = ver.OBJECT_ID
							INNER JOIN b_disk_object files ON ver.OBJECT_ID = files.ID and ver.FILE_ID != files.FILE_ID

							/* head */
							INNER JOIN (
								SELECT  object_id, max(id) as id
								FROM b_disk_version
								GROUP BY object_id
								{$prefSql}
							) head ON head.OBJECT_ID = files.ID

							LEFT JOIN b_disk_attached_object  attached
								ON attached.OBJECT_ID  = ver.OBJECT_ID
								AND attached.VERSION_ID = ver.ID
								AND attached.VERSION_ID != head.ID

							LEFT JOIN b_disk_external_link link
								ON link.OBJECT_ID  = ver.OBJECT_ID
								AND link.VERSION_ID = ver.ID
								AND link.VERSION_ID != head.ID
								AND COALESCE(link.TYPE,-1) != ". Disk\Internals\ExternalLinkTable::TYPE_AUTO. "

						WHERE
							files.TYPE = ". ObjectTable::TYPE_FILE. "
							AND files.ID = files.REAL_OBJECT_ID
							AND attached.VERSION_ID is null /* no attach */
							AND link.VERSION_ID is null /*no link */
							{$subWhereSql}

						GROUP BY
							files.ID,
							pth.PARENT_ID
							{$subGroupSql}
						{$prefSql}
					) src
					GROUP BY
						src.PARENT_ID
					{$prefSql}
				) CNT_FREE
					ON CNT_FREE.PARENT_ID = CNT_FILES.PARENT_ID
			";
		};


		$isRemeasure = ($this->getFilterId() > 0);

		if (is_array($storageId))
		{
			$subWhereSql = 'AND files.STORAGE_ID IN('. implode(',', $storageId). ')';
		}
		else
		{
			$subWhereSql = "AND files.STORAGE_ID = {$storageId}";
		}

		// with path structure
		$this->unsetFilter('FOLDER_ID');
		$this->addFilter('PARENT_ID', $folderId);

		$select = [
			"'{$folderIndicatorType}' as INDICATOR_TYPE",
			"{$ownerId} as OWNER_ID",
			$sqlHelper->getCurrentDateTimeFunction()." as CREATE_TIME ",
		];
		$columns = [
			'INDICATOR_TYPE',
			'OWNER_ID',
			'CREATE_TIME'
		];

		$from = [];
		$where = [];
		$where[] = Volume\QueryHelper::prepareWhere(
			$this->getFilter([
				'=DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			]),
			[
				'STORAGE_ID' => 'folder.STORAGE_ID',
				'PARENT_ID' => 'path.PARENT_ID',
				'FOLDER_ID' => 'path.OBJECT_ID',
				'TITLE' => 'folder.NAME',
				'DELETED_TYPE' => 'folder.DELETED_TYPE',
			]
		);

		$buildDiskPathSql($select, $from, $where, $columns, '', $subWhereSql);

		if (in_array(self::UNNECESSARY_VERSION, $collectData))
		{
			$buildUnnecessaryPathSql($select, $from, $where, $columns, '', $subWhereSql);
		}

		if (in_array(self::PREVIEW_FILE, $collectData))
		{
			$buildPreviewPathSql($select, $from, $where, $columns, '', $subWhereSql);
		}

		if (in_array(self::ATTACHED_OBJECT, $collectData))
		{
			$buildAttachedSql($select, $from, $where, $columns, '', $subWhereSql);
		}

		if (in_array(self::EXTERNAL_LINK, $collectData))
		{
			$buildExternalSql($select, $from, $where, $columns, '', $subWhereSql);
		}

		if (in_array(self::SHARING_OBJECT, $collectData))
		{
			$buildSharingSql($select, $from, $where, $columns, '', $subWhereSql);
		}

		$querySql =
			'SELECT '. implode("\n, ", $select)
			. ' FROM '. implode("\n", $from)
			. ' WHERE '. implode("\n AND ", $where);

		VolumeTable::createTemporally();
		VolumeTable::clearTemporally();
		$tableName = $sqlHelper->quote(VolumeTable::getTableName());
		$temporallyTableName = $sqlHelper->quote(VolumeTable::getTemporallyName());

		$columnList = Volume\QueryHelper::prepareInsert($columns, $this->getSelect());
		$connection->queryExecute("INSERT INTO {$temporallyTableName} ({$columnList}) {$querySql}");

		if ($isRemeasure)
		{
			$updateColumnList = Volume\QueryHelper::prepareUpdateOnSelect(
				array_diff($columns, ['STORAGE_ID', 'FOLDER_ID', 'PARENT_ID']),
				$this->getSelect(),
				'destinationTbl',
				'sourceQuery'
			);
			$querySql = $sqlHelper->prepareCorrelatedUpdate(
				$tableName, 'destinationTbl',
				$updateColumnList,
				"(SELECT {$columnList} FROM {$temporallyTableName}) sourceQuery",
				"
					destinationTbl.INDICATOR_TYPE = '{$folderIndicatorType}'
					AND destinationTbl.OWNER_ID = {$ownerId}
					AND destinationTbl.STORAGE_ID = sourceQuery.STORAGE_ID
					AND destinationTbl.FOLDER_ID = sourceQuery.FOLDER_ID
					AND COALESCE(destinationTbl.PARENT_ID, -1) = COALESCE(sourceQuery.PARENT_ID, -1)
				"
			);
		}
		else
		{
			$querySql = "INSERT INTO {$tableName} ({$columnList}) SELECT {$columnList} FROM {$temporallyTableName}";
		}

		if ($connection->lock(self::$lockName, self::$lockTimeout))
		{
			$connection->queryExecute($querySql);
			$connection->unlock(self::$lockName);
		}
		else
		{
			throw new Main\SystemException('Cannot get table lock for '.static::className(), self::ERROR_LOCK_TIMEOUT);
		}

		$this->recalculatePercent();

		return $this;
	}


	/**
	 * Recalculates percent from total file size per row selected by filter.
	 * @param string|Volume\IVolumeIndicator $totalSizeIndicator Use this indicator as total volume.
	 * @param string|Volume\IVolumeIndicator $excludeSizeIndicator Exclude indicator's volume from total volume.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return static
	 */
	public function recalculatePercent($totalSizeIndicator = '\\Bitrix\\Disk\\Volume\\Storage\\Storage', $excludeSizeIndicator = null): self
	{
		if (is_string($totalSizeIndicator) && !empty($totalSizeIndicator) && class_exists($totalSizeIndicator))
		{
			/** @var Volume\Storage\Storage $totalSizeIndicator */
			$totalSizeIndicator = new $totalSizeIndicator();
		}
		if (!($totalSizeIndicator instanceof Volume\IVolumeIndicator))
		{
			throw new \Bitrix\Main\ArgumentException('Wrong parameter totalSizeIndicator');
		}

		$storageId = $this->getFilterValue('STORAGE_ID', '=@');

		$totalSizeIndicator->setOwner($this->getOwner());
		$totalSizeIndicator->addFilter('STORAGE_ID', $storageId);
		$totalSizeIndicator->loadTotals();

		if($totalSizeIndicator->getTotalSize() > 0)
		{
			$connection = Application::getConnection();
			$tableName = VolumeTable::getTableName();
			$filter = [
				'=INDICATOR_TYPE' => Volume\Folder::className(),
				'=OWNER_ID' => $this->getOwner(),
				'=STORAGE_ID' => $storageId,
				'>FILE_COUNT' => 0,
			];

			$where = Query::buildFilterSql(VolumeTable::getEntity(), $filter);

			$total = $totalSizeIndicator->getTotalSize() + $totalSizeIndicator->getPreviewSize();

			$sql = 'UPDATE '.$tableName.' SET PERCENT = ROUND((FILE_SIZE + PREVIEW_SIZE) * 100 / '.$total.', 4) WHERE '.$where;

			if ($connection->lock(self::$lockName, self::$lockTimeout))
			{
				$connection->queryExecute($sql);
				$connection->unlock(self::$lockName);
			}
		}
		return $this;
	}

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return double[]
	 */
	public function loadTotals()
	{
		$filter = $this->getFilter(
			[
				'=INDICATOR_TYPE' => Volume\Folder::className(),
				'=OWNER_ID' => $this->getOwner(),
				'>FILE_COUNT' => 0,
				'>FILES_LEFT' => 0,
			],
			VolumeTable::getEntity()
		);
		// nested level folder's total results
		$folderId = $this->getFilterValue('FOLDER_ID', '=!@');
		if (!is_null($folderId))
		{
			if (is_array($folderId))
			{
				$filter['@PARENT_ID'] = $folderId;
			}
			else
			{
				$filter['=PARENT_ID'] = $folderId;
			}
		}
		$row = VolumeTable::getRow([
			'runtime' => [
				new Entity\ExpressionField('CNT', 'COUNT(*)'),
				new Entity\ExpressionField('FILE_SIZE', 'SUM(FILE_SIZE)'),
				new Entity\ExpressionField('FILE_COUNT', 'SUM(FILE_COUNT)'),
				new Entity\ExpressionField('DISK_SIZE', 'SUM(DISK_SIZE)'),
				new Entity\ExpressionField('DISK_COUNT', 'SUM(DISK_COUNT)'),
				new Entity\ExpressionField('VERSION_COUNT', 'SUM(VERSION_COUNT)'),
				new Entity\ExpressionField('PREVIEW_SIZE', 'SUM(PREVIEW_SIZE)'),
				new Entity\ExpressionField('PREVIEW_COUNT', 'SUM(PREVIEW_COUNT)'),
				//new Entity\ExpressionField('ATTACHED_COUNT', 'SUM(ATTACHED_COUNT)'),
				//new Entity\ExpressionField('LINK_COUNT', 'SUM(LINK_COUNT)'),
				//new Entity\ExpressionField('SHARING_COUNT', 'SUM(SHARING_COUNT)'),
				new Entity\ExpressionField('UNNECESSARY_VERSION_SIZE', 'SUM(UNNECESSARY_VERSION_SIZE)'),
				new Entity\ExpressionField('UNNECESSARY_VERSION_COUNT', 'SUM(UNNECESSARY_VERSION_COUNT)'),
			],
			'select' => [
				'CNT',
				'FILE_SIZE',
				'FILE_COUNT',
				'DISK_SIZE',
				'DISK_COUNT',
				'VERSION_COUNT',
				'PREVIEW_SIZE',
				'PREVIEW_COUNT',
				//'ATTACHED_COUNT',
				//'LINK_COUNT',
				//'SHARING_COUNT',
				'UNNECESSARY_VERSION_SIZE',
				'UNNECESSARY_VERSION_COUNT',
			],
			'filter' => $filter
		]);
		if ($row)
		{
			$this->resultAvailable = (bool)($row['CNT'] > 0);
			$this->totalSize = (double)$row['FILE_SIZE'];
			$this->totalCount = (double)$row['FILE_COUNT'];
			$this->diskSize = (double)$row['DISK_SIZE'];
			$this->diskCount = (double)$row['DISK_COUNT'];
			$this->totalVersion = (double)$row['VERSION_COUNT'];
			$this->previewSize = (double)$row['PREVIEW_SIZE'];
			$this->previewCount = (double)$row['PREVIEW_COUNT'];
			//$this->totalAttached = (double)$row['ATTACHED_COUNT'];
			//$this->totalLink = (double)$row['LINK_COUNT'];
			//$this->totalSharing = (double)$row['SHARING_COUNT'];
			$this->unnecessaryVersionSize = (double)$row['UNNECESSARY_VERSION_SIZE'];
			$this->unnecessaryVersionCount = (double)$row['UNNECESSARY_VERSION_COUNT'];
		}

		return $row;
	}

	/**
	 * Deletes objects selecting by filter.
	 * @return static
	 */
	public function purify(): self
	{
		$connection = Application::getConnection();
		$tableName = VolumeTable::getTableName();
		$filter = $this->getFilter(
			[
				'=INDICATOR_TYPE' => Volume\Folder::className(),
				'=OWNER_ID' => $this->getOwner(),
			],
			VolumeTable::getEntity()
		);

		$filterParent = array_intersect(array_keys($filter), ['PARENT_ID', '=PARENT_ID', '@PARENT_ID']);
		$parentKeyId = array_shift($filterParent);
		if ($parentKeyId && isset($filter[$parentKeyId]))
		{
			$filter[] = [
				'LOGIC' => 'OR',
				'PARENT_ID' => $filter[$parentKeyId],
				'FOLDER_ID' => $filter[$parentKeyId],
			];
			unset($filter[$parentKeyId]);
		}

		$where = Query::buildFilterSql(VolumeTable::getEntity(), $filter);
		$sql = 'DELETE FROM ' . $tableName . ' WHERE ' . $where;
		$connection->queryExecute($sql);

		return $this;
	}
}

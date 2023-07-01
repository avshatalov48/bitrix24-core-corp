<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Internals\ObjectPathTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Folder extends Volume\Base implements Volume\IVolumeIndicatorLink, Volume\IVolumeIndicatorParent, Volume\IDeleteConstraint
{
	/**
	 * Preforms data preparation.
	 * @return static
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentException
	 */
	public function prepareData(): self
	{
		$storageId = $this->getFilterValue('STORAGE_ID', '=@');

		if (empty($storageId))
		{
			throw new Main\ArgumentException('Undefined filter parameter: STORAGE_ID');
		}

		// ObjectPathTable::recalculate();
		ObjectPathTable::recalculateByStorage($storageId);

		return $this;
	}

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
		$indicatorType = $sqlHelper->forSql(static::className());
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

		/**
		 * no path structure
		 */
		$buildDiskSql = function(
			array &$select,
			array &$from,
			array &$where,
			array &$columns,
			string $subSelectSql = '',
			string $subWhereSql = '',
			string $subGroupSql = ''
		): void
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
				'folder.ID as FOLDER_ID',
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
						files.PARENT_ID,
						SUM(versions.SIZE) AS FILE_SIZE,
						COUNT(DISTINCT versions.ID) AS FILE_COUNT,
						SUM(versions.SIZE) AS DISK_SIZE,
						COUNT(DISTINCT files.ID) AS DISK_COUNT,
						COUNT(DISTINCT versions.ID) AS VERSION_COUNT
						{$subSelectSql}
					FROM
						b_disk_object files
						LEFT JOIN b_disk_version versions ON files.ID = versions.OBJECT_ID
					WHERE
						files.TYPE = ".ObjectTable::TYPE_FILE."
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						files.PARENT_ID
						{$subGroupSql}
					ORDER BY NULL
				) CNT_FILES

				INNER JOIN b_disk_object folder ON folder.ID = CNT_FILES.PARENT_ID

				INNER JOIN b_disk_storage storage ON storage.ID = folder.STORAGE_ID
			";
			$where[] = 'folder.TYPE = '. ObjectTable::TYPE_FOLDER;
		};

		/**
		 * preview
		 */
		$buildPreviewSql = function(
			array &$select,
			array &$from,
			array &$where,
			array &$columns,
			string $subSelectSql = '',
			string $subWhereSql = '',
			string $subGroupSql = ''
		): void
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
						SUM(IFNULL(preview_file.FILE_SIZE, 0)) + SUM(IFNULL(view_file.FILE_SIZE, 0)) AS PREVIEW_SIZE,
						COUNT(DISTINCT preview_file.ID) + COUNT(DISTINCT view_file.ID) AS PREVIEW_COUNT,
						files.PARENT_ID
					FROM
						b_disk_object files
						LEFT JOIN b_file preview_file ON preview_file.ID = files.PREVIEW_ID
						LEFT JOIN b_file view_file ON view_file.ID = files.VIEW_ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						files.PARENT_ID
						{$subGroupSql}
					ORDER BY NULL
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
		): void
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

			$select[] = 'IFNULL(CNT_ATTACH.ATTACHED_COUNT, 0) AS ATTACHED_COUNT';
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
					ORDER BY NULL
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
		): void
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

			$select[] = 'IFNULL(CNT_LINK.LINK_COUNT, 0) AS LINK_COUNT';
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
					ORDER BY NULL
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
		): void
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

			$select[] = 'IFNULL(CNT_SHARING.SHARING_COUNT, 0) AS SHARING_COUNT';
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
					ORDER BY NULL
				) CNT_SHARING
					ON CNT_FILES.PARENT_ID = CNT_SHARING.PARENT_ID
			";
		};

		/**
		 * no path structure
		 */
		$buildUnnecessarySql = function(
			array &$select,
			array &$from,
			array &$where,
			array &$columns,
			string $subSelectSql = '',
			string $subWhereSql = '',
			string $subGroupSql = ''
		): void
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

			$select[] = 'IFNULL(CNT_FREE.UNNECESSARY_VERSION_SIZE, 0) AS UNNECESSARY_VERSION_SIZE';
			$select[] = 'IFNULL(CNT_FREE.UNNECESSARY_VERSION_COUNT, 0) AS UNNECESSARY_VERSION_COUNT';
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
							files.PARENT_ID

						FROM
							b_disk_version ver
							INNER JOIN b_disk_object files ON ver.OBJECT_ID = files.ID and ver.FILE_ID != files.FILE_ID

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
							files.PARENT_ID
							{$subGroupSql}
						ORDER BY NULL
					) src
					GROUP BY
						src.PARENT_ID
					ORDER BY NULL
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

		$this->addFilter('FOLDER_ID', $folderId);

		$select = [];
		$columns = [];
		if (!$isRemeasure)
		{
			$select[] = "'{$indicatorType}' as INDICATOR_TYPE";
			$select[] = "{$ownerId} as OWNER_ID";
			$select[] = $connection->getSqlHelper()->getCurrentDateTimeFunction()." as CREATE_TIME ";
			$columns[] = 'INDICATOR_TYPE';
			$columns[] = 'OWNER_ID';
			$columns[] = 'CREATE_TIME';
		}

		$from = [];
		$where = [];
		$where[] = Volume\QueryHelper::prepareWhere(
			$this->getFilter([
				'=DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			]),
			[
				'STORAGE_ID' => 'folder.STORAGE_ID',
				'PARENT_ID' => 'folder.PARENT_ID',
				'FOLDER_ID' => 'folder.ID',
				'TITLE' => 'folder.NAME',
				'DELETED_TYPE' => 'folder.DELETED_TYPE',
			]
		);

		$buildDiskSql($select, $from, $where, $columns, '', $subWhereSql);

		if (in_array(self::UNNECESSARY_VERSION, $collectData))
		{
			$buildUnnecessarySql($select, $from, $where, $columns, '', $subWhereSql);
		}

		if (in_array(self::PREVIEW_FILE, $collectData))
		{
			$buildPreviewSql($select, $from, $where, $columns, '', $subWhereSql);
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
		$tableName = VolumeTable::getTableName();
		$temporallyTableName = VolumeTable::getTemporallyName();

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
			$querySql = "
				UPDATE
					{$tableName} destinationTbl,
					(SELECT {$columnList} FROM {$temporallyTableName}) sourceQuery
				SET {$updateColumnList}
				WHERE
					destinationTbl.INDICATOR_TYPE = '{$indicatorType}'
					AND destinationTbl.OWNER_ID = {$ownerId}
					AND destinationTbl.STORAGE_ID = sourceQuery.STORAGE_ID
					AND destinationTbl.FOLDER_ID = sourceQuery.FOLDER_ID
					AND IFNULL(destinationTbl.PARENT_ID, -1) = IFNULL(sourceQuery.PARENT_ID, -1)
			";
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
			throw new Main\SystemException('Cannot get table lock for '.$indicatorType, self::ERROR_LOCK_TIMEOUT);
		}

		//VolumeTable::clearTemporally();

		$this->recalculatePercent();

		return $this;
	}


	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string|null
	 * @throws ArgumentTypeException
	 */
	public static function getTitle(Volume\Fragment $fragment): ?string
	{
		$folder = $fragment->getFolder();
		if (!$folder instanceof Disk\Folder)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Folder::className());
		}

		return $folder->getOriginalName();
	}

	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string|null
	 * @throws ArgumentTypeException
	 */
	public static function getUrl(Volume\Fragment $fragment): ?string
	{
		$folder = $fragment->getFolder();
		if (!$folder instanceof Disk\Folder)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Folder::className());
		}

		if (in_array($fragment->getEntityType(), Volume\Module\Im::getEntityType()))
		{
			return null;
		}
		if (in_array($fragment->getEntityType(), Volume\Module\Mail::getEntityType()))
		{
			return null;
		}
		if (in_array($fragment->getEntityType(), Volume\Module\Documentgenerator::getEntityType()))
		{
			return null;
		}

		$urlManager = Disk\Driver::getInstance()->getUrlManager();

		return $urlManager->getUrlFocusController('openFolderList', ['folderId' => $folder->getId()]);
	}

	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string[]
	 * @throws ArgumentTypeException
	 */
	public static function getParents(Volume\Fragment $fragment): array
	{
		$folder = $fragment->getFolder();
		if (!$folder instanceof Disk\Folder)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Folder::className());
		}

		$parents = Disk\CrumbStorage::getInstance()->getByObject($folder);

		return array_slice($parents, 1, count($parents) - 1, true);
	}

	/**
	 * Returns last update time of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return \Bitrix\Main\Type\DateTime|null
	 * @throws ArgumentTypeException
	 */
	public static function getUpdateTime(Volume\Fragment $fragment): ?\Bitrix\Main\Type\DateTime
	{
		$folder = $fragment->getFolder();
		if (!$folder instanceof Disk\Folder)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Folder::className());
		}

		$updateTime = $folder->getUpdateTime()->toUserTime();

		return $updateTime;
	}

	/**
	 * Check ability to drop folder.
	 * @param Disk\Folder $folder Folder to drop.
	 * @return boolean
	 */
	public function isAllowDeleteFolder(Disk\Folder $folder): bool
	{
		return (bool)($folder->isRoot() !== true);
	}
}

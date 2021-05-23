<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Internals\ObjectPathTable;
use Bitrix\Disk\Volume;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Folder extends Volume\Base implements Volume\IVolumeIndicatorLink, Volume\IVolumeIndicatorParent, Volume\IDeleteConstraint
{
	/**
	 * Preforms data preparation.
	 * @return $this
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentException
	 */
	public function prepareData()
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
	 * @return $this
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public function measure($collectData = array(self::DISK_FILE, self::PREVIEW_FILE, self::UNNECESSARY_VERSION))
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$indicatorType = $sqlHelper->forSql(static::className());
		$ownerId = (string)$this->getOwner();

		$storageId = $this->getFilterValue('STORAGE_ID', '=@');
		$parentId = $this->getFilterValue('PARENT_ID', '=@');
		$folderId = $this->getFilterValue('FOLDER_ID', '=@');


		if (is_null($storageId))
		{
			throw new Main\ArgumentException('Undefined filter parameter: STORAGE_ID');
		}
		if (is_null($parentId) && is_null($folderId))
		{
			throw new Main\ArgumentException('Undefined filter parameters: PARENT_ID and FOLDER_ID');
		}

		/**
		 * with path structure
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @param string $subGroupSql
		 * @return void
		 */
		$buildDiskPathSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			/* path.PARENT_ID as top_id, */
			/* path.DEPTH_LEVEL as depth_level, */
			/* top_folder.NAME as top_folder_name, */
			$selectSql .= "
				, CNT_FILES.FILE_SIZE
				, CNT_FILES.FILE_COUNT
				, CNT_FILES.DISK_SIZE
				, CNT_FILES.DISK_COUNT
				, CNT_FILES.VERSION_COUNT
				, folder.STORAGE_ID
				, path.OBJECT_ID as FOLDER_ID
				, folder.PARENT_ID as PARENT_ID
				, folder.NAME  as TITLE
				, storage.ENTITY_TYPE
				, storage.ENTITY_ID
			";
			$columns = array_merge($columns, array(
				'FILE_SIZE',
				'FILE_COUNT',
				'DISK_SIZE',
				'DISK_COUNT',
				'VERSION_COUNT',
				'STORAGE_ID',
				'FOLDER_ID',
				'PARENT_ID',
				'TITLE',
				'ENTITY_TYPE',
				'ENTITY_ID',
			));
			// language=SQL
			$fromSql .= "
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
						AND {$fileDeletedTypeSql}
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY 
						pth.PARENT_ID
						{$subGroupSql}
					ORDER BY NULL
				) CNT_FILES

				INNER JOIN b_disk_object_path path ON CNT_FILES.PARENT_ID = path.OBJECT_ID

				INNER JOIN b_disk_object top_folder ON top_folder.ID = path.PARENT_ID 

				INNER JOIN b_disk_object folder ON folder.ID = CNT_FILES.PARENT_ID

				INNER JOIN b_disk_storage storage ON storage.ID = folder.STORAGE_ID 
			";
			$whereSql .= "
				AND folder.TYPE = ".ObjectTable::TYPE_FOLDER."
			";
		};

		/**
		 * no path structure
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @param string $subGroupSql
		 * @return void
		 */
		$buildDiskSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '') use ($folderId)
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			/*, folder.PARENT_ID as PARENT_ID */
			$selectSql .= "
				, CNT_FILES.FILE_SIZE
				, CNT_FILES.FILE_COUNT
				, CNT_FILES.DISK_SIZE
				, CNT_FILES.DISK_COUNT
				, CNT_FILES.VERSION_COUNT
				, folder.STORAGE_ID
				, folder.ID as FOLDER_ID
				, {$folderId} as PARENT_ID
				, folder.NAME  as TITLE
				, storage.ENTITY_TYPE
				, storage.ENTITY_ID
			";
			$columns = array_merge($columns, array(
				'FILE_SIZE',
				'FILE_COUNT',
				'DISK_SIZE',
				'DISK_COUNT',
				'VERSION_COUNT',
				'STORAGE_ID',
				'FOLDER_ID',
				'PARENT_ID',
				'TITLE',
				'ENTITY_TYPE',
				'ENTITY_ID',
			));
			// language=SQL
			$fromSql .= "
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
						AND {$fileDeletedTypeSql}
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
			$whereSql .= "
				AND folder.TYPE = ".ObjectTable::TYPE_FOLDER."
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
		$buildPreviewPathSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$selectSql .= "
				, CNT_PREVIEW.PREVIEW_SIZE AS PREVIEW_SIZE
				, CNT_PREVIEW.PREVIEW_COUNT AS PREVIEW_COUNT
			";
			$columns = array_merge($columns, array(
				'PREVIEW_SIZE',
				'PREVIEW_COUNT',
			));
			// language=SQL
			$fromSql .= "
				/* preview */
				LEFT JOIN 
				(
					SELECT
						SUM(IFNULL(preview_file.FILE_SIZE, 0)) + SUM(IFNULL(view_file.FILE_SIZE, 0)) AS PREVIEW_SIZE,
						COUNT(DISTINCT preview_file.ID) + COUNT(DISTINCT view_file.ID) AS PREVIEW_COUNT,
						pth.PARENT_ID
					FROM
						b_disk_object_path pth 
						INNER JOIN b_disk_object files ON pth.OBJECT_ID = files.ID
						LEFT JOIN b_file preview_file ON preview_file.ID = files.PREVIEW_ID
						LEFT JOIN b_file view_file ON view_file.ID = files.VIEW_ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND {$fileDeletedTypeSql}
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						pth.PARENT_ID
						{$subGroupSql}
					ORDER BY NULL
				) CNT_PREVIEW
					ON CNT_PREVIEW.PARENT_ID = CNT_FILES.PARENT_ID
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
		$buildPreviewSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$selectSql .= "
				, CNT_PREVIEW.PREVIEW_SIZE AS PREVIEW_SIZE
				, CNT_PREVIEW.PREVIEW_COUNT AS PREVIEW_COUNT
			";
			$columns = array_merge($columns, array(
				'PREVIEW_SIZE',
				'PREVIEW_COUNT',
			));
			// language=SQL
			$fromSql .= "
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
						AND {$fileDeletedTypeSql}
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
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @param string $subGroupSql
		 * @return void
		 */
		$buildAttachedSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$selectSql .= ', IFNULL(CNT_ATTACH.ATTACHED_COUNT, 0) AS ATTACHED_COUNT ';
			$columns = array_merge($columns, array(
				'ATTACHED_COUNT',
			));
			// language=SQL
			$fromSql .= "
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
						AND {$fileDeletedTypeSql}
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
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @param string $subGroupSql
		 * @return void
		 */
		$buildExternalSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$selectSql .= ', IFNULL(CNT_LINK.LINK_COUNT, 0) AS LINK_COUNT ';
			$columns = array_merge($columns, array(
				'LINK_COUNT',
			));
			// language=SQL
			$fromSql .= "
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
						AND link.TYPE != ".\Bitrix\Disk\Internals\ExternalLinkTable::TYPE_AUTO."
						AND {$fileDeletedTypeSql}
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
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @param string $subGroupSql
		 * @return void
		 */
		$buildSharingSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$selectSql .= ', IFNULL(CNT_SHARING.SHARING_COUNT, 0) AS SHARING_COUNT ';
			$columns = array_merge($columns, array(
				'SHARING_COUNT',
			));
			// language=SQL
			$fromSql .= "
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
						AND {$fileDeletedTypeSql}
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
		 * with path structure
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @param string $subGroupSql
		 * @return void
		 */
		$buildUnnecessaryPathSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$selectSql .= "
				, IFNULL(CNT_FREE.UNNECESSARY_VERSION_SIZE, 0) AS UNNECESSARY_VERSION_SIZE
				, IFNULL(CNT_FREE.UNNECESSARY_VERSION_COUNT, 0) AS UNNECESSARY_VERSION_COUNT
			";
			$columns = array_merge($columns, array(
				'UNNECESSARY_VERSION_SIZE',
				'UNNECESSARY_VERSION_COUNT',
			));
			// language=SQL
			$fromSql .= "
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
								AND ifnull(link.TYPE,-1) != ". \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_AUTO. "

						WHERE 
							files.TYPE = ". ObjectTable::TYPE_FILE. "
							AND files.ID = files.REAL_OBJECT_ID
							AND attached.VERSION_ID is null /* no attach */
							AND link.VERSION_ID is null /*no link */
							AND {$fileDeletedTypeSql}
							{$subWhereSql}
							
						GROUP BY 
							files.ID,
							pth.PARENT_ID
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


		/**
		 * no path structure
		 * @param string $selectSql
		 * @param string $fromSql
		 * @param string $whereSql
		 * @param string[] $columns
		 * @param string $subSelectSql
		 * @param string $subWhereSql
		 * @param string $subGroupSql
		 * @return void
		 */
		$buildUnnecessarySql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '')
		{
			$deletedType = $this->getFilterValue('DELETED_TYPE', '=@');
			if (!empty($deletedType) && $deletedType !== ObjectTable::DELETED_TYPE_NONE)
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE != '.ObjectTable::DELETED_TYPE_NONE;
			}
			else
			{
				$fileDeletedTypeSql = 'files.DELETED_TYPE = '.ObjectTable::DELETED_TYPE_NONE;
			}

			$selectSql .= "
				, IFNULL(CNT_FREE.UNNECESSARY_VERSION_SIZE, 0) AS UNNECESSARY_VERSION_SIZE
				, IFNULL(CNT_FREE.UNNECESSARY_VERSION_COUNT, 0) AS UNNECESSARY_VERSION_COUNT
			";
			$columns = array_merge($columns, array(
				'UNNECESSARY_VERSION_SIZE',
				'UNNECESSARY_VERSION_COUNT',
			));
			// language=SQL
			$fromSql .= "
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
								AND ifnull(link.TYPE,-1) != ". \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_AUTO. "

						WHERE 
							files.TYPE = ". ObjectTable::TYPE_FILE. "
							AND files.ID = files.REAL_OBJECT_ID
							AND attached.VERSION_ID is null /* no attach */
							AND link.VERSION_ID is null /*no link */
							AND {$fileDeletedTypeSql}
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

		$queries = array();

		if (is_array($storageId))
		{
			$subWhereSql = 'AND files.STORAGE_ID IN('. implode(',', $storageId). ')';
		}
		else
		{
			$subWhereSql = "AND files.STORAGE_ID = {$storageId}";
		}

		// with path structure
		if (!is_null($parentId))
		{
			$this->unsetFilter('FOLDER_ID');
			$this->addFilter('PARENT_ID', $parentId);

			$selectSql = "";
			$fromSql = "";

			$whereSql = Volume\QueryHelper::prepareWhere(
				$this->getFilter(array(
					'=DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
				)),
				array(
					'STORAGE_ID' => 'folder.STORAGE_ID',
					'PARENT_ID' => 'path.PARENT_ID',
					'FOLDER_ID' => 'path.OBJECT_ID',
					'TITLE' => 'folder.NAME',
					'DELETED_TYPE' => 'folder.DELETED_TYPE',
				)
			);

			$columns = array(
				'INDICATOR_TYPE',
				'OWNER_ID',
				'CREATE_TIME',
			);

			$buildDiskPathSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);

			if (in_array(self::UNNECESSARY_VERSION, $collectData))
			{
				$buildUnnecessaryPathSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}

			if (in_array(self::PREVIEW_FILE, $collectData))
			{
				$buildPreviewPathSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}

			if (in_array(self::ATTACHED_OBJECT, $collectData))
			{
				$buildAttachedSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}

			if (in_array(self::EXTERNAL_LINK, $collectData))
			{
				$buildExternalSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}

			if (in_array(self::SHARING_OBJECT, $collectData))
			{
				$buildSharingSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}


			$queries[] = "
				SELECT 
					'{$indicatorType}' as INDICATOR_TYPE,
					{$ownerId} as OWNER_ID,
					".$connection->getSqlHelper()->getCurrentDateTimeFunction()." as CREATE_TIME
					{$selectSql}
				FROM 
					{$fromSql}
				WHERE
					1 = 1
					{$whereSql}
			";
		}

		// no path structure
		if (!is_null($folderId))
		{
			$this->unsetFilter('PARENT_ID');
			$this->addFilter('FOLDER_ID', $folderId);

			$selectSql = '';
			$fromSql = '';
			$whereSql = Volume\QueryHelper::prepareWhere(
				$this->getFilter(array(
					'=DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
				)),
				array(
					'STORAGE_ID' => 'folder.STORAGE_ID',
					'PARENT_ID' => 'folder.PARENT_ID',
					'FOLDER_ID' => 'folder.ID',
					'TITLE' => 'folder.NAME',
					'DELETED_TYPE' => 'folder.DELETED_TYPE',
				)
			);

			$columns = array(
				'INDICATOR_TYPE',
				'OWNER_ID',
				'CREATE_TIME',
			);

			$buildDiskSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);

			if (in_array(self::UNNECESSARY_VERSION, $collectData))
			{
				$buildUnnecessarySql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}

			if (in_array(self::PREVIEW_FILE, $collectData))
			{
				$buildPreviewSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}

			if (in_array(self::ATTACHED_OBJECT, $collectData))
			{
				$buildAttachedSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}

			if (in_array(self::EXTERNAL_LINK, $collectData))
			{
				$buildExternalSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}

			if (in_array(self::SHARING_OBJECT, $collectData))
			{
				$buildSharingSql($selectSql, $fromSql, $whereSql, $columns, '', $subWhereSql);
			}


			$queries[] = "
				SELECT 
					'{$indicatorType}' as INDICATOR_TYPE,
					{$ownerId} as OWNER_ID,
					". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME
					{$selectSql}
				FROM 
					{$fromSql}
				WHERE
					1 = 1
					{$whereSql}
			";
		}

		$querySql = implode("\n\n UNION \n\n", $queries);

		VolumeTable::createTemporally();
		$tableName = VolumeTable::getTableName();
		$temporallyTableName = VolumeTable::getTemporallyName();

		$columnList = Volume\QueryHelper::prepareInsert($columns, $this->getSelect());
		$connection->queryExecute("INSERT INTO {$temporallyTableName} ({$columnList}) {$querySql}");

		$temporallyDataSource = "SELECT {$columnList} FROM {$temporallyTableName}";

		if ($this->getFilterId() > 0)
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
					AND destinationTbl.STORAGE_ID = sourceQuery.STORAGE_ID
					AND destinationTbl.FOLDER_ID = sourceQuery.FOLDER_ID
					AND IFNULL(destinationTbl.PARENT_ID, -1) = IFNULL(sourceQuery.PARENT_ID, -1)
			";
		}
		else
		{
			$querySql = "INSERT INTO {$tableName} ({$columnList}) {$temporallyDataSource}";
		}

		if (!$connection->lock(self::$lockName, self::$lockTimeout))
		{
			throw new Main\SystemException('Cannot get table lock for '.$indicatorType);
		}

		$connection->queryExecute($querySql);

		$connection->unlock(self::$lockName);

		VolumeTable::dropTemporally();

		$this->recalculatePercent();

		return $this;
	}

	/**
	 * Recalculates percent from total file size per row selected by filter.
	 * @param string|Volume\IVolumeIndicator $totalSizeIndicator Use this indicator as total volume.
	 * @param string|Volume\IVolumeIndicator $excludeSizeIndicator Exclude indicator's volume from total volume.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return self
	 */
	public function recalculatePercent($totalSizeIndicator = '\\Bitrix\\Disk\\Volume\\Storage\\Storage', $excludeSizeIndicator = null)
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
			$filter = array(
				'=INDICATOR_TYPE' => static::className(),
				'=OWNER_ID' => $this->getOwner(),
				'=STORAGE_ID' => $storageId,
				'>FILE_COUNT' => 0,
			);

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
			array(
				'=INDICATOR_TYPE' => static::className(),
				'=OWNER_ID' => $this->getOwner(),
				'>FILE_COUNT' => 0,
			),
			VolumeTable::getEntity()
		);
		// exclude nested level folder's total results
		if (is_null($this->getFilterValue('PARENT_ID', '=@')))
		{
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
		}


		$row = VolumeTable::getRow(array(
			'runtime' => array(
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
			),
			'select' => array(
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
			),
			'filter' => $filter
		));
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
	 * @return $this
	 */
	public function purify()
	{
		$connection = Application::getConnection();
		$tableName = VolumeTable::getTableName();
		$filter = $this->getFilter(
			array(
				'=INDICATOR_TYPE' => static::className(),
				'=OWNER_ID' => $this->getOwner(),
			),
			VolumeTable::getEntity()
		);

		$parentKeyId = array_shift(array_intersect(array_keys($filter), array('PARENT_ID', '=PARENT_ID', '@PARENT_ID')));
		if ($parentKeyId && isset($filter[$parentKeyId]))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'PARENT_ID' => $filter[$parentKeyId],
				'FOLDER_ID' => $filter[$parentKeyId],
			);
			unset($filter[$parentKeyId]);
		}

		$where = Query::buildFilterSql(VolumeTable::getEntity(), $filter);
		$sql = 'DELETE FROM ' . $tableName . ' WHERE ' . $where;
		$connection->queryExecute($sql);

		return $this;
	}


	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string
	 * @throws ArgumentTypeException
	 */
	public static function getTitle(Volume\Fragment $fragment)
	{
		$folder = $fragment->getFolder();
		if (!$folder instanceof \Bitrix\Disk\Folder)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.\Bitrix\Disk\Folder::className());
		}

		$title = $folder->getOriginalName();

		return $title;
	}

	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string
	 * @throws ArgumentTypeException
	 */
	public static function getUrl(Volume\Fragment $fragment)
	{
		$folder = $fragment->getFolder();
		if (!$folder instanceof \Bitrix\Disk\Folder)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.\Bitrix\Disk\Folder::className());
		}

		if (in_array($fragment->getEntityType(), \Bitrix\Disk\Volume\Module\Im::getEntityType()))
		{
			return null;
		}
		if (in_array($fragment->getEntityType(), \Bitrix\Disk\Volume\Module\Mail::getEntityType()))
		{
			return null;
		}

		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();

		$url = $urlManager->getUrlFocusController('openFolderList', array('folderId' => $folder->getId()));

		return $url;
	}

	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string[]
	 * @throws ArgumentTypeException
	 */
	public static function getParents(Volume\Fragment $fragment)
	{
		$folder = $fragment->getFolder();
		if (!$folder instanceof \Bitrix\Disk\Folder)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.\Bitrix\Disk\Folder::className());
		}

		$parents = \Bitrix\Disk\CrumbStorage::getInstance()->getByObject($folder);

		return array_slice($parents, 1, count($parents) - 1, true);
	}

	/**
	 * Returns last update time of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return \Bitrix\Main\Type\DateTime|null
	 * @throws ArgumentTypeException
	 */
	public static function getUpdateTime(Volume\Fragment $fragment)
	{
		$folder = $fragment->getFolder();
		if (!$folder instanceof \Bitrix\Disk\Folder)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.\Bitrix\Disk\Folder::className());
		}

		$updateTime = $folder->getUpdateTime()->toUserTime();

		return $updateTime;
	}

	/**
	 * Check ability to drop folder.
	 * @param \Bitrix\Disk\Folder $folder Folder to drop.
	 * @return boolean
	 */
	public function isAllowDeleteFolder(\Bitrix\Disk\Folder $folder)
	{
		return (bool)($folder->isRoot() !== true);
	}
}

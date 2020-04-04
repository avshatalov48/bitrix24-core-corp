<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main\Application;
use Bitrix\Main\DB;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Volume;
use Bitrix\Main\Entity;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class FileType extends Volume\Base
{
	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return $this
	 */
	public function measure($collectData = array(self::DISK_FILE, self::PREVIEW_FILE))
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$folderId = $this->getFilterValue('FOLDER_ID', '=@!');
		if (!empty($folderId))
		{
			$this
				->addSelect('FOLDER_ID', "'$folderId'")
				->addGroupBy('FOLDER_ID', 'files.PARENT_ID');
		}

		$parentFolderId = $this->getFilterValue('PARENT_ID', '=@!');
		if (!empty($parentFolderId))
		{
			$this
				->addSelect('PARENT_ID', "'$parentFolderId'")
				->unsetFilter('PARENT_ID')
				->addFilter('@PARENT_ID', Volume\QueryHelper::prepareFolderTreeQuery($parentFolderId));
		}

		$subSelectSql = Volume\QueryHelper::prepareSelect($this->getSelect());

		$subWhereSql = Volume\QueryHelper::prepareWhere(
			$this->getFilter(),
			array(
				'MODULE_ID' => 'storage.MODULE_ID',
				'STORAGE_ID' => 'files.STORAGE_ID',
				'FOLDER_ID' => 'files.PARENT_ID',
				'PARENT_ID' => 'files.PARENT_ID',
			)
		);

		$subGroupSql = Volume\QueryHelper::prepareGroupBy($this->getGroupBy());


		$queryTypeFileSql = '';
		$typeFileList = TypeFile::getListOfValues();
		foreach ($typeFileList as $fileType)
		{
			switch ($fileType)
			{
				case TypeFile::UNKNOWN:
					break;
				case TypeFile::KNOWN:
				case TypeFile::DOCUMENT:
				case TypeFile::PDF:
					$queryTypeFileSql .= " WHEN {$fileType} THEN ". TypeFile::DOCUMENT. ' ';
					break;
				default:
					$queryTypeFileSql .= " WHEN {$fileType} THEN files.TYPE_FILE ";
			}
		}
		$queryTypeFileSql = "
		 	(
				CASE files.TYPE_FILE 
					{$queryTypeFileSql}
					ELSE ". TypeFile::UNKNOWN. "
				END
		 	)
		";


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
		$buildDiskSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '') use ($queryTypeFileSql)
		{
			$selectSql .= "
				, CNT_FILES.FILE_SIZE as DISK_SIZE
				, CNT_FILES.FILE_COUNT as DISK_COUNT
				, CNT_FILES.FILE_SIZE
				, CNT_FILES.FILE_COUNT
				, CNT_FILES.VERSION_COUNT
				, CNT_FILES.TYPE_FILE
				, CNT_FILES.STORAGE_ID
			";
			$columns = array_merge($columns, array(
				'DISK_SIZE',
				'DISK_COUNT',
				'FILE_SIZE',
				'FILE_COUNT',
				'VERSION_COUNT',
				'TYPE_FILE',
				'STORAGE_ID',
			));
			if ($subSelectSql != '')
			{
				$sqlStatements = explode(',', $subSelectSql);
				foreach ($sqlStatements as $statement)
				{
					if (preg_match("/([a-z0-9_\.\']+)[ \t\n]+as[ \t\n]+([a-z0-9_\.\']+)/i", $statement, $parts))
					{
						$selectSql .= ', CNT_FILES.'. $parts[2];
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
						COUNT(DISTINCT ver.ID) AS VERSION_COUNT,
						{$queryTypeFileSql} as TYPE_FILE,
						files.STORAGE_ID
						{$subSelectSql}
					FROM 
						b_disk_object files 
						LEFT JOIN b_disk_version ver ON files.ID = ver.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE 
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.DELETED_TYPE = ". ObjectTable::DELETED_TYPE_NONE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY 
						{$queryTypeFileSql},
						files.STORAGE_ID
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
		 * @param string $subGroupSql
		 * @return void
		 */
		$buildPreviewSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '') use ($queryTypeFileSql)
		{
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
						{$queryTypeFileSql} as TYPE_FILE,
						files.STORAGE_ID AS STORAGE_ID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
						LEFT JOIN b_file preview_file ON preview_file.ID = files.PREVIEW_ID
						LEFT JOIN b_file view_file ON view_file.ID = files.VIEW_ID
					WHERE
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.DELETED_TYPE = ". ObjectTable::DELETED_TYPE_NONE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY
						{$queryTypeFileSql},
						files.STORAGE_ID
						{$subGroupSql}
					ORDER BY NULL
				) CNT_PREVIEW
					ON CNT_PREVIEW.TYPE_FILE = CNT_FILES.TYPE_FILE 
					AND CNT_PREVIEW.STORAGE_ID = CNT_FILES.STORAGE_ID 
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
		$buildAttachedSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '') use ($queryTypeFileSql)
		{
			$selectSql .= "
				, IFNULL(CNT_ATTACH.ATTACHED_COUNT, 0) AS ATTACHED_COUNT
			";
			$columns = array_merge($columns, array(
				'ATTACHED_COUNT',
			));
			// language=SQL
			$fromSql .= "
				/* attached */
				LEFT JOIN 
				(
					SELECT
						COUNT(DISTINCT attached.ID) AS ATTACHED_COUNT,
						{$queryTypeFileSql} as TYPE_FILE,
						files.STORAGE_ID
					FROM 
						b_disk_object files 
						LEFT JOIN b_disk_attached_object attached ON files.ID = attached.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE 
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND files.DELETED_TYPE = ". ObjectTable::DELETED_TYPE_NONE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY 
						{$queryTypeFileSql},
						files.STORAGE_ID
						{$subGroupSql}
					ORDER BY NULL
				) CNT_ATTACH
					ON CNT_ATTACH.TYPE_FILE = CNT_FILES.TYPE_FILE 
					AND CNT_ATTACH.STORAGE_ID = CNT_FILES.STORAGE_ID 
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
		$buildExternalSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '') use ($queryTypeFileSql)
		{
			$selectSql .= "
				, IFNULL(CNT_LINK.LINK_COUNT, 0) AS LINK_COUNT
			";
			$columns = array_merge($columns, array(
				'LINK_COUNT',
			));
			// language=SQL
			$fromSql .= "
				/* external_link */
				LEFT JOIN 
				(
					SELECT
						COUNT(DISTINCT link.ID) AS LINK_COUNT,
						{$queryTypeFileSql} as TYPE_FILE,
						files.STORAGE_ID
					FROM 
						b_disk_object files 
						LEFT JOIN b_disk_external_link link ON files.ID = link.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE 
						files.TYPE = ". ObjectTable::TYPE_FILE. "
						AND link.TYPE != ". \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_AUTO. "
						AND files.DELETED_TYPE = ". ObjectTable::DELETED_TYPE_NONE. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
					GROUP BY 
						{$queryTypeFileSql},
						files.STORAGE_ID
						{$subGroupSql}
					ORDER BY NULL
				) CNT_LINK
					ON CNT_LINK.TYPE_FILE = CNT_FILES.TYPE_FILE
					AND CNT_LINK.STORAGE_ID = CNT_FILES.STORAGE_ID
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
		$buildSharingSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '') use ($queryTypeFileSql)
		{
			$selectSql .= "
				, IFNULL(CNT_SHARING.SHARING_COUNT, 0) AS SHARING_COUNT
			";
			$columns = array_merge($columns, array(
				'SHARING_COUNT',
			));
			// language=SQL
			$fromSql .= "
				/* sharing */
				LEFT JOIN 
				(
					SELECT
						COUNT(DISTINCT sharing.ID) AS SHARING_COUNT,
						{$queryTypeFileSql} as TYPE_FILE,
						files.STORAGE_ID
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
						{$queryTypeFileSql},
						files.STORAGE_ID
						{$subGroupSql}
					ORDER BY NULL
				) CNT_SHARING
					ON CNT_SHARING.TYPE_FILE = CNT_FILES.TYPE_FILE
					AND CNT_SHARING.STORAGE_ID = CNT_FILES.STORAGE_ID
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
		$buildUnnecessarySql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '', $subGroupSql = '') use ($queryTypeFileSql)
		{
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
						src.TYPE_FILE AS TYPE_FILE,
						src.STORAGE_ID
					FROM
					(
						SELECT
							files.ID,
							SUM(ver.SIZE) AS SIZE,
							COUNT(ver.ID) AS CNT,
							{$queryTypeFileSql} as TYPE_FILE,
							files.STORAGE_ID
							
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
								AND ifnull(link.TYPE,-1) != ". \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_AUTO. "

						WHERE
							files.TYPE = ". ObjectTable::TYPE_FILE. "
							AND files.ID = files.REAL_OBJECT_ID
							AND attached.VERSION_ID is null /* no attach */
							AND link.VERSION_ID is null /*no link */
							AND files.DELETED_TYPE = ". ObjectTable::DELETED_TYPE_NONE. "
							{$subWhereSql}
							
						GROUP BY 
							files.ID,
							{$queryTypeFileSql},
							files.STORAGE_ID
							{$subGroupSql}
						ORDER BY NULL
					) src
					GROUP BY
						src.TYPE_FILE,
						src.STORAGE_ID
					ORDER BY NULL
				) CNT_FREE
					ON CNT_FREE.TYPE_FILE = CNT_FILES.TYPE_FILE
					AND CNT_FREE.STORAGE_ID = CNT_FILES.STORAGE_ID
			";
		};

		$columns = array(
			'INDICATOR_TYPE',
			'OWNER_ID',
			'CREATE_TIME',
		);

		$selectSql = '';
		$fromSql = '';
		$whereSql = '';

		$buildDiskSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql, $subGroupSql);

		if (in_array(self::PREVIEW_FILE, $collectData))
		{
			$buildPreviewSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql, $subGroupSql);
		}

		if (in_array(self::ATTACHED_OBJECT, $collectData))
		{
			$buildAttachedSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql, $subGroupSql);
		}

		if (in_array(self::EXTERNAL_LINK, $collectData))
		{
			$buildExternalSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql, $subGroupSql);
		}

		if (in_array(self::SHARING_OBJECT, $collectData))
		{
			$buildSharingSql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql, $subGroupSql);
		}

		if (in_array(self::UNNECESSARY_VERSION, $collectData))
		{
			$buildUnnecessarySql($selectSql, $fromSql, $whereSql, $columns, $subSelectSql, $subWhereSql, $subGroupSql);
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
		$tableName = VolumeTable::getTableName();
		$temporallyTableName = VolumeTable::getTemporallyName();

		$columnList = Volume\QueryHelper::prepareInsert($columns, $this->getSelect());
		$connection->queryExecute("INSERT INTO {$temporallyTableName} ({$columnList}) {$querySql}");

		$temporallyDataSource = "SELECT {$columnList} FROM {$temporallyTableName}";

		if ($this->getFilterId() > 0)
		{
			$columnList = Volume\QueryHelper::prepareUpdateOnSelect($columns, $this->getSelect(), 'destinationTbl', 'sourceQuery');
			$sql = "
				UPDATE 
					{$tableName} destinationTbl, 
					({$temporallyDataSource}) sourceQuery 
				SET {$columnList} 
				WHERE 
					destinationTbl.INDICATOR_TYPE = '{$indicatorType}'
					AND destinationTbl.OWNER_ID = {$ownerId}
			";

			if ($this->getFilterValue('STORAGE_ID'))
			{
				$sql .= ' AND destinationTbl.STORAGE_ID = sourceQuery.STORAGE_ID ';
			}
			if ($this->getFilterValue('FOLDER_ID'))
			{
				$sql .= ' AND (destinationTbl.FOLDER_ID = sourceQuery.FOLDER_ID OR (destinationTbl.FOLDER_ID IS NULL AND sourceQuery.FOLDER_ID IS NULL)) ';
			}
			if ($this->getFilterValue('PARENT_ID'))
			{
				$sql .= ' AND (destinationTbl.PARENT_ID = sourceQuery.PARENT_ID OR (destinationTbl.PARENT_ID IS NULL AND sourceQuery.PARENT_ID IS NULL)) ';
			}

			$connection->queryExecute($sql);
		}
		else
		{
			$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$temporallyDataSource}");
		}

		VolumeTable::dropTemporally();

		$this->recalculatePercent();

		return $this;
	}

	/**
	 * Returns result set of file list corresponding to filter.
	 * @param array $collectedData List types of collected data to return.
	 * @return DB\Result
	 */
	public function getMeasurementResult($collectedData = array())
	{
		$parameter = array(
			'runtime' => array(
				new Entity\ExpressionField('DISK_SIZE', 'SUM(DISK_SIZE)'),
				new Entity\ExpressionField('DISK_COUNT', 'SUM(DISK_COUNT)'),
				new Entity\ExpressionField('FILE_SIZE', 'SUM(FILE_SIZE)'),
				new Entity\ExpressionField('FILE_COUNT', 'SUM(FILE_COUNT)'),
				new Entity\ExpressionField('PREVIEW_SIZE', 'SUM(PREVIEW_SIZE)'),
				new Entity\ExpressionField('PREVIEW_COUNT', 'SUM(PREVIEW_COUNT)'),
				new Entity\ExpressionField('VERSION_COUNT', 'SUM(VERSION_COUNT)'),
				new Entity\ExpressionField('PERCENT', 'ROUND(SUM(PERCENT), 1)'),
			),
			'select' => array(
				'DISK_SIZE',
				'DISK_COUNT',
				'FILE_SIZE',
				'FILE_COUNT',
				'PREVIEW_SIZE',
				'PREVIEW_COUNT',
				'VERSION_COUNT',
				'TYPE_FILE',
				'PERCENT',
			),
			'filter' => $this->getFilter(
				array(
					'=INDICATOR_TYPE' => static::className(),
					'=OWNER_ID' => $this->getOwner(),
					'!TYPE_FILE' => null,
					'=STORAGE_ID' => null,
					'=FOLDER_ID' => null,
				),
				VolumeTable::getEntity()
			),
			'group' => array(
				'TYPE_FILE',
			),
			'order' => $this->getOrder(array(
				'FILE_SIZE' => 'DESC'
			)),
			'count_total' => true,
		);
		if ($this->getLimit() > 0)
		{
			$parameter['limit'] = $this->getLimit();
		}
		if ($this->getOffset() > 0)
		{
			$parameter['offset'] = $this->getOffset();
		}

		return VolumeTable::getList($parameter);
	}

	/**
	 * Deletes objects selecting by filter.
	 * @return $this
	 */
	public function purify()
	{
		$folderId = $this->getFilterValue('FOLDER_ID', '=@');
		if (is_null($folderId))
		{
			$removeFilterFolderId = true;
			$this->addFilter('FOLDER_ID', null);
		}

		parent::purify();

		if (isset($removeFilterFolderId))
		{
			$this->unsetFilter('FOLDER_ID');
		}

		return $this;
	}

	/**
	 * @param string[] $filter Row from VolumeTable as a filter.
	 * @return Volume\Fragment
	 */
	public static function getFragment(array $filter)
	{
		if (in_array((int)$filter['TYPE_FILE'], TypeFile::getListOfValues()))
		{
			$filter['SPECIFIC']['TYPE_FILE'] = (int)$filter['TYPE_FILE'];
		}
		else
		{
			$filter['SPECIFIC']['TYPE_FILE'] = TypeFile::UNKNOWN;
		}

		return parent::getFragment($filter);
	}

	/**
	 * @param Volume\Fragment $fragment File type structure.
	 * @return string
	 */
	public static function getTitle(Volume\Fragment $fragment)
	{
		$specific = $fragment->getSpecific();
		return TypeFile::getName($specific['TYPE_FILE']);
	}
}




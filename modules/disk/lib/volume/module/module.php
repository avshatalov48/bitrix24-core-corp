<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\SharingTable;

/**
 * Common class indicator to module measurement volume.
 * @package Bitrix\Disk\Volume
 */
abstract class Module extends Volume\Base implements Volume\IVolumeIndicatorModule
{
	/** @var string */
	protected static $moduleId;

	/**
	 * Returns module identifier.
	 * @return string
	 * @throws ObjectPropertyException
	 */
	public static function getModuleId(): string
	{
		if (empty(static::$moduleId))
		{
			throw new ObjectPropertyException('moduleId');
		}
		return static::$moduleId;
	}

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 */
	public function measure(array $collectData = [self::DISK_FILE]): self
	{
		if (!$this->isMeasureAvailable())
		{
			$this->addError(new Main\Error('', self::ERROR_MEASURE_UNAVAILABLE));
			return $this;
		}

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
		$buildDiskSql = function(&$selectSql, &$fromSql, &$whereSql, &$columns, $subSelectSql = '', $subWhereSql = '')
		{
			$selectSql .= "
				, CNT_FILES.FILE_SIZE
				, CNT_FILES.FILE_COUNT
				, CNT_FILES.FILE_SIZE as DISK_SIZE
				, CNT_FILES.DISK_COUNT
				, CNT_FILES.VERSION_COUNT
			";
			$columns = array_merge($columns, [
				'FILE_SIZE',
				'FILE_COUNT',
				'DISK_SIZE',
				'DISK_COUNT',
				'VERSION_COUNT',
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
					}
				}
			}
			// language=SQL
			$fromSql .= "
				(
					SELECT 
						SUM(ver.SIZE) AS FILE_SIZE,
						COUNT(DISTINCT ver.FILE_ID) AS FILE_COUNT,
						COUNT(DISTINCT files.ID) AS DISK_COUNT,
						COUNT(DISTINCT ver.ID) AS VERSION_COUNT,
						storage.MODULE_ID as MODULE_ID
						{$subSelectSql}
					FROM 
						b_disk_object files 
						LEFT JOIN b_disk_version ver ON files.ID = ver.OBJECT_ID
						INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
					WHERE 
						files.TYPE = ".ObjectTable::TYPE_FILE."
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
				LEFT JOIN 
				(
					SELECT
						SUM(IFNULL(preview_file.FILE_SIZE, 0)) + SUM(IFNULL(view_file.FILE_SIZE, 0)) AS PREVIEW_SIZE,
						COUNT(DISTINCT preview_file.ID) + COUNT(DISTINCT view_file.ID) AS PREVIEW_COUNT,
						storage.MODULE_ID as MODULE_ID
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
					ON CNT_FILES.MODULE_ID = CNT_PREVIEW.MODULE_ID
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
				LEFT JOIN 
				(
					SELECT
						COUNT(DISTINCT attached.ID) AS ATTACHED_COUNT,
						storage.MODULE_ID as MODULE_ID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID
						INNER JOIN b_disk_attached_object attached on attached.OBJECT_ID = files.ID
					WHERE
						files.TYPE = ".ObjectTable::TYPE_FILE."
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
				) CNT_ATTACH
					ON CNT_FILES.MODULE_ID = CNT_ATTACH.MODULE_ID
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
				LEFT JOIN 
				(
					SELECT
						COUNT(DISTINCT link.ID) AS LINK_COUNT,
						storage.MODULE_ID as MODULE_ID
					FROM
						b_disk_object files
						INNER JOIN b_disk_storage storage ON storage.ID = files.STORAGE_ID 
						INNER JOIN b_disk_external_link link on link.OBJECT_ID = files.ID
					WHERE
						files.TYPE = ".ObjectTable::TYPE_FILE."
						AND link.TYPE != ". Disk\Internals\ExternalLinkTable::TYPE_AUTO. "
						AND files.ID = files.REAL_OBJECT_ID
						{$subWhereSql}
				) CNT_LINK
					ON CNT_FILES.MODULE_ID = CNT_LINK.MODULE_ID
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
				LEFT JOIN 
				(
					SELECT
						COUNT(DISTINCT sharing.ID) AS SHARING_COUNT,
						storage.MODULE_ID as MODULE_ID
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
					ON CNT_FILES.MODULE_ID = CNT_SHARING.MODULE_ID
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
				LEFT JOIN
				(
					SELECT
						SUM(src.SIZE) AS UNNECESSARY_VERSION_SIZE,
						SUM(src.CNT) AS UNNECESSARY_VERSION_COUNT,
						src.MODULE_ID as MODULE_ID
					FROM
					(
						SELECT
							files.ID,
							SUM(ver.SIZE) AS SIZE,
							COUNT(ver.ID) AS CNT,
							storage.MODULE_ID as MODULE_ID
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
							storage.MODULE_ID
						ORDER BY NULL
					) src
					GROUP BY
						src.MODULE_ID
					ORDER BY NULL
				) CNT_FREE
					ON CNT_FILES.MODULE_ID = CNT_FREE.MODULE_ID
			";
		};


		$subSelectSql = '';

		$subWhereSql = Volume\QueryHelper::prepareWhere(
			$this->getFilter(),
			[
				'DELETED_TYPE' => 'files.DELETED_TYPE',
				'MODULE_ID' => 'storage.MODULE_ID',
				'ENTITY_TYPE' => 'storage.ENTITY_TYPE',
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

		$tableName = VolumeTable::getTableName();
		$temporallyTableName = VolumeTable::getTemporallyName();

		$columnList = Volume\QueryHelper::prepareInsert($columns, $this->getSelect());
		$connection->queryExecute("INSERT INTO {$temporallyTableName} ({$columnList}) {$querySql}");

		$temporallyDataSource = "SELECT {$columnList} FROM {$temporallyTableName}";

		if ($this->getFilterId() > 0)
		{
			$filterId = $this->getFilterId();
			$columnList = Volume\QueryHelper::prepareUpdateOnSelect($columns, $this->getSelect(), 'destinationTbl', 'sourceQuery');
			$connection->queryExecute("
				UPDATE 
					{$tableName} destinationTbl, 
					({$temporallyDataSource}) sourceQuery 
				SET {$columnList} 
				WHERE destinationTbl.ID = {$filterId}
			");
		}
		else
		{
			$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$temporallyDataSource}");
		}

		VolumeTable::clearTemporally();

		return $this;
	}

	/**
	 * Recalculates percent from total file size per row selected by filter.
	 * @param string|Volume\IVolumeIndicator $totalSizeIndicator Use this indicator as total volume.
	 * @param string|Volume\IVolumeIndicator $excludeSizeIndicator Exclude indicator's volume from total volume.
	 * @throws Main\ArgumentException
	 * @return static
	 */
	public function recalculatePercent($totalSizeIndicator = '\\Bitrix\\Disk\\Volume\\Bfile', $excludeSizeIndicator = null): self
	{
		if (is_string($totalSizeIndicator) && !empty($totalSizeIndicator) && class_exists($totalSizeIndicator))
		{
			/** @var Volume\Bfile $totalSizeIndicator */
			$totalSizeIndicator = new $totalSizeIndicator();
		}
		if (!($totalSizeIndicator instanceof Volume\IVolumeIndicator))
		{
			throw new Main\ArgumentException('Wrong parameter totalSizeIndicator');
		}
		$totalSizeIndicator->setOwner($this->getOwner());
		$totalSizeIndicator->loadTotals();

		if ($totalSizeIndicator->getTotalSize() > 0)
		{
			$connection = Application::getConnection();
			$tableName = VolumeTable::getTableName();
			$filter = $this->getFilter(
				[
					'=INDICATOR_TYPE' => static::className(),
					'=OWNER_ID' => $this->getOwner(),
					'>FILE_COUNT' => 0,
				],
				VolumeTable::getEntity()
			);
			$where = Query::buildFilterSql(VolumeTable::getEntity(), $filter);

			$total = $totalSizeIndicator->getTotalSize() + $totalSizeIndicator->getPreviewSize();

			$sql = 'UPDATE '.$tableName.' SET PERCENT = ROUND((FILE_SIZE + PREVIEW_SIZE) * 100 / '.$total.', 4) WHERE '.$where;

			$connection->queryExecute($sql);
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
		$this
			->addFilter('=INDICATOR_TYPE', static::className())
			->addFilter('=OWNER_ID', $this->getOwner())
			//->addFilter('=STORAGE_ID', null)
			//->addFilter('=MODULE_ID', null)
			//->addFilter('=FOLDER_ID', null)
			//->addFilter('=USER_ID', null)
			//->addFilter('=GROUP_ID', null)
			//->addFilter('=TYPE_FILE', null)
			//->addFilter('=IBLOCK_ID', null)
		;
		return parent::getMeasurementResult($collectedData);
	}

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return double[]
	 */
	public function loadTotals()
	{
		$this
			->addFilter('=INDICATOR_TYPE', static::className())
			->addFilter('=OWNER_ID', $this->getOwner())
			//->addFilter('=STORAGE_ID', null)
			//->addFilter('=MODULE_ID', null)
			//->addFilter('=FOLDER_ID', null)
			//->addFilter('=USER_ID', null)
			//->addFilter('=GROUP_ID', null)
			//->addFilter('=TYPE_FILE', null)
			//->addFilter('=IBLOCK_ID', null)
		;
		return parent::loadTotals();
	}


	/**
	 * @param string[] $filter Filter with module id.
	 * @return Volume\Fragment
	 */
	public static function getFragment(array $filter): Volume\Fragment
	{
		/** @var Volume\IVolumeIndicatorModule $class */
		$class = $filter['INDICATOR_TYPE'];
		$filter['MODULE_ID'] = $class::getModuleId();
		return parent::getFragment($filter);
	}

	/**
	 * @param Volume\Fragment $fragment Module description structure.
	 * @return string|null
	 */
	public static function getTitle(Volume\Fragment $fragment): ?string
	{
		static $title = [];
		if (empty($title[$fragment->getModuleId()]))
		{
			if ($info = \CModule::createModuleObject($fragment->getModuleId()))
			{
				$title[$fragment->getModuleId()] = $info->MODULE_NAME;
			}
			else
			{
				$title[$fragment->getModuleId()] = $fragment->getModuleId();
			}
		}

		return $title[$fragment->getModuleId()];
	}

	/**
	 * Returns entity specific corresponding to module.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return array
	 */
	public static function getSpecific(Volume\Fragment $fragment): array
	{
		return $fragment->getSpecific();
	}

	/**
	 * Returns true if module installed and available to measure.
	 * @return boolean
	 */
	public function isMeasureAvailable(): bool
	{
		return
			Main\ModuleManager::isModuleInstalled(self::getModuleId())
			&& Main\Loader::includeModule(self::getModuleId());
	}

	/**
	 * Returns module corresponding to module.
	 * @return Disk\Storage[]|array
	 */
	public function getStorageList(): array
	{
		return [];
	}

	/**
	 * Returns folder list corresponding to module.
	 * @param Disk\Storage $storage Module's storage.
	 * @return Disk\Folder[]|array
	 */
	public function getFolderList($storage): array
	{
		return [];
	}

	/**
	 * Returns special folder code list.
	 * @return string[]
	 */
	public static function getSpecialFolderCode(): array
	{
		return [];
	}

	/**
	 * Returns special folder xml_id code list.
	 * @return string[]
	 */
	public static function getSpecialFolderXmlId(): array
	{
		return [];
	}

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType(): array
	{
		return [];
	}

	/**
	 * Returns entity user field list corresponding to module.
	 * @return string[]
	 */
	public function getEntityList(): array
	{
		return [];
	}

	/**
	 * Returns iblock list corresponding to module.
	 * @return array
	 */
	public function getIblockList(): array
	{
		return [];
	}

	/**
	 * Returns entity list attached to disk object corresponding to module.
	 * @return string[]
	 */
	public function getAttachedEntityList(): array
	{
		return [];
	}

	/**
	 * Returns list of user fields corresponding to entity.
	 * @param string $entityClass Class name of entity.
	 * @param string[] $userTypeField User field's type codes.
	 * @return array
	 */
	public function getUserTypeFieldList(
		string $entityClass,
		array $userTypeField = [\CUserTypeFile::USER_TYPE_ID, Disk\Uf\FileUserType::USER_TYPE_ID, Disk\Uf\VersionUserType::USER_TYPE_ID]
	): array
	{
		static $fields = [];

		if (!isset($fields[$entityClass]))
		{
			$fields[$entityClass] = [];

			/** @var Main\ORM\Data\DataManager $entityClass */
			$ufName = $entityClass::getUfId();
			if ($ufName <> '' && count($userTypeField) > 0)
			{
				$filter = [
					'=ENTITY_ID'    => $ufName,
					'=USER_TYPE_ID' => (count($userTypeField) == 1 ? $userTypeField[0] : $userTypeField),
				];
				$userFieldList = Main\UserFieldTable::getList([
					'filter' => $filter,
					'select' => [
						'ID',
						'ENTITY_ID',
						'USER_TYPE_ID',
						'FIELD_NAME',
						'MULTIPLE',
						'XML_ID',
					],
				]);
				foreach ($userFieldList as $userField)
				{
					$fields[$entityClass][$userField['FIELD_NAME']] = $userField;
				}
			}
		}

		return $fields[$entityClass];
	}

	/**
	 * Gets SQL query code to userfield table.
	 * @param string $entityClass Class name of entity.
	 * @param array $userField User field params.
	 * @param array $relation Additional relation entity table.
	 * @return string
	 */
	protected function prepareUserFieldQuery($entityClass, array $userField, array $relation = null)
	{
		$connection = Application::getConnection();

		/** @var Main\ORM\Data\DataManager $entityClass */
		$ufName = $entityClass::getUfId();
		$ufType = $userField['USER_TYPE_ID'];

		$relationSql = '';
		$relationSelectSql = '';
		$relationGroupBySql = '';
		$relationGroupSelectSql = '';
		if (is_array($relation))
		{
			$relationSelect = [];
			$relationGroupBy = [];
			$relationGroupSelect = [];
			foreach ($relation['select'] as $alias => $field)
			{
				$relationSelect[] = "REL.$field as $alias";
				$relationGroupBy[] = "flsrc.$alias";
				$relationGroupSelect[] = "flsrc.$alias";
			}
			$relationSelectSql = ', '. implode(', ', $relationSelect);
			$relationGroupBySql = 'GROUP BY '. implode(', ', $relationGroupBy);
			$relationGroupSelectSql = ', '. implode(', ', $relationGroupSelect);
			$relationSql = ' INNER JOIN '. $relation['table']. ' REL on REL.'. $relation['relation'] .' = ufsrc.VALUE_ID ';
		}

		$querySql = '';
		if ($userField['MULTIPLE'] == 'Y')
		{
			$ufId = $userField['ID'];
			$utmEntityTableName = 'b_utm_'.mb_strtolower($ufName);

			if ($connection->isTableExists($utmEntityTableName))
			{
				switch ($ufType)
				{
					case Disk\Uf\FileUserType::USER_TYPE_ID:
					{
						$querySql = "
							SELECT
								SUM(FILE_SIZE) as FILE_SIZE,
								COUNT(*) as FILE_COUNT,
								SUM(FILE_SIZE) as DISK_SIZE,
								COUNT(*) as DISK_COUNT,
								COUNT(*) as VERSION_COUNT
								{$relationGroupSelectSql}
							FROM 
							(
								SELECT DISTINCT
									f.ID,
									f.FILE_SIZE
									{$relationSelectSql}
								FROM
									{$utmEntityTableName} ufsrc
									{$relationSql}
									INNER JOIN b_disk_attached_object attached
										ON attached.ID = ufsrc.VALUE_INT
										AND ufsrc.FIELD_ID = '{$ufId}'
									INNER JOIN b_disk_object files
										ON files.ID = attached.OBJECT_ID 
										AND files.ID = files.REAL_OBJECT_ID
										AND files.TYPE = '".ObjectTable::TYPE_FILE."'
									INNER JOIN b_file f
										ON f.ID = files.FILE_ID 
							) flsrc
							{$relationGroupBySql}
							ORDER BY NULL
						";
						break;
					}

					case Disk\Uf\VersionUserType::USER_TYPE_ID:
					{
						$querySql = "
							SELECT
								SUM(FILE_SIZE) as FILE_SIZE,
								COUNT(*) as FILE_COUNT,
								SUM(FILE_SIZE) as DISK_SIZE,
								COUNT(*) as DISK_COUNT,
								COUNT(*) as VERSION_COUNT
								{$relationGroupSelectSql}
							FROM 
							(
								SELECT DISTINCT
									f.ID,
									f.FILE_SIZE
									{$relationSelectSql}
								FROM
									{$utmEntityTableName} ufsrc
									{$relationSql}
									INNER JOIN b_disk_attached_object attached
										ON attached.ID = ufsrc.VALUE_INT
										AND ufsrc.FIELD_ID = '{$ufId}'
									INNER JOIN b_disk_version versions
										ON versions.ID = attached.VERSION_ID 
									INNER JOIN b_disk_object files
										ON files.ID = versions.OBJECT_ID
										AND files.ID = attached.OBJECT_ID 
										AND files.ID = files.REAL_OBJECT_ID
										AND files.TYPE = '".ObjectTable::TYPE_FILE."'
									INNER JOIN b_file f
										ON f.ID = versions.FILE_ID
							) flsrc
							{$relationGroupBySql}
							ORDER BY NULL
						";
						break;
					}

					case \CUserTypeFile::USER_TYPE_ID:
					{
						$querySql = "
							SELECT
								SUM(FILE_SIZE) as FILE_SIZE,
								COUNT(*) as FILE_COUNT,
								0 as DISK_SIZE,
								0 as DISK_COUNT,
								0 as VERSION_COUNT
								{$relationGroupSelectSql}
							FROM 
							(
								SELECT DISTINCT
									f.ID,
									f.FILE_SIZE
									{$relationSelectSql}
								FROM
									{$utmEntityTableName} ufsrc
									{$relationSql}
									INNER JOIN b_file f
										ON f.ID = ufsrc.VALUE_INT
										AND ufsrc.FIELD_ID = '{$ufId}'
							) flsrc
							{$relationGroupBySql}
							ORDER BY NULL
						";
						break;
					}
				}
			}
		}
		else
		{
			$ufEntityTableFieldName = $userField['FIELD_NAME'];
			$utsEntityTableName = 'b_uts_'.mb_strtolower($ufName);

			if ($connection->isTableExists($utsEntityTableName))
			{
				switch ($ufType)
				{
					case Disk\Uf\FileUserType::USER_TYPE_ID:
					{
						$querySql = "
							SELECT
								SUM(FILE_SIZE) as FILE_SIZE,
								COUNT(*) as FILE_COUNT,
								SUM(FILE_SIZE) as DISK_SIZE,
								COUNT(*) as DISK_COUNT,
								COUNT(*) as VERSION_COUNT
								{$relationGroupSelectSql}
							FROM 
							(
								SELECT DISTINCT
									f.ID,
									f.FILE_SIZE
									{$relationSelectSql}
								FROM
									{$utsEntityTableName} ufsrc
									{$relationSql}
									INNER JOIN b_disk_attached_object attached
										ON attached.ID = cast(ufsrc.{$ufEntityTableFieldName} as UNSIGNED)
										and ufsrc.{$ufEntityTableFieldName} REGEXP '^[0-9]+$'
									INNER JOIN b_disk_object files
										ON files.ID = attached.OBJECT_ID 
										AND files.ID = files.REAL_OBJECT_ID
										AND files.TYPE = '".ObjectTable::TYPE_FILE."'
									INNER JOIN b_file f
										ON f.ID = files.FILE_ID 
							) flsrc
							{$relationGroupBySql}
							ORDER BY NULL
						";
						break;
					}

					case Disk\Uf\VersionUserType::USER_TYPE_ID:
					{
						$querySql = "
							SELECT
								SUM(FILE_SIZE) as FILE_SIZE,
								COUNT(*) as FILE_COUNT,
								SUM(FILE_SIZE) as DISK_SIZE,
								COUNT(*) as DISK_COUNT,
								COUNT(*) as VERSION_COUNT
								{$relationGroupSelectSql}
							FROM 
							(
								SELECT DISTINCT
									f.ID,
									f.FILE_SIZE
									{$relationSelectSql}
								FROM
									{$utsEntityTableName} ufsrc
									{$relationSql}
									INNER JOIN b_disk_attached_object attached
										ON attached.ID = cast(ufsrc.{$ufEntityTableFieldName} as UNSIGNED)
										and ufsrc.{$ufEntityTableFieldName} REGEXP '^[0-9]+$'
									INNER JOIN b_disk_version versions
										ON versions.ID = attached.VERSION_ID 
									INNER JOIN b_disk_object files
										ON files.ID = versions.OBJECT_ID
										AND files.ID = attached.OBJECT_ID
										AND files.ID = files.REAL_OBJECT_ID
										AND files.TYPE = '".ObjectTable::TYPE_FILE."'
									INNER JOIN b_file f
										ON f.ID = versions.FILE_ID 
							) flsrc
							{$relationGroupBySql}
							ORDER BY NULL
						";
						break;
					}

					case \CUserTypeFile::USER_TYPE_ID:
					{
						$querySql = "
							SELECT
								SUM(FILE_SIZE) as FILE_SIZE,
								COUNT(*) as FILE_COUNT,
								0 as DISK_SIZE,
								0 as DISK_COUNT,
								0 as VERSION_COUNT
								{$relationGroupSelectSql}
							FROM 
							(
								SELECT DISTINCT
									f.ID,
									f.FILE_SIZE
									{$relationSelectSql}
								FROM
									{$utsEntityTableName} ufsrc
									{$relationSql}
									INNER JOIN b_file f
										ON f.ID = cast(ufsrc.{$ufEntityTableFieldName} as UNSIGNED)
										and ufsrc.{$ufEntityTableFieldName} REGEXP '^[0-9]+$'
							) flsrc
							{$relationGroupBySql}
							ORDER BY NULL
						";
						break;
					}
				}
			}
		}

		return $querySql;
	}

	/**
	 * Returns SQL query code for all module userfields.
	 * @param array $relation Additional relation entity table.
	 * @param string[] $userTypeField User field's type codes.
	 * @return string
	 */
	protected function prepareUserFieldSourceSql(
		$relation = null,
		$userTypeField = [\CUserTypeFile::USER_TYPE_ID, Disk\Uf\FileUserType::USER_TYPE_ID, Disk\Uf\VersionUserType::USER_TYPE_ID]
	)
	{
		$entityList = $this->getEntityList();
		$source = [];
		if (count($entityList) > 0)
		{
			foreach ($entityList as $entityClass)
			{
				$entityUserFieldList = $this->getUserTypeFieldList($entityClass, $userTypeField);

				if (count($entityUserFieldList) > 0)
				{
					foreach ($entityUserFieldList as $entityUserField)
					{
						$source[] = $this->prepareUserFieldQuery($entityClass, $entityUserField, $relation);
					}
				}
			}
		}
		$querySql = '';
		if (count($source) > 0)
		{
			$querySql = ' ( '.implode(' ) UNION ( ', $source).' ) ';
		}

		return $querySql;
	}
}


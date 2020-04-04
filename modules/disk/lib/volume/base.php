<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Application;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;


abstract class Base implements \Bitrix\Disk\Volume\IVolumeIndicator, IErrorable
{
	/** @var array */
	protected $filter = array();

	/** @var int */
	protected $filterId = -1;

	/** @var array */
	protected $select = array();

	/** @var array */
	protected $groupBy = array();

	/** @var array */
	protected $order = array('FILE_SIZE' => 'DESC');

	/** @var int */
	protected $limit = -1;

	/** @var int */
	protected $offset = -1;

	/** @var boolean */
	protected $resultAvailable = false;

	/** @var double */
	protected $totalSize = 0;

	/** @var double */
	protected $totalCount = 0;

	/** @var double */
	protected $diskSize = 0;

	/** @var double */
	protected $diskCount = 0;

	/** @var double */
	protected $previewSize = 0;

	/** @var double */
	protected $previewCount = 0;

	/** @var double */
	protected $totalVersion = 0;

	/** @var double */
	protected $totalAttached = 0;

	/** @var double */
	protected $totalLink = 0;

	/** @var double */
	protected $totalSharing = 0;

	/** @var double */
	protected $unnecessaryVersionSize = 0;

	/** @var double */
	protected $unnecessaryVersionCount = 0;

	/** @var double */
	protected $ownerId = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID;

	/** @var  ErrorCollection */
	protected $errorCollection;

	const ERROR_MEASURE_UNAVAILABLE = 'DISK_VOLUME_MEASURE_UNAVAILABLE';

	// type data to collect
	const DISK_FILE = 'disk_file';
	const ATTACHED_OBJECT = 'attached_object';
	const SHARING_OBJECT = 'sharing_object';
	const EXTERNAL_LINK = 'external_link';
	const UNNECESSARY_VERSION = 'unnecessary_version';
	const PREVIEW_FILE = 'preview_file';
	const CRM_OBJECT = 'crm_object';

	/** @var string */
	protected $stageId = null;

	/** @var array Indicator list available in library. */
	public static $indicatorTypeList = array();

	/** @var string[] Clear constraint list. */
	public static $clearConstraintList = array();

	/** @var string[] Clear folder constraint list. */
	public static $clearFolderConstraintList = array();

	/** @var string[] Delete constraint list. */
	public static $deleteConstraintList = array();

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK,
	 *     UNNECESSARY_VERSION or PREVIEW_FILE.
	 * @return $this
	 * @throws Main\NotImplementedException
	 */
	public function measure($collectData = array(self::DISK_FILE, self::PREVIEW_FILE))
	{
		// method must declared as abstract, but in some php 5.3 it throws Fatal error: Can't inherit abstract function .. previously declared abstract
		// https://bugs.php.net/bug.php?id=66818
		// therefore it throws exception
		throw new Main\NotImplementedException();
		return $this;
	}

	/**
	 * Returns measure process stages list.
	 * List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION or PREVIEW_FILE.
	 * @return string[]
	 */
	public function getMeasureStages()
	{
		return array();
	}

	/**
	 * Gets current stage id.
	 * @return string
	 */
	public function getStage()
	{
		return $this->stageId;
	}

	/**
	 * Sets current stage id.
	 * @param string $stageId Stage id.
	 * @return $this
	 */
	public function setStage($stageId)
	{
		$this->stageId = $stageId;

		return $this;
	}

	/**
	 * Preforms data preparation.
	 * @return $this
	 */
	public function prepareData()
	{
		// do nothing
		return $this;
	}

	/**
	 * @return string the fully qualified name of this class.
	 */
	final public static function className()
	{
		return get_called_class();
	}

	/**
	 * @return string The short indicator name of this class.
	 */
	final public static function getIndicatorId()
	{
		return str_replace(array(__NAMESPACE__. '\\', '\\'), array('', '_'), static::className());
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
		$where = Query::buildFilterSql(VolumeTable::getEntity(), $filter);

		$sql = 'DELETE FROM '.$tableName.' WHERE '.$where;
		$connection->queryExecute($sql);

		return $this;
	}

	/**
	 * Unset calculated values.
	 * @return $this
	 */
	public function resetMeasurementResult()
	{
		if ($this->getFilterId() > 0)
		{
			\Bitrix\Disk\Internals\VolumeTable::update(
				$this->getFilterId(),
				array(
					'FILE_SIZE' => 0,
					'FILE_COUNT' => 0,
					'DISK_SIZE' => 0,
					'DISK_COUNT' => 0,
					'VERSION_COUNT' => 0,
					'PREVIEW_SIZE' => 0,
					'PREVIEW_COUNT' => 0,
					'ATTACHED_COUNT' => 0,
					'LINK_COUNT' => 0,
					'SHARING_COUNT' => 0,
					'UNNECESSARY_VERSION_SIZE' => 0,
					'UNNECESSARY_VERSION_COUNT' => 0,
					'PERCENT' => 0,
				)
			);
		}
		return $this;
	}


	/**
	 * Returns calculation result set.
	 * @param array $collectedData List types of collected data to return.
	 * @return DB\Result
	 */
	public function getMeasurementResult($collectedData = array())
	{
		$filter = $this->getFilter(
			array(
				'=INDICATOR_TYPE' => static::className(),
				'=OWNER_ID' => $this->getOwner(),
				'>FILE_COUNT' => 0,
			),
			VolumeTable::getEntity()
		);
		$select = array(
			'ID',
			'INDICATOR_TYPE',
			'FILE_SIZE',
			'FILE_COUNT',
			'DISK_SIZE',
			'DISK_COUNT',
			'VERSION_COUNT',
			'PREVIEW_SIZE',
			'PREVIEW_COUNT',
			'ATTACHED_COUNT',
			'LINK_COUNT',
			'SHARING_COUNT',
			'UNNECESSARY_VERSION_SIZE',
			'UNNECESSARY_VERSION_COUNT',
			'STORAGE_ID',
			'MODULE_ID',
			'FOLDER_ID',
			'PARENT_ID',
			'USER_ID',
			'GROUP_ID',
			'ENTITY_TYPE',
			'ENTITY_ID',
			'IBLOCK_ID',
			'TYPE_FILE',
			'COLLECTED',
			'DATA',
			'TITLE',
			'PERCENT',
			'USING_COUNT',
			// task info
			'AGENT_LOCK',
			'DROP_UNNECESSARY_VERSION',
			'DROP_TRASHCAN',
			'EMPTY_FOLDER',
			'DROP_FOLDER',
		);
		if ($this instanceof Volume\Folder)
		{
			$select[] = 'FOLDER.UPDATE_TIME';
			$filter['>FOLDER.ID'] = 0;//folder exists
		}

		$parameter = array(
			'runtime' => array(
				new Entity\ExpressionField('PERCENT', 'ROUND(PERCENT, 1)'),
				new Entity\ExpressionField('USING_COUNT', '(ATTACHED_COUNT + LINK_COUNT + SHARING_COUNT)'),
			),
			'select' => $select,
			'filter' => $filter,
			'order'  => $this->getOrder(array('FILE_SIZE' => 'DESC')),
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
	 * Sets filter parameters.
	 * @param string $key Parameter name to filter.
	 * @param string|string[] $value Parameter value.
	 * @return $this
	 */
	public function addFilter($key, $value)
	{
		if ($key !== 'LOGIC' && !is_numeric($key))
		{
			if ($key)
			{
				// remove default null value if exists
				$findKey = '='.trim($key, '=<>!@%');
				if (array_key_exists($findKey, $this->filter) && is_null($this->filter[$findKey]))
				{
					unset($this->filter[$findKey]);
				}
			}
		}

		$this->filter[$key] = $value;

		return $this;
	}

	/**
	 * Gets filter parameter by key.
	 *
	 * @param string $key Parameter name to filter.
	 * @param string $acceptedListModificators List of accepted filter modificator. Defaults are '=<>!@%'.
	 *
	 * @return mixed|null
	 */
	public function getFilterValue($key, $acceptedListModificators = '=<>!@%')
	{
		$filter = $this->getFilter();

		$value = null;
		foreach ($filter as $keyId => $val)
		{
			$testKey = trim($keyId, $acceptedListModificators);
			if ($testKey == $key)
			{
				$value = $val;
				break;
			}
		}

		return $value;
	}

	/**
	 * Gets filter parameters.
	 * @param string[] $defaultFilter Default filter set.
	 * @param \Bitrix\Main\Entity\Base $entity Leave only fields for this entity.
	 * @return array
	 */
	public function getFilter(array $defaultFilter = array(), $entity = null)
	{
		$filter = $this->filter;
		foreach ($defaultFilter as $defaultKey => $defaultValue)
		{
			$findDefaultKey = trim($defaultKey, '=<>!@%');
			$found = false;
			foreach ($filter as $key => $value)
			{
				if ($key === 'LOGIC')
				{
					continue;
				}
				if (is_numeric($key) && is_array($value))
				{
					continue;
				}
				$findKey = trim($key, '=<>!@%');
				if ($findDefaultKey == $findKey)
				{
					$found = true;
					break;
				}
			}
			if (!$found)
			{
				$filter[$defaultKey] = $defaultValue;
			}
		}

		if ($entity instanceof \Bitrix\Main\Entity\Base)
		{
			foreach ($filter as $fieldName => $value)
			{
				if ($fieldName === 'LOGIC')
				{
					continue;
				}
				if (is_numeric($fieldName) && is_array($value))
				{
					continue;
				}
				$findFieldName = trim($fieldName, '=<>!@%');
				if (!$entity->hasField($findFieldName))
				{
					unset($filter[$fieldName]);
				}
			}
		}

		return $filter;
	}

	/**
	 * Clear filter parameters.
	 * @param string $key Parameter name to unset.
	 * @return $this
	 */
	public function unsetFilter($key = '')
	{
		if ($key != '')
		{
			$findKey = trim($key, '=<>!@%');
			foreach ($this->filter as $keyId => $value)
			{
				$testKey = trim($keyId, '=<>!@%');
				if ($findKey === $testKey)
				{
					unset($this->filter[$keyId]);
				}
			}
		}
		else
		{
			$this->filter = array();
		}

		return $this;
	}

	/**
	 * Restores filter state from saved $measurement result.
	 * @param int|array $measurementResult The id of result row or row from table.
	 * @return $this
	 */
	public function restoreFilter($measurementResult)
	{
		$restoringFields = array(
			'STORAGE_ID',
			'MODULE_ID',
			'FOLDER_ID',
			'PARENT_ID',
			'USER_ID',
			'GROUP_ID',
			'ENTITY_TYPE',
			'ENTITY_ID',
			'IBLOCK_ID',
			'TYPE_FILE',
		);

		if (is_array($measurementResult) && isset($measurementResult['ID']))
		{
			$this->setFilterId($measurementResult['ID']);

			foreach ((array)$measurementResult as $key => $value)
			{
				if (!in_array($key, $restoringFields)) continue;
				if (!is_null($value))
				{
					$this->addFilter("=$key", $value);
				}
			}
		}
		else
		{
			$this->setFilterId((int)$measurementResult);

			$parameter = array(
				'select' => $restoringFields,
				'filter' => array(
					'=INDICATOR_TYPE' => static::className(),
					'=OWNER_ID' => $this->getOwner(),
					'=ID' => $this->getFilterId(),
				),
			);

			$row = VolumeTable::getList($parameter)->fetchRaw();

			foreach ($row as $key => $value)
			{
				if (!is_null($value))
				{
					$this->addFilter("=$key", $value);
				}
			}
		}

		return $this;
	}

	/**
	 * Sets filter id.
	 * @param int $filterId Stored filter id.
	 * @return void
	 */
	public function setFilterId($filterId)
	{
		$this->filterId = $filterId;
	}

	/**
	 * Gets stored filter id.
	 * @return int
	 */
	public function getFilterId()
	{
		return $this->filterId;
	}

	/**
	 * Sets select field.
	 * @param string $alias Parameter alias.
	 * @param string $statement Parameter value.
	 * @return $this
	 * @throws Main\ArgumentNullException
	 */
	public function addSelect($alias, $statement)
	{
		if (!$alias)
		{
			throw new Main\ArgumentNullException('Wrong parameter alias');
		}

		$this->select[$alias] = $statement;

		return $this;
	}

	/**
	 * Gets select fields.
	 * @return array
	 */
	public function getSelect()
	{
		return $this->select;
	}

	/**
	 * Sets group by field.
	 * @param string $alias Parameter alias.
	 * @param string $statement Parameter value.
	 * @return $this
	 * @throws Main\ArgumentNullException
	 */
	public function addGroupBy($alias, $statement)
	{
		if (!$alias)
		{
			throw new Main\ArgumentNullException('Wrong parameter alias');
		}

		$this->groupBy[$alias] = $statement;

		return $this;
	}

	/**
	 * Gets group by fields.
	 * @return array
	 */
	public function getGroupBy()
	{
		return $this->groupBy;
	}


	/**
	 * Sets sort order parameters.
	 * @param string[] $order Sort order parameters and directions.
	 * @return $this
	 */
	public function setOrder($order)
	{
		$this->order = $order;

		return $this;
	}

	/**
	 * Gets sort order parameters.
	 * @param string[] $defaultOrder Default order set.
	 * @return array
	 */
	public function getOrder(array $defaultOrder = array())
	{
		if (count($this->order) > 0)
		{
			return $this->order;
		}

		return $defaultOrder;
	}


	/**
	 * Sets limit result rows count.
	 * @param int $limit Limit value.
	 * @return $this
	 */
	public function setLimit($limit)
	{
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Gets limit result rows count.
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}


	/**
	 * Sets offset in result.
	 * @param int $offset Offset value.
	 * @return $this
	 */
	public function setOffset($offset)
	{
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Gets offset in result.
	 * @return int
	 */
	public function getOffset()
	{
		return $this->offset;
	}


	/**
	 * Tells true if total result is available.
	 * @return boolean
	 */
	public function isResultAvailable()
	{
		return $this->resultAvailable;
	}

	/**
	 * Returns total volume size of objects selecting by filter.
	 * @return double
	 */
	public function getTotalSize()
	{
		return (double)$this->totalSize;
	}

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return double
	 */
	public function getTotalCount()
	{
		return (double)$this->totalCount;
	}

	/**
	 * Returns total volume size of objects on disk.
	 * @return double
	 */
	public function getDiskSize()
	{
		return (double)$this->diskSize;
	}

	/**
	 * Returns total amount of objects on disk.
	 * @return double
	 */
	public function getDiskCount()
	{
		return (double)$this->diskCount;
	}

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return double
	 */
	public function getTotalVersion()
	{
		return (double)$this->totalVersion;
	}

	/**
	 * Returns total volume size of preview files.
	 * @return double
	 */
	public function getPreviewSize()
	{
		return (double)$this->previewSize;
	}

	/**
	 * Returns total amount of preview files.
	 * @return double
	 */
	public function getPreviewCount()
	{
		return (double)$this->previewCount;
	}

	/**
	 * Returns total amount of attached objects selecting by filter.
	 * @return double
	 */
	public function getTotalAttached()
	{
		return (double)$this->totalAttached;
	}

	/**
	 * Returns total amount of external links to objects selecting by filter.
	 * @return double
	 */
	public function getTotalLink()
	{
		return (double)$this->totalLink;
	}
	/**
	 * Returns total number sharing of objects selecting by filter.
	 * @return double
	 */
	public function getTotalSharing()
	{
		return (double)$this->totalSharing;
	}

	/**
	 * Returns total amount of files without links and attached object.
	 * @return integer
	 */
	public function getUnnecessaryVersionSize()
	{
		return (double)$this->unnecessaryVersionSize;
	}

	/**
	 * Returns total count of files without links and attached object.
	 * @return double
	 */
	public function getUnnecessaryVersionCount()
	{
		return (double)$this->unnecessaryVersionCount;
	}

	/**
	 * Sets owner id.
	 * @param int $ownerId User id.
	 * @return $this
	 */
	public function setOwner($ownerId)
	{
		$this->ownerId = $ownerId;

		return $this;
	}

	/**
	 * Gets owner id.
	 * @return int|null
	 */
	public function getOwner()
	{
		return $this->ownerId > 0 ? $this->ownerId : \Bitrix\Disk\SystemUser::SYSTEM_USER_ID;
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
				new Entity\ExpressionField('ATTACHED_COUNT', 'SUM(ATTACHED_COUNT)'),
				new Entity\ExpressionField('LINK_COUNT', 'SUM(LINK_COUNT)'),
				new Entity\ExpressionField('SHARING_COUNT', 'SUM(SHARING_COUNT)'),
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
				'ATTACHED_COUNT',
				'LINK_COUNT',
				'SHARING_COUNT',
				'UNNECESSARY_VERSION_SIZE',
				'UNNECESSARY_VERSION_COUNT',
			),
			'filter' => $filter,
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
			$this->totalAttached = (double)$row['ATTACHED_COUNT'];
			$this->totalLink = (double)$row['LINK_COUNT'];
			$this->totalSharing = (double)$row['SHARING_COUNT'];
			$this->unnecessaryVersionSize = (double)$row['UNNECESSARY_VERSION_SIZE'];
			$this->unnecessaryVersionCount = (double)$row['UNNECESSARY_VERSION_COUNT'];
		}

		return $row;
	}

	/**
	 * Recalculates percent from total file size per row selected by filter.
	 * @param string|Volume\IVolumeIndicator $totalSizeIndicator Use this indicator as total volume.
	 * @param string|Volume\IVolumeIndicator $excludeSizeIndicator Exclude indicator's volume from total volume.
	 * @return self
	 */
	public function recalculatePercent($totalSizeIndicator = null, $excludeSizeIndicator = null)
	{
		if ($totalSizeIndicator instanceof Volume\IVolumeIndicator)
		{
			$totalSizeIndicator->loadTotals();
			$total = $totalSizeIndicator->getTotalSize() + $totalSizeIndicator->getPreviewSize();
		}
		else
		{
			$this->loadTotals();
			$total = $this->getTotalSize() + $this->getPreviewSize();
		}

		if ($total > 0)
		{
			$connection = Application::getConnection();
			$tableName = VolumeTable::getTableName();
			$filter = $this->getFilter(
				array(
					'=INDICATOR_TYPE' => static::className(),
					'=OWNER_ID' => $this->getOwner(),
					'>FILE_COUNT' => 0,
				),
				VolumeTable::getEntity()
			);
			$where = Query::buildFilterSql(VolumeTable::getEntity(), $filter);

			//$sql = 'UPDATE '.$tableName.' SET PERCENT = ROUND(FILE_SIZE * 100 / '.$total.', 4) WHERE '.$where;
			$sql = 'UPDATE '.$tableName.' SET PERCENT = ROUND((FILE_SIZE + PREVIEW_SIZE) * 100 / '.$total.', 4) WHERE '.$where;

			$connection->queryExecute($sql);
		}
		return $this;
	}


	/**
	 * Loads file list corresponding to indicator's filter.
	 * @param array $additionalFilter Additional parameters to filter file list.
	 * @return \Bitrix\Main\DB\Result
	 */
	public function getCorrespondingFileList($additionalFilter = array())
	{
		$filterToFileList = array();

		$storageId =  $this->getFilterValue('STORAGE_ID', '=');
		if (!empty($storageId))
		{
			$filterToFileList['=STORAGE_ID'] = $storageId;
		}
		$folderId =  $this->getFilterValue('FOLDER_ID', '=');
		if (!empty($folderId))
		{
			$filterToFileList['=PATH_CHILD.PARENT_ID'] = $folderId;
		}

		$filterToFileList['=TYPE'] = \Bitrix\Disk\Internals\ObjectTable::TYPE_FILE;

		if ($this instanceof \Bitrix\Disk\Volume\Storage\TrashCan)
		{
			$filterToFileList['>=IS_REAL_OBJECT'] = 0;
		}
		else
		{
			$filterToFileList['=IS_REAL_OBJECT'] = 1;
		}

		if (!isset($additionalFilter['!DELETED_TYPE']))
		{
			$filterToFileList['=DELETED_TYPE'] = \Bitrix\Disk\Internals\ObjectTable::DELETED_TYPE_NONE;
		}

		$filterToFileList = array_merge($filterToFileList, $additionalFilter);

		$fileList = \Bitrix\Disk\File::getList(array(
			'select'  => array('ID'),
			'filter'  => $filterToFileList,
			'runtime' => array(
				'IS_REAL_OBJECT' => new \Bitrix\Main\Entity\ExpressionField(
					'IS_REAL_OBJECT',
					'CASE WHEN disk_internals_file.ID = disk_internals_file.REAL_OBJECT_ID THEN 1 ELSE 0 END'
				),
			),
			'order' => array(
				//'PATH_CHILD.DEPTH_LEVEL' => 'DESC',
				'ID' => 'ASC',
			),
			'limit' => $this->getLimit(),
		));

		return $fileList;
	}


	/**
	 * Loads folder list corresponding to indicator's filter.
	 * @param array $additionalFilter Additional parameters to filter file list.
	 * @return \Bitrix\Main\DB\Result
	 */
	public function getCorrespondingFolderList($additionalFilter = array())
	{
		$filterToFolderList = array();

		$storageId =  $this->getFilterValue('STORAGE_ID', '=');
		if (!empty($storageId))
		{
			$filterToFolderList['=STORAGE_ID'] = $storageId;
		}
		$folderId =  $this->getFilterValue('FOLDER_ID', '=');
		if (!empty($folderId))
		{
			$filterToFolderList['=PATH_CHILD.PARENT_ID'] = $folderId;
		}

		$filterToFolderList['=TYPE'] = \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER;

		if ($this instanceof \Bitrix\Disk\Volume\Storage\TrashCan)
		{
			$filterToFolderList['>=IS_REAL_OBJECT'] = 0;
		}
		else
		{
			$filterToFolderList['=IS_REAL_OBJECT'] = 1;
		}

		if (!isset($additionalFilter['!DELETED_TYPE']))
		{
			$filterToFolderList['=DELETED_TYPE'] = \Bitrix\Disk\Internals\ObjectTable::DELETED_TYPE_NONE;
		}

		$filterToFolderList = array_merge($filterToFolderList, $additionalFilter);

		$folderList = \Bitrix\Disk\Folder::getList(array(
			'select'  => array('ID'),
			'filter'  => $filterToFolderList,
			'runtime' => array(
				'IS_REAL_OBJECT' => new \Bitrix\Main\Entity\ExpressionField(
					'IS_REAL_OBJECT',
					'CASE WHEN disk_internals_folder.ID = disk_internals_folder.REAL_OBJECT_ID THEN 1 ELSE 0 END'
				),
			),
			'order' => array(
				'PATH_CHILD.DEPTH_LEVEL' => 'DESC',
				'ID' => 'ASC',
			),
			'limit' => $this->getLimit(),
		));

		return $folderList;
	}


	/**
	 * Loads file list corresponding to indicator's filter.
	 * @param array $additionalFilter Additional parameters to filter file list.
	 * @return \Bitrix\Main\DB\Result
	 */
	public function getCorrespondingUnnecessaryVersionList($additionalFilter = array())
	{
		$connection = Application::getConnection();

		$parentFolderId = $this->getFilterValue('PARENT_ID', '=@!');
		if (!empty($parentFolderId))
		{
			$this
				->unsetFilter('PARENT_ID')
				->addFilter('@PARENT_ID', Volume\QueryHelper::prepareFolderTreeQuery($parentFolderId));
		}

		$whereSql = Volume\QueryHelper::prepareWhere(
			$this->getFilter(array(
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			)),
			array(
				'ENTITY_TYPE' => 'storage.ENTITY_TYPE',
				'ENTITY_ID' => 'storage.ENTITY_ID',
				'USER_ID' => 'storage.ENTITY_ID',
				'GROUP_ID' => 'storage.ENTITY_ID',
				'DELETED_TYPE' => 'files.DELETED_TYPE',
				'STORAGE_ID' => 'storage.ID',
				'FOLDER_ID' => 'files.PARENT_ID',
				'PARENT_ID' => 'files.PARENT_ID',
				'FILE_ID' => 'files.ID',
				'VERSION_ID' => 'ver.ID',
			)
		);

		$limitSql = '';
		if ($this->getLimit() > 0)
		{
			$limitSql = 'LIMIT '.$this->getLimit();
		}

		$querySql = "
			SELECT
				files.ID as FILE_ID,
				ver.ID AS VERSION_ID
			FROM 
				b_disk_version ver 
				INNER JOIN b_disk_object files ON ver.OBJECT_ID = files.ID AND ver.FILE_ID != files.FILE_ID /*no head */
				INNER JOIN b_disk_storage storage ON files.STORAGE_ID = storage.ID
			
				/* head */
				INNER JOIN (
					SELECT  object_id, max(id) as id
					FROM b_disk_version 
					GROUP BY object_id
					ORDER BY NULL
				) head ON head.OBJECT_ID = files.ID
			
				LEFT JOIN b_disk_attached_object attached
					ON attached.OBJECT_ID  = ver.OBJECT_ID
					AND attached.VERSION_ID = ver.ID
					AND attached.VERSION_ID != head.ID
			
				LEFT JOIN b_disk_external_link link
					ON link.OBJECT_ID = ver.OBJECT_ID
					AND link.VERSION_ID = ver.ID
					AND link.VERSION_ID != head.ID
					AND ifnull(link.TYPE, -1) != ". \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_AUTO. "

			WHERE 
				files.TYPE = ". ObjectTable::TYPE_FILE. "
				and files.ID = files.REAL_OBJECT_ID /* not link */
				AND attached.VERSION_ID is null /* no attach */
				AND link.VERSION_ID is null	/*no ext link */
				
				{$whereSql}
				
			ORDER BY
				files.ID ASC,
				ver.ID ASC
				
			{$limitSql}
		";

		return $connection->query($querySql);
	}


	/**
	 * Finds entity object by filter.
	 * @param string[] $filter Array filter set to find entity object.
	 * @return Volume\Fragment
	 */
	public static function getFragment(array $filter)
	{
		return new Volume\Fragment($filter);
	}

	/**
	 * Returns title of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return string
	 * @throws Main\NotImplementedException
	 */
	public static function getTitle(Volume\Fragment $fragment)
	{
		throw new Main\NotImplementedException();
		return '';
	}

	/**
	 * Returns last update time of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return \Bitrix\Main\Type\DateTime|null
	 */
	public static function getUpdateTime(Volume\Fragment $fragment)
	{
		return null;
	}


	/**
	 * Returns indicator list available in library.
	 * @return array
	 */
	final public static function listIndicator()
	{
		if (empty(self::$indicatorTypeList))
		{
			self::loadListIndicator();
		}

		return self::$indicatorTypeList;
	}

	/**
	 * Returns clearance constraint list.
	 * @return string[]
	 */
	final public static function listClearConstraint()
	{
		if (empty(self::$clearConstraintList))
		{
			self::loadListIndicator();
		}

		return self::$clearConstraintList;
	}

	/**
	 * Returns clearance constraint list.
	 * @return string[]
	 */
	final public static function listClearFolderConstraint()
	{
		if (empty(self::$clearFolderConstraintList))
		{
			self::loadListIndicator();
		}

		return self::$clearFolderConstraintList;
	}

	/**
	 * Returns delete constraint list.
	 * @return string[]
	 */
	final public static function listDeleteConstraint()
	{
		if (empty(self::$deleteConstraintList))
		{
			self::loadListIndicator();
		}

		return self::$deleteConstraintList;
	}

	/**
	 * Recursively looks for indicator class files available in library.
	 * @param string $libraryPath Sub folder name inside current library.
	 * @return void
	 */
	private static function loadListIndicator($libraryPath = '')
	{
		$directory = new \Bitrix\Main\IO\Directory(__DIR__. '/'. $libraryPath);
		$fileList = $directory->getChildren();
		foreach ($fileList as $entry)
		{
			if ($entry->isFile() && preg_match("/^(.+)\.php$/i", $entry->getName(), $parts))
			{
				$subNamespace = ($libraryPath != '' ? '\\'.$libraryPath : ''). '\\';
				/** @var Volume\IVolumeIndicator $class */
				$class = __NAMESPACE__. $subNamespace. $parts[1];
				try
				{
					$reflection = new \ReflectionClass($class);
					if (
						!$reflection->isInterface() &&
						!$reflection->isAbstract()
					)
					{
						if($reflection->implementsInterface(__NAMESPACE__.'\\IVolumeIndicator'))
						{
							self::$indicatorTypeList[$class::getIndicatorId()] = $class::className();
						}
						if($reflection->implementsInterface(__NAMESPACE__.'\\IClearConstraint'))
						{
							self::$clearConstraintList[$class::getIndicatorId()] = $class::className();
						}
						if($reflection->implementsInterface(__NAMESPACE__.'\\IClearFolderConstraint'))
						{
							self::$clearFolderConstraintList[$class::getIndicatorId()] = $class::className();
						}
						if($reflection->implementsInterface(__NAMESPACE__.'\\IDeleteConstraint'))
						{
							self::$deleteConstraintList[$class::getIndicatorId()] = $class::className();
						}
					}
				}
				catch(\ReflectionException $exception)
				{
				}
			}
			elseif ($entry->isDirectory())
			{
				self::loadListIndicator($entry->getName());
			}
		}
	}

	/**
	 * Constructs and returns indicator type object.
	 * @param string $indicatorTypeId Indicator class name.
	 * @return Volume\IVolumeIndicator
	 * @throws Main\ObjectException
	 * @throws Main\ArgumentNullException
	 */
	final public static function getIndicator($indicatorTypeId)
	{
		if (!$indicatorTypeId)
		{
			throw new Main\ArgumentNullException('Wrong parameter indicatorTypeId');
		}

		if (strpos($indicatorTypeId, __NAMESPACE__) !== false)
		{
			$className = $indicatorTypeId;
		}
		else
		{
			$className = __NAMESPACE__.'\\'.str_replace('_', '\\', $indicatorTypeId);
		}

		/** @var Volume\IVolumeIndicator $indicator */
		$indicator = new $className();
		if (!$indicator instanceof Volume\IVolumeIndicator)
		{
			throw new Main\ObjectException('Return must implements '. __NAMESPACE__. '\\IVolumeIndicator interface.');
		}

		return $indicator;
	}

	/**
	 * Upends stack of errors.
	 * @param \Bitrix\Main\Error $error Error message object.
	 * @return void
	 */
	public function addError(\Bitrix\Main\Error $error)
	{
		if (!$this->errorCollection instanceof ErrorCollection)
		{
			$this->errorCollection = new ErrorCollection();
		}
		$this->errorCollection->add(array($error));
	}

	/**
	 * Tells true if error have happened.
	 * @return boolean
	 */
	public function hasErrors()
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			return $this->errorCollection->hasErrors();
		}

		return false;
	}

	/**
	 * Empty stack of errors.
	 * @return void
	 */
	public function clearErrors()
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			$this->errorCollection->clear();
		}
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			return $this->errorCollection->toArray();
		}

		return array();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			return $this->errorCollection->getErrorsByCode($code);
		}

		return array();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return \Bitrix\Main\Error|null
	 */
	public function getErrorByCode($code)
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			return $this->errorCollection->getErrorByCode($code);
		}

		return null;
	}
}

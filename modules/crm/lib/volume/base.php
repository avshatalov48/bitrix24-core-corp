<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Error;
use Bitrix\Disk;

/**
 * @implements \Bitrix\Crm\Volume\IVolumeClear
 * @implements \Bitrix\Crm\Volume\IVolumeClearFile
 * @implements \Bitrix\Crm\Volume\IVolumeClearActivity
 * @implements \Bitrix\Crm\Volume\IVolumeClearEvents
 */
abstract class Base
	implements Volume\IVolumeIndicator, Main\Errorable
{
	use Main\ErrorableImplementation;

	/** @var array Indicator list available in library. */
	protected static $indicatorTypeList = array();

	/** @var array */
	protected static $tablesInformation = array();

	/** @var array */
	protected static $userFieldInformation = array();

	/** @var array */
	protected static $maxIdRangeCache = array();

	/** @var array */
	protected $tableList = array();

	/** @var int */
	protected $ownerId = 0;

	/** @var int */
	protected $entitySize = 0;

	/** @var int */
	protected $entityCount = 0;

	/** @var int */
	protected $fileSize = 0;

	/** @var int */
	protected $fileCount = 0;

	/** @var int */
	protected $diskSize = 0;

	/** @var int */
	protected $diskCount = 0;

	/** @var int */
	protected $eventSize = 0;

	/** @var int */
	protected $eventCount = 0;

	/** @var int */
	protected $activitySize = 0;

	/** @var int */
	protected $activityCount = 0;

	/** @var array */
	protected $filter = array();

	/** @var float seconds */
	private $timeLimit = -1;

	/** @var float seconds */
	private $startTime = -1;

	/** @var boolean */
	private $timeLimitReached = false;

	/** @var int */
	private $processOffset = 0;

	/** @var int */
	private $droppedCount = 0;

	/** @var int */
	private $droppedFileCount = 0;

	/** @var int */
	private $failCount = 0;


	const ERROR_PERMISSION_DENIED = 'CRM_PERMISSION_DENIED';
	const ERROR_DELETION_FAILED = 'CRM_DELETION_FAILED';

	// limit maximum number selected entity
	public const MAX_ENTITY_PER_INTERACTION = 1000;

	// limit maximum number selected files
	public const MAX_FILE_PER_INTERACTION = 1000;

	/** @var array */
	protected static $filterFieldAlias = array();

	/** @var array */
	protected static $entityList = array();

	/** @var array */
	protected $dateSplitPeriod = array(1, 'months');

	/** @var false */
	protected $collectEntityRowSize = false;


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
	 * Checks if module Voximplant is available.
	 * @param string $moduleId Mmodule Id.
	 * @return boolean
	 */
	protected static function isModuleAvailable($moduleId)
	{
		static $available = array();
		if (!isset($available[$moduleId]))
		{
			$available[$moduleId] =
				Main\ModuleManager::isModuleInstalled($moduleId) &&
				Main\Loader::includeModule($moduleId);
		}

		return $available[$moduleId];
	}

	/**
	 * Gets task owner.
	 * @return \CUser
	 */
	protected function getUser()
	{
		/** @global \CUser $USER */
		global $USER;
		return $USER;
	}

	/**
	 * Checks data base structure and vipe old data.
	 * @return void
	 */
	protected function checkTemporally()
	{
		if (Crm\VolumeTmpTable::checkTemporally())
		{
			Crm\VolumeTmpTable::deleteBatch(array(
				'=INDICATOR_TYPE' => static::getIndicatorId(),
				'=OWNER_ID' => $this->getOwner(),
			));
		}
		else
		{
			Crm\VolumeTmpTable::createTemporally();
		}
	}

	/**
	 * Copy data from temporally table.
	 * @return void
	 */
	protected function copyTemporallyData()
	{
		$connection = Main\Application::getConnection();

		$keyFields = array(
			'INDICATOR_TYPE',
			'OWNER_ID',
			'DATE_CREATE',
			'STAGE_SEMANTIC_ID',
		);
		$updateFields = array(
			'ENTITY_SIZE',
			'ENTITY_COUNT',
			'FILE_SIZE',
			'FILE_COUNT',
			'DISK_SIZE',
			'DISK_COUNT',
			'EVENT_SIZE',
			'EVENT_COUNT',
			'ACTIVITY_SIZE',
			'ACTIVITY_COUNT',
		);

		$target = $connection->getSqlHelper()->quote(Crm\VolumeTable::getTableName());

		$query = Crm\VolumeTmpTable::query();
		$query
			->setSelect(array_merge($keyFields, $updateFields))
			->setFilter(array(
				'=INDICATOR_TYPE' => static::getIndicatorId(),
				'=OWNER_ID' => $this->getOwner(),
			));
		$sourceSql = $query->getQuery();

		$columns = $update = array();
		foreach ($keyFields as $field)
		{
			$field = $connection->getSqlHelper()->quote($field);
			$columns[] = $field;
		}
		foreach ($updateFields as $field)
		{
			$field = $connection->getSqlHelper()->quote($field);
			$columns[] = $field;
			$update[] = "{$target}.{$field} = {$target}.{$field} + VALUES({$field})";
		}

		$sqlIns =
			"INSERT INTO {$target} (". implode(', ', $columns). ") {$sourceSql} ".
			"ON DUPLICATE KEY UPDATE ". implode(', ', $update)
		;

		$connection->queryExecute($sqlIns);
	}

	/**
	 * Runs measure test.
	 * @return self
	 */
	public function measure()
	{
		$this
			->purify()
			->measureEntity()
			->measureFiles();

		if ($this instanceof Volume\IVolumeClearActivity || is_callable([$this, 'measureActivity']))
		{
			$this->measureActivity();
		}

		if ($this instanceof Volume\IVolumeClearEvent || is_callable([$this, 'measureEvent']))
		{
			$this->measureEvent();
		}

		return $this;
	}


	/**
	 * Deletes objects selecting by filter.
	 * @return self
	 */
	public function purify()
	{
		Crm\VolumeTable::deleteBatch(array(
			'=INDICATOR_TYPE' => static::getIndicatorId(),
			'=OWNER_ID' => $this->getOwner(),
		));

		return $this;
	}

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return double[]
	 */
	public function loadTotals()
	{
		$query = Crm\VolumeTable::query();

		$filter = array(
			'=INDICATOR_TYPE' => static::getIndicatorId(),
			'=OWNER_ID' => $this->getOwner(),
			'=AGENT_LOCK' => Volume\Cleaner::TASK_STATUS_NONE,
		);

		$filterExt = $this->getFilter();
		foreach ($filterExt as $key => $value)
		{
			if (empty($value))
			{
				continue;
			}
			$filter[$key] = $value;
		}

		$query
			->setFilter($filter)
			->registerRuntimeField(new ORM\Fields\ExpressionField('CNT', 'COUNT(*)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_SIZE', 'SUM(ENTITY_SIZE)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_COUNT', 'SUM(ENTITY_COUNT)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('FILE_SIZE', 'SUM(FILE_SIZE)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('FILE_COUNT', 'SUM(FILE_COUNT)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('DISK_SIZE', 'SUM(DISK_SIZE)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('DISK_COUNT', 'SUM(DISK_COUNT)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('EVENT_SIZE', 'SUM(EVENT_SIZE)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('EVENT_COUNT', 'SUM(EVENT_COUNT)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('ACTIVITY_SIZE', 'SUM(ACTIVITY_SIZE)'))
			->registerRuntimeField(new ORM\Fields\ExpressionField('ACTIVITY_COUNT', 'SUM(ACTIVITY_COUNT)'))
			->addSelect('CNT')
			->addSelect('ENTITY_SIZE')
			->addSelect('ENTITY_COUNT')
			->addSelect('FILE_SIZE')
			->addSelect('FILE_COUNT')
			->addSelect('DISK_SIZE')
			->addSelect('DISK_COUNT')
			->addSelect('EVENT_SIZE')
			->addSelect('EVENT_COUNT')
			->addSelect('ACTIVITY_SIZE')
			->addSelect('ACTIVITY_COUNT')
		;

		$res = $query->exec();
		if ($row = $res->fetch())
		{
			$this->entitySize = (double)$row['ENTITY_SIZE'];
			$this->entityCount = (double)$row['ENTITY_COUNT'];
			$this->fileSize = (double)$row['FILE_SIZE'];
			$this->fileCount = (double)$row['FILE_COUNT'];
			$this->diskSize = (double)$row['DISK_SIZE'];
			$this->diskCount = (double)$row['DISK_COUNT'];
			$this->eventSize = (double)$row['EVENT_SIZE'];
			$this->eventCount = (double)$row['EVENT_COUNT'];
			$this->activitySize = (double)$row['ACTIVITY_SIZE'];
			$this->activityCount = (double)$row['ACTIVITY_COUNT'];
		}

		return $row;
	}


	/**
	 * Runs measure test for tables.
	 * @return self
	 */
	public function measureEntity()
	{
		self::loadTablesInformation();

		// database size
		$this->entitySize = 0;
		$this->entityCount = 0;

		if (count($this->getFilter()) > 0)
		{
			if (!$this->canBeFiltered())
			{
				// nonfiterable
				return $this;
			}

			$entityList = static::getEntityList();
			foreach ($entityList as $classEntity)
			{
				/**
				 * @var ORM\Data\DataManager $classEntity
				 */
				$query = $classEntity::query();
				$entity = $classEntity::getEntity();

				// filter
				if ($this->prepareEntityFilter($query, $entity))
				{
					$query
						->registerRuntimeField(new ORM\Fields\ExpressionField('CNT', 'COUNT(*)'))
						->addSelect('CNT');

					$res = $query->exec();
					if ($row = $res->fetch())
					{
						$table = $classEntity::getTableName();
						$avgTableRowLength = (double)self::$tablesInformation[$table]['AVG_SIZE'];
						$this->entitySize += $avgTableRowLength * (double)$row['CNT'];
					}
				}
			}
		}
		else
		{
			$tableList = $this->getTableList();
			if (count($tableList) > 0)
			{
				$this->entityCount = self::$tablesInformation[$tableList[0]]['TABLE_ROWS'];

				foreach ($tableList as $tableName)
				{
					$this->entitySize += (double)self::$tablesInformation[$tableName]['SIZE'];
				}
			}
		}

		$connection = Main\Application::getConnection();

		$data = array(
			'INDICATOR_TYPE' => static::getIndicatorId(),
			'OWNER_ID' => $this->getOwner(),
			'ENTITY_COUNT' => ($this->entityCount ? : 0),
			'ENTITY_SIZE' => ($this->entitySize ? : 0),
			'STAGE_SEMANTIC_ID' => '-',
		);

		$insert = $connection->getSqlHelper()->prepareInsert(Crm\VolumeTable::getTableName(), $data);

		$querySql = 'INSERT INTO '.$connection->getSqlHelper()->quote(Crm\VolumeTable::getTableName()). '('. $insert[0]. ') VALUES ('. $insert[1]. ')';

		Crm\VolumeTable::deleteBatch(array(
			'=INDICATOR_TYPE' => static::getIndicatorId(),
			'=OWNER_ID' => $this->getOwner(),
			'=AGENT_LOCK' => Volume\Cleaner::TASK_STATUS_NONE,
		));

		$connection->queryExecute($querySql);

		return $this;
	}

	/**
	 * Setups filter params into query.
	 * @param ORM\Query\Query $query Query.
	 * @param ORM\Entity $entity Use only this entity fields.
	 * @param string $entityAlias Table alias.
	 * @return boolean
	 */
	public function prepareEntityFilter(ORM\Query\Query $query, ORM\Entity $entity, $entityAlias = '')
	{
		$isAllValueApplied = true;

		// Samples naming:
		// EVENT.DATE_CREATE
		// DealCategory.CREATED_DATE
		// InvoiceStatusHistory.CREATED_TIME
		// ActivityTable.CREATED
		$dateCreatedAlias = array('DATE_CREATE', 'CREATED_TIME', 'CREATED_DATE', 'CREATED');

		// Samples naming:
		// DEAL.STAGE_SEMANTIC_ID
		// LEAD.STATUS_SEMANTIC_ID
		$stageSemanticAlias = array('STAGE_SEMANTIC_ID', 'STATUS_SEMANTIC_ID');

		$filter = $this->getFilter();

		foreach ($filter as $key => $value)
		{
			if (empty($value))
			{
				continue;
			}
			$key0 = trim($key, '<>!=@');
			if (mb_strlen($key) > mb_strlen($key0))
			{
				$operator = mb_substr($key, 0, mb_strlen($key) - mb_strlen($key0));
			}
			else
			{
				$operator = '=';
				if (is_array($value))
				{
					$operator = 'in';
				}
			}
			switch ($key0)
			{
				case 'STAGE_SEMANTIC_ID':
					$isApplied = false;
					foreach ($stageSemanticAlias as $aliasStageSemantic)
					{
						if ($entity->hasField($aliasStageSemantic))
						{
							$isApplied = true;
							if ($entityAlias !== '')
							{
								$aliasStageSemantic = "{$entityAlias}.{$aliasStageSemantic}";
							}
							$query->where($aliasStageSemantic, $operator, $value);
							break;
						}
					}
					if (!$isApplied)
					{
						$isAllValueApplied = false;
					}
					break;

				case 'DATE_CREATE':
					$isApplied = false;
					foreach ($dateCreatedAlias as $aliasDateCreated)
					{
						if ($entity->hasField($aliasDateCreated))
						{
							if ($entity->getField($aliasDateCreated) instanceof ORM\Fields\DatetimeField)
							{
								$isApplied = true;
								if ($entityAlias !== '')
								{
									$aliasDateCreated = "{$entityAlias}.{$aliasDateCreated}";
								}
								$query->where($aliasDateCreated, $operator, $value);
								break;
							}
						}
					}
					if (!$isApplied)
					{
						$isAllValueApplied = false;
					}
					break;


				default:
					$isApplied = $this->addFilterEntityField($query, $entity, $key, $value);
					if (!$isApplied)
					{
						$isAllValueApplied = false;
					}

			}
		}

		return $isAllValueApplied;
	}

	/**
	 * Setups filter params into query.
	 * @param ORM\Query\Query $query Query.
	 * @param ORM\Entity $entity Use only this entity fields.
	 * @param string $key Key name.
	 * @param mixed $value Value.
	 * @return boolean
	 */
	protected function addFilterEntityField(ORM\Query\Query $query, ORM\Entity $entity, $key, $value)
	{
		$isAllValueApplied = false;

		$key0 = trim($key, '<>!=');
		if (mb_strpos($key0, '.') !== false)
		{
			$key0 = mb_substr($key0, 0, mb_strpos($key0, '.'));
			if ($entity->hasField($key0))
			{
				$query->addFilter($key, $value);
				$isAllValueApplied = true;
			}
		}
		elseif ($entity->hasField($key0))
		{
			$query->addFilter($key, $value);
			$isAllValueApplied = true;
		}

		return $isAllValueApplied;
	}

	/**
	 * Runs measure test for files.
	 *
	 * @return self
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 */
	public function measureFiles()
	{
		$this->fileSize = 0;
		$this->fileCount = 0;
		$this->diskSize = 0;
		$this->diskCount = 0;

		if (count($this->getFilter()) > 0 && !$this->canBeFiltered())
		{
			// nonfiterable
			return $this;
		}

		self::loadTablesInformation();

		$connection = Main\Application::getConnection();

		$source = array();

		$entityList = static::getEntityList();
		foreach ($entityList as $entityClass)
		{
			$entityUserFieldList = $this->getUserTypeFieldList($entityClass);
			/** @var array $userField */
			foreach ($entityUserFieldList as $userField)
			{
				$sql = $this->prepareUserFieldQuery($entityClass, $userField);
				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}

			$diskConnector = static::getDiskConnector($entityClass);
			if ($diskConnector !== null)
			{
				$sql = $this->prepareDiskAttachedQuery($entityClass, $diskConnector);
				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}

			$liveFeedConnector = static::getLiveFeedConnector($entityClass);
			if ($liveFeedConnector !== null)
			{
				$sql = $this->prepareLiveFeedQuery($entityClass, $liveFeedConnector);
				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}
		}
		if (count($source) > 0)
		{

			$querySql = "
				SELECT 
					SUM(src.FILE_SIZE) as FILE_SIZE,
					SUM(src.FILE_COUNT) as FILE_COUNT,
					SUM(src.DISK_SIZE) as DISK_SIZE,
					SUM(src.DISK_COUNT) as DISK_COUNT
				FROM 
				(
					(". implode(' ) UNION ( ', $source). ")
				) src
			";

			$result = $connection->query($querySql);
			if ($row = $result->fetch())
			{
				$this->fileSize += (double)$row['FILE_SIZE'];
				$this->fileCount += (double)$row['FILE_COUNT'];
				$this->diskSize += (double)$row['DISK_SIZE'];
				$this->diskCount += (double)$row['DISK_COUNT'];
			}
		}


		$data = array(
			'INDICATOR_TYPE' => static::getIndicatorId(),
			'OWNER_ID' => $this->getOwner(),
			'STAGE_SEMANTIC_ID' => '-',
			'FILE_SIZE' => $this->fileSize,
			'FILE_COUNT' => $this->fileCount,
			'DISK_SIZE' => $this->diskSize,
			'DISK_COUNT' => $this->diskCount,
		);

		$insert = $connection->getSqlHelper()->prepareInsert(Crm\VolumeTable::getTableName(), $data);

		$querySql = 'INSERT INTO '.$connection->getSqlHelper()->quote(Crm\VolumeTable::getTableName()). '('. $insert[0]. ') VALUES ('. $insert[1]. ')';

		Crm\VolumeTable::deleteBatch(array(
			'=INDICATOR_TYPE' => static::getIndicatorId(),
			'=OWNER_ID' => $this->getOwner(),
			'=AGENT_LOCK' => Volume\Cleaner::TASK_STATUS_NONE,
		));

		$connection->queryExecute($querySql);

		return $this;
	}




	/**
	 * Tells that is is participated in the total volume.
	 * @return boolean
	 */
	public function isParticipatedTotal()
	{
		return true;
	}

	/**
	 * Returns query.
	 *
	 * @return ORM\Query\Query
	 */
	public function prepareQuery()
	{
		return null;
	}

	/**
	 * Setups filter params into query.
	 * @param ORM\Query\Query $query Query.
	 * @return boolean
	 */
	public function prepareFilter(ORM\Query\Query $query)
	{
		$filter = $this->getFilter();

		if (count($filter) > 0 && !$this->canBeFiltered())
		{
			return false;
		}

		$isAllValueApplied = true;
		foreach ($filter as $key => $value)
		{
			if (empty($value))
			{
				continue;
			}
			$key0 = trim($key, '<>!=');
			if (isset(static::$filterFieldAlias[$key0]))
			{
				$key1 = str_replace($key0, static::$filterFieldAlias[$key0], $key);
				if (is_array($value))
				{
					$query->where($key1, 'in', $value);
				}
				else
				{
					$query->addFilter($key1, $value);
				}
			}
			else
			{

				$isAllValueApplied = $isAllValueApplied && $this->addFilterEntityField($query, $query->getEntity(), $key, $value);
			}
		}

		return $isAllValueApplied;
	}


	/**
	 * Returns indicator list available in library.
	 * @return array
	 */
	final public static function getListIndicator()
	{
		if (empty(self::$indicatorTypeList))
		{
			self::loadListIndicator();
		}

		return self::$indicatorTypeList;
	}

	/**
	 * Recursively looks for indicator class files available in library.
	 * @param string $libraryPath Sub folder name inside current library.
	 * @return void
	 */
	private static function loadListIndicator($libraryPath = '')
	{
		$directory = new Main\IO\Directory(__DIR__. '/'. $libraryPath);
		$fileList = $directory->getChildren();
		foreach ($fileList as $entry)
		{
			if ($entry->isFile() && preg_match("/^(.+)\.php$/i", $entry->getName(), $parts))
			{
				$subNamespace = ($libraryPath != '' ? '\\'.$libraryPath : ''). '\\';
				/** @var Volume\IVolumeIndicator $indicatorType */
				$indicatorType = __NAMESPACE__. $subNamespace. $parts[1];
				try
				{
					$reflection = new \ReflectionClass($indicatorType);
					if (
						!$reflection->isInterface() &&
						!$reflection->isAbstract() &&
						!$reflection->isTrait() &&
						$reflection->implementsInterface(__NAMESPACE__.'\\IVolumeIndicator')
					)
					{
						self::$indicatorTypeList[$indicatorType::getIndicatorId()] = $indicatorType::className();
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
	 * @param string $indicatorId Indicator class name.
	 * @return Volume\IVolumeIndicator
	 * @throws Main\ObjectException
	 * @throws Main\ArgumentNullException
	 */
	final public static function getIndicator($indicatorId)
	{
		if (!$indicatorId)
		{
			throw new Main\ArgumentNullException('Wrong parameter indicatorTypeId');
		}

		if (mb_strpos($indicatorId, __NAMESPACE__) !== false)
		{
			$className = $indicatorId;
		}
		else
		{
			$className = __NAMESPACE__.'\\'.str_replace('_', '\\', $indicatorId);
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
	 * Component action list for measure process.
	 * @param array $componentCommandAlias Command alias.
	 * @return array
	 */
	public function getActionList($componentCommandAlias)
	{
		$indicatorId = static::getIndicatorId();

		$queueList[] = array(
			'indicatorId' => $indicatorId,
			'action' => $componentCommandAlias['MEASURE_ENTITY'],
		);
		$queueList[] = array(
			'indicatorId' => $indicatorId,
			'action' => $componentCommandAlias['MEASURE_FILE'],
		);
		$queueList[] = array(
			'indicatorId' => $indicatorId,
			'action' => $componentCommandAlias['MEASURE_ACTIVITY'],
		);
		$queueList[] = array(
			'indicatorId' => $indicatorId,
			'action' => $componentCommandAlias['MEASURE_EVENT'],
		);

		return $queueList;
	}

	/**
	 * Returns date split period.
	 * @return array
	 */
	public function getDateSplitPeriod()
	{
		return $this->dateSplitPeriod;
	}

	/**
	 * Sets date split period.
	 * @param array $dateSplitPeriod Value and units.
	 * @return void
	 */
	public function setDateSplitPeriod(array $dateSplitPeriod)
	{
		$this->dateSplitPeriod = $dateSplitPeriod;
	}

	/**
	 * Can filter applied to the indicator.
	 * @return boolean
	 */
	public function canBeFiltered()
	{
		// can not be filtered
		return false;
	}

	/**
	 * Sets filter parameters.
	 * @param string $key Parameter name to filter.
	 * @param string|string[] $value Parameter value.
	 * @return $this
	 */
	public function addFilter($key, $value)
	{
		$this->filter[$key] = $value;
		return $this;
	}

	/**
	 * Replace filter parameters.
	 * @param array $filter Filter key = value pair.
	 * @return $this
	 */
	public function setFilter(array $filter)
	{
		$this->filter = $filter;
		return $this;
	}

	/**
	 * Gets filter parameter by key.
	 *
	 * @param string $key Parameter name to filter.
	 * @param mixed|null $defaultValue Default value.
	 * @param string $acceptedListModificators List of accepted filter modificator. Defaults are '=<>!'.
	 *
	 * @return mixed|null
	 */
	public function getFilterValue($key, $defaultValue = null, $acceptedListModificators = '<>!=')
	{
		if (isset($this->filter[$key]))
		{
			return $this->filter[$key];
		}

		$filter = $this->getFilter();
		foreach ($filter as $k => $value)
		{
			$k0 = trim($k, $acceptedListModificators);
			if ($k0 == $key)
			{
				return $value;
			}
		}

		return $defaultValue;
	}

	/**
	 * Removes filter parameter by key.
	 * @param string $key Parameter name to filter.
	 * @param string $acceptedListModificators List of accepted filter modificator. Defaults are '=<>!'.
	 * @return void
	 */
	public function delFilterValue($key, $acceptedListModificators = '<>!=')
	{
		if (isset($this->filter[$key]))
		{
			unset($this->filter[$key]);
		}

		$filter = $this->getFilter();
		foreach ($filter as $k => $value)
		{
			$k0 = trim($k, $acceptedListModificators);
			if ($k0 == $key)
			{
				unset($this->filter[$k]);
			}
		}
	}

	/**
	 * Gets filter parameters.
	 * @param string[] $defaultFilter Default filter set.
	 * @return array
	 */
	public function getFilter(array $defaultFilter = array())
	{
		return (!empty($this->filter) ? $this->filter : $defaultFilter);
	}


	/**
	 * Returns total volume size.
	 * @return double
	 */
	public function getTotalSize()
	{
		return (double)$this->entitySize + (double)$this->fileSize;
	}

	/**
	 * Returns total volume size of tables corresponding to indicator.
	 * @return integer
	 */
	public function getEntitySize()
	{
		return (double)$this->entitySize;
	}

	/**
	 * Returns total count of entities corresponding to indicator.
	 * @return integer
	 */
	public function getEntityCount()
	{
		return (double)$this->entityCount;
	}

	/**
	 * Returns total volume size of files corresponding to indicator.
	 * @return integer
	 */
	public function getFileSize()
	{
		return (double)$this->fileSize;
	}

	/**
	 * Returns total amount of files corresponding to indicator.
	 * @return integer
	 */
	public function getFileCount()
	{
		return (double)$this->fileCount;
	}

	/**
	 * Returns total volume size of file on disk.
	 * @return double
	 */
	public function getDiskSize()
	{
		return (double)$this->diskSize;
	}

	/**
	 * Returns total amount of files on disk.
	 * @return double
	 */
	public function getDiskCount()
	{
		return (double)$this->diskCount;
	}

	/**
	 * Returns total volume size of activities and associated files.
	 * @return integer
	 */
	public function getActivitySize()
	{
		return (double)$this->activitySize;
	}

	/**
	 * Returns total amount of activities and associated files.
	 * @return integer
	 */
	public function getActivityCount()
	{
		return (double)$this->activityCount;
	}

	/**
	 * Returns total volume size of events and associated files.
	 * @return integer
	 */
	public function getEventSize()
	{
		return (double)$this->eventSize;
	}

	/**
	 * Returns total amount of events and associated files.
	 * @return integer
	 */
	public function getEventCount()
	{
		return (double)$this->eventCount;
	}

	/**
	 * Returns title of the indicator.
	 * @return string
	 * @throws Main\NotImplementedException
	 */
	abstract public function getTitle();

	/**
	 * Returns entity list.
	 * @return string[]
	 */
	public static function getEntityList()
	{
		return static::$entityList;
	}

	/**
	 * Returns special folder list.
	 * @return Disk\Folder[]|null
	 */
	public function getSpecialFolderList()
	{
		return array();
	}

	/**
	 * Returns entity list attached to disk object.
	 * @param string $entityClass Class name of entity.
	 * @return string|null
	 */
	public static function getDiskConnector($entityClass)
	{
		return null;
	}

	/**
	 * Returns Socialnetwork log entity list attached to disk object.
	 * @param string $entityClass Class name of entity.
	 * @return string|null
	 */
	public static function getLiveFeedConnector($entityClass)
	{
		return null;
	}

	/**
	 * Load tables information.
	 *
	 * @return void
	 * @throws Main\Db\SqlQueryException
	 */
	protected static function loadTablesInformation()
	{
		if (empty(self::$tablesInformation))
		{
			$connection = Main\Application::getConnection();

			self::$tablesInformation = array();

			$querySql = "
				SELECT 
					TABLE_NAME, 
					TABLE_ROWS AS TABLE_ROWS, 
					DATA_LENGTH + INDEX_LENGTH AS SIZE, 
					case TABLE_ROWS 
						when 0 then 0 
						else round((DATA_LENGTH + INDEX_LENGTH) / TABLE_ROWS)
					end AS AVG_SIZE
				FROM information_schema.TABLES 
				WHERE 
					TABLE_SCHEMA = '".$connection->getDatabase()."'
					AND (
						TABLE_NAME LIKE 'b_crm_%' OR 
						TABLE_NAME LIKE 'b_uts_crm_%' OR
						TABLE_NAME LIKE 'b_utm_crm_%' OR
						TABLE_NAME LIKE 'b_uts_order' OR
						TABLE_NAME LIKE 'b_utm_order'
					)
			";
			$result = $connection->query($querySql);
			while ($row = $result->fetch())
			{
				self::$tablesInformation[mb_strtolower($row['TABLE_NAME'])] = $row;
			}
		}
	}


	/**
	 * Returns table list corresponding to indicator.
	 *
	 * @return string[]
	 * @throws Main\Db\SqlQueryException
	 */
	public function getTableList()
	{
		if (empty($this->tableList))
		{
			$this->tableList = array();

			self::loadTablesInformation();

			$entityList = static::getEntityList();

			foreach ($entityList as $entity)
			{
				try
				{
					$reflection = new \ReflectionClass($entity);
					if (
						!$reflection->isInterface() &&
						!$reflection->isAbstract() &&
						$reflection->isSubclassOf(ORM\Data\DataManager::class)
					)
					{
						/** @var ORM\Data\DataManager $entity */
						$this->tableList[] = $entity::getTableName();

						/** @var ORM\Data\DataManager $entity */
						$ufName = $entity::getUfId();
						if ($ufName != '')
						{
							$utmEntityTableName = 'b_utm_'.mb_strtolower($ufName);
							if (isset(self::$tablesInformation[$utmEntityTableName]))
							{
								$this->tableList[] = $utmEntityTableName;
							}

							$utsEntityTableName = 'b_uts_'.mb_strtolower($ufName);
							if (isset(self::$tablesInformation[$utsEntityTableName]))
							{
								$this->tableList[] = $utsEntityTableName;
							}
						}
					}
				}
				catch (\ReflectionException $exception)
				{
				}
			}
		}

		return $this->tableList;
	}


	/**
	 * Loads list of user fields information.
	 *
	 * @return void
	 * @throws Main\ArgumentException
	 */
	protected static function loadUserFieldInformation()
	{
		$entityList = static::getEntityList();

		foreach ($entityList as $entity)
		{
			if (isset(self::$userFieldInformation[$entity]))
			{
				continue;
			}
			self::$userFieldInformation[$entity] = false;

			try
			{
				$reflection = new \ReflectionClass($entity);
				if (
					!$reflection->isInterface() &&
					!$reflection->isAbstract() &&
					$reflection->isSubclassOf(ORM\Data\DataManager::class)
				)
				{
					/** @var ORM\Data\DataManager $entity */
					$ufName = $entity::getUfId();
					if ($ufName <> '')
					{
						$userFieldList = Main\UserFieldTable::getList(array(
							'filter' => array(
								'=ENTITY_ID' => $ufName,
							),
							'select' => array(
								'ID',
								'ENTITY_ID',
								'USER_TYPE_ID',
								'FIELD_NAME',
								'MULTIPLE',
								'XML_ID',
							),
						));
						if ($userFieldList->getSelectedRowsCount() > 0)
						{
							self::$userFieldInformation[$entity] = array();
							foreach ($userFieldList as $userField)
							{
								self::$userFieldInformation[$entity][$userField['FIELD_NAME']] = $userField;
							}
						}
					}
				}
			}
			catch (\ReflectionException $exception)
			{
			}
		}
	}


	/**
	 * Returns list of user fields corresponding to entity.
	 * @param string $entity Class name of entity.
	 * @return array
	 */
	public function getUserTypeFieldList($entity)
	{
		self::loadUserFieldInformation();

		$fields = array();

		if (isset(self::$userFieldInformation[$entity]) && is_array(self::$userFieldInformation[$entity]))
		{
			$userTypeField = array(
				\CUserTypeFile::USER_TYPE_ID,
			);
			if (self::isModuleAvailable('disk'))
			{
				$userTypeField[] = Disk\Uf\FileUserType::USER_TYPE_ID;
				$userTypeField[] = Disk\Uf\VersionUserType::USER_TYPE_ID;
			}

			foreach (self::$userFieldInformation[$entity] as $userField)
			{
				if (is_array($userField) && in_array($userField['USER_TYPE_ID'], $userTypeField))
				{
					$fields[$userField['FIELD_NAME']] = $userField;
				}
			}
		}

		return $fields;
	}


	/**
	 * Gets SQL query code to userfield table.
	 *
	 * @param string $entityClass Class name of entity.
	 * @param array $userField User field params.
	 * @param array $entityGroupField Entity fields to group by.
	 *
	 * @return string
	 */
	protected function prepareUserFieldQuery($entityClass, array $userField, array $entityGroupField = array())
	{
		/**
		 * @var ORM\Data\DataManager $entityClass
		 */
		$ufName = $entityClass::getUfId();
		if (empty($ufName))
		{
			return '';
		}

		$ufType = $userField['USER_TYPE_ID'];
		$isDiskAvailable = self::isModuleAvailable('disk');

		// need to filter by Entity
		$entityQuery = $entityClass::query();
		$entityEntity = $entityClass::getEntity();

		$entityQuery->addSelect('ID');


		// STAGE_SEMANTIC_ID
		if ($entityClass == Crm\QuoteTable::class)
		{
			Volume\Quote::registerStageField($entityQuery, '', 'STAGE_SEMANTIC_ID');
			Volume\Quote::registerStageField($entityQuery, '', 'QUOTE_STAGE_SEMANTIC_ID');
		}
		if ($entityClass == Crm\InvoiceTable::class)
		{
			Volume\Invoice::registerStageField($entityQuery, '', 'STAGE_SEMANTIC_ID');
			Volume\Invoice::registerStageField($entityQuery, '', 'INVOICE_STAGE_SEMANTIC_ID');
		}
		// DATE
		if (
			$entityClass == Crm\CompanyTable::class ||
			$entityClass == Crm\ContactTable::class
		)
		{
			$dayField = new ORM\Fields\ExpressionField(
				'DATE_CREATE_SHORT',
				'DATE(%s)',
				'DATE_CREATE'
			);
			$entityQuery->registerRuntimeField($dayField);
		}
		if (
			$entityClass == Crm\InvoiceTable::class
		)
		{
			$dayField = new ORM\Fields\ExpressionField(
				'DATE_CREATE_SHORT',
				'DATE(%s)',
				'DATE_INSERT'
			);
			$entityQuery->registerRuntimeField($dayField);
		}



		$entityFieldsSql = '';
		$entityFieldsGroupSql = '';
		$entityFields = array();
		foreach ($entityGroupField as $alias => $field)
		{
			$entityQuery->addSelect($field, $alias);
			$entityFields[] = 'entity.'. $alias;
		}

		if ($this->prepareEntityFilter($entityQuery, $entityEntity))
		{
			$entityFilterQuerySql = $entityQuery->getQuery();

			if (count($entityFields) > 0)
			{
				$entityFieldsSql = ', '.implode(', ', $entityFields);
				$entityFieldsGroupSql = 'GROUP BY '.implode(', ', $entityFields);
			}
		}
		else
		{
			// cannot filter this Entity
			return '';
		}


		$querySql = '';
		if ($userField['MULTIPLE'] === 'Y')
		{
			$ufId = $userField['ID'];
			$utmEntityTableName = 'b_utm_'.mb_strtolower($ufName);

			if (isset(self::$tablesInformation[$utmEntityTableName]))
			{
				if (
					$isDiskAvailable &&
					$ufType === Disk\Uf\FileUserType::USER_TYPE_ID
				)
				{
					$querySql = "
						SELECT 
							SUM(f.FILE_SIZE) as FILE_SIZE,
							COUNT(DISTINCT f.ID) as FILE_COUNT,
							SUM(f.FILE_SIZE) as DISK_SIZE,
							COUNT(DISTINCT f.ID) as DISK_COUNT
							{$entityFieldsSql}
						FROM
							{$utmEntityTableName} ufsrc
							INNER JOIN ( {$entityFilterQuerySql} ) entity 
								ON entity.ID = ufsrc.VALUE_ID
							INNER JOIN b_disk_attached_object attached
								ON attached.ID = ufsrc.VALUE_INT
								AND ufsrc.FIELD_ID = '{$ufId}'
							INNER JOIN b_disk_object files
								ON files.ID = attached.OBJECT_ID 
								AND files.ID = files.REAL_OBJECT_ID
								AND files.TYPE = '".Disk\Internals\ObjectTable::TYPE_FILE."'
							INNER JOIN b_file f
								ON f.ID = files.FILE_ID 
						{$entityFieldsGroupSql}  
					";
				}

				elseif (
					$isDiskAvailable &&
					$ufType === Disk\Uf\VersionUserType::USER_TYPE_ID
				)
				{
					$querySql = "
						SELECT DISTINCT
							SUM(f.FILE_SIZE) as FILE_SIZE,
							COUNT(DISTINCT f.ID) as FILE_COUNT,
							SUM(f.FILE_SIZE) as DISK_SIZE,
							COUNT(DISTINCT f.ID) as DISK_COUNT
							{$entityFieldsSql}
						FROM
							{$utmEntityTableName} ufsrc
							INNER JOIN ( {$entityFilterQuerySql} ) entity 
								ON entity.ID = ufsrc.VALUE_ID
							INNER JOIN b_disk_attached_object attached
								ON attached.ID = ufsrc.VALUE_INT
								AND ufsrc.FIELD_ID = '{$ufId}'
							INNER JOIN b_disk_version versions
								ON versions.ID = attached.VERSION_ID 
							INNER JOIN b_disk_object files
								ON files.ID = versions.OBJECT_ID
								AND files.ID = attached.OBJECT_ID 
								AND files.ID = files.REAL_OBJECT_ID
								AND files.TYPE = '".Disk\Internals\ObjectTable::TYPE_FILE."'
							INNER JOIN b_file f
								ON f.ID = versions.FILE_ID
						{$entityFieldsGroupSql}
					";
				}

				elseif (
					$ufType === \CUserTypeFile::USER_TYPE_ID
				)
				{
					$querySql = "
						SELECT 
							SUM(f.FILE_SIZE) as FILE_SIZE,
							COUNT(DISTINCT f.ID) as FILE_COUNT,
							0 as DISK_SIZE,
							0 as DISK_COUNT
							{$entityFieldsSql}
						FROM
							{$utmEntityTableName} ufsrc
							INNER JOIN ( {$entityFilterQuerySql} ) entity 
								ON entity.ID = ufsrc.VALUE_ID
							INNER JOIN b_file f
								ON f.ID = ufsrc.VALUE_INT
								AND ufsrc.FIELD_ID = '{$ufId}'
						{$entityFieldsGroupSql}
					";
				}
			}
		}
		else
		{
			$ufEntityTableFieldName = $userField['FIELD_NAME'];
			$utsEntityTableName = 'b_uts_'.mb_strtolower($ufName);

			if (isset(self::$tablesInformation[$utsEntityTableName]))
			{
				if (
					$isDiskAvailable &&
					$ufType === Disk\Uf\FileUserType::USER_TYPE_ID
				)
				{
					$querySql = "
						SELECT 
							SUM(f.FILE_SIZE) as FILE_SIZE,
							COUNT(DISTINCT f.ID) as FILE_COUNT,
							SUM(f.FILE_SIZE) as DISK_SIZE,
							COUNT(DISTINCT f.ID) as DISK_COUNT
							{$entityFieldsSql}
						FROM
							{$utsEntityTableName} ufsrc
							INNER JOIN ( {$entityFilterQuerySql} ) entity 
								ON entity.ID = ufsrc.VALUE_ID
							INNER JOIN b_disk_attached_object attached
								ON attached.ID = cast(ufsrc.{$ufEntityTableFieldName} as UNSIGNED)
								and ufsrc.{$ufEntityTableFieldName} REGEXP '^[0-9]+$'
							INNER JOIN b_disk_object files
								ON files.ID = attached.OBJECT_ID 
								AND files.ID = files.REAL_OBJECT_ID
								AND files.TYPE = '".Disk\Internals\ObjectTable::TYPE_FILE."'
							INNER JOIN b_file f
								ON f.ID = files.FILE_ID 
						{$entityFieldsGroupSql}
					";
				}

				elseif (
					$isDiskAvailable &&
					$ufType === Disk\Uf\VersionUserType::USER_TYPE_ID
				)
				{
					$querySql = "
						SELECT 
							SUM(f.FILE_SIZE) as FILE_SIZE,
							COUNT(DISTINCT f.ID) as FILE_COUNT,
							SUM(f.FILE_SIZE) as DISK_SIZE,
							COUNT(DISTINCT f.ID) as DISK_COUNT
							{$entityFieldsSql}
						FROM
							{$utsEntityTableName} ufsrc
							INNER JOIN ( {$entityFilterQuerySql} ) entity 
								ON entity.ID = ufsrc.VALUE_ID
							INNER JOIN b_disk_attached_object attached
								ON attached.ID = cast(ufsrc.{$ufEntityTableFieldName} as UNSIGNED)
								and ufsrc.{$ufEntityTableFieldName} REGEXP '^[0-9]+$'
							INNER JOIN b_disk_version versions
								ON versions.ID = attached.VERSION_ID 
							INNER JOIN b_disk_object files
								ON files.ID = versions.OBJECT_ID
								AND files.ID = attached.OBJECT_ID
								AND files.ID = files.REAL_OBJECT_ID
								AND files.TYPE = '".Disk\Internals\ObjectTable::TYPE_FILE."'
							INNER JOIN b_file f
								ON f.ID = versions.FILE_ID 
						{$entityFieldsGroupSql}
					";
				}

				elseif (
					$ufType === \CUserTypeFile::USER_TYPE_ID
				)
				{
					$querySql = "
						SELECT 
							SUM(f.FILE_SIZE) as FILE_SIZE,
							COUNT(DISTINCT f.ID) as FILE_COUNT,
							0 as DISK_SIZE,
							0 as DISK_COUNT
							{$entityFieldsSql}
						FROM
							{$utsEntityTableName} ufsrc
							INNER JOIN ( {$entityFilterQuerySql} ) entity 
								ON entity.ID = ufsrc.VALUE_ID
							INNER JOIN b_file f
								ON f.ID = cast(ufsrc.{$ufEntityTableFieldName} as UNSIGNED)
								and ufsrc.{$ufEntityTableFieldName} REGEXP '^[0-9]+$'
						{$entityFieldsGroupSql}
					";
				}
			}
		}

		return $querySql;
	}


	/**
	 * Gets SQL query code for disk attached entity.
	 *
	 * @param string $entityClass Entity class name.
	 * @param string $diskConnector Connector class name.
	 * @param array $entityGroupField Entity fields to group by.
	 *
	 * @return string
	 */
	protected function prepareDiskAttachedQuery($entityClass, $diskConnector, array $entityGroupField = array())
	{
		if (self::isModuleAvailable('disk') !== true)
		{
			return '';
		}

		/**
		 * @var ORM\Data\DataManager $entityClass
		 */
		$entityQuery = $entityClass::query();
		$entityEntity = $entityClass::getEntity();

		$entityQuery->addSelect('ID');

		// STAGE_SEMANTIC_ID
		if ($entityClass == Crm\QuoteTable::class)
		{
			Volume\Quote::registerStageField($entityQuery, '', 'QUOTE_STAGE_SEMANTIC_ID');
		}
		if ($entityClass == Crm\InvoiceTable::class)
		{
			Volume\Invoice::registerStageField($entityQuery, '', 'INVOICE_STAGE_SEMANTIC_ID');
		}
		// DATE
		if (
			$entityClass == Crm\CompanyTable::class ||
			$entityClass == Crm\ContactTable::class
		)
		{
			$dayField = new ORM\Fields\ExpressionField(
				'DATE_CREATE_SHORT',
				'DATE(%s)',
				'DATE_CREATE'
			);
			$entityQuery->registerRuntimeField($dayField);
		}
		if (
			$entityClass == Crm\InvoiceTable::class
		)
		{
			$dayField = new ORM\Fields\ExpressionField(
				'DATE_CREATE_SHORT',
				'DATE(%s)',
				'DATE_INSERT'
			);
			$entityQuery->registerRuntimeField($dayField);
		}

		$entityFieldsSql = '';
		$entityFieldsGroupSql = '';
		$entityFields = array();
		foreach ($entityGroupField as $alias => $field)
		{
			$entityQuery->addSelect($field, $alias);
			$entityFields[] = 'entity.'. $alias;
		}

		if ($this->prepareEntityFilter($entityQuery, $entityEntity))
		{
			$entityFilterQuerySql = $entityQuery->getQuery();

			if (count($entityFields) > 0)
			{
				$entityFieldsSql = ', '.implode(', ', $entityFields);
				$entityFieldsGroupSql = 'GROUP BY '.implode(', ', $entityFields);
			}
		}
		else
		{
			// cannot filter this Entity
			return '';
		}

		$attachedEntitySql = Main\Application::getConnection()->getSqlHelper()->forSql($diskConnector);
		$querySql = "
			SELECT 
				SUM(ver.SIZE) as FILE_SIZE,
				COUNT(ver.FILE_ID) as FILE_COUNT,
				SUM(ver.SIZE) as DISK_SIZE,
				COUNT(DISTINCT files.ID) as DISK_COUNT
				{$entityFieldsSql}
			FROM 
				b_disk_version ver 
				INNER JOIN b_disk_object files
					ON files.ID  = ver.OBJECT_ID
					AND files.TYPE = '".Disk\Internals\ObjectTable::TYPE_FILE."'
					AND files.ID = files.REAL_OBJECT_ID
				INNER JOIN b_disk_attached_object attached 
					ON attached.OBJECT_ID = files.ID
					AND (attached.VERSION_ID IS NULL OR attached.VERSION_ID = ver.ID)
					AND attached.ENTITY_TYPE = '{$attachedEntitySql}'
				INNER JOIN ( {$entityFilterQuerySql} ) entity 
					ON entity.ID = attached.ENTITY_ID
			{$entityFieldsGroupSql}
		";

		return $querySql;
	}

	/**
	 * Gets SQL query code for disk attache linked by social network log.
	 *
	 * @param string $entityClass Entity class name.
	 * @param string $eventEntityType Connector class name.
	 * @param array $entityGroupField Entity fields to group by.
	 *
	 * @return string
	 */
	protected function prepareLiveFeedQuery($entityClass, $eventEntityType, array $entityGroupField = array())
	{
		if (!(self::isModuleAvailable('socialnetwork') && self::isModuleAvailable('disk')))
		{
			return '';
		}

		/**
		 * @var ORM\Data\DataManager $entityClass
		 */
		$entityQuery = $entityClass::query();
		$entityEntity = $entityClass::getEntity();

		$entityQuery->addSelect('ID');

		// STAGE_SEMANTIC_ID
		if ($entityClass == Crm\QuoteTable::class)
		{
			Volume\Quote::registerStageField($entityQuery, '', 'QUOTE_STAGE_SEMANTIC_ID');
		}
		if ($entityClass == Crm\InvoiceTable::class)
		{
			Volume\Invoice::registerStageField($entityQuery, '', 'INVOICE_STAGE_SEMANTIC_ID');
		}
		// DATE
		if (
			$entityClass == Crm\CompanyTable::class ||
			$entityClass == Crm\ContactTable::class
		)
		{
			$dayField = new ORM\Fields\ExpressionField(
				'DATE_CREATE_SHORT',
				'DATE(%s)',
				'DATE_CREATE'
			);
			$entityQuery->registerRuntimeField($dayField);
		}
		if (
			$entityClass == Crm\InvoiceTable::class
		)
		{
			$dayField = new ORM\Fields\ExpressionField(
				'DATE_CREATE_SHORT',
				'DATE(%s)',
				'DATE_INSERT'
			);
			$entityQuery->registerRuntimeField($dayField);
		}

		$entityFieldsSql = '';
		$entityFieldsGroupSql = '';
		$entityFields = array();
		foreach ($entityGroupField as $alias => $field)
		{
			$entityQuery->addSelect($field, $alias);
			$entityFields[] = 'entity.'. $alias;
		}

		if ($this->prepareEntityFilter($entityQuery, $entityEntity))
		{
			$entityFilterQuerySql = $entityQuery->getQuery();

			if (count($entityFields) > 0)
			{
				$entityFieldsSql = ', '.implode(', ', $entityFields);
				$entityFieldsGroupSql = 'GROUP BY '.implode(', ', $entityFields);
			}
		}
		else
		{
			// cannot filter this Entity
			return '';
		}

		$logTable = \Bitrix\Socialnetwork\LogTable::getTableName();
		$helper = Main\Application::getConnection()->getSqlHelper();

		$attachedEntitySql = $helper->forSql(Disk\Uf\SonetLogConnector::class);
		$eventEntitySql = $helper->forSql($eventEntityType);

		$querySql = "
			SELECT 
				SUM(ver.SIZE) as FILE_SIZE,
				COUNT(ver.FILE_ID) as FILE_COUNT,
				SUM(ver.SIZE) as DISK_SIZE,
				COUNT(DISTINCT files.ID) as DISK_COUNT
				{$entityFieldsSql}
			FROM 
				b_disk_version ver 
				INNER JOIN b_disk_object files
					ON files.ID  = ver.OBJECT_ID
					AND files.TYPE = '".Disk\Internals\ObjectTable::TYPE_FILE."'
					AND files.ID = files.REAL_OBJECT_ID
				INNER JOIN b_disk_attached_object attached
					ON attached.OBJECT_ID = files.ID
					AND (attached.VERSION_ID IS NULL OR attached.VERSION_ID = ver.ID)
					AND attached.ENTITY_TYPE = '{$attachedEntitySql}'
				INNER JOIN {$logTable} live_feed_log
					ON attached.ENTITY_ID = live_feed_log.ID
					AND live_feed_log.ENTITY_TYPE = '{$eventEntitySql}'
				INNER JOIN ( {$entityFilterQuerySql} ) entity 
					ON entity.ID = live_feed_log.ENTITY_ID
			{$entityFieldsGroupSql}
		";

		return $querySql;
	}



	/**
	 * Method generates component action list for measure process.
	 *
	 * @param string $entityClass Entity class name.
	 * @param string $dateFieldAlias Date field alias.
	 * @param array $actionAliases Command alias.
	 *
	 * @return array
	 */
	protected function prepareRangeActionList($entityClass, $dateFieldAlias, $actionAliases)
	{
		$indicatorId = static::getIndicatorId();

		$queueList = array();

		$actionCommands = array(
			'MEASURE_ENTITY',
			'MEASURE_FILE',
			'MEASURE_ACTIVITY',
			'MEASURE_EVENT',
		);

		$maxIdRange = -1;
		if (isset(static::$maxIdRangeCache[$entityClass]))
		{
			$maxIdRange = static::$maxIdRangeCache[$entityClass];
		}
		else
		{
			$cache = new \CPHPCache();
			if ($cache->startDataCache(3 * 3600, "{$entityClass}:{$indicatorId}:maxIdRange", 'crm/configs/volume'))
			{
				/**
				 * @var ORM\Data\DataManager $entityClass
				 */
				$entityQuery = $entityClass::query();

				$row0 = $entityQuery
					->registerRuntimeField(new ORM\Fields\ExpressionField('CNT', 'COUNT(*)'))
					->addSelect('CNT')
					->exec()
					->fetch();

				if ($row0)
				{
					if ((int)$row0['CNT'] > 500000)
					{
						$maxIdRange = 100000;
					}
					elseif ((int)$row0['CNT'] > 100000)
					{
						$maxIdRange = 50000;
					}
				}

				static::$maxIdRangeCache[$entityClass] = $maxIdRange;

				$cache->endDataCache($maxIdRange);
			}
			else
			{
				$maxIdRange = $cache->getVars();
				static::$maxIdRangeCache[$entityClass] = $maxIdRange;
			}
		}

		if ($maxIdRange > 0)
		{
			$cache = new \CPHPCache();
			if ($cache->startDataCache(3 * 3600, "{$entityClass}:{$indicatorId}:queueList", 'crm/configs/volume'))
			{
				/**
				 * @var ORM\Data\DataManager $entityClass
				 */
				$query = $entityClass::query();

				$month = new ORM\Fields\ExpressionField('YY', "YEAR(%s)", $dateFieldAlias);
				$query->registerRuntimeField($month)->addSelect('YY');

				$month = new ORM\Fields\ExpressionField('MM', "MONTH(%s)", $dateFieldAlias);
				$query->registerRuntimeField($month)->addSelect('MM');

				$month = new ORM\Fields\ExpressionField('DD', "DAY(%s)", $dateFieldAlias);
				$query->registerRuntimeField($month)->addSelect('DD');

				$border = new ORM\Fields\ExpressionField('BRDR', 'MAX(%s)', 'ID');
				$query->registerRuntimeField($border)->addSelect('BRDR');

				$counter = new ORM\Fields\ExpressionField('CNT', 'COUNT(*)');
				$query->registerRuntimeField($counter)->addSelect('CNT');

				$query->setGroup(array('YY', 'MM', 'DD'))->setOrder(array('BRDR' => 'ASC'));

				$res = $query->exec();
				if ($row = $res->fetch())
				{
					$count = 0;
					$appendQueueList = function($range) use ($actionCommands, $actionAliases, &$queueList, $indicatorId)
					{
						foreach ($actionCommands as $command)
						{
							if (isset($actionAliases[$command]))
							{
								$queueList[] = array(
									'indicatorId' => $indicatorId,
									'action' => $actionAliases[$command],
									'range' => $range,
								);
							}
						}
					};

					$prevId = null;
					do
					{
						if (
							$row['CNT'] >= $maxIdRange ||
							($count + (int)$row['CNT']) >= $maxIdRange * 1.3 ||
							$count >= $maxIdRange
						)
						{
							$range = '';
							if ($prevId > 0)
							{
								$range .= $prevId;
							}
							$range .= '-'.(int)$row['BRDR'];

							$appendQueueList($range);

							$count = 0;
							$prevId = (int)$row['BRDR'];
							continue;
						}

						$count += (int)$row['CNT'];
					}
					while ($row = $res->fetch());

					if ($count >= 0 && $prevId > 0)
					{
						$range = $prevId.'-';

						$appendQueueList($range);
					}
				}

				$cache->endDataCache($queueList);
			}
			else
			{
				$queueList = $cache->getVars();
			}
		}
		else
		{
			foreach ($actionCommands as $command)
			{
				if (isset($actionAliases[$command]))
				{
					$queueList[] = array(
						'indicatorId' => $indicatorId,
						'action' => $actionAliases[$command],
					);
				}
			}
		}

		return $queueList;
	}


	/**
	 * Method generates component action list for measure process.
	 *
	 * @param string $entityClass Entity class name.
	 * @param string $dateFieldAlias Date field alias.
	 * @param array $actionAliases Command alias.
	 *
	 * @return array
	 */
	protected function prepareDateActionList($entityClass, $dateFieldAlias, $actionAliases)
	{
		$indicatorId = static::getIndicatorId();

		$queueList = array();

		$actionCommands = array(
			'MEASURE_ENTITY',
			'MEASURE_FILE',
			'MEASURE_ACTIVITY',
			'MEASURE_EVENT',
		);

		/**
		 * @var ORM\Data\DataManager $entityClass
		 */
		$query = $entityClass::query();

		$dateMin = new ORM\Fields\ExpressionField('DATE_MIN', "DATE_FORMAT(MIN(%s), '%%Y-%%m-%%d')", $dateFieldAlias);
		$query->registerRuntimeField($dateMin)->addSelect('DATE_MIN');

		$monthCount = new ORM\Fields\ExpressionField('MONTHS', 'TIMESTAMPDIFF(MONTH, MIN(%s), MAX(%s))', array($dateFieldAlias, $dateFieldAlias));
		$query->registerRuntimeField($monthCount)->addSelect('MONTHS');

		$res = $query->exec();
		if ($row = $res->fetch())
		{
			list($dateSplitPeriod, $dateSplitPeriodUnits) = $this->getDateSplitPeriod();

			$dateMin =  new Main\Type\DateTime($row['DATE_MIN'], 'Y-m-d');
			$months =  $row['MONTHS'];

			while ($months >= 0)
			{
				$period = $dateMin->format('Y.m');
				$dateMin->add("$dateSplitPeriod $dateSplitPeriodUnits");
				$period .= '-';
				$period .= $dateMin->format('Y.m');
				$months -= $dateSplitPeriod;

				foreach ($actionCommands as $command)
				{
					if (isset($actionAliases[$command]))
					{
						$queueList[] = array(
							'indicatorId' => $indicatorId,
							'action' => $actionAliases[$command],
							'period' => $period,
						);
					}
				}
			}
		}

		return $queueList;
	}

	/**
	 * Sets owner id.
	 * @param int $ownerId User id.
	 * @return void
	 */
	public function setOwner($ownerId)
	{
		$this->ownerId = $ownerId;
	}

	/**
	 * Gets owner id.
	 * @return int|null
	 */
	public function getOwner()
	{
		return $this->ownerId > 0 ? $this->ownerId : 0;
	}


	/**
	 * Adds an array of errors to the collection.
	 *
	 * @param Main\Error[] | Main\Error $errors Raised error.
	 * @return void
	 */
	public function collectError($errors)
	{
		if (!($this->errorCollection instanceof Main\ErrorCollection))
		{
			$this->errorCollection = new Main\ErrorCollection();
		}

		if (is_array($errors))
		{
			$this->errorCollection->add($errors);
		}
		else
		{
			$this->errorCollection->add(array($errors));
		}
	}

	/**
	 * Returns errors list.
	 *
	 * @implements Volume\IVolumeClear
	 * @return Main\Error|null
	 */
	public function getLastError()
	{
		if ($this->errorCollection instanceof Main\ErrorCollection)
		{
			$offset = $this->errorCollection->count() - 1;
			return $this->errorCollection->offsetGet($offset);
		}

		return null;
	}

	/**
	 * Returns process offset.
	 *
	 * @implements Volume\IVolumeClear
	 * @p
	 * @return int
	 */
	public function getProcessOffset()
	{
		return $this->processOffset;
	}

	/**
	 * Setup process offset.
	 *
	 * @implements Volume\IVolumeClear
	 * @param int $offset Offset position.
	 * @return void
	 */
	public function setProcessOffset($offset)
	{
		$this->processOffset = $offset;
	}

	/**
	 * Sets dropped count of entity attachments.
	 *
	 * @implements Volume\IVolumeClearFile
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setDroppedFileCount($count)
	{
		$this->droppedFileCount = $count;
	}

	/**
	 * Returns dropped count of entity attachments.
	 *
	 * @implements Volume\IVolumeClearFile
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementDroppedFileCount($count = 1)
	{
		$this->droppedFileCount += $count;
	}

	/**
	 * Returns dropped count of entity attachments.
	 * @implements Volume\IVolumeClearFile
	 *
	 * @return int
	 */
	public function getDroppedFileCount()
	{
		return $this->droppedFileCount;
	}


	/**
	 * Sets dropped count of entities.
	 *
	 * @implements Volume\IVolumeClear
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setDroppedEntityCount($count)
	{
		$this->droppedCount = $count;
	}

	/**
	 * Returns dropped count of entities.
	 *
	 * @implements Volume\IVolumeClear
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementDroppedEntityCount($count = 1)
	{
		$this->droppedCount += $count;
	}

	/**
	 * Returns dropped count of entities.
	 *
	 * @implements Volume\IVolumeClear
	 * @return int
	 */
	public function getDroppedEntityCount()
	{
		return $this->droppedCount;
	}

	/**
	 * Returns error count.
	 *
	 * @implements Volume\IVolumeClear
	 * @return int
	 */
	public function getFailCount()
	{
		return $this->failCount;
	}

	/**
	 * Sets error count.
	 *
	 * @implements Volume\IVolumeClear
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setFailCount($count)
	{
		$this->failCount = $count;
	}

	/**
	 * Returns error count.
	 *
	 * @implements Volume\IVolumeClearEvent
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementFailCount($count = 1)
	{
		$this->failCount += $count;
	}

	/**
	 * Start up timer.
	 *
	 * @implements Volume\IVolumeClear
	 * @param int $timeLimit Time limit.
	 * @return void
	 */
	public function startTimer($timeLimit = 25)
	{
		$this->timeLimit = $timeLimit;

		if (defined('START_EXEC_TIME') && START_EXEC_TIME > 0)
		{
			$this->startTime = (int)START_EXEC_TIME;
		}
		else
		{
			$this->startTime = time();
		}
	}


	/**
	 * Tells true if time limit reached.
	 *
	 * @implements Volume\IVolumeClear
	 * @return boolean
	 */
	public function hasTimeLimitReached()
	{
		if ($this->timeLimit > 0)
		{
			if ($this->timeLimitReached)
			{
				return true;
			}
			if ((time() - $this->startTime) >= $this->timeLimit)
			{
				$this->timeLimitReached = true;

				return true;
			}
		}

		return false;
	}


	/**
	 * Returns count of files in disk folder.
	 *
	 * @param Disk\Folder $folder Disk folder to analize.
	 * @param array $filter Additional filter for file selection.
	 *
	 * @return int
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function countDiskFiles(Disk\Folder $folder, $filter = array())
	{
		$count = 0;
		if (self::isModuleAvailable('disk'))
		{
			if ($folder instanceof Disk\Folder)
			{
				$filter['=STORAGE_ID'] = $folder->getStorageId();
				$filter['=PATH_CHILD.PARENT_ID'] = $folder->getId();
				$filter['=TYPE'] = Disk\Internals\ObjectTable::TYPE_FILE;

				$count = Disk\Internals\ObjectTable::getCount($filter);
			}
		}

		return $count;
	}


	/**
	 * Performs dropping entity attachments.
	 *
	 * @param Disk\Folder $folder Disk folder to analize.
	 * @param array $filter Additional filter for file selection.
	 *
	 * @return boolean
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function clearDiskFiles(Disk\Folder $folder, $filter = array())
	{
		if (!self::isModuleAvailable('disk'))
		{
			return false;
		}
		if (!($folder instanceof Disk\Folder))
		{
			return false;
		}

		$filter['=STORAGE_ID'] = $folder->getStorageId();
		$filter['=PATH_CHILD.PARENT_ID'] = $folder->getId();
		$filter['=TYPE'] = Disk\Internals\ObjectTable::TYPE_FILE;

		if ($this->getProcessOffset() > 0)
		{
			$filter['>ID'] = $this->getProcessOffset();
		}

		$objectList = Disk\Internals\ObjectTable::getList(array(
			'filter' => $filter,
			'order' => array(
				'PATH_CHILD.DEPTH_LEVEL' => 'DESC',
				'ID' => 'ASC',
			),
			'limit' => static::MAX_FILE_PER_INTERACTION,
		));

		$success = true;

		foreach ($objectList as $row)
		{
			$file = Disk\BaseObject::buildFromArray($row);

			if($file instanceof Disk\File)
			{
				/** @var Disk\File $file */
				$securityContext = $this->getDiskSecurityContext($file);
				if($file->canDelete($securityContext))
				{
					if ($this->deleteDiskFile($file))
					{
						$this->incrementDroppedFileCount();
					}
					else
					{
						//$this->collectError(new Error('Deletion failed with file #'. $file->getId(), self::ERROR_DELETION_FAILED));
						$this->incrementFailCount();
					}
				}
				else
				{
					$this->collectError(new Error('Access denied to file #'. $file->getId(), self::ERROR_PERMISSION_DENIED));
					$this->incrementFailCount();
				}
			}

			$this->setProcessOffset($row['ID']);

			if ($this->hasTimeLimitReached())
			{
				$success = false;
				break;
			}
		}

		return $success;
	}

	/**
	 * Returns disk security context.
	 * @param Disk\BaseObject $object File or folder.
	 * @return Disk\Security\SecurityContext
	 */
	protected function getDiskSecurityContext($object)
	{
		static $securityContext = null;

		$userId = $this->getUser()->getId();

		if (!($securityContext instanceof Disk\Security\SecurityContext))
		{
			if (Disk\User::isCurrentUserAdmin())
			{
				$securityContext = new Disk\Security\FakeSecurityContext($userId);
			}
			else
			{
				$securityContext = $object->getStorage()->getSecurityContext($userId);
			}
		}

		return $securityContext;
	}

	/**
	 * Deletes file.
	 * @param Disk\File $file File to drop.
	 * @return boolean
	 */
	protected function deleteDiskFile(Disk\File $file)
	{
		$userId = $this->getUser()->getId();

		if(!$file->delete($userId))
		{
			$this->collectError($file->getErrors());

			return false;
		}

		return true;
	}
}

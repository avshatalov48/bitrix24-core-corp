<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Localization\Loc;


class Callrecord
	extends Volume\Base
	implements Volume\IVolumeClear, Volume\IVolumeClearFile, Volume\IVolumeUrl
{
	/** @var Volume\Activity */
	private $activityFiles;

	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_CALL_RECORD_TITLE');
	}

	/**
	 * Returns availability to drop entity.
	 *
	 * @return boolean
	 */
	public function canClearEntity()
	{
		return true;
	}

	/**
	 * Returns availability to drop entity attachments.
	 *
	 * @return boolean
	 */
	public function canClearFile()
	{
		return $this->canClearEntity();
	}

	/**
	 * Can filter applied to the indicator.
	 * @return boolean
	 */
	public function canBeFiltered()
	{
		return true;
	}

	/**
	 * Tells that is is participated in the total volume.
	 * @return boolean
	 */
	public function isParticipatedTotal()
	{
		return false;
	}

	/**
	 * Get entity list path.
	 * @return string
	 */
	public function getUrl()
	{
		static $entityListPath;
		if($entityListPath === null)
		{
			$entityListPath = \CComponentEngine::MakePathFromTemplate(
				\Bitrix\Main\Config\Option::get('crm', 'path_to_activity_list', '/crm/activity/')
			);
		}

		return $entityListPath;
	}

	/**
	 * Get filter reset params for entity grid.
	 * @return array
	 */
	public function getGridFilterResetParam()
	{
		$entityListReset = array(
			'FILTER_ID' => 'CRM_ACTIVITY_LIST_MY_ACTIVITIES',
			'GRID_ID' => 'CRM_ACTIVITY_LIST_MY_ACTIVITIES',
			'FILTER_FIELDS' => 'CREATED,TYPE_ID',
		);

		return $entityListReset;
	}

	/**
	 * Get filter alias for url to entity list path.
	 * @return array
	 */
	public function getFilterAlias()
	{
		return array(
			'DATE_CREATE' => 'CREATED',
		);
	}

	/**
	 * Component action list for measure process.
	 * @param array $componentCommandAlias Command alias.
	 * @return array
	 */
	public function getActionList($componentCommandAlias)
	{
		return $this->prepareRangeActionList(
			Crm\ActivityTable::class,
			'CREATED',
			array(
				'MEASURE_ENTITY' => $componentCommandAlias['MEASURE_ENTITY'],
				'MEASURE_FILE' => $componentCommandAlias['MEASURE_FILE'],
			)
		);
	}

	/**
	 * Runs measure test for files.
	 * @return self
	 */
	public function measureEntity()
	{
		self::loadTablesInformation();

		$activity = new Volume\Activity();
		$activity->setFilter($this->getFilter());

		$activityQuery = $activity->prepareQuery();

		$activity->prepareFilter($activityQuery);

		// only call records
		$activityQuery->where('TYPE_ID', '=', \CCrmActivityType::Call);

		$activityCount = new ORM\Fields\ExpressionField('ACTIVITY_COUNT', 'COUNT(DISTINCT %s)', 'ID');
		$activityBindingCount = new ORM\Fields\ExpressionField('BINDINGS_COUNT', 'COUNT(%s)', 'BINDINGS.ID');
		$activityQuery
			->registerRuntimeField($activityCount)
			->registerRuntimeField($activityBindingCount)
			->addSelect('ACTIVITY_COUNT')
			->addSelect('BINDINGS_COUNT');

		$entityGroupField = array(
			'DATE_CREATE' => 'DATE_CREATED_SHORT',
			'STAGE_SEMANTIC_ID' => 'STAGE_SEMANTIC_ID',
		);
		foreach ($entityGroupField as $alias => $field)
		{
			$activityQuery->addSelect($field, $alias);
			$activityQuery->addGroup($field);
		}

		$querySql = $activityQuery->getQuery();

		if ($querySql != '')
		{
			$avgActivityTableRowLength = (double)self::$tablesInformation[Crm\ActivityTable::getTableName()]['AVG_SIZE'];
			$avgBindingTableRowLength = (double)self::$tablesInformation[Crm\ActivityBindingTable::getTableName()]['AVG_SIZE'];

			$querySql = "
				SELECT 
					'".static::getIndicatorId()."' as INDICATOR_TYPE,
					'".$this->getOwner()."' as OWNER_ID,
					DATE_CREATE,
					STAGE_SEMANTIC_ID, 
					(
						ACTIVITY_COUNT * {$avgActivityTableRowLength} + 
						BINDINGS_COUNT * {$avgBindingTableRowLength} ) as ACTIVITY_SIZE,
					ACTIVITY_COUNT
				FROM 
				(
					{$querySql}
				) src
			";

			$connection = \Bitrix\Main\Application::getConnection();

			$this->checkTemporally();

			$data = array(
				'INDICATOR_TYPE' => '',
				'OWNER_ID' => '',
				'DATE_CREATE' => new \Bitrix\Main\Type\Date(),
				'STAGE_SEMANTIC_ID' => '',
				'ENTITY_SIZE' => '',
				'ENTITY_COUNT' => '',
			);

			$insert = $connection->getSqlHelper()->prepareInsert(Crm\VolumeTmpTable::getTableName(), $data);

			$sqlIns = 'INSERT INTO '.$connection->getSqlHelper()->quote(Crm\VolumeTmpTable::getTableName()). '('. $insert[0]. ') ';

			$querySql = $sqlIns. $querySql;

			$connection->queryExecute($querySql);

			$this->copyTemporallyData();
		}

		return $this;
	}


	/**
	 * Runs measure test for files.
	 * @return self
	 */
	public function measureFiles()
	{
		$entityGroupField = array(
			'DATE_CREATE' => 'DATE_CREATED_SHORT',
			'STAGE_SEMANTIC' => 'STAGE_SEMANTIC_ID',
		);

		$activityQuery = $this->prepareQuery($entityGroupField);

		// only call records
		//$activityQuery->where('TYPE_ID', '=', \CCrmActivityType::Call);

		foreach ($entityGroupField as $alias => $field)
		{
			$activityQuery->addSelect($field, $alias);
			$activityQuery->addGroup($field);
		}

		$activityQuery->addFilter('>FILE_COUNT', 0);

		$querySql = $activityQuery->getQuery();

		if ($querySql != '')
		{
			$querySql = "
				SELECT 
					'".static::getIndicatorId()."' as INDICATOR_TYPE,
					'".$this->getOwner()."' as OWNER_ID,
					DATE_CREATE,
					STAGE_SEMANTIC, 
					SUM(FILE_SIZE) as FILE_SIZE,
					SUM(FILE_COUNT) as FILE_COUNT,
					SUM(DISK_SIZE) as DISK_SIZE,
					SUM(DISK_COUNT) as DISK_COUNT
				FROM 
				(
					{$querySql}
				) src
				GROUP BY
					DATE_CREATE,
					STAGE_SEMANTIC
			";

			Crm\VolumeTable::updateFromSelect(
				$querySql,
				array(
					'FILE_SIZE' => 'destination.FILE_SIZE + source.FILE_SIZE',
					'FILE_COUNT' => 'destination.FILE_COUNT + source.FILE_COUNT',
					'DISK_SIZE' => 'destination.DISK_SIZE + source.DISK_SIZE',
					'DISK_COUNT' => 'destination.DISK_COUNT + source.DISK_COUNT',
				),
				array(
					'INDICATOR_TYPE' => 'INDICATOR_TYPE',
					'OWNER_ID' => 'OWNER_ID',
					'DATE_CREATE' => 'DATE_CREATE',
					'STAGE_SEMANTIC_ID' => 'STAGE_SEMANTIC',
				)
			);
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$sqlDel = "
			DELETE FROM ".$connection->getSqlHelper()->quote(Crm\VolumeTable::getTableName())." 
			WHERE 
				INDICATOR_TYPE = '".static::getIndicatorId()."' AND
				OWNER_ID = '".$this->getOwner()."' AND
				FILE_COUNT = 0
		";
		$connection->queryExecute($sqlDel);

		return $this;
	}

	/**
	 * Performs dropping entity.
	 *
	 * @return boolean
	 */
	public function clearEntity()
	{
		if (!$this->canClearEntity())
		{
			return false;
		}

		$query = $this->prepareQuery();

		$success = true;

		if ($this->prepareFilter($query))
		{
			$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());
			$diskAvailable = \Bitrix\Main\Loader::includeModule('disk');

			$query
				//->where('TYPE_ID', '=', \CCrmActivityType::Call)// Call records
				->setSelect(array('ID', 'OWNER_TYPE_ID', 'OWNER_ID'))
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(array('ID' => 'ASC'));

			if ($this->getProcessOffset() > 0)
			{
				$query->where('ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			while ($activity = $res->fetch())
			{
				$this->setProcessOffset($activity['ID']);

				if (\CCrmActivity::CheckItemDeletePermission($activity, $userPermissions))
				{
					$activityElementList = Crm\ActivityElementTable::getList(array(
						'filter' => array('=ACTIVITY_ID' => $activity['ID']),
						'select' => array('STORAGE_TYPE_ID', 'ELEMENT_ID'),
					));

					if (\CCrmActivity::DeleteStorageElements($activity['ID']))
					{
						// check existence and force removing
						while ($row = $activityElementList->fetch())
						{
							if ($row['STORAGE_TYPE_ID'] == Crm\Integration\StorageType::File)
							{
								\CFile::Delete($row['ELEMENT_ID']);
							}
							elseif ($row['STORAGE_TYPE_ID'] == Crm\Integration\StorageType::Disk && $diskAvailable)
							{
								$file = \Bitrix\Disk\File::getById($row['ELEMENT_ID']);
								if ($file instanceof \Bitrix\Disk\File)
								{
									if(!$file->delete(\Bitrix\Disk\SystemUser::SYSTEM_USER_ID))
									{
										$this->collectError($file->getErrors());
									}
								}
							}
						}

						if (\CCrmActivity::Delete($activity['ID'], false, false))
						{
							$this->incrementDroppedEntityCount();
						}
						else
						{
							$this->collectError(new Main\Error('Deletion failed with activity #'.$activity['ID'], self::ERROR_DELETION_FAILED));
							$this->incrementFailCount();
						}
					}
					else
					{
						$error = \CCrmActivity::GetLastErrorMessage();
						if (empty($error))
						{
							$error = 'Deletion failed with activity #'.$activity['ID'];
						}
						$this->collectError(new Main\Error($error,self::ERROR_DELETION_FAILED));
						$this->incrementFailCount();
					}
				}
				else
				{
					$this->collectError(new Main\Error('Access denied to activity #'.$activity['ID'], self::ERROR_PERMISSION_DENIED));
					$this->incrementFailCount();
				}

				if ($this->hasTimeLimitReached())
				{
					$success = false;
					break;
				}
			}
		}

		return $success;
	}

	/**
	 * Returns count of entities.
	 *
	 * @return int
	 */
	public function countEntity()
	{
		$count = -1;

		$query = $this->prepareQuery();

		if ($this->prepareFilter($query))
		{
			$count = 0;

			$query
				//->where('TYPE_ID', '=', \CCrmActivityType::Call)// Call records
				->registerRuntimeField(new ORM\Fields\ExpressionField('CNT', 'COUNT(%s)', 'ID'))
				->addSelect('CNT');

			$res = $query->exec();
			if ($row = $res->fetch())
			{
				$count = $row['CNT'];
			}
		}

		return $count;
	}


	/**
	 * Returns query.
	 * @param  array $entityGroupField Fields for grouping.
	 * @return ORM\Query\Query
	 */
	public function prepareQuery($entityGroupField = array())
	{
		$this->activityFiles = new Volume\Activity();
		$this->activityFiles->setFilter($this->getFilter());

		$query = $this->activityFiles->getActivityFileMeasureQuery(Volume\Activity::className(), $entityGroupField);

		// only call records
		$query->where('TYPE_ID', '=', \CCrmActivityType::Call);

		return $query;
	}

	/**
	 * Setups filter params into query.
	 *
	 * @param ORM\Query\Query $query Query.
	 *
	 * @return boolean
	 */
	public function prepareFilter(ORM\Query\Query $query)
	{
		return $this->activityFiles->prepareFilter($query);
	}

	/**
	 * Performs dropping entity attachments.
	 *
	 * @return boolean
	 */
	public function clearFiles()
	{
		if (!$this->canClearEntity())
		{
			return false;
		}

		$query = $this->prepareQuery();

		$success = true;

		if ($this->prepareFilter($query))
		{
			$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());
			$diskAvailable = \Bitrix\Main\Loader::includeModule('disk');

			$query
				//->where('TYPE_ID', '=', \CCrmActivityType::Call)// Call records
				->setSelect(array('ID', 'OWNER_TYPE_ID', 'OWNER_ID'))
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(array('ID' => 'ASC'));

			if ($this->getProcessOffset() > 0)
			{
				$query->where('ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			while ($activity = $res->fetch())
			{
				$this->setProcessOffset($activity['ID']);

				if (\CCrmActivity::CheckItemDeletePermission($activity, $userPermissions))
				{
					$activityElementList = Crm\ActivityElementTable::getList(array(
						'filter' => array('=ACTIVITY_ID' => $activity['ID']),
						'select' => array('STORAGE_TYPE_ID', 'ELEMENT_ID'),
					));

					if (\CCrmActivity::DeleteStorageElements($activity['ID']))
					{
						// check existence and force removing
						while ($row = $activityElementList->fetch())
						{
							if ($row['STORAGE_TYPE_ID'] == Crm\Integration\StorageType::File)
							{
								\CFile::Delete($row['ELEMENT_ID']);
							}
							elseif ($row['STORAGE_TYPE_ID'] == Crm\Integration\StorageType::Disk && $diskAvailable)
							{
								$file = \Bitrix\Disk\File::getById($row['ELEMENT_ID']);
								if ($file instanceof \Bitrix\Disk\File)
								{
									if(!$file->delete(\Bitrix\Disk\SystemUser::SYSTEM_USER_ID))
									{
										$this->collectError($file->getErrors());
									}
								}
								$this->incrementDroppedFileCount();
							}
						}
					}
					else
					{
						$error = \CCrmActivity::GetLastErrorMessage();
						if (empty($error))
						{
							$error = 'Deletion failed with activity #'.$activity['ID'];
						}
						$this->collectError(new Main\Error($error,self::ERROR_DELETION_FAILED));
						$this->incrementFailCount();
					}
				}
				else
				{
					$this->collectError(new Main\Error('Access denied to activity #'.$activity['ID'], self::ERROR_PERMISSION_DENIED));
					$this->incrementFailCount();
				}

				if ($this->hasTimeLimitReached())
				{
					$success = false;
					break;
				}
			}
		}

		return $success;
	}

	/**
	 * Returns count of entities.
	 *
	 * @return int
	 */
	public function countEntityWithFile()
	{
		$count = -1;

		$query = $this->prepareQuery();

		if ($this->prepareFilter($query))
		{
			$count = 0;

			$query
				//->where('TYPE_ID', '=', \CCrmActivityType::Call)// Call records
				->registerRuntimeField(new ORM\Fields\ExpressionField('CNT', 'COUNT(%s)', 'ID'))
				->registerRuntimeField(new ORM\Fields\ExpressionField('CNTFL', 'IFNULL(%s,0) + IFNULL(%s,0)', ['FILE.FILE_COUNT', 'DISK_FILE.DISK_COUNT']))
				->addSelect('CNT')
				->addFilter('>CNTFL', 0);

			$res = $query->exec();
			if ($row = $res->fetch())
			{
				$count = $row['CNT'];
			}
		}

		return $count;
	}
}


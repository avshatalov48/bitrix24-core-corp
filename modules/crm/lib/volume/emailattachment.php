<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Disk;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;


class EmailAttachment
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
		return Loc::getMessage('CRM_VOLUME_EMAIL_TITLE');
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

		// only email attachments
		$activityQuery->where('TYPE_ID', '=', \CCrmActivityType::Email);

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

		// only email attachments
		//$activityQuery->where('TYPE_ID', '=', \CCrmActivityType::Email);

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
	 * @return int
	 */
	public function clearEntity()
	{
		if (!$this->canClearEntity())
		{
			return -1;
		}

		$query = $this->prepareQuery();

		$dropped = -1;

		if ($this->prepareFilter($query))
		{
			$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());
			$diskAvailable = \Bitrix\Main\Loader::includeModule('disk');
			$exceptMailTemplateAttachment = Volume\MailTemplate::getAttachmentList();

			$query
				//->where('TYPE_ID', '=', \CCrmActivityType::Email)// Email
				->setSelect(array('ID', 'OWNER_TYPE_ID', 'OWNER_ID'))
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(array('ID' => 'ASC'));

			if ($this->getProcessOffset() > 0)
			{
				$query->where('ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			$dropped = 0;
			while ($activity = $res->fetch())
			{
				$this->setProcessOffset($activity['ID']);

				if (\CCrmActivity::CheckItemDeletePermission($activity, $userPermissions))
				{
					$activityElementList = Crm\ActivityElementTable::getList(array(
						'filter' => array('=ACTIVITY_ID' => $activity['ID']),
						'select' => array('STORAGE_TYPE_ID', 'ELEMENT_ID'),
					));

					// check existence and force removing
					$failOccurred = false;
					while ($row = $activityElementList->fetch())
					{
						// ignoring mail template attachments
						if ($exceptMailTemplateAttachment && in_array((int)$row['ELEMENT_ID'], $exceptMailTemplateAttachment))
						{
							continue;
						}
						$elementIds = array($row['ELEMENT_ID']);
						if (\CCrmActivity::DeleteStorageElements($activity['ID'], array('STORAGE_ELEMENT_IDS' => $elementIds)))
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
						else
						{
							$failOccurred = true;
							$error = \CCrmActivity::GetLastErrorMessage();
							if (empty($error))
							{
								$error = 'Deletion failed with activity #'.$activity['ID'];
							}
							$this->collectError(new Main\Error($error,self::ERROR_DELETION_FAILED));
							$this->incrementFailCount();
						}
					}
					if ($failOccurred !== true)
					{
						// drop activity
						if (\CCrmActivity::Delete($activity['ID'], false, false))
						{
							$this->incrementDroppedEntityCount();
							$dropped ++;
						}
						else
						{
							$this->collectError(new Main\Error('Deletion failed with activity #'.$activity['ID'], self::ERROR_DELETION_FAILED));
							$this->incrementFailCount();
						}
					}
				}
				else
				{
					$this->collectError(new Main\Error('Access denied to activity #'.$activity['ID'], self::ERROR_PERMISSION_DENIED));
					$this->incrementFailCount();
				}

				if ($this->hasTimeLimitReached())
				{
					break;
				}
			}
		}
		else
		{
			$this->collectError(new Main\Error('Filter error', self::ERROR_DELETION_FAILED));
		}

		return $dropped;
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
				//->where('TYPE_ID', '=', \CCrmActivityType::Email)// Email
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
	 * @param  array $entityGroupField Fileds for groupping.
	 * @return ORM\Query\Query
	 */
	public function prepareQuery($entityGroupField = array())
	{
		$this->activityFiles = new Volume\Activity();
		$this->activityFiles->setFilter($this->getFilter());

		$query = $this->activityFiles->getActivityFileMeasureQuery(Volume\Activity::className(), $entityGroupField);

		// only email attachments
		$query->where('TYPE_ID', '=', \CCrmActivityType::Email);

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
	 * @return int
	 */
	public function clearFiles()
	{
		if (!$this->canClearEntity())
		{
			return -1;
		}

		$query = $this->prepareQuery();

		$dropped = -1;

		if ($this->prepareFilter($query))
		{
			$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());
			$diskAvailable = \Bitrix\Main\Loader::includeModule('disk');
			$exceptMailTemplateAttachment = Volume\MailTemplate::getAttachmentList();

			$query
				//->where('TYPE_ID', '=', \CCrmActivityType::Email)
				->setSelect(array('ID', 'OWNER_TYPE_ID', 'OWNER_ID'))
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(array('ID' => 'ASC'));

			if ($this->getProcessOffset() > 0)
			{
				$query->where('ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			$dropped = 0;
			while ($activity = $res->fetch())
			{
				$this->setProcessOffset($activity['ID']);

				if (\CCrmActivity::CheckItemDeletePermission($activity, $userPermissions))
				{
					$activityElementList = Crm\ActivityElementTable::getList(array(
						'filter' => array('=ACTIVITY_ID' => $activity['ID']),
						'select' => array('STORAGE_TYPE_ID', 'ELEMENT_ID'),
					));

					// check existence and force removing
					while ($row = $activityElementList->fetch())
					{
						// ignore mail template attachments
						if (in_array((int)$row['ELEMENT_ID'], $exceptMailTemplateAttachment))
						{
							$this->incrementFailCount();
							continue;
						}
						$elementIds = array($row['ELEMENT_ID']);
						if (\CCrmActivity::DeleteStorageElements($activity['ID'], array('STORAGE_ELEMENT_IDS' => $elementIds)))
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
									if (!$file->delete(\Bitrix\Disk\SystemUser::SYSTEM_USER_ID))
									{
										$this->collectError($file->getErrors());
									}
								}
							}
							$this->incrementDroppedFileCount();
							$dropped ++;
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
				}
				else
				{
					$this->collectError(new Main\Error('Access denied to activity #'.$activity['ID'], self::ERROR_PERMISSION_DENIED));
					$this->incrementFailCount();
				}

				if ($this->hasTimeLimitReached())
				{
					break;
				}
			}
		}
		else
		{
			$this->collectError(new Main\Error('Filter error', self::ERROR_DELETION_FAILED));
		}

		return $dropped;
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
				//->where('TYPE_ID', '=', \CCrmActivityType::Email)
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


<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Localization\Loc;


class Lead
	extends Volume\Base
	implements Volume\IVolumeClear, Volume\IVolumeClearActivity, Volume\IVolumeClearEvent, Volume\IVolumeUrl
{
	use Volume\ClearEvent;
	use Volume\ClearActivity;

	/** @var array */
	protected static $entityList = array(
		Crm\LeadTable::class,
		Crm\History\Entity\LeadStatusHistoryTable::class,
		Crm\History\Entity\LeadStatusHistoryWithSupposedTable::class,
		Crm\Statistics\Entity\LeadActivityStatisticsTable::class,
		Crm\Statistics\Entity\LeadChannelStatisticsTable::class,
		Crm\Statistics\Entity\LeadConversionStatisticsTable::class,
		Crm\Statistics\Entity\LeadSumStatisticsTable::class,
		Crm\Binding\LeadContactTable::class,
	);

	/** @var array */
	protected static $filterFieldAlias = array(
		'STAGE_SEMANTIC_ID' => 'STATUS_SEMANTIC_ID',
		'LEAD_STAGE_SEMANTIC_ID' => 'STATUS_SEMANTIC_ID',
		'LEAD_DATE_CREATE' => 'DATE_CREATE',
		'DATE_CREATE' => 'DATE_CREATE',
		'LEAD_DATE_CLOSE' => 'DATE_CLOSED',
		'SORT_ID' => 'ID',
	);

	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_LEAD_TITLE');
	}


	/**
	 * Returns entity list attached to disk object.
	 * @param string $entityClass Class name of entity.
	 * @return string|null
	 */
	public static function getDiskConnector($entityClass)
	{
		$attachedEntityList = array();
		if (parent::isModuleAvailable('disk'))
		{
			$attachedEntityList[Crm\LeadTable::class] = \Bitrix\Disk\Uf\CrmLeadConnector::class;
		}

		return $attachedEntityList[$entityClass] ? : null;
	}

	/**
	 * Returns Socialnetwork log entity list attached to disk object.
	 * @param string $entityClass Class name of entity.
	 * @return string|null
	 */
	public static function getLiveFeedConnector($entityClass)
	{
		$attachedEntityList = array();
		if (parent::isModuleAvailable('socialnetwork') && parent::isModuleAvailable('disk'))
		{
			$attachedEntityList[Crm\LeadTable::class] = \CCrmLiveFeedEntity::Lead;
		}

		return $attachedEntityList[$entityClass] ? : null;
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
	 * Returns availability to drop entity.
	 *
	 * @return boolean
	 */
	public function canClearEntity()
	{
		$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());
		if (!\CCrmLead::CheckReadPermission(0, $userPermissions))
		{
			$this->collectError(new Main\Error('', self::ERROR_PERMISSION_DENIED));

			return false;
		}
		if ($userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'DELETE'))
		{
			$this->collectError(new Main\Error('', self::ERROR_PERMISSION_DENIED));

			return false;
		}

		return true;
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
				\Bitrix\Main\Config\Option::get('crm', 'path_to_lead_list', '/crm/lead/list/')
			);
		}

		return $entityListPath;
	}

	/**
	 * Get filter reset parems for entity grid.
	 * @return array
	 */
	public function getGridFilterResetParam()
	{
		$entityListReset = array(
			'FILTER_ID' => 'CRM_LEAD_LIST_V12',
			'GRID_ID' => 'CRM_LEAD_LIST_V12',
			'FILTER_FIELDS' => 'STATUS_SEMANTIC_ID,DATE_CREATE',
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
			'STAGE_SEMANTIC_ID' => 'STATUS_SEMANTIC_ID',
			'DATE_CREATE' => 'DATE_CREATE',
		);
	}

	/**
	 * Component action list for measure process.
	 * @param array $componentCommandAlias Command alias.
	 * @return array
	 */
	public function getActionList($componentCommandAlias)
	{
		$queueList = $this->prepareRangeActionList(
			Crm\LeadTable::class,
			'DATE_CREATE',
			array(
				'MEASURE_ENTITY' => $componentCommandAlias['MEASURE_ENTITY'],
				'MEASURE_FILE' => $componentCommandAlias['MEASURE_FILE'],
			)
		);

		$queueList = array_merge(
			$queueList,
			$this->prepareRangeActionList(
				Crm\ActivityTable::class,
				'CREATED',
				array(
					'MEASURE_ACTIVITY' => $componentCommandAlias['MEASURE_ACTIVITY'],
				)
			)
		);

		$queueList = array_merge(
			$queueList,
			$this->prepareRangeActionList(
				Crm\EventTable::class,
				'DATE_CREATE',
				array(
					'MEASURE_EVENT' => $componentCommandAlias['MEASURE_EVENT'],
				)
			)
		);

		return $queueList;
	}


	/**
	 * Returns query.
	 * @return ORM\Query\Query
	 */
	public function prepareQuery()
	{
		return Crm\LeadTable::query();
	}


	/**
	 * Runs measure test for tables.
	 * @return self
	 */
	public function measureEntity()
	{
		self::loadTablesInformation();

		$query = $this->prepareQuery();

		if ($this->prepareFilter($query))
		{
			$avgLeadTableRowLength = (double)self::$tablesInformation[Crm\LeadTable::getTableName()]['AVG_SIZE'];

			$connection = \Bitrix\Main\Application::getConnection();

			$this->checkTemporally();

			$data = array(
				'INDICATOR_TYPE' => '',
				'OWNER_ID' => '',
				'DATE_CREATE' => new \Bitrix\Main\Type\Date(),
				'STAGE_SEMANTIC_ID' => '',
				'ENTITY_COUNT' => '',
				'ENTITY_SIZE' => '',
			);

			$insert = $connection->getSqlHelper()->prepareInsert(Crm\VolumeTmpTable::getTableName(), $data);

			$sqlIns = 'INSERT INTO '.$connection->getSqlHelper()->quote(Crm\VolumeTmpTable::getTableName()). '('. $insert[0]. ') ';

			$query
				->registerRuntimeField(new ORM\Fields\ExpressionField('INDICATOR_TYPE', '\''.static::getIndicatorId().'\''))
				->addSelect('INDICATOR_TYPE')

				->registerRuntimeField(new ORM\Fields\ExpressionField('OWNER_ID', '\''.$this->getOwner().'\''))
				->addSelect('OWNER_ID')

				//date
				->addSelect('DATE_CREATE_SHORT')
				->addGroup('DATE_CREATE_SHORT')

				// STAGE_SEMANTIC_ID
				->addSelect('STATUS_SEMANTIC_ID', 'STAGE_SEMANTIC_ID')
				->addGroup('STAGE_SEMANTIC_ID')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_COUNT', 'COUNT(%s)', 'ID'))
				->addSelect('ENTITY_COUNT')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_SIZE', 'COUNT(%s) * '.$avgLeadTableRowLength, 'ID'))
				->addSelect('ENTITY_SIZE');

			$connection->queryExecute($sqlIns. $query->getQuery());

			if ($this->collectEntityRowSize)
			{
				$entityList = self::getEntityList();
				foreach ($entityList as $entityClass)
				{
					if ($entityClass == Crm\LeadTable::class)
					{
						continue;
					}
					/**
					 * @var \Bitrix\Main\ORM\Data\DataManager $entityClass
					 */
					$entityEntity = $entityClass::getEntity();

					if ($entityEntity->hasField('LEAD_ID'))
					{
						$fieldName = 'LEAD_ID';
					}
					elseif ($entityEntity->hasField('OWNER_ID'))
					{
						$fieldName = 'OWNER_ID';
					}
					else
					{
						continue;
					}

					$query = $this->prepareQuery();

					if ($this->prepareFilter($query))
					{
						$reference = new ORM\Fields\Relations\Reference(
							'RefEntity',
							$entityClass,
							array('this.ID' => 'ref.'.$fieldName),
							array('join_type' => 'INNER')
						);
						$query->registerRuntimeField($reference);

						$primary = $entityEntity->getPrimary();
						if (is_array($primary) && !empty($primary))
						{
							array_walk($primary, function (&$item) {
								$item = 'RefEntity.'.$item;
							});
						}
						elseif (!empty($primary))
						{
							$primary = array('RefEntity.'.$primary);
						}

						$query
							//primary
							//->setSelect($primary)
							->registerRuntimeField(new ORM\Fields\ExpressionField('COUNT_REF', 'COUNT(*)'))
							->addSelect('COUNT_REF')
							->setGroup($primary)

							//date
							->addSelect('DATE_CREATE_SHORT')
							->addGroup('DATE_CREATE_SHORT')

							// STAGE_SEMANTIC_ID
							->addSelect('STATUS_SEMANTIC_ID', 'STAGE_SEMANTIC_ID')
							->addGroup('STAGE_SEMANTIC_ID');

						$avgTableRowLength = (double)self::$tablesInformation[$entityClass::getTableName()]['AVG_SIZE'];

						$query1 = new ORM\Query\Query($query);
						$query1
							->registerRuntimeField(new ORM\Fields\ExpressionField('INDICATOR_TYPE', '\''.static::getIndicatorId().'\''))
							->addSelect('INDICATOR_TYPE')
							->registerRuntimeField(new ORM\Fields\ExpressionField('OWNER_ID', '\''.$this->getOwner().'\''))
							->addSelect('OWNER_ID')

							//date
							->addSelect('DATE_CREATE_SHORT')
							->addGroup('DATE_CREATE_SHORT')

							// STAGE_SEMANTIC_ID
							->addSelect('STAGE_SEMANTIC_ID')
							->addGroup('STAGE_SEMANTIC_ID')
							->registerRuntimeField(new ORM\Fields\ExpressionField('REF_SIZE', 'SUM(COUNT_REF) * '.$avgTableRowLength))
							->addSelect('REF_SIZE');


						Crm\VolumeTmpTable::updateFromSelect(
							$query1,
							array('ENTITY_SIZE' => 'destination.ENTITY_SIZE + source.REF_SIZE'),
							array(
								'INDICATOR_TYPE' => 'INDICATOR_TYPE',
								'OWNER_ID' => 'OWNER_ID',
								'DATE_CREATE' => 'DATE_CREATE_SHORT',
								'STAGE_SEMANTIC_ID' => 'STAGE_SEMANTIC_ID',
							)
						);
					}
				}
			}

			$this->copyTemporallyData();
		}

		return $this;
	}

	/**
	 * Runs measure test for tables.
	 * @return self
	 */
	public function measureFiles()
	{
		self::loadTablesInformation();

		$query = $this->prepareQuery();

		if ($this->prepareFilter($query))
		{
			$source = array();

			$groupByFields = array(
				'DATE_CREATE_SHORT' => 'DATE_CREATE_SHORT',
				'STAGE_SEMANTIC_ID' => 'STATUS_SEMANTIC_ID',
			);

			$entityUserFieldList = $this->getUserTypeFieldList(Crm\LeadTable::class);
			/** @var array $userField */
			foreach ($entityUserFieldList as $userField)
			{
				$sql = $this->prepareUserFieldQuery(Crm\LeadTable::class, $userField, $groupByFields);

				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}

			$diskConnector = static::getDiskConnector(Crm\LeadTable::class);
			if ($diskConnector !== null)
			{
				$sql = $this->prepareDiskAttachedQuery(Crm\LeadTable::class, $diskConnector, $groupByFields);
				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}

			$liveFeedConnector = static::getLiveFeedConnector(Crm\LeadTable::class);
			if ($liveFeedConnector !== null)
			{
				$sql = $this->prepareLiveFeedQuery(Crm\LeadTable::class, $liveFeedConnector, $groupByFields);
				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}

			if (count($source) > 0)
			{
				$querySql = "
					SELECT 
						'".static::getIndicatorId()."' as INDICATOR_TYPE,
						'".$this->getOwner()."' as OWNER_ID,
						DATE_CREATE_SHORT as DATE_CREATE,
						STAGE_SEMANTIC_ID, 
						SUM(FILE_SIZE) as FILE_SIZE,
						SUM(FILE_COUNT) as FILE_COUNT,
						SUM(DISK_SIZE) as DISK_SIZE,
						SUM(DISK_COUNT) as DISK_COUNT
					FROM 
					(
						(".implode(' ) UNION ( ', $source).")
					) src
					GROUP BY 
						STAGE_SEMANTIC_ID, 
						DATE_CREATE
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
						'INDICATOR_TYPE',
						'OWNER_ID',
						'DATE_CREATE',
						'STAGE_SEMANTIC_ID',
					)
				);
			}
		}

		return $this;
	}

	/**
	 * Runs measure test for activities.
	 * @param array $additionActivityFilter Filter for activity list.
	 * @return self
	 */
	public function measureActivity($additionActivityFilter = array())
	{
		self::loadTablesInformation();

		$querySql = $this->prepareActivityRelationQuerySql(array(
			'DATE_CREATE' => 'DATE_CREATED_SHORT',
			'LEAD_STAGE_SEMANTIC' => 'STAGE_SEMANTIC_ID',
		));

		if ($querySql != '')
		{
			$avgActivityTableRowLength = (double)self::$tablesInformation[Crm\ActivityTable::getTableName()]['AVG_SIZE'];
			$avgBindingTableRowLength = (double)self::$tablesInformation[Crm\ActivityBindingTable::getTableName()]['AVG_SIZE'];

			$querySql = "
				SELECT 
					'".static::getIndicatorId()."' as INDICATOR_TYPE,
					'".$this->getOwner()."' as OWNER_ID,
					DATE_CREATE,
					LEAD_STAGE_SEMANTIC, 
					(	FILE_SIZE +
						ACTIVITY_COUNT * {$avgActivityTableRowLength} + 
						BINDINGS_COUNT * {$avgBindingTableRowLength} ) as ACTIVITY_SIZE,
					ACTIVITY_COUNT
				FROM 
				(
					{$querySql}
				) src
			";

			Crm\VolumeTable::updateFromSelect(
				$querySql,
				array(
					'ACTIVITY_SIZE' => 'destination.ACTIVITY_SIZE + source.ACTIVITY_SIZE',
					'ACTIVITY_COUNT' => 'destination.ACTIVITY_COUNT + source.ACTIVITY_COUNT',
				),
				array(
					'INDICATOR_TYPE' => 'INDICATOR_TYPE',
					'OWNER_ID' => 'OWNER_ID',
					'DATE_CREATE' => 'DATE_CREATE',
					'STAGE_SEMANTIC_ID' => 'LEAD_STAGE_SEMANTIC',
				)
			);
		}

		return $this;
	}

	/**
	 * Runs measure test for events.
	 * @param array $additionEventFilter Filter for event list.
	 * @return self
	 */
	public function measureEvent($additionEventFilter = array())
	{
		self::loadTablesInformation();

		$querySql = $this->prepareEventRelationQuerySql(array(
			'EVENT_DATE_CREATE' => 'LEAD.DATE_CREATE_SHORT',
			'LEAD_STAGE_SEMANTIC_ID' => 'LEAD.STATUS_SEMANTIC_ID',
		));

		if ($querySql != '')
		{
			$avgEventTableRowLength = (double)self::$tablesInformation[Crm\EventTable::getTableName()]['AVG_SIZE'];

			$querySql = "
				SELECT 
					'".static::getIndicatorId()."' as INDICATOR_TYPE,
					'".$this->getOwner()."' as OWNER_ID,
					EVENT_DATE_CREATE as DATE_CREATE,
					LEAD_STAGE_SEMANTIC_ID, 
					(	FILE_SIZE +
						EVENT_COUNT * {$avgEventTableRowLength} ) as EVENT_SIZE,
					EVENT_COUNT
				FROM 
				(
					{$querySql}
				) src
			";

			Crm\VolumeTable::updateFromSelect(
				$querySql,
				array(
					'EVENT_SIZE' => 'destination.EVENT_SIZE + source.EVENT_SIZE',
					'EVENT_COUNT' => 'destination.EVENT_COUNT + source.EVENT_COUNT',
				),
				array(
					'INDICATOR_TYPE' => 'INDICATOR_TYPE',
					'OWNER_ID' => 'OWNER_ID',
					'DATE_CREATE' => 'DATE_CREATE',
					'STAGE_SEMANTIC_ID' => 'LEAD_STAGE_SEMANTIC_ID',
				)
			);
		}

		return $this;
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
			$query
				->registerRuntimeField(new ORM\Fields\ExpressionField('CNT', 'COUNT(%s)', 'ID'))
				->addSelect('CNT')
			;

			$count = 0;
			$res = $query->exec();
			if ($row = $res->fetch())
			{
				$count = $row['CNT'];
			}
		}

		return $count;
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

		$connection = \Bitrix\Main\Application::getConnection();

		$query = $this->prepareQuery();

		$dropped = -1;
		if ($this->prepareFilter($query))
		{
			$query
				->addSelect('ID', 'LEAD_ID')
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(array('ID' => 'ASC'))
			;

			if ($this->getProcessOffset() > 0)
			{
				$query->where('ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			$dropped = 0;
			$entity = new \CCrmLead(false);
			while ($lead = $res->fetch())
			{
				$this->setProcessOffset($lead['LEAD_ID']);

				$connection->startTransaction();

				if ($entity->Delete($lead['LEAD_ID'], array('CURRENT_USER' => $this->getOwner())) !== false)
				{
					$connection->commitTransaction();
					$this->incrementDroppedEntityCount();
					$dropped ++;
				}
				else
				{
					$connection->rollbackTransaction();

					$err = '';
					global $APPLICATION;
					if ($APPLICATION instanceof \CMain)
					{
						$err = $APPLICATION->GetException();
					}
					if ($err == '')
					{
						$err = 'Deletion failed with lead #'.$lead['LEAD_ID'];
					}
					$this->collectError(new Main\Error($err, self::ERROR_DELETION_FAILED));

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
	 * Returns count of activities.
	 * @param array $additionActivityFilter Filter for activity list.
	 * @return int
	 */
	public function countActivity($additionActivityFilter = array())
	{
		$additionActivityFilter['=BINDINGS.OWNER_TYPE_ID'] = \CCrmOwnerType::Lead;
		return $this->countRelationActivity($additionActivityFilter);
	}

	/**
	 * Performs dropping associated entity activities.
	 *
	 * @return int
	 */
	public function clearActivity()
	{
		if (!$this->canClearActivity())
		{
			return -1;
		}

		$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());

		$activityVolume = new Volume\Activity();
		$activityVolume->setFilter($this->getFilter());

		$query = $activityVolume->prepareQuery();

		$dropped = -1;

		if ($activityVolume->prepareFilter($query))
		{
			$query
				->setSelect(array(
					'ID' => 'ID',
					'LEAD_ID' => 'BINDINGS.OWNER_ID',
				))
				->where('BINDINGS.OWNER_TYPE_ID', '=', \CCrmOwnerType::Lead)
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

				$activity['OWNER_TYPE_ID'] = \CCrmOwnerType::Lead;
				$activity['OWNER_ID'] = $activity['LEAD_ID'];

				if (\CCrmActivity::CheckItemDeletePermission($activity, $userPermissions))
				{
					\CCrmActivity::DeleteByOwner(\CCrmOwnerType::Lead, $activity['LEAD_ID']);

					//todo: fail count here

					$this->incrementDroppedActivityCount();
					$dropped ++;
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
	 * Returns count of events.
	 * @param array $additionEventFilter Filter for events list.
	 * @return int
	 */
	public function countEvent($additionEventFilter = array())
	{
		$additionEventFilter['=ENTITY_TYPE'] = \CCrmOwnerType::LeadName;
		return $this->countRelationEvent($additionEventFilter);
	}

	/**
	 * Performs dropping associated entity events.
	 *
	 * @return int
	 */
	public function clearEvent()
	{
		if (!$this->canClearEvent())
		{
			return -1;
		}

		$eventVolume = new Volume\Event();
		$eventVolume->setFilter($this->getFilter());

		$query = $eventVolume->prepareRelationQuery(static::className());

		$dropped = -1;

		if ($eventVolume->prepareFilter($query))
		{
			$query
				->addSelect('EVENT_ID')
				->where('ENTITY_TYPE', '=', \CCrmOwnerType::LeadName)
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(array('EVENT_ID' => 'ASC'));

			if ($this->getProcessOffset() > 0)
			{
				$query->where('EVENT_ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			$dropped = 0;
			while ($event = $res->fetch())
			{
				$this->setProcessOffset($event['EVENT_ID']);

				if (Volume\Event::dropEvent($event['EVENT_ID'], $this->getOwner()))
				{
					$this->incrementDroppedEventCount();
					$dropped ++;
				}
				else
				{
					$this->collectError(new Main\Error('Deletion failed with event #'.$event['EVENT_ID'], self::ERROR_DELETION_FAILED));
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
}


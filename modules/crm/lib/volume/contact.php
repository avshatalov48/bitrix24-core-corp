<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;


class Contact
	extends Volume\Base
	implements Volume\IVolumeClear, Volume\IVolumeClearActivity, Volume\IVolumeClearEvent, Volume\IVolumeUrl
{
	use Volume\ClearEvent;
	use Volume\ClearActivity;

	/** @var array */
	protected static $entityList = array(
		Crm\ContactTable::class,
		Crm\Statistics\Entity\ContactActivityMarkStatisticsTable::class,
		Crm\Statistics\Entity\ContactActivityStatisticsTable::class,
		Crm\Statistics\Entity\ContactActivityStreamStatisticsTable::class,
		Crm\Statistics\Entity\ContactActivityStatusStatisticsTable::class,
		Crm\Statistics\Entity\ContactActivitySumStatisticsTable::class,
		Crm\Statistics\Entity\ContactGrowthStatisticsTable::class,
		Crm\Binding\ContactCompanyTable::class,
	);

	/** @var array */
	protected static $filterFieldAlias = array(
		'DATE_CREATE_CONTACT' => 'DATE_CREATE',
		'SORT_ID' => 'ID',
	);

	/**
	 * Contact category ID
	 *
	 * @var int
	 */
	protected static int $categoryId = 0;

	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_CONTACT_TITLE');
	}

	/**
	 * Returns entity category ID
	 *
	 * @return int
	 */
	public static function getCategoryId(): int
	{
		return self::$categoryId;
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
			$attachedEntityList[Crm\ContactTable::class]  = \Bitrix\Disk\Uf\CrmContactConnector::class;
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
			$attachedEntityList[Crm\ContactTable::class] = \CCrmLiveFeedEntity::Contact;
		}

		return $attachedEntityList[$entityClass] ? : null;
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
				\Bitrix\Main\Config\Option::get('crm', 'path_to_contact_list', '/crm/contact/list/')
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
			'FILTER_ID' => 'CRM_CONTACT_LIST_V12',
			'GRID_ID' => 'CRM_CONTACT_LIST_V12',
			'FILTER_FIELDS' => 'DATE_CREATE',
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
			'DATE_CREATE' => 'DATE_CREATE',
		);
	}

	/**
	 * Returns availability to drop entity.
	 *
	 * @return boolean
	 */
	public function canClearEntity()
	{
		$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());
		if (!\CCrmContact::CheckReadPermission(0, $userPermissions))
		{
			$this->collectError(new Main\Error('', self::ERROR_PERMISSION_DENIED));

			return false;
		}
		if ($userPermissions->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'DELETE'))
		{
			$this->collectError(new Main\Error('', self::ERROR_PERMISSION_DENIED));

			return false;
		}

		return true;
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
	 * Component action list for measure process.
	 * @param array $componentCommandAlias Command alias.
	 * @return array
	 */
	public function getActionList($componentCommandAlias)
	{
		$queueList = $this->prepareRangeActionList(
			Crm\ContactTable::class,
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
		$query = Crm\ContactTable::query();

		$dayField = new ORM\Fields\ExpressionField(
			'DATE_CREATE_SHORT',
			'DATE(%s)',
			'DATE_CREATE'
		);
		$query->registerRuntimeField($dayField);
		$query->where('CATEGORY_ID', self::$categoryId);

		return $query;
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
			$avgContactTableRowLength = (double)self::$tablesInformation[Crm\ContactTable::getTableName()]['AVG_SIZE'];

			$connection = \Bitrix\Main\Application::getConnection();

			$this->checkTemporally();

			$data = array(
				'INDICATOR_TYPE' => '',
				'OWNER_ID' => '',
				'STAGE_SEMANTIC_ID' => '',
				'DATE_CREATE' => new \Bitrix\Main\Type\Date(),
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

				->registerRuntimeField(new ORM\Fields\ExpressionField('STAGE_SEMANTIC_ID', '\'-\''))
				->addSelect('STAGE_SEMANTIC_ID')

				//date
				->addSelect('DATE_CREATE_SHORT', 'DATE_CREATE_SHORT')
				->addGroup('DATE_CREATE_SHORT')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_COUNT', 'COUNT(%s)', 'ID'))
				->addSelect('ENTITY_COUNT')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_SIZE', 'COUNT(%s) * '.$avgContactTableRowLength, 'ID'))
				->addSelect('ENTITY_SIZE');

			$querySql = $sqlIns. $query->getQuery();

			$connection->queryExecute($querySql);

			if ($this->collectEntityRowSize)
			{
				$entityList = self::getEntityList();
				foreach ($entityList as $entityClass)
				{
					if ($entityClass == Crm\ContactTable::class)
					{
						continue;
					}
					/**
					 * @var \Bitrix\Main\ORM\Data\DataManager $entityClass
					 */
					$entityEntity = $entityClass::getEntity();

					if ($entityEntity->hasField('CONTACT_ID'))
					{
						$fieldName = 'CONTACT_ID';
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
							->registerRuntimeField(new ORM\Fields\ExpressionField('COUNT_REF', 'COUNT(*)'))
							->addSelect('COUNT_REF')
							->setGroup($primary)

							//date
							->addSelect('DATE_CREATE_SHORT', 'DATE_CREATE_SHORT')
							->addGroup('DATE_CREATE_SHORT');

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
							->registerRuntimeField(new ORM\Fields\ExpressionField('REF_SIZE', 'SUM(COUNT_REF) * '.$avgTableRowLength))
							->addSelect('REF_SIZE');


						Crm\VolumeTmpTable::updateFromSelect(
							$query1,
							array('ENTITY_SIZE' => 'destination.ENTITY_SIZE + source.REF_SIZE'),
							array(
								'INDICATOR_TYPE' => 'INDICATOR_TYPE',
								'OWNER_ID' => 'OWNER_ID',
								'DATE_CREATE' => 'DATE_CREATE_SHORT',
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
			);

			$query
				->registerRuntimeField(new ORM\Fields\ExpressionField('PHOTO_FILE_ID', 'cast(%s as UNSIGNED)', 'PHOTO'))
				->registerRuntimeField(new ORM\Fields\Relations\Reference(
					'PHOTO_FILE',
					Main\FileTable::class,
					ORM\Query\Join::on('this.PHOTO_FILE_ID', 'ref.ID')->whereNotNull('this.PHOTO'),
					array('join_type' => 'INNER')
				));

			$query
				->registerRuntimeField(new ORM\Fields\ExpressionField('FILE_SIZE', 'SUM(%s)', 'PHOTO_FILE.FILE_SIZE'))
				->registerRuntimeField(new ORM\Fields\ExpressionField('FILE_COUNT', 'COUNT(%s)', 'PHOTO_FILE.FILE_SIZE'))
				->addSelect('FILE_SIZE')
				->addSelect('FILE_COUNT')

				->registerRuntimeField(new ORM\Fields\ExpressionField('DISK_SIZE', '0'))
				->registerRuntimeField(new ORM\Fields\ExpressionField('DISK_COUNT', '0'))
				->addSelect('DISK_SIZE')
				->addSelect('DISK_COUNT')
			;

			foreach ($groupByFields as $alias => $field)
			{
				$query->addSelect($field, $alias);
				$query->addGroup($alias);
			}

			$source[] = $query->getQuery();


			$entityUserFieldList = $this->getUserTypeFieldList(Crm\ContactTable::class);
			/** @var array $userField */
			foreach ($entityUserFieldList as $userField)
			{
				$sql = $this->prepareUserFieldQuery(Crm\ContactTable::class, $userField, $groupByFields);

				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}

			$diskConnector = static::getDiskConnector(Crm\ContactTable::class);
			if ($diskConnector !== null)
			{
				$sql = $this->prepareDiskAttachedQuery(Crm\ContactTable::class, $diskConnector, $groupByFields);
				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}

			$liveFeedConnector = static::getLiveFeedConnector(Crm\ContactTable::class);
			if ($liveFeedConnector !== null)
			{
				$sql = $this->prepareLiveFeedQuery(Crm\ContactTable::class, $liveFeedConnector, $groupByFields);
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
						'-' as STAGE_SEMANTIC_ID,
						DATE_CREATE_SHORT as DATE_CREATE,
						SUM(FILE_SIZE) as FILE_SIZE,
						SUM(FILE_COUNT) as FILE_COUNT,
						SUM(DISK_SIZE) as DISK_SIZE,
						SUM(DISK_COUNT) as DISK_COUNT
					FROM 
					(
						(".implode(' ) UNION ( ', $source).")
					) src
					GROUP BY 
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
		$query = $this->prepareQuery();

		if ($this->prepareFilter($query))
		{

			self::loadTablesInformation();

			$querySql = $this->prepareActivityRelationQuerySql(array(
				'DATE_CREATE' => 'DATE_CREATED_SHORT',
			));

			if ($querySql != '')
			{
				$avgActivityTableRowLength = (double)self::$tablesInformation[Crm\ActivityTable::getTableName()]['AVG_SIZE'];
				$avgBindingTableRowLength = (double)self::$tablesInformation[Crm\ActivityBindingTable::getTableName()]['AVG_SIZE'];

				$querySql = "
					SELECT 
						'".static::getIndicatorId()."' as INDICATOR_TYPE,
						'".$this->getOwner()."' as OWNER_ID,
						'-' as STAGE_SEMANTIC_ID,
						DATE_CREATE,
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
						'STAGE_SEMANTIC_ID' => 'STAGE_SEMANTIC_ID',
					)
				);
			}
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
		$query = $this->prepareQuery();

		if ($this->prepareFilter($query))
		{
			self::loadTablesInformation();

			$querySql = $this->prepareEventRelationQuerySql(array(
				'EVENT_DATE_CREATED' => 'DATE_CREATED_SHORT',
			));

			if ($querySql != '')
			{
				$avgEventTableRowLength = (double)self::$tablesInformation[Crm\EventTable::getTableName()]['AVG_SIZE'];

				$querySql = "
					SELECT 
						'".static::getIndicatorId()."' as INDICATOR_TYPE,
						'".$this->getOwner()."' as OWNER_ID,
						'-' as STAGE_SEMANTIC_ID,
						EVENT_DATE_CREATED as DATE_CREATE,
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
						'STAGE_SEMANTIC_ID' => 'STAGE_SEMANTIC_ID',
					)
				);
			}
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

		$query = $this->prepareQuery();

		$dropped = -1;

		if ($this->prepareFilter($query))
		{
			$query
				->addSelect('ID', 'CONTACT_ID')
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(array('ID' => 'ASC'))
			;

			if ($this->getProcessOffset() > 0)
			{
				$query->where('ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			$connection = \Bitrix\Main\Application::getConnection();

			$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());

			$crmContact = new \CCrmContact(false);

			$dropped = 0;
			while ($contact = $res->fetch())
			{
				$this->setProcessOffset($contact['CONTACT_ID']);

				$entityAttr = $userPermissions->GetEntityAttr('CONTACT', array($contact['CONTACT_ID']));
				if ($userPermissions->CheckEnityAccess('CONTACT', 'DELETE', $entityAttr[$contact['CONTACT_ID']]))
				{
					$connection->startTransaction();

					if ($crmContact->delete($contact['CONTACT_ID'], array('CURRENT_USER' => $this->getOwner())))
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
							$err = 'Deletion failed with contact #'.$contact['CONTACT_ID'];
						}
						$this->collectError(new Main\Error($err, self::ERROR_DELETION_FAILED));
						$this->incrementFailCount();
					}
				}
				else
				{
					$this->collectError(new Main\Error('Access denied to drop contact #'.$contact['CONTACT_ID'], self::ERROR_PERMISSION_DENIED));
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
		$additionActivityFilter['=BINDINGS.OWNER_TYPE_ID'] = \CCrmOwnerType::Contact;
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

		$query = $activityVolume->prepareQuery(static::className());

		$dropped = -1;

		if ($activityVolume->prepareFilter($query))
		{
			$query
				->setSelect(array(
					'ID' => 'ID',
					'CONTACT_ID' => 'BINDINGS.OWNER_ID',
				))
				->where('BINDINGS.OWNER_TYPE_ID', '=', \CCrmOwnerType::Contact)
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

				$activity['OWNER_TYPE_ID'] = \CCrmOwnerType::Contact;
				$activity['OWNER_ID'] = $activity['CONTACT_ID'];

				if (\CCrmActivity::CheckItemDeletePermission($activity, $userPermissions))
				{
					\CCrmActivity::DeleteByOwner(\CCrmOwnerType::Contact, $activity['CONTACT_ID']);

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
		$additionEventFilter['=ENTITY_TYPE'] = \CCrmOwnerType::ContactName;
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
				->where('ENTITY_TYPE', '=', \CCrmOwnerType::ContactName)
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

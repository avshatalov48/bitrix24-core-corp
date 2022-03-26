<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Localization\Loc;


class Invoice
	extends Volume\Base
	implements Volume\IVolumeClear, Volume\IVolumeClearActivity, Volume\IVolumeClearEvent, Volume\IVolumeUrl
{
	use Volume\ClearEvent;
	use Volume\ClearActivity;

	/** @var array */
	protected static $entityList = array(
		Crm\InvoiceTable::class,
		Crm\InvoiceSpecTable::class,
		Crm\InvoiceStUtsTable::class,
		Crm\InvoiceRecurTable::class,
		Crm\Invoice\Internals\InvoiceChangeTable::class,
		Crm\Invoice\Internals\InvoiceRulesDescrTable::class,
		Crm\Invoice\Internals\InvoicePropsValueTable::class,
		Crm\Invoice\Internals\InvoiceRoundTable::class,
		Crm\Invoice\Internals\PaymentTable::class,
		Crm\Invoice\Internals\ShipmentTable::class,
		Crm\Invoice\Internals\ShipmentItemTable::class,
		Crm\Invoice\Internals\TaxTable::class,
		Crm\Invoice\Internals\BasketTable::class,
		Crm\Invoice\Internals\BasketPropertyTable::class,
		Crm\Invoice\Internals\EntityMarkerTable::class,
		Crm\Invoice\Internals\InvoiceCouponsTable::class,
		Crm\Invoice\Internals\InvoiceDiscountTable::class,
		Crm\Invoice\Internals\InvoiceDiscountDataTable::class,
		Crm\Invoice\Internals\InvoiceModulesTable::class,
		Crm\Invoice\Internals\InvoiceRulesTable::class,
		Crm\History\Entity\InvoiceStatusHistoryTable::class,
		Crm\Statistics\Entity\InvoiceSumStatisticsTable::class,
	);

	/** @var array */
	protected static $filterFieldAlias = array(
		'INVOICE_STATUS_ID' => 'STATUS_ID',
		'INVOICE_DATE_CREATE' => 'DATE_INSERT',
		'DATE_CREATE' => 'DATE_INSERT',
	);


	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_INVOICE_TITLE');
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
			$attachedEntityList[Crm\InvoiceRecurTable::class] = \CCrmLiveFeedEntity::Invoice;
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
				\Bitrix\Main\Config\Option::get('crm', 'path_to_invoice_list', '/crm/invoice/list/')
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
			'FILTER_ID' => 'CRM_INVOICE_LIST_V12',
			'GRID_ID' => 'CRM_INVOICE_LIST_V12',
			'FILTER_FIELDS' => 'STATUS_ID,DATE_INSERT',
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
			'DATE_CREATE' => 'DATE_INSERT',
			'STAGE_SEMANTIC_ID' => function(&$param, $filterInp)
			{
				$statuses = Volume\Invoice::getStatusSemantics($filterInp);
				foreach ($statuses as $status)
				{
					if (is_array($param))
					{
						if (!isset($param['STATUS_ID']))
						{
							$param['STATUS_ID'] = array();
						}
						$param['STATUS_ID'][] = htmlspecialcharsbx($status);
					}
					else
					{
						$param .= "&STATUS_ID[]=". htmlspecialcharsbx($status);
					}
				}
			},
		);
	}


	/**
	 * Returns relations between quote status and stage semantic.
	 * @param array $stageIds Only for this stage will return statuses.
	 * @return array
	 */
	public static function getStatusSemantics($stageIds = array())
	{
		static $processStatusIDs;

		if (empty($processStatusIDs))
		{
			$processStatusIDs = array(
				Crm\PhaseSemantics::PROCESS => array(),
				Crm\PhaseSemantics::FAILURE => array(),
				Crm\PhaseSemantics::SUCCESS => array(),
			);
			foreach (array_keys(\CCrmStatus::GetStatusList('INVOICE_STATUS')) as $statusID)
			{
				if (\CCrmInvoice::GetSemanticID($statusID) === Crm\PhaseSemantics::PROCESS)
				{
					$processStatusIDs[Crm\PhaseSemantics::PROCESS][] = $statusID;
				}
				if (\CCrmInvoice::GetSemanticID($statusID) === Crm\PhaseSemantics::FAILURE)
				{
					$processStatusIDs[Crm\PhaseSemantics::FAILURE][] = $statusID;
				}
				if (\CCrmInvoice::GetSemanticID($statusID) === Crm\PhaseSemantics::SUCCESS)
				{
					$processStatusIDs[Crm\PhaseSemantics::SUCCESS][] = $statusID;
				}
			}
		}
		if (count($stageIds) > 0)
		{
			$statuses = array();
			foreach ($stageIds as $stageId)
			{
				if (isset($processStatusIDs[$stageId]))
				{
					$statuses = array_merge($statuses, $processStatusIDs[$stageId]);
				}
			}

			return $statuses;
		}

		return $processStatusIDs;
	}

	/**
	 * Returns availability to drop entity.
	 *
	 * @return boolean
	 */
	public function canClearEntity()
	{
		$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());
		if (!\CCrmInvoice::CheckReadPermission(0, $userPermissions))
		{
			$this->collectError(new Main\Error('', self::ERROR_PERMISSION_DENIED));

			return false;
		}
		if ($userPermissions->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'DELETE'))
		{
			$this->collectError(new Main\Error('', self::ERROR_PERMISSION_DENIED));

			return false;
		}

		return true;
	}


	/**
	 * Returns query.
	 * @return ORM\Query\Query
	 */
	public function prepareQuery()
	{
		$query = Crm\InvoiceTable::query();

		self::registerStageField($query);

		$dayField = new ORM\Fields\ExpressionField(
			'DATE_CREATE_SHORT',
			'DATE(%s)',
			'DATE_INSERT'
		);
		$query->registerRuntimeField($dayField);

		return $query;
	}

	/**
	 * Registers runtime field STAGE_SEMANTIC_ID.
	 * @param ORM\Query\Query $query Query to append.
	 * @param string $sourceAlias Source table alias.
	 * @param string $fieldAlias Field alias.
	 * @return void
	 */
	public static function registerStageField(ORM\Query\Query $query, $sourceAlias = '', $fieldAlias = 'STAGE_SEMANTIC_ID')
	{
		// STAGE_SEMANTIC_ID
		$caseSql = '';
		$stageStatusMirror = self::getStatusSemantics();
		foreach ($stageStatusMirror as $stageId => $statusList)
		{
			foreach ($statusList as $statusId)
			{
				$caseSql .= " WHEN '{$statusId}' THEN '{$stageId}' ";
			}
		}
		$stageField = new ORM\Fields\ExpressionField(
			$fieldAlias,
			"CASE %s {$caseSql} ELSE NULL END",
			($sourceAlias != '' ? "{$sourceAlias}.STATUS_ID" : 'STATUS_ID')
		);

		$query->registerRuntimeField($stageField);
	}

	/**
	 * Setups filter params into query.
	 * @param ORM\Query\Query $query Query.
	 * @return boolean
	 */
	public function prepareFilter(ORM\Query\Query $query)
	{
		$isAllValueApplied = true;
		$filter = $this->getFilter();

		foreach ($filter as $key => $value)
		{
			if (empty($value))
			{
				continue;
			}
			$key0 = trim($key, '<>!=');
			if ($key0 == 'STAGE_SEMANTIC_ID' || $key0 == 'INVOICE_STAGE_SEMANTIC_ID')
			{
				$statuses = self::getStatusSemantics($value);
				$query->where('STATUS_ID', 'in', $statuses);
			}
			elseif (isset(static::$filterFieldAlias[$key0]))
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
				$isAllValueApplied = $this->addFilterEntityField($query, $query->getEntity(), $key, $value);
			}
		}

		return $isAllValueApplied;
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
			$avgInvoiceTableRowLength = (double)self::$tablesInformation[Crm\InvoiceTable::getTableName()]['AVG_SIZE'];

			$connection = \Bitrix\Main\Application::getConnection();

			$data = array(
				'INDICATOR_TYPE' => '',
				'OWNER_ID' => '',
				'DATE_CREATE' => new \Bitrix\Main\Type\Date(),
				'STAGE_SEMANTIC_ID' => '',
				'ENTITY_COUNT' => '',
				'ENTITY_SIZE' => '',
			);

			$this->checkTemporally();

			$insert = $connection->getSqlHelper()->prepareInsert(Crm\VolumeTmpTable::getTableName(), $data);

			$sqlIns = 'INSERT INTO '.$connection->getSqlHelper()->quote(Crm\VolumeTmpTable::getTableName()). '('. $insert[0]. ') ';

			$query
				->registerRuntimeField(new ORM\Fields\ExpressionField('INDICATOR_TYPE', '\''.static::getIndicatorId().'\''))
				->addSelect('INDICATOR_TYPE')

				->registerRuntimeField(new ORM\Fields\ExpressionField('OWNER_ID', '\''.$this->getOwner().'\''))
				->addSelect('OWNER_ID')

				//date
				->addSelect('DATE_CREATE_SHORT', 'DATE_CREATE')
				->addGroup('DATE_CREATE_SHORT')

				// STAGE_SEMANTIC_ID
				->addSelect('STAGE_SEMANTIC_ID')
				->addGroup('STAGE_SEMANTIC_ID')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_COUNT', 'COUNT(%s)', 'ID'))
				->addSelect('ENTITY_COUNT')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_SIZE', 'COUNT(%s) * '.$avgInvoiceTableRowLength, 'ID'))
				->addSelect('ENTITY_SIZE');

			$querySql = $sqlIns. $query->getQuery();

			$connection->queryExecute($querySql);

			if ($this->collectEntityRowSize)
			{
				$entityList = self::getEntityList();
				foreach ($entityList as $entityClass)
				{
					if ($entityClass == Crm\InvoiceTable::class)
					{
						continue;
					}
					/**
					 * @var \Bitrix\Main\ORM\Data\DataManager $entityClass
					 */
					$entityEntity = $entityClass::getEntity();

					if ($entityEntity->hasField('ORDER_ID'))
					{
						$fieldName = 'ORDER_ID';
					}
					elseif ($entityEntity->hasField('INVOICE_ID'))
					{
						$fieldName = 'INVOICE_ID';
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
							->addSelect('DATE_CREATE_SHORT', 'DATE_CREATE')
							->addGroup('DATE_CREATE_SHORT')

							// STAGE_SEMANTIC_ID
							->addSelect('STAGE_SEMANTIC_ID', 'STAGE_SEMANTIC_ID')
							->addGroup('STAGE_SEMANTIC_ID');

						$avgTableRowLength = (double)self::$tablesInformation[$entityClass::getTableName()]['AVG_SIZE'];

						$query1 = new ORM\Query\Query($query);
						$query1
							->registerRuntimeField(new ORM\Fields\ExpressionField('INDICATOR_TYPE', '\''.static::getIndicatorId().'\''))
							->addSelect('INDICATOR_TYPE')
							->registerRuntimeField(new ORM\Fields\ExpressionField('OWNER_ID', '\''.$this->getOwner().'\''))
							->addSelect('OWNER_ID')

							//date
							->addSelect('DATE_CREATE')
							->addGroup('DATE_CREATE')

							// STAGE_SEMANTIC_ID
							->addSelect('STAGE_SEMANTIC_ID')
							->addGroup('STAGE_SEMANTIC_ID')
							->registerRuntimeField(new ORM\Fields\ExpressionField('REF_SIZE', 'SUM(COUNT_REF) * '.$avgTableRowLength))
							->addSelect('REF_SIZE');


						Crm\VolumeTmpTable::updateFromSelect(
							$query1,
							array('ENTITY_SIZE' => 'destination.ENTITY_SIZE + source.REF_SIZE'),
							array(
								'INDICATOR_TYPE',
								'OWNER_ID',
								'DATE_CREATE',
								'STAGE_SEMANTIC_ID',
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
				'DATE_CREATE' => 'DATE_CREATE_SHORT',
				'STAGE_SEMANTIC_ID' => 'STAGE_SEMANTIC_ID',
			);

			$entityUserFieldList = $this->getUserTypeFieldList(Crm\InvoiceTable::class);
			/** @var array $userField */
			foreach ($entityUserFieldList as $userField)
			{
				$sql = $this->prepareUserFieldQuery(Crm\InvoiceTable::class, $userField, $groupByFields);

				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}

			$diskConnector = static::getDiskConnector(Crm\InvoiceTable::class);
			if ($diskConnector !== null)
			{
				$sql = $this->prepareDiskAttachedQuery(Crm\InvoiceTable::class, $diskConnector, $groupByFields);
				if ($sql !== '')
				{
					$source[] = $sql;
				}
			}

			$liveFeedConnector = static::getLiveFeedConnector(Crm\InvoiceTable::class);
			if ($liveFeedConnector !== null)
			{
				$sql = $this->prepareLiveFeedQuery(Crm\InvoiceTable::class, $liveFeedConnector, $groupByFields);
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
						DATE_CREATE,
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
			'INVOICE_STAGE_SEMANTIC' => 'STAGE_SEMANTIC_ID',
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
					INVOICE_STAGE_SEMANTIC, 
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
					'STAGE_SEMANTIC_ID' => 'INVOICE_STAGE_SEMANTIC',
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
			'EVENT_DATE_CREATE' => 'INVOICE_DATE_CREATE_SHORT',
			'INVOICE_STAGE_SEMANTIC_ID' => 'INVOICE_STAGE_SEMANTIC_ID',
		));

		if ($querySql != '')
		{
			$avgEventTableRowLength = (double)self::$tablesInformation[Crm\EventTable::getTableName()]['AVG_SIZE'];

			$querySql = "
				SELECT 
					'".static::getIndicatorId()."' as INDICATOR_TYPE,
					'".$this->getOwner()."' as OWNER_ID,
					EVENT_DATE_CREATE as DATE_CREATE,
					INVOICE_STAGE_SEMANTIC_ID, 
					(	FILE_SIZE +
						EVENT_COUNT * {$avgEventTableRowLength} ) as EVENT_SIZE,
					EVENT_COUNT as EVENT_COUNT
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
					'STAGE_SEMANTIC_ID' => 'INVOICE_STAGE_SEMANTIC_ID',
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

		$query = $this->prepareQuery();

		$dropped = -1;

		if ($this->prepareFilter($query))
		{
			$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());

			$connection = \Bitrix\Main\Application::getConnection();

			$query
				->addSelect('ID', 'INVOICE_ID')
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(array('ID' => 'ASC'))
			;

			if ($this->getProcessOffset() > 0)
			{
				$query->where('ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			$dropped = 0;
			$entity = new \CCrmInvoice(false);
			while ($invoice = $res->fetch())
			{
				$this->setProcessOffset($invoice['INVOICE_ID']);

				$entityAttr = $userPermissions->GetEntityAttr('INVOICE', array($invoice['INVOICE_ID']));
				$attr = $entityAttr[$invoice['INVOICE_ID']];

				if($userPermissions->CheckEnityAccess('INVOICE', 'DELETE', $attr))
				{
					$connection->startTransaction();

					if ($entity->delete($invoice['INVOICE_ID']))
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
							$err = 'Deletion failed with invoice #'.$invoice['INVOICE_ID'];
						}
						$this->collectError(new Main\Error($err, self::ERROR_DELETION_FAILED));
						$this->incrementFailCount();
					}
				}
				else
				{
					$this->collectError(new Main\Error('Access denied to drop invoice #'.$invoice['INVOICE_ID'], self::ERROR_PERMISSION_DENIED));
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
		$additionActivityFilter['=BINDINGS.OWNER_TYPE_ID'] = \CCrmOwnerType::Invoice;
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
					'INVOICE_ID' => 'BINDINGS.OWNER_ID',
				))
				->where('BINDINGS.OWNER_TYPE_ID', '=', \CCrmOwnerType::Invoice)
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

				$activity['OWNER_TYPE_ID'] = \CCrmOwnerType::Invoice;
				$activity['OWNER_ID'] = $activity['INVOICE_ID'];

				if (\CCrmActivity::CheckItemDeletePermission($activity, $userPermissions))
				{
					\CCrmActivity::DeleteByOwner(\CCrmOwnerType::Invoice, $activity['INVOICE_ID']);
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
		$additionEventFilter['=ENTITY_TYPE'] = \CCrmOwnerType::InvoiceName;
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
				->addSelect( 'EVENT_ID')
				->where('ENTITY_TYPE', '=', \CCrmOwnerType::InvoiceName)
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

		return $dropped;
	}
}


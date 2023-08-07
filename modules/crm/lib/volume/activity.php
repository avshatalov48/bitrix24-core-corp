<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Disk;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Localization\Loc;


class Activity
	extends Volume\Base
	implements Volume\IVolumeClear, Volume\IVolumeUrl
{

	/** @var array */
	protected static $entityList = array(
		Crm\ActivityTable::class,
		Crm\ActivityBindingTable::class,
		Crm\ActivityElementTable::class,
		Crm\Activity\MailMetaTable::class,
		Crm\Activity\Entity\CustomTypeTable::class,
		Crm\UserActivityTable::class,
		Crm\Statistics\Entity\ActivityStatisticsTable::class,
		Crm\Statistics\Entity\ActivityChannelStatisticsTable::class,
		Crm\Activity\Entity\AppTypeTable::class,
		Crm\Activity\MailMetaTable::class,
	);


	/** @var array */
	protected static $filterFieldAlias = array(
		'DEAL_STAGE_SEMANTIC_ID' => 'DEAL.STAGE_SEMANTIC_ID',
		'LEAD_STAGE_SEMANTIC_ID' => 'LEAD.STATUS_SEMANTIC_ID',
		'QUOTE_STATUS_ID' => 'QUOTE.STATUS_ID',
		'INVOICE_STATUS_ID' => 'INVOICE.STATUS_ID',
		'DEAL_DATE_CREATE' => 'DEAL.DATE_CREATE',
		'LEAD_DATE_CREATE' => 'LEAD.DATE_CREATE',
		'QUOTE_DATE_CREATE' => 'QUOTE.DATE_CREATE',
		'INVOICE_DATE_CREATE' => 'INVOICE.DATE_INSERT',
		'DEAL_DATE_CLOSE' => 'DEAL.CLOSEDATE',
		'LEAD_DATE_CLOSE' => 'LEAD.DATE_CLOSED',
		'QUOTE_DATE_CLOSE' => 'QUOTE.CLOSEDATE',
		'DATE_CREATE' => 'CREATED',
		'SORT_ID' => 'ID',
	);

	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_ACTIVITY_TITLE');
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
			$attachedEntityList[Crm\ActivityTable::class] = \CCrmLiveFeedEntity::Activity;
		}

		return $attachedEntityList[$entityClass] ? : null;
	}

	/**
	 * Returns table list corresponding to indicator.
	 * @return string[]
	 */
	public function getTableList()
	{
		$tableNames = parent::getTableList();

		$tableNames[] = \CCrmActivity::COMMUNICATION_TABLE_NAME;

		return $tableNames;
	}

	/**
	 * Returns availability to drop entity.
	 *
	 * @return boolean
	 */
	public function canClearEntity()
	{
		// @see: Using of \CCrmActivity::CheckItemDeletePermission()
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
	 * Get filter reset parems for entity grid.
	 * @return array
	 */
	public function getGridFilterResetParam()
	{
		$entityListReset = array(
			'FILTER_ID' => 'CRM_ACTIVITY_LIST_MY_ACTIVITIES',
			'GRID_ID' => 'CRM_ACTIVITY_LIST_MY_ACTIVITIES',
			'FILTER_FIELDS' => 'CREATED',
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
	 *
	 * @param array $componentCommandAlias Command alias.
	 *
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
	 * Returns query.
	 * @param string $indicator Volume indicator class name.
	 * @param  array $entityGroupField Fileds for groupping.
	 * @return Main\ORM\Query\Query
	 */
	public function prepareQuery($indicator = '', $entityGroupField = array())
	{
		$query = Crm\ActivityTable::query();

		if (
			$indicator == Volume\Company::class
		)
		{
			$categoryId = Volume\Company::getCategoryId();
			$companyRelation = new ORM\Fields\Relations\Reference(
				'COMPANY',
				Crm\CompanyTable::class,
				ORM\Query\Join::on('this.BINDINGS.OWNER_ID', 'ref.ID')
					->where('this.BINDINGS.OWNER_TYPE_ID', \CCrmOwnerType::Company)
					->where('ref.CATEGORY_ID', $categoryId)
					->where('ref.IS_MY_COMPANY', 'N'),
				array('join_type' => 'INNER')
			);
			$query->registerRuntimeField($companyRelation);
		}
		elseif (
			$indicator == Volume\Contact::class
		)
		{
			$categoryId = Volume\Contact::getCategoryId();
			$contactRelation = new ORM\Fields\Relations\Reference(
				'CONTACT',
				Crm\ContactTable::class,
				ORM\Query\Join::on('this.BINDINGS.OWNER_ID', 'ref.ID')
					->where('this.BINDINGS.OWNER_TYPE_ID', \CCrmOwnerType::Contact)
					->where('ref.CATEGORY_ID', $categoryId),
				array('join_type' => 'INNER')
			);
			$query->registerRuntimeField($contactRelation);
		}
		else
		{
			//---- Deal
			$dealRelation = new ORM\Fields\Relations\Reference(
				'DEAL',
				Crm\DealTable::class,
				ORM\Query\Join::on('this.BINDINGS.OWNER_ID', 'ref.ID')->where('this.BINDINGS.OWNER_TYPE_ID', \CCrmOwnerType::Deal),
				array('join_type' => ($indicator == Volume\Deal::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($dealRelation);

			//---- Lead
			$leadRelation = new ORM\Fields\Relations\Reference(
				'LEAD',
				Crm\LeadTable::class,
				ORM\Query\Join::on('this.BINDINGS.OWNER_ID', 'ref.ID')->where('this.BINDINGS.OWNER_TYPE_ID', \CCrmOwnerType::Lead),
				array('join_type' => ($indicator == Volume\Lead::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($leadRelation);

			//---- Quote
			$quoteRelation = new ORM\Fields\Relations\Reference(
				'QUOTE',
				Crm\QuoteTable::class,
				ORM\Query\Join::on('this.BINDINGS.OWNER_ID', 'ref.ID')->where('this.BINDINGS.OWNER_TYPE_ID', \CCrmOwnerType::Quote),
				array('join_type' => ($indicator == Volume\Quote::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($quoteRelation);

			// STAGE_SEMANTIC_ID
			Volume\Quote::registerStageField($query, 'QUOTE', 'QUOTE_STAGE_SEMANTIC_ID');


			$invoiceRelation = new ORM\Fields\Relations\Reference(
				'INVOICE',
				Crm\InvoiceTable::class,
				ORM\Query\Join::on('this.BINDINGS.OWNER_ID', 'ref.ID')->where('this.BINDINGS.OWNER_TYPE_ID', \CCrmOwnerType::Invoice),
				array('join_type' => ($indicator == Volume\Invoice::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($invoiceRelation);

			$dayField = new ORM\Fields\ExpressionField(
				'INVOICE_DATE_CREATE_SHORT',
				'DATE(%s)',
				'INVOICE.DATE_INSERT'
			);
			$query->registerRuntimeField($dayField);

			// STAGE_SEMANTIC_ID
			Volume\Invoice::registerStageField($query, 'INVOICE', 'INVOICE_STAGE_SEMANTIC_ID');

			$stageField = new ORM\Fields\ExpressionField(
				'STAGE_SEMANTIC_ID',
				'case '.
				' when %s is not null then %s '.
				' when %s is not null then %s '.
				' when (%s) is not null then (%s) '.
				' when (%s) is not null then (%s) '.
				' else \'-\' '.
				'end',
				array(
					'DEAL.STAGE_SEMANTIC_ID', 'DEAL.STAGE_SEMANTIC_ID',
					'LEAD.STATUS_SEMANTIC_ID', 'LEAD.STATUS_SEMANTIC_ID',
					'QUOTE_STAGE_SEMANTIC_ID', 'QUOTE_STAGE_SEMANTIC_ID',
					'INVOICE_STAGE_SEMANTIC_ID', 'INVOICE_STAGE_SEMANTIC_ID'
				)
			);
			$query->registerRuntimeField($stageField);
		}

		return $query;
	}

	/**
	 * Returns query.
	 * @param string $indicator Volume indicator class name.
	 * @param  array $entityGroupField Fileds for groupping.
	 * @return ORM\Query\Query
	 */
	public function prepareFileQuery($indicator = '', $entityGroupField = array())
	{
		$queryBindings = Crm\ActivityBindingTable::query();

		$activityRelation = new ORM\Fields\Relations\Reference(
			'ACTIVITY',
			Crm\ActivityTable::class,
			ORM\Query\Join::on('this.ACTIVITY_ID', 'ref.ID'),
			array('join_type' => 'INNER')
		);
		$queryBindings->registerRuntimeField($activityRelation);

		$entityStageSemanticField = '';

		if (
			$indicator == Volume\Company::class
		)
		{
			$categoryId = Volume\Company::getCategoryId();
			$companyRelation = new ORM\Fields\Relations\Reference(
				'COMPANY',
				Crm\CompanyTable::class,
				ORM\Query\Join::on('this.OWNER_ID', 'ref.ID')
					->where('this.OWNER_TYPE_ID', \CCrmOwnerType::Company)
					->where('ref.CATEGORY_ID', $categoryId)
					->where('ref.IS_MY_COMPANY', 'N'),
				array('join_type' => 'INNER')
			);
			$queryBindings->registerRuntimeField($companyRelation);
		}
		elseif (
			$indicator == Volume\Contact::class
		)
		{
			$categoryId = Volume\Contact::getCategoryId();
			$contactRelation = new ORM\Fields\Relations\Reference(
				'CONTACT',
				Crm\ContactTable::class,
				ORM\Query\Join::on('this.OWNER_ID', 'ref.ID')
					->where('this.OWNER_TYPE_ID', \CCrmOwnerType::Contact)
					->where('ref.CATEGORY_ID', $categoryId),
				array('join_type' => 'INNER')
			);
			$queryBindings->registerRuntimeField($contactRelation);
		}
		else
		{
			//----- Deal
			$dealRelation = new ORM\Fields\Relations\Reference(
				'DEAL',
				Crm\DealTable::class,
				ORM\Query\Join::on('this.OWNER_ID', 'ref.ID')->where('this.OWNER_TYPE_ID', \CCrmOwnerType::Deal),
				array('join_type' => ($indicator == Volume\Deal::class ? 'INNER' : 'LEFT'))
			);
			$queryBindings->registerRuntimeField($dealRelation);

			if ($indicator === Volume\Deal::class)
			{
				$entityStageSemanticField = 'DEAL_STAGE_SEMANTIC_ID';
				$queryBindings
					->registerRuntimeField(new ORM\Fields\ExpressionField($entityStageSemanticField, 'MAX(%s)', 'DEAL.STAGE_SEMANTIC_ID'))
					->addSelect($entityStageSemanticField);
			}
			else
			{
				$queryBindings
					->registerRuntimeField(new ORM\Fields\ExpressionField('DEAL_STAGE_SEMANTIC_ID', '%s', 'DEAL.STAGE_SEMANTIC_ID'));
			}


			//----- Lead
			$leadRelation = new ORM\Fields\Relations\Reference(
				'LEAD',
				Crm\LeadTable::class,
				ORM\Query\Join::on('this.OWNER_ID', 'ref.ID')->where('this.OWNER_TYPE_ID', \CCrmOwnerType::Lead),
				array('join_type' => ($indicator == Volume\Lead::class ? 'INNER' : 'LEFT'))
			);
			$queryBindings->registerRuntimeField($leadRelation);

			if ($indicator === Volume\Lead::class)
			{
				$entityStageSemanticField = 'LEAD_STATUS_SEMANTIC_ID';
				$queryBindings
					->registerRuntimeField(new ORM\Fields\ExpressionField($entityStageSemanticField, 'MAX(%s)', 'LEAD.STATUS_SEMANTIC_ID'))
					->addSelect($entityStageSemanticField);
			}
			else
			{
				$queryBindings
					->registerRuntimeField(new ORM\Fields\ExpressionField('LEAD_STATUS_SEMANTIC_ID', '%s', 'LEAD.STATUS_SEMANTIC_ID'));
			}


			//---- Quote
			$quoteRelation = new ORM\Fields\Relations\Reference(
				'QUOTE',
				Crm\QuoteTable::class,
				ORM\Query\Join::on('this.OWNER_ID', 'ref.ID')->where('this.OWNER_TYPE_ID', \CCrmOwnerType::Quote),
				array('join_type' => ($indicator == Volume\Quote::class ? 'INNER' : 'LEFT'))
			);
			$queryBindings->registerRuntimeField($quoteRelation);

			if ($indicator === Volume\Quote::class)
			{
				$entityStageSemanticField = 'QUOTE_STAGE_SEMANTIC_ID';
				Volume\Quote::registerStageField($queryBindings, 'QUOTE', 'QUOTE_STAGE_SRC');

				$queryBindings
					->registerRuntimeField(new ORM\Fields\ExpressionField($entityStageSemanticField, 'MAX(%s)', 'QUOTE_STAGE_SRC'))
					->addSelect($entityStageSemanticField);
			}
			else
			{
				// QUOTE_STAGE_SEMANTIC_ID
				Volume\Quote::registerStageField($queryBindings, 'QUOTE', 'QUOTE_STAGE_SEMANTIC_ID');
			}


			//----- Invoice
			$invoiceRelation = new ORM\Fields\Relations\Reference(
				'INVOICE',
				Crm\InvoiceTable::class,
				ORM\Query\Join::on('this.OWNER_ID', 'ref.ID')->where('this.OWNER_TYPE_ID', \CCrmOwnerType::Invoice),
				array('join_type' => ($indicator == Volume\Invoice::class ? 'INNER' : 'LEFT'))
			);
			$queryBindings->registerRuntimeField($invoiceRelation);

			if ($indicator === Volume\Invoice::class)
			{
				$entityStageSemanticField = 'INVOICE_STAGE_SEMANTIC_ID';
				Volume\Invoice::registerStageField($queryBindings, 'INVOICE', 'INVOICE_STAGE_SRC');

				$queryBindings
					->registerRuntimeField(new ORM\Fields\ExpressionField($entityStageSemanticField, 'MAX(%s)', 'INVOICE_STAGE_SRC'))
					->addSelect($entityStageSemanticField);
			}
			else
			{
				// INVOICE_STAGE_SEMANTIC_ID
				Volume\Invoice::registerStageField($queryBindings, 'INVOICE', 'INVOICE_STAGE_SEMANTIC_ID');
			}


			if ($indicator === Volume\Activity::class)
			{
				$entityStageSemanticField = 'STAGE_SEMANTIC_ID_MAX';
				// STAGE_SEMANTIC_ID
				$stageField = new ORM\Fields\ExpressionField(
					'ACT_STAGE_SEMANTIC_ID',
					'CASE '.
					' when %s is not null then %s '.
					' when %s is not null then %s '.
					' when %s is not null then %s '.
					' when %s is not null then %s '.
					'END',
					array(
						'DEAL_STAGE_SEMANTIC_ID', 'DEAL_STAGE_SEMANTIC_ID',
						'LEAD_STATUS_SEMANTIC_ID', 'LEAD_STATUS_SEMANTIC_ID',
						'QUOTE_STAGE_SEMANTIC_ID', 'QUOTE_STAGE_SEMANTIC_ID',
						'INVOICE_STAGE_SEMANTIC_ID', 'INVOICE_STAGE_SEMANTIC_ID',
					)
				);
				$queryBindings
					->registerRuntimeField($stageField)
					->registerRuntimeField(new ORM\Fields\ExpressionField($entityStageSemanticField, 'MAX(%s)', 'ACT_STAGE_SEMANTIC_ID'))
					->addSelect($entityStageSemanticField);
			}
		}

		$queryBindings
			->registerRuntimeField(new ORM\Fields\ExpressionField('DATE_CREATE_SHORT_MAX', 'MAX(%s)', 'ACTIVITY.DATE_CREATED_SHORT'))
			->addSelect('DATE_CREATE_SHORT_MAX')

			->registerRuntimeField(new ORM\Fields\ExpressionField('ACTIVITY_ID_MAX', 'MAX(%s)', 'ACTIVITY_ID'))
			->addSelect('ACTIVITY_ID_MAX')

			// group by
			->addGroup('ACTIVITY_ID')
			// having
			->registerRuntimeField(new ORM\Fields\ExpressionField('CNT', 'COUNT(*)'))
			->addFilter('>CNT', 0)
		;

		// filter here

		$subEntityBindings = Main\ORM\Entity::getInstanceByQuery($queryBindings);
		$bindings = new Main\ORM\Fields\Relations\Reference('BIND', $subEntityBindings, ORM\Query\Join::on('this.ID', 'ref.ACTIVITY_ID_MAX'));

		$query = Crm\ActivityTable::query();
		$query
			->registerRuntimeField($bindings)
			->registerRuntimeField(new ORM\Fields\ExpressionField('DATE_CREATED_SHORT', '%s', 'BIND.DATE_CREATE_SHORT_MAX'))
		;

		if ($entityStageSemanticField !== '')
		{
			$query->registerRuntimeField(new ORM\Fields\ExpressionField('STAGE_SEMANTIC_ID', '%s', 'BIND.'.$entityStageSemanticField));
		}

		return $query;
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
			if ($key0 === 'QUOTE_STAGE_SEMANTIC_ID')
			{
				$statuses = Volume\Quote::getStatusSemantics($value);
				$query->where('QUOTE.STATUS_ID', 'in', $statuses);
			}
			elseif ($key0 === 'INVOICE_STAGE_SEMANTIC_ID')
			{
				$statuses = Volume\Invoice::getStatusSemantics($value);
				$query->where('INVOICE.STATUS_ID', 'in', $statuses);
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
	 * Returns query to measure files attached to activity.
	 * @param string $indicator Volume indicator class name.
	 * @param  array $entityGroupField Fileds for groupping.
	 * @return ORM\Query\Query
	 */
	public function getActivityFileMeasureQuery($indicator, $entityGroupField = array())
	{
		$query = $this->prepareFileQuery($indicator, $entityGroupField);

		$this->prepareFilter($query);

		// file type
		$subQueryFile = Main\FileTable::query();
		$elementCrmFile = new ORM\Fields\Relations\Reference(
			'elem',
			Crm\ActivityElementTable::class,
			ORM\Query\Join::on('this.ID', 'ref.ELEMENT_ID')
							 ->where('ref.STORAGE_TYPE_ID', Crm\Integration\StorageType::File),
			array('join_type' => 'INNER')
		);
		$subQueryFile
			->registerRuntimeField($elementCrmFile)

			->registerRuntimeField(new ORM\Fields\ExpressionField('SIZE', 'MAX(%s)', 'FILE_SIZE'))
			->addSelect('SIZE')

			->registerRuntimeField(new ORM\Fields\ExpressionField('ACTIVITY_ID', 'MAX(%s)', 'elem.ACTIVITY_ID'))
			->addSelect('ACTIVITY_ID')

			->registerRuntimeField(new ORM\Fields\ExpressionField('FILE_COUNT', 'COUNT(DISTINCT(%s))', 'elem.ELEMENT_ID'))
			->addSelect('FILE_COUNT')

			// group by
			->addGroup('elem.ELEMENT_ID')

			// having count(*) > 0
			->registerRuntimeField(new ORM\Fields\ExpressionField('CNT', 'COUNT(*)'))
			->addFilter('>CNT', 0)
		;

		// file type
		$subEntityFile = Main\ORM\Entity::getInstanceByQuery($subQueryFile);
		$file = new Main\ORM\Fields\Relations\Reference('FILE', $subEntityFile, ORM\Query\Join::on('this.ID', 'ref.ACTIVITY_ID'));
		$query->registerRuntimeField($file);

		// disk type
		if (parent::isModuleAvailable('disk'))
		{
			$subQueryDisk = Disk\Internals\FileTable::query();
			$elementCrmDisk = new ORM\Fields\Relations\Reference(
				'elem',
				Crm\ActivityElementTable::class,
				ORM\Query\Join::on('this.ID', 'ref.ELEMENT_ID')
								->where('ref.STORAGE_TYPE_ID', Crm\Integration\StorageType::Disk)
								->where('this.TYPE', '=', \Bitrix\Disk\Internals\ObjectTable::TYPE_FILE),
				array('join_type' => 'INNER')
			);
			$subQueryDisk
				->registerRuntimeField($elementCrmDisk)

				->registerRuntimeField(new ORM\Fields\ExpressionField('DISK_SIZE', 'MAX(%s)', 'SIZE'))
				->addSelect('DISK_SIZE')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ACTIVITY_ID', 'MAX(%s)', 'elem.ACTIVITY_ID'))
				->addSelect('ACTIVITY_ID')

				->registerRuntimeField(new ORM\Fields\ExpressionField('DISK_COUNT', 'COUNT(DISTINCT(%s))', 'elem.ELEMENT_ID'))
				->addSelect('DISK_COUNT')

				// group by
				->addGroup('elem.ELEMENT_ID')

				// having count(*) > 0
				->registerRuntimeField(new ORM\Fields\ExpressionField('CNT', 'COUNT(*)'))
				->addFilter('>CNT', 0)
			;

			// disk type
			$subEntityDisk = Main\ORM\Entity::getInstanceByQuery($subQueryDisk);
			$diskFile = new Main\ORM\Fields\Relations\Reference('DISK_FILE', $subEntityDisk, ORM\Query\Join::on('this.ID', 'ref.ACTIVITY_ID'));
			$query->registerRuntimeField($diskFile);

			$diskSize = new ORM\Fields\ExpressionField('DISK_SIZE', 'SUM(IFNULL(%s, 0))', 'DISK_FILE.DISK_SIZE');
			$diskCount = new ORM\Fields\ExpressionField('DISK_COUNT', 'SUM(IFNULL(%s, 0))', 'DISK_FILE.DISK_COUNT');
			$query
				->registerRuntimeField($diskSize)
				->registerRuntimeField($diskCount);

			$fileSize = new ORM\Fields\ExpressionField('FILE_SIZE', 'SUM(IFNULL(%s, 0)) + SUM(IFNULL(%s, 0))', array('FILE.SIZE', 'DISK_FILE.DISK_SIZE'));
			$fileCount = new ORM\Fields\ExpressionField('FILE_COUNT', 'SUM(IFNULL(%s, 0)) + SUM(IFNULL(%s, 0))', array('FILE.FILE_COUNT', 'DISK_FILE.DISK_COUNT'));
			$query
				->registerRuntimeField($fileSize)
				->registerRuntimeField($fileCount);

			$query
				->addSelect('FILE_SIZE')
				->addSelect('FILE_COUNT')
				->addSelect('DISK_SIZE')
				->addSelect('DISK_COUNT');
		}
		else
		{
			$fileSize = new ORM\Fields\ExpressionField('FILE_SIZE', 'SUM(IFNULL(%s, 0))', 'FILE.SIZE');
			$fileCount = new ORM\Fields\ExpressionField('FILE_COUNT', 'SUM(IFNULL(%s, 0))', 'FILE.FILE_COUNT');
			$query
				->registerRuntimeField($fileSize)
				->addSelect('FILE_SIZE')
				->registerRuntimeField($fileCount)
				->addSelect('FILE_COUNT');
		}

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
			$avgActivityTableRowLength = (double)self::$tablesInformation[Crm\ActivityTable::getTableName()]['AVG_SIZE'];

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
				->addSelect('DATE_CREATED_SHORT', 'DATE_CREATE')
				->addGroup('DATE_CREATED_SHORT')

				// STAGE_SEMANTIC_ID
				->addSelect('STAGE_SEMANTIC_ID')
				->addGroup('STAGE_SEMANTIC_ID')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_COUNT', 'COUNT(DISTINCT %s)', 'ID'))
				->addSelect('ENTITY_COUNT')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_SIZE', 'COUNT(DISTINCT %s) * '.$avgActivityTableRowLength, 'ID'))
				->addSelect('ENTITY_SIZE');

			$querySql = $sqlIns. $query->getQuery();

			$connection->queryExecute($querySql);

			if ($this->collectEntityRowSize)
			{
				$entityList = self::getEntityList();
				foreach ($entityList as $entityClass)
				{
					if ($entityClass == Crm\ActivityTable::class || $entityClass == Crm\ActivityBindingTable::class)
					{
						continue;
					}
					/**
					 * @var \Bitrix\Main\ORM\Data\DataManager $entityClass
					 */
					$entityEntity = $entityClass::getEntity();

					$fieldName = 'ACTIVITY_ID';
					if ($entityEntity->hasField($fieldName))
					{
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
								->addSelect('DATE_CREATED_SHORT', 'DATE_CREATE')
								->addGroup('DATE_CREATED_SHORT')

								// STAGE_SEMANTIC_ID
								->addSelect('STAGE_SEMANTIC_ID')
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
			}
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
		$querySql = $this->prepareRelationQuerySql(array(
			'DATE_CREATE' => 'DATE_CREATED_SHORT',
			'STAGE_SEMANTIC_ID' => 'STAGE_SEMANTIC_ID',
		));

		if ($querySql != '')
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
					{$querySql}
				) src
				GROUP BY
					DATE_CREATE,
					STAGE_SEMANTIC_ID
				HAVING 
					SUM(FILE_COUNT) > 0
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

		return $this;
	}


	/**
	 * Returns count of entities.
	 * @return int
	 */
	public function countEntity()
	{
		$count = -1;

		$filter = $this->getFilter();
		if (empty($filter))
		{
			$query = Crm\ActivityTable::query();
		}
		else
		{
			$query = $this->prepareQuery();
		}

		if ($this->prepareFilter($query))
		{
			$count = 0;

			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(DISTINCT %s)', 'ID');
			$query
				->registerRuntimeField($countField)
				->addSelect('CNT');


			$res = $query->exec();
			if ($row = $res->fetch())
			{
				$count = $row['CNT'];
				$this->activityCount = $row['CNT'];
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

		$filter = $this->getFilter();
		if (empty($filter))
		{
			$query = Crm\ActivityTable::query();
		}
		else
		{
			$query = $this->prepareQuery();
		}

		$dropped = -1;

		if ($this->prepareFilter($query))
		{
			$userPermissions = \CCrmPerms::GetUserPermissions($this->getOwner());

			$query
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
					if (!\CCrmActivity::DeleteStorageElements($activity['ID']))
					{
						$this->collectError(new Main\Error(\CCrmActivity::GetLastErrorMessage(), self::ERROR_DELETION_FAILED));
					}
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
	 * Gets SQL query code for activity count.
	 *
	 * @param array $entityGroupField Entity fields to group by.
	 *
	 * @return string
	 */
	private function prepareRelationQuerySql(array $entityGroupField = array())
	{
		$query = $this->prepareQuery();
		if ($this->prepareFilter($query))
		{
			$activityVolume = new Volume\Activity();
			$activityVolume->setFilter($this->getFilter());
		}
		else
		{
			return '';
		}

		//-----------

		$activityQuery = $activityVolume->prepareQuery(static::className());
		$activityVolume->prepareFilter($activityQuery);

		$fileCount = new ORM\Fields\ExpressionField('FILE_SIZE', '0');
		$fileBindingCount = new ORM\Fields\ExpressionField('FILE_COUNT', '0');
		$activityQuery
			->registerRuntimeField($fileCount)
			->registerRuntimeField($fileBindingCount)
			->addSelect('FILE_SIZE')
			->addSelect('FILE_COUNT');

		if (self::isModuleAvailable('disk'))
		{
			$diskCount = new ORM\Fields\ExpressionField('DISK_SIZE', '0');
			$diskBindingCount = new ORM\Fields\ExpressionField('DISK_COUNT', '0');
			$activityQuery
				->registerRuntimeField($diskCount)
				->registerRuntimeField($diskBindingCount)
				->addSelect('DISK_SIZE')
				->addSelect('DISK_COUNT');
		}

		$activityCount = new ORM\Fields\ExpressionField('ACTIVITY_COUNT', 'COUNT(DISTINCT %s)', 'ID');
		$activityBindingCount = new ORM\Fields\ExpressionField('BINDINGS_COUNT', 'COUNT(%s)', 'BINDINGS.ID');
		$activityQuery
			->registerRuntimeField($activityCount)
			->registerRuntimeField($activityBindingCount)
			->addSelect('ACTIVITY_COUNT')
			->addSelect('BINDINGS_COUNT');

		foreach ($entityGroupField as $alias => $field)
		{
			$activityQuery->addSelect($field, $alias);
			$activityQuery->addGroup($field);
		}

		//-----------

		$activityFileQuery = $activityVolume->getActivityFileMeasureQuery(static::className(), $entityGroupField);

		$activityCount = new ORM\Fields\ExpressionField('ACTIVITY_COUNT', '0');
		$activityBindingCount = new ORM\Fields\ExpressionField('BINDINGS_COUNT', '0');
		$activityFileQuery
			->registerRuntimeField($activityCount)
			->registerRuntimeField($activityBindingCount)
			->addSelect('ACTIVITY_COUNT')
			->addSelect('BINDINGS_COUNT');

		foreach ($entityGroupField as $alias => $field)
		{
			$field = str_replace('.', '_', $field);
			$activityFileQuery->addSelect($field, $alias);
			$activityFileQuery->addGroup($field);
		}

		$sqlQuery =
			$activityQuery->getQuery().
			' UNION '.
			$activityFileQuery->getQuery();

		return $sqlQuery;
	}
}

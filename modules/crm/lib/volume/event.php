<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM;
use Bitrix\Main\Localization\Loc;


class Event extends Crm\Volume\Base implements Crm\Volume\IVolumeClear, Crm\Volume\IVolumeClearFile, Crm\Volume\IVolumeUrl
{
	/** @var array */
	protected static $entityList = array(
		Crm\EventTable::class,
		Crm\EventRelationsTable::class,
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
		'DATE_CREATE' => 'EVENT_BY.DATE_CREATE',
		'DATE_CREATED_SHORT' => 'DATE_CREATED_SHORT',
		'SORT_ID' => 'EVENT_BY.ID',
	);

	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_EVENT_TITLE');
	}

	/**
	 * Returns availability to drop entity.
	 * @return boolean
	 */
	public function canClearEntity()
	{
		$restriction = Crm\Restriction\RestrictionManager::getHistoryViewRestriction();
		if(!$restriction->hasPermission())
		{
			$error = $restriction->getHtml();
			if(is_string($error) && $error !== '')
			{
				$this->collectError(new Main\Error($error));
			}
			else
			{
				$this->collectError(new Main\Error('', self::ERROR_PERMISSION_DENIED));
			}

			return false;
		}

		return true;
	}

	/**
	 * Returns availability to drop entity attachments.
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
	 * Get entity list path.
	 * @return string
	 */
	public function getUrl()
	{
		static $entityListPath;
		if($entityListPath === null)
		{
			$entityListPath = \CComponentEngine::MakePathFromTemplate(
				\Bitrix\Main\Config\Option::get('crm', 'path_to_event_list', '/crm/events/')
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
			'FILTER_ID' => 'CRM_EVENT_LIST',
			'GRID_ID' => 'CRM_EVENT_LIST',
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
	 * Component action list for measure process.
	 * @param array $componentCommandAlias Command alias.
	 * @return array
	 */
	public function getActionList($componentCommandAlias)
	{
		return $this->prepareRangeActionList(
			Crm\EventTable::class,
			'DATE_CREATE',
			array(
				'MEASURE_ENTITY' => $componentCommandAlias['MEASURE_ENTITY'],
				'MEASURE_FILE' => $componentCommandAlias['MEASURE_FILE'],
			)
		);
	}

	/**
	 * Returns query.
	 * @param string $indicator Volume indicator class name.
	 * @return ORM\Query\Query
	 */
	public function prepareQuery($indicator = '')
	{
		$query = Crm\EventRelationsTable::query();

		/** @global \CDatabase $DB */
		global $DB;

		$dayField = new ORM\Fields\ExpressionField(
			'DATE_CREATED_SHORT',
			$DB->datetimeToDateFunction('%s'),
			'EVENT_BY.DATE_CREATE'
		);
		$query->registerRuntimeField($dayField);

		if (
			$indicator == Crm\Volume\Company::class
		)
		{
			$companyRelation = new ORM\Fields\Relations\Reference(
				'COMPANY',
				Crm\CompanyTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::CompanyName),
				array('join_type' => ($indicator == Crm\Volume\Company::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($companyRelation);
		}
		elseif (
			$indicator == Crm\Volume\Contact::class
		)
		{
			$leadRelation = new ORM\Fields\Relations\Reference(
				'CONTACT',
				Crm\ContactTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::ContactName),
				array('join_type' => ($indicator == Crm\Volume\Contact::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($leadRelation);
		}
		else
		{
			$dealRelation = new ORM\Fields\Relations\Reference(
				'DEAL',
				Crm\DealTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::DealName),
				array('join_type' => ($indicator == Crm\Volume\Deal::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($dealRelation);

			$leadRelation = new ORM\Fields\Relations\Reference(
				'LEAD',
				Crm\LeadTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::LeadName),
				array('join_type' => ($indicator == Crm\Volume\Lead::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($leadRelation);

			$leadRelation = new ORM\Fields\Relations\Reference(
				'QUOTE',
				Crm\QuoteTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::QuoteName),
				array('join_type' => ($indicator == Crm\Volume\Quote::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($leadRelation);

			// STAGE_SEMANTIC_ID
			Crm\Volume\Quote::registerStageField($query, 'QUOTE', 'QUOTE_STAGE_SEMANTIC_ID');

			$leadRelation = new ORM\Fields\Relations\Reference(
				'INVOICE',
				Crm\InvoiceTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::InvoiceName),
				array('join_type' => ($indicator == Crm\Volume\Invoice::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($leadRelation);

			$dayField = new ORM\Fields\ExpressionField(
				'INVOICE_DATE_CREATE_SHORT',
				$DB->datetimeToDateFunction('%s'),
				'INVOICE.DATE_INSERT'
			);
			$query->registerRuntimeField($dayField);


			// STAGE_SEMANTIC_ID
			Crm\Volume\Invoice::registerStageField($query, 'INVOICE', 'INVOICE_STAGE_SEMANTIC_ID');

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
			if ($key0 == 'STAGE_SEMANTIC_ID')
			{
				$query->where(ORM\Query\Query::filter()
					->logic('or')
					->where(array(
						array('DEAL.STAGE_SEMANTIC_ID', 'in', $value),
						array('LEAD.STATUS_SEMANTIC_ID', 'in', $value),
						array('QUOTE.STATUS_ID', 'in', Crm\Volume\Quote::getStatusSemantics($value)),
						array('INVOICE.STATUS_ID', 'in', Crm\Volume\Invoice::getStatusSemantics($value)),
					))
				);
			}
			elseif ($key0 == 'QUOTE_STAGE_SEMANTIC_ID')
			{
				$statuses = Crm\Volume\Quote::getStatusSemantics($value);
				$query->where('QUOTE.STATUS_ID', 'in', $statuses);
			}
			elseif ($key0 == 'INVOICE_STAGE_SEMANTIC_ID')
			{
				$statuses = Crm\Volume\Invoice::getStatusSemantics($value);
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
	 * Returns query to measure files attached to events.
	 * @param string $indicator Volume indicator class name.
	 * @return ORM\Query\Query
	 */
	public function getEventFileMeasureQuery($indicator = '')
	{
		if (!class_exists('\\Bitrix\\Crm\\Volume\\EventFileReferenceTable'))
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			$eventTable = $helper->quote(Crm\EventTable::getTableName());

			// analise b_crm_event with non empty field FILES
			$querySql = "(
				select  
					src.ID AS EVENT_ID,
					CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(src.fids, ' ', NS.n), ' ', -1) AS UNSIGNED) as FILE_ID
				from (
					select 1 as n union
					select 2 union
					select 3 union
					select 4 union
					select 5 union
					select 6 union
					select 7 union
					select 8 union
					select 9 union
					select 10 union
					select 11 union
					select 12 union
					select 13 union
					select 14 union
					select 15 union
					select 16 union
					select 17 union
					select 18 union
					select 19 union
					select 20
				) NS
				inner join
				(
					select
						@xml := replace(replace(replace(replace(e.FILES,'a:','<a><len>'),';}','</i></a>'),':{i:','</len><i>'),';i:','</i><i>') as xml,
						CAST(ExtractValue(@xml, '/a/len') AS UNSIGNED) as len,
						ExtractValue(@xml, '/a/i[position() mod 2 = 0]') as fids,
						e.ID
					from 
						{$eventTable} e
					where 
						e.FILES IS NOT NULL
				) src 
				ON NS.n <= src.len
			)";

			\Bitrix\Main\ORM\Entity::compileEntity(
				'EventFileReference',
				array(
					'EVENT_ID' => array('data_type' => 'integer'),
					'FILE_ID' => array('data_type' => 'integer'),
					/*
					new ORM\Fields\Relations\Reference(
						'EVENT',
						Crm\EventTable::class,
						array('=this.EVENT_ID' => 'ref.ID'),
						array('join_type' => 'INNER')
					),
					*/
					new ORM\Fields\Relations\Reference(
						'FILE',
						Main\FileTable::class,
						array('=this.FILE_ID' => 'ref.ID'),
						array('join_type' => 'INNER')
					),
				),
				array('table_name' => $querySql, 'namespace' => __NAMESPACE__)
			);
		}

		/** @var \Bitrix\Main\ORM\Query\Query $query */
		//$query = Crm\Volume\EventFileReferenceTable::query();

		$query = $this->prepareQuery($indicator);
		$this->prepareFilter($query);


		$fileRef = new ORM\Fields\Relations\Reference(
			'FILEREF',
			Crm\Volume\EventFileReferenceTable::class,
			ORM\Query\Join::on('this.EVENT_ID', 'ref.EVENT_ID'),
			array('join_type' => 'INNER')
		);
		$query->registerRuntimeField($fileRef);

		$file = new ORM\Fields\Relations\Reference(
			'FILE',
			Main\FileTable::class,
			ORM\Query\Join::on('this.FILEREF.FILE_ID', 'ref.ID'),
			array('join_type' => 'INNER')
		);
		$query->registerRuntimeField($file);


		$fileSize = new ORM\Fields\ExpressionField('FILE_SIZE', 'SUM(IFNULL(%s, 0))', 'FILEREF.FILE.FILE_SIZE');
		$fileCount = new ORM\Fields\ExpressionField('FILE_COUNT', 'COUNT(%s)', 'FILEREF.FILE.ID');
		$query
			->registerRuntimeField($fileSize)
			->addSelect('FILE_SIZE')
			->registerRuntimeField($fileCount)
			->addSelect('FILE_COUNT');

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
			$avgEventTableRowLength = (double)self::$tablesInformation[Crm\EventTable::getTableName()]['AVG_SIZE'];
			$avgEventRelationsTableRowLength = (double)self::$tablesInformation[Crm\EventRelationsTable::getTableName()]['AVG_SIZE'];

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

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_COUNT', 'COUNT(DISTINCT %s)', 'EVENT_BY.ID'))
				->addSelect('ENTITY_COUNT')

				->registerRuntimeField(new ORM\Fields\ExpressionField(
					'ENTITY_SIZE',
					'COUNT(DISTINCT %s) * '.$avgEventTableRowLength. ' + COUNT(%s) * '.$avgEventRelationsTableRowLength,
					array('EVENT_BY.ID', 'EVENT_ID')
				))
				->addSelect('ENTITY_SIZE');

			$querySql = $sqlIns. $query->getQuery();

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
		$querySql = $this->prepareEventQuery(array(
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
					0 as DISK_SIZE,
					0 as DISK_COUNT
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

		if (count($this->getFilter()) === 0)
		{
			$query = Crm\EventTable::query();
			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(%s)', 'ID');
		}
		else
		{
			$query = $this->prepareQuery();
			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(DISTINCT %s)', 'EVENT_BY.ID');
		}

		if ($this->prepareFilter($query))
		{
			$count = 0;

			$query->registerRuntimeField($countField);
			$query->addSelect('CNT');

			$res = $query->exec();
			if ($row = $res->fetch())
			{
				$count = $row['CNT'];
				$this->eventCount = $row['CNT'];
			}
		}

		return $count;
	}

	/**
	 * Returns count of entities.
	 *
	 * @return int
	 */
	public function countEntityWithFile()
	{
		$count = -1;

		if (count($this->getFilter()) === 0)
		{
			$query = Crm\EventTable::query();
			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(%s)', 'ID');
		}
		else
		{
			$query = $this->prepareQuery();
			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(DISTINCT %s)', 'EVENT_BY.ID');

			$filesFieldAlias = new ORM\Fields\ExpressionField('FILES', '%s', 'EVENT_BY.FILES');
			$query->registerRuntimeField($filesFieldAlias);
		}

		if ($this->prepareFilter($query))
		{
			$count = 0;

			$query
				->registerRuntimeField($countField)
				->addSelect('CNT')
				->whereNotNull('FILES');

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
	 * @return boolean
	 */
	public function clearEntity()
	{
		if (!$this->canClearEntity())
		{
			return false;
		}

		if (count($this->getFilter()) === 0)
		{
			$query = Crm\EventRelationsTable::query();
		}
		else
		{
			$query = $this->prepareQuery();
		}

		if ($this->prepareFilter($query))
		{
			$query
				->addSelect('ID', 'RELATION_ID')
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(array('RELATION_ID' => 'ASC'));

			if ($this->getProcessOffset() > 0)
			{
				$query->where('RELATION_ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			$success = true;
			$entity = new \CCrmEvent();
			while ($event = $res->fetch())
			{
				$this->setProcessOffset($event['RELATION_ID']);

				if ($entity->Delete($event['RELATION_ID'], array('CURRENT_USER' => $this->getOwner())) !== false)
				{
					$this->incrementDroppedEntityCount();
				}
				else
				{
					$this->collectError(new Main\Error('Deletion failed with event #'.$event['RELATION_ID'], self::ERROR_DELETION_FAILED));
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
	 * Performs dropping entity attachments.
	 * @return boolean
	 */
	public function clearFiles()
	{
		if (!$this->canClearFile())
		{
			return false;
		}

		if (count($this->getFilter()) === 0)
		{
			$query = Crm\EventRelationsTable::query();
		}
		else
		{
			$query = $this->prepareQuery();
		}

		if ($this->prepareFilter($query))
		{
			$eventIds = new ORM\Fields\ExpressionField('EID', 'DISTINCT %s', 'EVENT_BY.ID');

			$query
				->registerRuntimeField($eventIds)
				->addSelect('EID')
				->addSelect('EVENT_BY.FILES', 'FILES')
				->setLimit(self::MAX_FILE_PER_INTERACTION)
				->setOrder(array('EVENT_BY.ID' => 'ASC'))
				//->where('FILES', '>', 6)// length of serialized string 'a:0:{}'
				->whereNotNull('EVENT_BY.FILES')
			;
			/*
			$query
				->whereNotNull('EVENT_BY.FILES')
				->where('EVENT_BY.FILES', '!=', '')
				->where('EVENT_BY.FILES', '!=', 'a:0:{}');
			*/

			if ($this->getProcessOffset() > 0)
			{
				$query->where('EVENT_BY.ID', '>', $this->getProcessOffset());
			}

			$result = $query->exec();

			$success = true;
			while ($event = $result->fetch())
			{
				$files = unserialize($event['FILES']);
				if (!is_array($files))
				{
					$this->setProcessOffset($event['EID']);
					continue;
				}

				for ($i = count($files) - 1; $i >= 0; $i--)
				{
					$fileId = $files[$i];

					\CFile::Delete((int)$fileId);
					//todo: How to count fail here

					unset($files[$i]);

					if ($this->hasTimeLimitReached())
					{
						$success = false;
						break;
					}
				}

				if (count($files) > 0)
				{
					$res = Crm\EventTable::update($event['EID'], array('FILES' => serialize($files)));
				}
				else
				{
					$res = Crm\EventTable::update($event['EID'], array('FILES' => null));
				}
				if ($res->isSuccess())
				{
					$this->incrementDroppedFileCount();
				}
				else
				{
					$this->incrementFailCount();
				}

				if ($this->hasTimeLimitReached())
				{
					$success = false;
					break;
				}

				$this->setProcessOffset($event['EID']);
			}
		}

		return $success;
	}
}

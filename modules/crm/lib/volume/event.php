<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Localization\Loc;


class Event
	extends Volume\Base
	implements Volume\IVolumeClear, Volume\IVolumeClearFile, Volume\IVolumeUrl
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
		'DATE_CREATE' => 'DATE_CREATE',
		'DATE_CREATED_SHORT' => 'DATE_CREATED_SHORT',
		'SORT_ID' => 'EVENT_ID',
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
				Main\Config\Option::get('crm', 'path_to_event_list', '/crm/events/')
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
		if ($indicator !== '' && $indicator !== Volume\Event::className())
		{
			return $this->prepareRelationQuery($indicator);
		}

		return $this->prepareNonRelationQuery();
	}

	/**
	 * Returns query.
	 *
	 * @return ORM\Query\Query
	 */
	public function prepareNonRelationQuery()
	{
		$query = Crm\EventTable::query();

		$query->registerRuntimeField(new ORM\Fields\ExpressionField(
			'EVENT_ID',
			'%s',
			'ID'
		));

		$query->registerRuntimeField(new ORM\Fields\ExpressionField(
			'DATE_CREATED_SHORT',
			'DATE(%s)',
			'DATE_CREATE'
		));

		return $query;
	}

	/**
	 * Returns query.
	 *
	 * @param string $indicatorType Volume indicator class name.
	 *
	 * @return ORM\Query\Query
	 */
	public function prepareRelationQuery($indicatorType)
	{
		$query = Crm\EventRelationsTable::query();

		$query->registerRuntimeField(new ORM\Fields\ExpressionField(
			'FILES',
			'%s',
			'EVENT_BY.FILES'
		));

		$query->registerRuntimeField(
			(new ORM\Fields\ExpressionField(
				'DATE_CREATE',
				'%s',
				'EVENT_BY.DATE_CREATE'
			))
			->configureValueType(ORM\Fields\DatetimeField::class)
		);

		$query->registerRuntimeField(new ORM\Fields\ExpressionField(
			'DATE_CREATED_SHORT',
			'DATE(%s)',
			'DATE_CREATE'
		));

		if (
			$indicatorType == Crm\Volume\Company::class
		)
		{
			$categoryId = Crm\Volume\Company::getCategoryId();
			$companyRelation = new ORM\Fields\Relations\Reference(
				'COMPANY',
				Crm\CompanyTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')
					->where('this.ENTITY_TYPE', \CCrmOwnerType::CompanyName)
					->where('ref.CATEGORY_ID', $categoryId)
					->where('ref.IS_MY_COMPANY', 'N'),
				['join_type' => ($indicatorType == Crm\Volume\Company::class ? 'INNER' : 'LEFT')]
			);
			$query->registerRuntimeField($companyRelation);
		}
		elseif (
			$indicatorType == Crm\Volume\Contact::class
		)
		{
			$categoryId = Crm\Volume\Contact::getCategoryId();
			$leadRelation = new ORM\Fields\Relations\Reference(
				'CONTACT',
				Crm\ContactTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')
					->where('this.ENTITY_TYPE', \CCrmOwnerType::ContactName)
					->where('ref.CATEGORY_ID', $categoryId),
				['join_type' => ($indicatorType == Crm\Volume\Contact::class ? 'INNER' : 'LEFT')]
			);
			$query->registerRuntimeField($leadRelation);
		}
		else
		{
			$dealRelation = new ORM\Fields\Relations\Reference(
				'DEAL',
				Crm\DealTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::DealName),
				array('join_type' => ($indicatorType == Crm\Volume\Deal::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($dealRelation);

			$leadRelation = new ORM\Fields\Relations\Reference(
				'LEAD',
				Crm\LeadTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::LeadName),
				array('join_type' => ($indicatorType == Crm\Volume\Lead::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($leadRelation);

			$leadRelation = new ORM\Fields\Relations\Reference(
				'QUOTE',
				Crm\QuoteTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::QuoteName),
				array('join_type' => ($indicatorType == Crm\Volume\Quote::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($leadRelation);

			// STAGE_SEMANTIC_ID
			Crm\Volume\Quote::registerStageField($query, 'QUOTE', 'QUOTE_STAGE_SEMANTIC_ID');

			$leadRelation = new ORM\Fields\Relations\Reference(
				'INVOICE',
				Crm\InvoiceTable::class,
				ORM\Query\Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', \CCrmOwnerType::InvoiceName),
				array('join_type' => ($indicatorType == Crm\Volume\Invoice::class ? 'INNER' : 'LEFT'))
			);
			$query->registerRuntimeField($leadRelation);

			$dayField = new ORM\Fields\ExpressionField(
				'INVOICE_DATE_CREATE_SHORT',
				'DATE(%s)',
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
			if ($key0 == 'STAGE_SEMANTIC_ID' && $query->getEntity()->hasField($key0))
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
			$connection = Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			$eventTable = $helper->quote(Crm\EventTable::getTableName());

			for ($i = 2, $auxiliaries = ['1 as n']; $i <= 50; $i++)
			{
				$auxiliaries[] = $i;
			}
			$auxiliarySql = implode(' union select ', $auxiliaries);

			// analise b_crm_event with non empty field FILES
			$querySql = "(
				select  
					src.ID AS EVENT_ID,
					CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(src.fids, ' ', NS.n), ' ', -1) AS UNSIGNED) as FILE_ID
				from (
					select {$auxiliarySql}
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

			Main\ORM\Entity::compileEntity(
				'EventFileReference',
				array(
					'EVENT_ID' => array('data_type' => 'integer'),
					'FILE_ID' => array('data_type' => 'integer'),
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

		/** @var Main\ORM\Query\Query $query */
		$query = $this->prepareQuery($indicator);
		$this->prepareFilter($query);


		$fileRef = new ORM\Fields\Relations\Reference(
			'FILEREF',
			Crm\Volume\EventFileReferenceTable::class,
			ORM\Query\Join::on('this.ID', 'ref.EVENT_ID'),
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

			$connection = Main\Application::getConnection();

			$this->checkTemporally();

			$data = array(
				'INDICATOR_TYPE' => '',
				'OWNER_ID' => '',
				'DATE_CREATE' => new Main\Type\Date(),
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
				->addSelect('DATE_CREATED_SHORT')
				->addGroup('DATE_CREATED_SHORT')

				->registerRuntimeField(new ORM\Fields\ExpressionField('ENTITY_COUNT', 'COUNT(DISTINCT %s)', 'EVENT_ID'))
				->addSelect('ENTITY_COUNT')

				->registerRuntimeField(new ORM\Fields\ExpressionField(
					'ENTITY_SIZE',
					'COUNT(DISTINCT %s) * '.$avgEventTableRowLength,
					'ID'
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
		$eventQuery = $this->prepareQuery();
		if ($this->prepareFilter($eventQuery))
		{
			$entityGroupField = array(
				'DATE_CREATED_SHORT' => 'DATE_CREATED_SHORT',
			);

			$eventFileQuery = $this->getEventFileMeasureQuery();
			foreach ($entityGroupField as $alias => $field)
			{
				$eventFileQuery->addSelect($field, $alias);
				$eventFileQuery->addGroup($field);
			}

			$querySql = $eventFileQuery->getQuery();

			$querySql = "
				SELECT 
					'".static::getIndicatorId()."' as INDICATOR_TYPE,
					'".$this->getOwner()."' as OWNER_ID,
					DATE_CREATED_SHORT as DATE_CREATE,
					SUM(FILE_SIZE) as FILE_SIZE,
					SUM(FILE_COUNT) as FILE_COUNT
				FROM 
				(
					{$querySql}
				) src
				GROUP BY
					DATE_CREATE
				HAVING 
					SUM(FILE_COUNT) > 0
			";

			Crm\VolumeTable::updateFromSelect(
				$querySql,
				array(
					'FILE_SIZE' => 'destination.FILE_SIZE + source.FILE_SIZE',
					'FILE_COUNT' => 'destination.FILE_COUNT + source.FILE_COUNT',
				),
				array(
					'INDICATOR_TYPE',
					'OWNER_ID',
					'DATE_CREATE',
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

		if (count($this->getFilter()) > 0)
		{
			$query = $this->prepareQuery();
			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(DISTINCT %s)', 'EVENT_ID');
		}
		else
		{
			//$query = Crm\EventTable::query();
			$query = $this->prepareNonRelationQuery();
			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(%s)', 'EVENT_ID');
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

		if (count($this->getFilter()) > 0)
		{
			$query = $this->prepareQuery();
			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(DISTINCT %s)', 'EVENT_ID');
		}
		else
		{
			//$query = Crm\EventTable::query();
			$query = $this->prepareNonRelationQuery();
			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(%s)', 'EVENT_ID');
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
	 * Performs dropping crm event.
	 *
	 * @param int $eventId Event Id.
	 * @param int $deletedBy Who did it.
	 *
	 * @return boolean
	 */
	public static function dropEvent($eventId, $deletedBy)
	{
		$success = true;

		$eventRes = Crm\EventTable::getByPrimary($eventId);
		if ($event = $eventRes->fetch())
		{
			$relationsList = Crm\EventRelationsTable::getList(['filter' => ['EVENT_ID' => $event['ID']]]);
			if ($relationsList->getSelectedRowsCount() > 0)
			{
				$entity = new \CCrmEvent();
				while ($relation = $relationsList->fetch())
				{
					if ($entity->delete($relation['ID'], ['CURRENT_USER' => $deletedBy]) === false)
					{
						$success = false;
					}
				}
			}

			if ($success)
			{
				$files = \unserialize($event['FILES'], ['allowed_classes' => false]);
				if (is_array($files))
				{
					for ($i = count($files) - 1; $i >= 0; $i--)
					{
						\CFile::delete((int)$files[$i]);
					}
				}
			}

			if ($success)
			{
				$deleteResult = Crm\EventTable::delete($event['ID']);
				$success = $deleteResult->isSuccess();
			}
		}

		return $success;
	}

	/**
	 * Performs dropping crm event files.
	 *
	 * @param int $eventId Event Id.
	 * @param int $deletedBy Who did it.
	 *
	 * @return int
	 */
	public static function dropEventFiles($eventId, $deletedBy)
	{
		$droppedCount = 0;

		$eventRes = Crm\EventTable::getByPrimary($eventId);
		if ($event = $eventRes->fetch())
		{
			$files = \unserialize($event['FILES'], ['allowed_classes' => false]);
			if (is_array($files))
			{
				for ($i = count($files) - 1; $i >= 0; $i--)
				{
					\CFile::delete((int)$files[$i]);
					$droppedCount ++;

					//todo: How to count fail here

					unset($files[$i]);
				}
				if (count($files) > 0)
				{
					Crm\EventTable::update($eventId, ['FILES' => serialize($files)]);
				}
				else
				{
					Crm\EventTable::update($eventId, ['FILES' => null]);
				}
			}
		}

		return $droppedCount;
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

		if (count($this->getFilter()) > 0)
		{
			$query = $this->prepareQuery();
		}
		else
		{
			$query = $this->prepareNonRelationQuery();
		}

		$dropped = -1;

		if ($this->prepareFilter($query))
		{
			$query
				->addSelect('EVENT_ID')
				->setLimit(self::MAX_ENTITY_PER_INTERACTION)
				->setOrder(['EVENT_ID' => 'ASC']);

			if ($this->getProcessOffset() > 0)
			{
				$query->where('EVENT_ID', '>', $this->getProcessOffset());
			}

			$res = $query->exec();

			$dropped = 0;
			while ($event = $res->fetch())
			{
				$this->setProcessOffset($event['EVENT_ID']);

				if (self::dropEvent($event['EVENT_ID'], $this->getOwner()))
				{
					$this->incrementDroppedEntityCount();
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

	/**
	 * Performs dropping entity attachments.
	 * @return int
	 */
	public function clearFiles()
	{
		if (!$this->canClearFile())
		{
			return -1;
		}

		if (count($this->getFilter()) > 0)
		{
			$query = $this->prepareQuery();
		}
		else
		{
			$query = $this->prepareNonRelationQuery();
		}

		$dropped = -1;

		if ($this->prepareFilter($query))
		{
			$query
				->addSelect('EVENT_ID')
				->setLimit(self::MAX_FILE_PER_INTERACTION)
				->setOrder(array('EVENT_ID' => 'ASC'))
				->whereNotNull('FILES')
				->where('FILES', '<>', '')
			;
			/*
			if ($this->getProcessOffset() > 0)
			{
				$query->where('EVENT_ID', '>=', $this->getProcessOffset());
			}
			*/

			$result = $query->exec();

			$dropped = 0;
			while ($event = $result->fetch())
			{
				$this->setProcessOffset($event['EVENT_ID']);

				$droppedCount = self::dropEventFiles($event['EVENT_ID'], $this->getOwner());
				if ($droppedCount >= 0)
				{
					$this->incrementDroppedFileCount($droppedCount);
					$dropped ++;
				}
				else
				{
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

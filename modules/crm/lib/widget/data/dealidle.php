<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Crm\History\HistoryEntryType;
use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Crm\Statistics\Entity\DealActivityStatisticsTable;
use Bitrix\Crm\Statistics\Entity\DealInvoiceStatisticsTable;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

class DealIdle extends DealDataSource
{
	const TYPE_NAME = 'DEAL_IDLE';
	const GROUP_BY_DATE = 'DATE';
	const GROUP_BY_USER = 'USER';
	private static $messagesLoaded = false;
	/**
	* @return string
	*/
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}
	/**
	 * @return array
	 */
	public function getList(array $params)
	{
		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		$this->applyFilterContext($filter);

		$permissionSql = '';
		if($this->enablePermissionCheck)
		{
			$permissionSql = $this->preparePermissionSql();
			if($permissionSql === false)
			{
				//Access denied;
				return array();
			}
		}


		/** @var array $select */
		$select = isset($params['select']) && is_array($params['select']) ? $params['select'] : array();
		$name = '';
		if(!empty($select))
		{
			$selectItem = $select[0];
			if(isset($selectItem['name']))
			{
				$name = $selectItem['name'];
			}
		}

		if($name === '')
		{
			$name = 'COUNT';
		}

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_USER && $group !== self::GROUP_BY_DATE)
		{
			$group = '';
		}

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query = new Query(DealStageHistoryTable::getEntity());
		$query->registerRuntimeField('', new ExpressionField($name, "COUNT(*)"));
		$query->addSelect($name);
		$query->addFilter('=TYPE_ID', HistoryEntryType::CREATION);
		$query->addFilter('>=START_DATE', $periodStartDate);
		$query->addFilter('<=START_DATE', $periodEndDate);

		if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
		{
			$query->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(is_array($responsibleIDs) && !empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$categoryID = (int)$filter->getExtraParam('dealCategoryID', -1);
		if($categoryID >= 0)
		{
			//HACK: use SqlExpression to avoid filter sql like (CATEGORY_ID IS NULL OR CATEGORY_ID = 0), that cause the filesort.
			$query->addFilter('=CATEGORY_ID', new Main\DB\SqlExpression('?i', $categoryID));
		}

		$sort = isset($params['sort']) && is_array($params['sort']) && !empty($params['sort']) ? $params['sort'] : null;
		if($sort)
		{
			foreach($sort as $sortItem)
			{
				if(isset($sortItem['name']))
				{
					$query->addOrder($sortItem['name'], isset($sortItem['order']) ? $sortItem['order'] : 'ASC');
				}
			}
		}

		if($group !== '')
		{
			if($group === self::GROUP_BY_USER)
			{
				$query->addSelect('RESPONSIBLE_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}
			else//if($group === self::GROUP_BY_DATE)
			{
				$query->addSelect('START_DATE', 'DATE');
				$query->addGroup('START_DATE');
				if(!$sort)
				{
					$query->addOrder('START_DATE', 'ASC');
				}
			}
		}

		$query->registerRuntimeField('',
			new ExpressionField(
				'E1',
				'(CASE WHEN NOT EXISTS('.self::prepareHistoryQuery($periodStartDate, $periodEndDate, HistoryEntryType::MODIFICATION, '%s', '_h')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E1', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E2',
				'(CASE WHEN NOT EXISTS('.self::prepareHistoryQuery($periodStartDate, $periodEndDate, HistoryEntryType::FINALIZATION, '%s', '_h')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E2', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E3',
				'(CASE WHEN NOT EXISTS('.self::prepareActivityQuery($periodStartDate, $periodEndDate, '%s', '_a')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E3', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E4',
				'(CASE WHEN NOT EXISTS('.self::prepareInvoiceQuery($periodStartDate, $periodEndDate, '%s', '_i')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E4', 1);

		$results = array();
		$dbResult = $query->exec();
		if($group === self::GROUP_BY_DATE)
		{
			while($ary = $dbResult->fetch())
			{
				$ary['DATE'] = $ary['DATE']->format('Y-m-d');
				$results[] = $ary;
			}
		}
		elseif($group === self::GROUP_BY_USER)
		{
			$rawResult = array();
			$userIDs = array();
			while($ary = $dbResult->fetch())
			{
				$userID = $ary['RESPONSIBLE_ID'] = (int)$ary['RESPONSIBLE_ID'];
				if($userID > 0 && !isset($userIDs[$userID]))
				{
					$userIDs[$userID] = true;
				}
				$rawResult[] = $ary;
			}
			$userNames = self::prepareUserNames(array_keys($userIDs));
			foreach($rawResult as $item)
			{
				$userID = $item['RESPONSIBLE_ID'];
				$item['USER_ID'] = $userID;
				$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
				unset($item['RESPONSIBLE_ID']);

				$results[] = $item;
			}
		}
		else
		{
			while($ary = $dbResult->fetch())
			{
				$results[] = $ary;
			}
		}

		return $results;
	}

	/**
	 * @return Query
	 */
	public static function prepareHistoryQuery($startDate, $endDate, $typeID, $ownerFieldReference = '%s', $postfix = '')
	{
		$query = new Query(DealStageHistoryTable::getEntity());
		if($postfix !== '')
		{
			$query->setTableAliasPostfix($postfix);
		}
		$query->addFilter('=TYPE_ID', $typeID);
		$query->addFilter('>=CREATED_DATE', $startDate);
		$query->addFilter('<=CREATED_DATE', $endDate);
		$query->addFilter('=OWNER_ID', new SqlExpression($ownerFieldReference));
		if(!(Main\Application::getConnection() instanceof Main\DB\OracleConnection))
		{
			$query->setLimit(1);
		}

		return $query;
	}

	/**
	 * @return Query
	 */
	protected static function prepareActivityQuery($startDate, $endDate, $ownerFieldReference = '%s', $postfix = '')
	{
		$query = new Query(DealActivityStatisticsTable::getEntity());
		if($postfix !== '')
		{
			$query->setTableAliasPostfix($postfix);
		}
		$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=DEADLINE_DATE', $startDate);
		$query->addFilter('<=DEADLINE_DATE', $endDate);
		$query->addFilter('=OWNER_ID', new SqlExpression($ownerFieldReference));
		if(!(Main\Application::getConnection() instanceof Main\DB\OracleConnection))
		{
			$query->setLimit(1);
		}

		return $query;
	}

	/**
	 * @return Query
	 */
	protected static function prepareInvoiceQuery($startDate, $endDate, $ownerFieldReference = '%s', $postfix = '')
	{
		$query = new Query(DealInvoiceStatisticsTable::getEntity());
		if($postfix !== '')
		{
			$query->setTableAliasPostfix($postfix);
		}
		$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=CREATED_DATE', $startDate);
		$query->addFilter('<=CREATED_DATE', $endDate);
		$query->addFilter('=OWNER_ID', new SqlExpression($ownerFieldReference));
		if(!(Main\Application::getConnection() instanceof Main\DB\OracleConnection))
		{
			$query->setLimit(1);
		}

		return $query;
	}
	/**
	 * Get current data context
	 * @return DataContext
	 */
	public function getDataContext()
	{
		return DataContext::ENTITY;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		self::includeModuleFile();
		return array(
			array(
				'entity' => \CCrmOwnerType::DealName,
				'title' => GetMessage('CRM_DEAL_IDLE_PRESET_OVERALL_COUNT'),
				'listTitle' => GetMessage('CRM_DEAL_IDLE_PRESET_OVERALL_COUNT_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT'),
				'context' => DataContext::ENTITY,
				'category' => 'IDLE'
			)
		);
	}
	/**
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		if(isset($categories['DEAL_IDLE']))
		{
			return;
		}

		self::includeModuleFile();
		$categories['DEAL_IDLE'] = array(
			'entity' => \CCrmOwnerType::DealName,
			'title' => GetMessage('CRM_DEAL_IDLE_CATEGORY'),
			'name' => 'IDLE',
			'enableSemantics' => false
		);
	}
	/**
	 * @return void
	 */
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}

	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query->addFilter('=TYPE_ID', HistoryEntryType::CREATION);
		$query->addFilter('>=START_DATE', $periodStartDate);
		$query->addFilter('<=START_DATE', $periodEndDate);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E1',
				'(CASE WHEN NOT EXISTS('.self::prepareHistoryQuery($periodStartDate, $periodEndDate, HistoryEntryType::MODIFICATION, '%s', '_i')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E1', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E2',
				'(CASE WHEN NOT EXISTS('.self::prepareHistoryQuery($periodStartDate, $periodEndDate, HistoryEntryType::FINALIZATION, '%s', '_i')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E2', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E3',
				'(CASE WHEN NOT EXISTS('.self::prepareActivityQuery($periodStartDate, $periodEndDate, '%s')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E3', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E4',
				'(CASE WHEN NOT EXISTS('.self::prepareInvoiceQuery($periodStartDate, $periodEndDate, '%s')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E4', 1);

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$categoryID = (int)$filter->getExtraParam('dealCategoryID', -1);
		if($categoryID >= 0)
		{
			//HACK: use SqlExpression to avoid filter sql like (CATEGORY_ID IS NULL OR CATEGORY_ID = 0), that cause the filesort.
			$query->addFilter('=CATEGORY_ID', new Main\DB\SqlExpression('?i', $categoryID));
		}

		return array(
			'__JOINS' => array(
					array(
						'TYPE' => 'INNER',
						'SQL' => 'INNER JOIN('.$query->getQuery().') DS ON DS.OWNER_ID = L.ID'
					)
				)
		);
	}
}
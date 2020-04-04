<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Crm\History\HistoryEntryType;
use Bitrix\Crm\History\Entity\LeadStatusHistoryTable;
use Bitrix\Crm\Statistics\Entity\LeadActivityStatisticsTable;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

class LeadIdle extends LeadDataSource
{
	const TYPE_NAME = 'LEAD_IDLE';
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

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_USER && $group !== self::GROUP_BY_DATE)
		{
			$group = '';
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

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query = new Query(LeadStatusHistoryTable::getEntity());
		$query->registerRuntimeField('', new ExpressionField($name, "COUNT(*)"));
		$query->addSelect($name);
		$query->addFilter('=TYPE_ID', HistoryEntryType::CREATION);
		$query->addFilter('>=CREATED_DATE', $periodStartDate);
		$query->addFilter('<=CREATED_DATE', $periodEndDate);

		if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
		{
			$query->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(is_array($responsibleIDs) && !empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
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
				$query->addSelect('CREATED_DATE', 'DATE');
				$query->addGroup('CREATED_DATE');
				if(!$sort)
				{
					$query->addOrder('CREATED_DATE', 'ASC');
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

		$dbResult = $query->exec();
		$result = array();
		if($group === self::GROUP_BY_DATE)
		{
			while($ary = $dbResult->fetch())
			{
				$ary['DATE'] = $ary['DATE']->format('Y-m-d');
				$result[] = $ary;
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

				$result[] = $item;
			}
		}
		else
		{
			while($ary = $dbResult->fetch())
			{
				$result[] = $ary;
			}
		}

		return $result;
	}

	/**
	 * @return Query
	 */
	public static function prepareHistoryQuery($startDate, $endDate, $typeID, $ownerFieldReference = '%s', $postfix = '')
	{
		$query = new Query(LeadStatusHistoryTable::getEntity());
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
		$query = new Query(LeadActivityStatisticsTable::getEntity());
		if($postfix !== '')
		{
			$query->setTableAliasPostfix($postfix);
		}
		$query->addFilter('=IS_JUNK', false);
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
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_IDLE_PRESET_OVERALL_COUNT'),
				'listTitle' => GetMessage('CRM_LEAD_IDLE_PRESET_OVERALL_COUNT_SHORT'),
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
		if(isset($categories['IDLE']))
		{
			return;
		}

		self::includeModuleFile();
		$categories['IDLE'] = array(
			'entity' => \CCrmOwnerType::LeadName,
			'title' => GetMessage('CRM_LEAD_IDLE_CATEGORY'),
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
		$query = new Query(LeadStatusHistoryTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query->addFilter('=TYPE_ID', HistoryEntryType::CREATION);
		$query->addFilter('>=CREATED_DATE', $periodStartDate);
		$query->addFilter('<=CREATED_DATE', $periodEndDate);

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

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		return array(
			'__JOINS' => array(
					array(
						'TYPE' => 'INNER',
						'SQL' => 'INNER JOIN('.$query->getQuery().') LI ON LI.OWNER_ID = L.ID'
					)
				)
		);
	}
}
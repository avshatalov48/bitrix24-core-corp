<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\DB\SqlExpression;

use Bitrix\Crm\History\Entity\LeadStatusHistoryTable;
use Bitrix\Crm\Statistics\Entity\LeadActivityStatisticsTable;
use Bitrix\Crm\Widget\Filter;

class LeadInWork extends LeadDataSource
{
	const TYPE_NAME = 'LEAD_IN_WORK';
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
		if($group !== '' && $group !== self::GROUP_BY_DATE && $group !== self::GROUP_BY_USER)
		{
			$group = '';
		}

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$responsibleIDs = $filter->getResponsibleIDs();

		$queries = array(
			self::prepareHistoryQuery($periodStartDate, $periodEndDate, $responsibleIDs, $group),
			self::prepareActivityQuery($periodStartDate, $periodEndDate, $responsibleIDs, $group)
		);

		$userIDs = array();
		$map = array();
		foreach($queries as $query)
		{
			/** @var Query $query*/
			if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
			{
				$query->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
			}

			$dbResult = $query->exec();
			if($group === self::GROUP_BY_DATE)
			{
				while($ary = $dbResult->fetch())
				{
					/** @var Date $date */
					$date =  $ary['DATE'];
					$key = $date->format('Y-m-d');

					if($key === '9999-12-31')
					{
						//Skip empty dates
						continue;
					}

					if(!isset($map[$key]))
					{
						$map[$key] = array();
					}

					$ownerID =  $ary['OWNER_ID'];
					if(!isset($map[$key][$ownerID]))
					{
						$map[$key][$ownerID] = true;
					}
				}
			}
			elseif($group === self::GROUP_BY_USER)
			{
				while($ary = $dbResult->fetch())
				{
					$userID = $ary['RESPONSIBLE_ID'] = (int)$ary['RESPONSIBLE_ID'];
					if($userID <= 0)
					{
						//Skip empty users
						continue;
					}

					if(!isset($userIDs[$userID]))
					{
						$userIDs[$userID] = true;
					}

					if(!isset($map[$userID]))
					{
						$map[$userID] = array();
					}

					$ownerID =  $ary['OWNER_ID'];
					if(!isset($map[$userID][$ownerID]))
					{
						$map[$userID][$ownerID] = true;
					}
				}
			}
			else
			{
				while($ary = $dbResult->fetch())
				{
					$ownerID =  $ary['OWNER_ID'];
					if(!isset($map[$ownerID]))
					{
						$map[$ownerID] = true;
					}
				}
			}
		}

		$results = array();
		if($group === self::GROUP_BY_DATE)
		{
			foreach($map as $k => $v)
			{
				$results[] = array('DATE' => $k, $name => count($v));
			}
		}
		elseif($group === self::GROUP_BY_USER)
		{
			$userNames = self::prepareUserNames(array_keys($userIDs));
			foreach($map as $k => $v)
			{
				$results[] = array(
					'USER_ID' => $k,
					'USER' => isset($userNames[$k]) ? $userNames[$k] : "[{$k}]",
					$name => count($v)
				);
			}
		}
		else
		{
			$results[] = array($name => count($map));
		}

		if(isset($params['sort']) && is_array($params['sort']))
		{
			foreach($params['sort'] as $sortItem)
			{
				if(isset($sortItem['name']) && $sortItem['name'] === $name)
				{
					$order = isset($sortItem['order']) && strtolower($sortItem['order']) === 'desc'
						? SORT_DESC : SORT_ASC;
					Collection::sortByColumn($results, array($name => $order));
					break;
				}
			}
		}

		return $results;
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
				'title' => GetMessage('CRM_LEAD_IN_WORK_PRESET_OVERALL_COUNT'),
				'listTitle' => GetMessage('CRM_LEAD_IN_WORK_PRESET_OVERALL_COUNT_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT'),
				'context' => DataContext::ENTITY,
				'category' => 'IN_WORK'
			)
		);
	}

	/**
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		if(isset($categories['LEAD_IN_WORK']))
		{
			return;
		}

		self::includeModuleFile();
		$categories['LEAD_IN_WORK'] = array(
			'entity' => \CCrmOwnerType::LeadName,
			'title' => GetMessage('CRM_LEAD_IN_WORK_CATEGORY'),
			'name' => 'IN_WORK',
			'enableSemantics' => false
		);
	}

	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
	/**
	 * @return Query
	 */
	protected static function prepareHistoryQuery($startDate, $endDate, $responsibleIDs = null, $group = '')
	{
		$query = new Query(LeadStatusHistoryTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addFilter('=IS_IN_WORK', true);
		$query->addFilter('>=CREATED_DATE', $startDate);
		$query->addFilter('<=CREATED_DATE', $endDate);
		$query->addGroup('OWNER_ID');

		if(is_array($responsibleIDs) && !empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		if($group === self::GROUP_BY_DATE)
		{
			$query->addSelect('CREATED_DATE', 'DATE');
			$query->addGroup('CREATED_DATE');
			$query->addOrder('CREATED_DATE', 'ASC');
		}
		elseif($group === self::GROUP_BY_USER)
		{
			$query->addSelect('RESPONSIBLE_ID');
			$query->addGroup('RESPONSIBLE_ID');
		}

		return $query;
	}
	/**
	 * @return Query
	 */
	protected static function prepareActivityQuery($startDate, $endDate, $responsibleIDs = null, $group = '')
	{
		$query = new Query(LeadActivityStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addFilter('=IS_JUNK', false);
		$query->addFilter('>=DEADLINE_DATE', $startDate);
		$query->addFilter('<=DEADLINE_DATE', $endDate);
		$query->addGroup('OWNER_ID');

		if(is_array($responsibleIDs) && !empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		if($group === self::GROUP_BY_DATE)
		{
			$query->addSelect('DEADLINE_DATE', 'DATE');
			$query->addGroup('DEADLINE_DATE');
			$query->addOrder('DEADLINE_DATE', 'ASC');
		}
		elseif($group === self::GROUP_BY_USER)
		{
			$query->addSelect('RESPONSIBLE_ID');
			$query->addGroup('RESPONSIBLE_ID');
		}

		return $query;
	}

	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$responsibleIDs = $filter->getResponsibleIDs();

		$queries = array(
			self::prepareHistoryQuery($periodStartDate, $periodEndDate, $responsibleIDs)->getQuery(),
			self::prepareActivityQuery($periodStartDate, $periodEndDate, $responsibleIDs)->getQuery()
		);

		return array(
			'__JOINS' => array(
				array(
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN('.implode("\nUNION\n", $queries).') DS ON DS.OWNER_ID = L.ID'
				)
			)
		);
	}
}
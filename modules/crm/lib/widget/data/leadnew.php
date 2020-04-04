<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\Collection;

use Bitrix\Crm\History\HistoryEntryType;
use Bitrix\Crm\History\Entity\LeadStatusHistoryTable;
use Bitrix\Crm\Widget\Filter;

class LeadNew extends LeadDataSource
{
	const TYPE_NAME = 'LEAD_NEW';
	const GROUP_BY_USER = 'USER';
	const GROUP_BY_DATE = 'DATE';
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
		if($group !== ''
			&& $group !== self::GROUP_BY_USER
			&& $group !== self::GROUP_BY_DATE)
		{
			$group = '';
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

		if($group !== '')
		{
			if($group === self::GROUP_BY_USER)
			{
				$query->addSelect('RESPONSIBLE_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}
			elseif($group === self::GROUP_BY_DATE)
			{
				$query->addSelect('CREATED_DATE', 'DATE');
				$query->addGroup('CREATED_DATE');
				if(!$sort)
				{
					$query->addOrder('CREATED_DATE', 'ASC');
				}
			}
		}

		$results = array();
		$dbResult = $query->exec();
		if($group === self::GROUP_BY_DATE)
		{
			while($ary = $dbResult->fetch())
			{
				/** @var Date $date */
				$date =  $ary['DATE'];
				$ary['DATE'] = $date->format('Y-m-d');
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

		if($sort)
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
				'title' => GetMessage('CRM_LEAD_NEW_PRESET_OVERALL_COUNT'),
				'listTitle' => GetMessage('CRM_LEAD_NEW_PRESET_OVERALL_COUNT_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT'),
				'context' => DataContext::ENTITY,
				'category' => 'NEW'
			)
		);
	}

	/**
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		if(isset($categories['NEW']))
		{
			return;
		}

		self::includeModuleFile();
		$categories['NEW'] = array(
			'entity' => \CCrmOwnerType::LeadName,
			'title' => GetMessage('CRM_LEAD_NEW_CATEGORY'),
			'name' => 'NEW',
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

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		return array(
			'__JOINS' => array(
					array(
						'TYPE' => 'INNER',
						'SQL' => 'INNER JOIN('.$query->getQuery().') LN ON LN.OWNER_ID = L.ID'
					)
				)
		);
	}
}
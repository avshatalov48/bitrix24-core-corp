<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Crm\Statistics\Entity\DealActivityStatisticsTable;
use Bitrix\Crm\Statistics\Entity\DealInvoiceStatisticsTable;
use Bitrix\Main;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Crm\Widget\Filter;

class DealWork extends DealDataSource
{
	const GROUP_BY_DATE = 'DATE';
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
		if($group !== '' && $group !== self::GROUP_BY_DATE)
		{
			$group = '';
		}
		$enableGroupByDate = $group !== '';

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$queries = array(
			self::prepareHistoryQuery($periodStartDate, $periodEndDate, $enableGroupByDate),
			self::prepareActivityQuery($periodStartDate, $periodEndDate, $enableGroupByDate),
			self::prepareInvoiceQuery($periodStartDate, $periodEndDate, $enableGroupByDate)
		);

		$map = array();
		foreach($queries as $query)
		{
			/** @var  Query $query*/
			$dbResult = $query->exec();
			if($enableGroupByDate)
			{
				while($ary = $dbResult->fetch())
				{
					/** @var Date $date */
					$date =  $ary['DATE'];
					$key = $date->format('Y-m-d');
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
		if($enableGroupByDate)
		{
			foreach($map as $k => $v)
			{
				$results[] = array('DATE' => $k, $name => count($v));
			}
		}
		else
		{
			$results[] = array($name => count($map));
		}
		return $results;
	}

	/**
	 * @return Query
	 */
	protected static function prepareHistoryQuery($startDate, $endDate, $groupByDate = true)
	{
		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=CREATED_DATE', $startDate);
		$query->addFilter('<=CREATED_DATE', $endDate);
		$query->addGroup('OWNER_ID');

		if($groupByDate)
		{
			$query->addSelect('CREATED_DATE', 'DATE');
			$query->addGroup('CREATED_DATE');
			$query->addOrder('CREATED_DATE', 'ASC');
		}

		return $query;
	}
	/**
	 * @return Query
	 */
	protected static function prepareActivityQuery($startDate, $endDate, $groupByDate = true)
	{
		$query = new Query(DealActivityStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		//$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=DEADLINE_DATE', $startDate);
		$query->addFilter('<=DEADLINE_DATE', $endDate);
		$query->addGroup('OWNER_ID');

		if($groupByDate)
		{
			$query->addSelect('DEADLINE_DATE', 'DATE');
			$query->addGroup('DEADLINE_DATE');
			$query->addOrder('DEADLINE_DATE', 'ASC');
		}

		return $query;
	}
	/**
	 * @return Query
	 */
	protected static function prepareInvoiceQuery($startDate, $endDate, $groupByDate = true)
	{
		$query = new Query(DealInvoiceStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=CREATED_DATE', $startDate);
		$query->addFilter('<=CREATED_DATE', $endDate);
		$query->addGroup('OWNER_ID');

		if($groupByDate)
		{
			$query->addSelect('CREATED_DATE', 'DATE');
			$query->addGroup('CREATED_DATE');
			$query->addOrder('CREATED_DATE', 'ASC');
		}

		return $query;
	}
}
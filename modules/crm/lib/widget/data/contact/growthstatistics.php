<?php
namespace Bitrix\Crm\Widget\Data\Contact;

use Bitrix\Crm\Statistics\Entity\ContactGrowthStatisticsTable;
use Bitrix\Crm\Widget\Data\DataContext;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;

class GrowthStatistics extends DataSource
{
	const TYPE_NAME = 'CONTACT_GROWTH_STATS';
	const GROUP_BY_USER = 'USER';
	const GROUP_BY_DATE = 'DATE';
	private static $messagesLoaded = false;

	protected static $entityListPath = null;

	/**
	 * @return string
	 */
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}

	/**
	 * @param array $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\InvalidOperationException
	 * @throws Main\ObjectNotFoundException
	 */
	public function getList(array $params)
	{
		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		$group = isset($params['group'])? mb_strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_USER && $group !== self::GROUP_BY_DATE)
		{
			$group = '';
		}

		//only TOTAL_COUNT and aggregate by COUNT supported yet.
		$name = 'TOTAL_COUNT';

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

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query = new Query(ContactGrowthStatisticsTable::getEntity());

		$query->registerRuntimeField('', new ExpressionField($name, "COUNT(*)"));
		$query->addSelect($name);

		$query->addFilter('>=CREATED_DATE', $periodStartDate);
		$query->addFilter('<=CREATED_DATE', $periodEndDate);

		if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
		{
			$query->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$cntQuery = new Query(ContactGrowthStatisticsTable::getEntity());
		$cntQuery->registerRuntimeField('', new ExpressionField($name, "COUNT(*)"));
		$cntQuery->addSelect($name);
		$cntQuery->addFilter('<CREATED_DATE', $periodStartDate);

		if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
		{
			$cntQuery->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$cntQuery->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$cntValue = 0;
		if ($group !== self::GROUP_BY_USER)
		{
			$cntRow = $cntQuery->exec()->fetch();
			$cntValue = $cntRow[$name];
		}
		else
		{
			$cntQuery->addSelect('RESPONSIBLE_ID');
			$cntQuery->addGroup('RESPONSIBLE_ID');
			$cntResult = $cntQuery->exec();
			$cntResponsibleValue = array();
			while ($cntRow = $cntResult->fetch())
			{
				$cntResponsibleValue[$cntRow['RESPONSIBLE_ID']] = $cntRow[$name];
			}
		}
		$totalCountBeforePeriod = $cntValue;

		$sort = isset($params['sort']) && is_array($params['sort']) && !empty($params['sort']) ? $params['sort'] : null;
		if($sort)
		{
			foreach($sort as $sortItem)
			{
				if(isset($sortItem['name']))
				{
					$sortName = $sortItem['name'];
					$query->addOrder($sortName, isset($sortItem['order']) ? $sortItem['order'] : 'ASC');
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
			else //if($groupBy === self::GROUP_BY_DATE)
			{
				$query->addSelect('CREATED_DATE');
				if(!$sort)
				{
					$query->addOrder('CREATED_DATE', 'ASC');
				}
			}
		}

		$dbResult = $query->exec();
		$result = array();
		if($group === self::GROUP_BY_DATE)
		{
			while($ary = $dbResult->fetch())
			{
				$date = $ary['CREATED_DATE']->format('Y-m-d');
				if (isset($result[$date]))
				{
					++$result[$date][$name];
					continue;
				}

				$ary['DATE'] = $date;
				unset($ary['CREATED_DATE']);

				$ary[$name] += $cntValue;
				$cntValue = $ary[$name];
				$result[$date] = $ary;
			}

			if ($periodStartDate && $periodEndDate)
			{
				try
				{
					$valuesWholePeriod = array();
					$currentCount = $totalCountBeforePeriod;

					if (is_string($periodStartDate))
					{
						$periodStartDate = \DateTime::createFromFormat(FORMAT_DATE, $periodStartDate)->getTimestamp();
						$periodStartDate = date('Y-m-d', $periodStartDate);
					}
					if (is_string($periodEndDate))
					{
						$periodEndDate = \DateTime::createFromFormat(FORMAT_DATE, $periodEndDate)->getTimestamp();
						$periodEndDate = date('Y-m-d', $periodEndDate);
					}

					$startDate = new \DateTime($periodStartDate);
					$endDate = new \DateTime($periodEndDate);
					while ($startDate <= $endDate)
					{
						$date = $startDate->format('Y-m-d');
						if (array_key_exists($date, $result))
						{
							$valuesWholePeriod[$date] = $result[$date];
							$currentCount = $result[$date]['TOTAL_COUNT'];
						}
						else
						{
							$valuesWholePeriod[$date] = array(
								'TOTAL_COUNT' => $currentCount,
								'DATE' => $date
							);
						}
						$startDate->add(new \DateInterval('P1D'));
					}
					if ($valuesWholePeriod)
					{
						$result = $valuesWholePeriod;
					}
				}
				catch (\Exception $e) {}
			}

			$result = array_values($result);
		}
		elseif($group === self::GROUP_BY_USER)
		{
			while($ary = $dbResult->fetch())
			{
				$result[] = $ary;
			}
			self::parseUserInfo($result, ['RESPONSIBLE_ID' => 'USER']);
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
	 * Get current data context
	 * @return DataContext
	 */
	public function getDataContext()
	{
		return DataContext::ENTITY;
	}
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$period = $filter->getPeriod();

		//fix date -> datetime
		$periodStart = Main\Type\DateTime::createFromTimestamp($period['START']->getTimestamp());
		$periodStart->setTime(0, 0, 0);
		$periodEnd = Main\Type\DateTime::createFromTimestamp($period['END']->getTimestamp());
		$periodEnd->setTime(23, 59, 59);

		$result = array(
			'>=DATE_CREATE' => $period['START'],
			'<=DATE_CREATE' => $periodEnd,
		);

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$result['@ASSIGNED_BY_ID'] = $responsibleIDs;
		}

		return $result;
	}
	/**
	 * Get entity list path.
	 * @static
	 * @return string
	 */
	protected static function getEntityListPath()
	{
		if(self::$entityListPath === null)
		{
			self::$entityListPath = \CComponentEngine::MakePathFromTemplate(
				Main\Config\Option::get('crm', 'path_to_contact_list', '/crm/contact/list/', false),
				array()
			);
		}
		return self::$entityListPath;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		self::includeModuleFile();
		return array(
			array(
				'entity' => \CCrmOwnerType::ContactName,
				'title' => GetMessage('CRM_CONTACT_GROWTH_STAT_PRESET_TOTAL_COUNT'),
				'name' => self::TYPE_NAME.'::TOTAL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'TOTAL_COUNT', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY
			)
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
}
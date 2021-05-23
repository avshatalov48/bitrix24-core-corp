<?php
namespace Bitrix\Crm\Widget\Data\Company;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Widget\Data\DataContext;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Statistics\Entity\CompanyActivitySumStatisticsTable;

class ActivitySumStatistics extends DataSource
{
	const TYPE_NAME = 'COMPANY_ACTIVITY_SUM_STATS';
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
	/** @return array */
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

		/** @var array $select */
		$select = isset($params['select']) && is_array($params['select']) ? $params['select'] : array();
		$name = '';
		$aggregate = '';
		if(!empty($select))
		{
			$selectItem = $select[0];
			if(isset($selectItem['name']))
			{
				$name = $selectItem['name'];
			}
			if(isset($selectItem['aggregate']))
			{
				$aggregate = mb_strtoupper($selectItem['aggregate']);
			}
		}

		list($providerId, $providerTypeId) = $this->getActivityProviderInfo();

		if($name === '')
		{
			$name = 'TOTAL_QTY';
		}

		if($aggregate !== '' && !in_array($aggregate, array('SUM', 'COUNT', 'MAX', 'MIN')))
		{
			$aggregate = '';
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

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query = new Query(CompanyActivitySumStatisticsTable::getEntity());

		$nameAlias = $name;
		if($aggregate !== '')
		{
			if($aggregate === 'COUNT')
			{
				$query->registerRuntimeField('', new ExpressionField($name, "COUNT(*)"));
			}
			else
			{
				$nameAlias = "{$nameAlias}_R";
				$query->registerRuntimeField('', new ExpressionField($nameAlias, "{$aggregate}(%s)", $name));
			}
		}
		$query->addSelect($nameAlias);

		$query->addFilter('>=DEADLINE_DATE', $periodStartDate);
		$query->addFilter('<=DEADLINE_DATE', $periodEndDate);

		if ($providerId)
			$query->addFilter('=PROVIDER_ID', $providerId);
		if ($providerTypeId)
			$query->addFilter('=PROVIDER_TYPE_ID', $providerTypeId);

		if (
			$filter->getContextEntityTypeName() === \CCrmOwnerType::CompanyName
			&& $filter->getContextEntityID() > 0
		)
		{
			$query->addFilter('=OWNER_ID', $filter->getContextEntityID());
		}

		if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
		{
			$query->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
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
					$sortName = $sortItem['name'];
					if($sortName === $name)
					{
						$sortName = $nameAlias;
					}
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
				$query->addSelect('DEADLINE_DATE');
				$query->addGroup('DEADLINE_DATE');
				if(!$sort)
				{
					$query->addOrder('DEADLINE_DATE', 'ASC');
				}
			}
		}

		$dbResult = $query->exec();
		$result = array();
		$useAlias = $nameAlias !== $name;
		if($group === self::GROUP_BY_DATE)
		{
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

				$ary['DATE'] = $this->getLocalDate($ary['DEADLINE_DATE'], 'Y-m-d');
				unset($ary['DEADLINE_DATE']);

				if($ary['DATE'] === '9999-12-31')
				{
					//Skip empty dates
					continue;
				}
				$result[] = $ary;
			}
		}
		elseif($group === self::GROUP_BY_USER)
		{
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}
				$result[] = $ary;
			}
			self::parseUserInfo($result, ['RESPONSIBLE_ID' => 'USER']);
		}
		else
		{
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

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
	/**
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		static::includeModuleFile();
		$result = array();
		$categories = static::getProviderCategories(CommunicationStatistics::STATISTICS_MONEY);

		foreach ($categories as $categoryId => $presetPrefix)
		{
			$result[] =	array(
				'entity' => \CCrmOwnerType::CompanyName,
				'title' => GetMessage('CRM_COMPANY_ACTIVITY_SUM_STAT_PROVIDER_PRESET'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':SUM_TOTAL',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'SUM_TOTAL',
					'aggregate' => 'SUM'
				),
				'context' => DataContext::FUND,
				'category' => $categoryId
			);
		}

		return $result;
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
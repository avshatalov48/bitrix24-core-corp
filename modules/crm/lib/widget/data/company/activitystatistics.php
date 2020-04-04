<?php
namespace Bitrix\Crm\Widget\Data\Company;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Activity\CommunicationWidgetPanel;
use Bitrix\Crm\Widget\Data\DataContext;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Statistics\Entity\CompanyActivityStatisticsTable;

class ActivityStatistics extends DataSource
{
	const TYPE_NAME = 'COMPANY_ACTIVITY_STATS';
	const GROUP_BY_USER = 'USER';
	const GROUP_BY_DATE = 'DATE';
	const GROUP_BY_PROVIDER_ID = 'PROVIDER_ID';
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

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_USER && $group !== self::GROUP_BY_DATE && $group !== self::GROUP_BY_PROVIDER_ID)
		{
			$group = '';
		}
		$enableGroupKey = isset($params['enableGroupKey']) ? (bool)$params['enableGroupKey'] : false;

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
				$aggregate = strtoupper($selectItem['aggregate']);
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

		$query = new Query(CompanyActivityStatisticsTable::getEntity());

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
			elseif($group === self::GROUP_BY_PROVIDER_ID)
			{
				$query->addSelect('PROVIDER_ID');
				$query->addGroup('PROVIDER_ID');
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

				$ary['DATE'] = $ary['DEADLINE_DATE']->format('Y-m-d');
				unset($ary['DEADLINE_DATE']);

				if($ary['DATE'] === '9999-12-31')
				{
					//Skip empty dates
					continue;
				}
				$result[] = $ary;
			}
		}
		elseif($group === self::GROUP_BY_PROVIDER_ID)
		{
			$providers = \CCrmActivity::GetProviders();
			
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

				$providerId = $ary['PROVIDER_ID'];
				if (isset($providers[$providerId]))
				{
					$ary['PROVIDER_ID'] = $providers[$providerId]::getName();
				}

				if($enableGroupKey)
				{
					$result[$providerId] = $ary;
				}
				else
				{
					$result[] = $ary;
				}
			}
		}
		elseif($group === self::GROUP_BY_USER)
		{
			$userIDs = array();
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

				$userID = $ary['RESPONSIBLE_ID'] = (int)$ary['RESPONSIBLE_ID'];
				if($userID > 0 && !isset($userIDs[$userID]))
				{
					$userIDs[$userID] = true;
				}
				$result[] = $ary;
			}
			$userNames = self::prepareUserNames(array_keys($userIDs));
			foreach($result as &$item)
			{
				$userID = $item['RESPONSIBLE_ID'];
				$item['USER_ID'] = $userID;
				$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
				unset($item['RESPONSIBLE_ID']);
			}
			unset($item);
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
		$categories = static::getProviderCategories(CommunicationStatistics::STATISTICS_QUANTITY);

		$result[] =	array(
			'entity' => \CCrmOwnerType::CompanyName,
			'title' => GetMessage('CRM_COMPANY_ACTIVITY_STAT_PROVIDER_PRESET'),
			'name' => self::TYPE_NAME.'::TOTAL_QTY',
			'source' => self::TYPE_NAME,
			'select' => array(
				'name' => 'TOTAL_QTY',
				'aggregate' => 'SUM'
			),
			'context' => DataContext::ENTITY,
			'grouping' => array('extras' => array(self::GROUP_BY_PROVIDER_ID))
		);

		foreach ($categories as $categoryId => $presetPrefix)
		{
			$result[] =	array(
				'entity' => \CCrmOwnerType::CompanyName,
				'title' => GetMessage('CRM_COMPANY_ACTIVITY_STAT_PROVIDER_PRESET'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':TOTAL_QTY',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'TOTAL_QTY',
					'aggregate' => 'SUM'
				),
				'context' => DataContext::ENTITY,
				'category' => $categoryId,
				'grouping' => array('extras' => array(self::GROUP_BY_PROVIDER_ID))
			);
		}

		return $result;
	}

	/**
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		static::includeModuleFile();
		$providers = \CCrmActivity::GetProviders();
		foreach ($providers as $provider)
		{
			$categoryId = 'ACTIVITY_'.$provider::getId();

			if(isset($categories[\CCrmOwnerType::CompanyName.$categoryId]))
			{
				continue;
			}
			
			$types = CommunicationWidgetPanel::getProviderTypes($provider);

			if ($types && $provider::getSupportedCommunicationStatistics())
			{
				$categories[\CCrmOwnerType::CompanyName.$categoryId] = array(
					'entity' => \CCrmOwnerType::CompanyName,
					'title' => Main\Localization\Loc::getMessage('CRM_COMPANY_ACTIVITY_STAT_PROVIDER_CATEGORY',
						array('#PROVIDER_NAME#' => $provider::getName())
					),
					'name' => $categoryId,
					'enableSemantics' => false
				);

				foreach ($types as $type)
				{
					$categoryId .= '_'.$type['PROVIDER_TYPE_ID'];
					$categories[\CCrmOwnerType::CompanyName.$categoryId] = array(
						'entity' => \CCrmOwnerType::CompanyName,
						'title' => $type['NAME'],
						'name' => $categoryId,
						'enableSemantics' => false
					);
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function initializeDemoData(array $data, array $params)
	{
		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group === self::GROUP_BY_PROVIDER_ID)
		{
			$providers = \CCrmActivity::GetProviders();

			$identityField = isset($data['identityField']) && $data['identityField'] !== ''
				? $data['identityField'] : 'PROVIDER_ID';

			$titleField = isset($data['titleField']) && $data['titleField'] !== ''
				? $data['titleField'] : 'PROVIDER';

			foreach($data['items'] as $k => $item)
			{
				$providerId = isset($item[$identityField]) ? $item[$identityField] : '';
				if($providerId !== '' && isset($providers[$providerId]))
				{
					$data['items'][$k][$titleField] = $providers[$providerId]::getName();
				}
			}
		}
		return $data;
	}

	/**
	 * @param array $groupings
	 * @return array Array of arrays
	 */
	public static function prepareGroupingExtras(array &$groupings)
	{
		$sourceKey = \CCrmOwnerType::CompanyName.':'.self::GROUP_BY_PROVIDER_ID;
		if(isset($groupings[$sourceKey]))
		{
			return;
		}

		self::includeModuleFile();
		$groupings[$sourceKey] = array(
			'entity' => \CCrmOwnerType::CompanyName,
			'title' => GetMessage('CRM_COMPANY_ACTIVITY_STAT_GROUP_BY_PROVIDER'),
			'name' => self::GROUP_BY_PROVIDER_ID
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
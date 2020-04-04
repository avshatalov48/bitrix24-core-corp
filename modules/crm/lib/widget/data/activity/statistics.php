<?php
namespace Bitrix\Crm\Widget\Data\Activity;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Activity\CommunicationWidgetPanel;
use Bitrix\Crm\Widget\Data\DataContext;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Statistics\Entity\ActivityStatisticsTable;

class Statistics extends DataSource
{
	const TYPE_NAME = 'ACTIVITY_STATS';
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

	/**
	 * @param array $params
	 * @return array
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

		$enableGroupKey = isset($params['group']) &&
			is_string($params['group']) &&
			isset($params['enableGroupKey']) &&
			$params['enableGroupKey'] === true ? strtoupper($params['group']) : false;

		$group = array();
		if (isset($params['group']) && (is_string($params['group']) || is_array($params['group'])))
		{
			if (is_string($params['group']))
			{
				$params['group'] = array($params['group']);
			}
			foreach ($params['group'] as $g)
			{
				$g = strtoupper($g);
				if ($g === self::GROUP_BY_USER || $g === self::GROUP_BY_DATE || $g === self::GROUP_BY_PROVIDER_ID)
				{
					$group[] = $g;
				}
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

		list($providerId, $providerTypeId) = $this->getActivityProviderInfo();

		if($name === '')
		{
			$name = 'TOTAL_QTY';
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

		/*
		//Caching is temporary disabled
		$cacheID = serialize($params);
		$data = $this->getCacheData($cacheID, $filter);
		if ($data !== false)
		{
			return $data;
		}
		*/

		$query = new Query(ActivityStatisticsTable::getEntity());

		$nameAlias = $name;
		$query->registerRuntimeField('', new ExpressionField($name, "COUNT(*)"));
		$query->addSelect($nameAlias);

		if ($periodStartDate !== null)
			$query->addFilter('>=DEADLINE_DATE', $periodStartDate);
		if ($periodEndDate !== null)
			$query->addFilter('<=DEADLINE_DATE', $periodEndDate);
		$query->addFilter('=COMPLETED', $filter->getExtraParam('COMPLETED', 'Y'));

		if ($providerId)
			$query->addFilter('=PROVIDER_ID', $providerId);
		if ($providerTypeId)
			$query->addFilter('=PROVIDER_TYPE_ID', $providerTypeId);

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

		if (in_array(self::GROUP_BY_USER, $group))
		{
			$query->addSelect('RESPONSIBLE_ID');
			$query->addGroup('RESPONSIBLE_ID');
		}
		if (in_array(self::GROUP_BY_PROVIDER_ID, $group))
		{
			$query->addSelect('PROVIDER_ID');
			$query->addGroup('PROVIDER_ID');
		}
		if (in_array(self::GROUP_BY_DATE, $group))
		{
			$query->addSelect('DEADLINE_DATE');
			$query->addGroup('DEADLINE_DATE');
			if(!$sort)
			{
				$query->addOrder('DEADLINE_DATE', 'ASC');
			}
		}
		if (isset($params['limit']) && $params['limit'] > 0)
		{
			$query->setLimit($params['limit']);
		}

		$dbResult = $query->exec();
		$result = array();
		$useAlias = $nameAlias !== $name;
		$providers = null;
		$userIDs = array();

		while($ary = $dbResult->fetch())
		{
			if($useAlias && isset($ary[$nameAlias]))
			{
				$ary[$name] = $ary[$nameAlias];
				unset($ary[$nameAlias]);
			}

			if (in_array(self::GROUP_BY_DATE, $group))
			{
				$ary['DATE'] = $ary['DEADLINE_DATE']->format('Y-m-d');
				unset($ary['DEADLINE_DATE']);

				if($ary['DATE'] === '9999-12-31')
				{
					continue;
				}
			}
			if (in_array(self::GROUP_BY_PROVIDER_ID, $group))
			{
				if ($providers === null)
					$providers = \CCrmActivity::GetProviders();

				$ary['~PROVIDER_ID'] = $providerId = $ary['PROVIDER_ID'];
				if (isset($providers[$providerId]))
					$ary['PROVIDER_ID'] = $providers[$providerId]::getName();
			}
			if (in_array(self::GROUP_BY_USER, $group))
			{
				$ary['RESPONSIBLE_ID'] = (int)$ary['RESPONSIBLE_ID'];
			}

			if ($enableGroupKey === self::GROUP_BY_DATE)
			{
				$result[$ary['DATE']] = $ary;
			}
			else if ($enableGroupKey === self::GROUP_BY_PROVIDER_ID)
			{
				$result[$ary['~PROVIDER_ID']] = $ary;
			}
			else if ($enableGroupKey === self::GROUP_BY_USER)
			{
				if ($ary['RESPONSIBLE_ID'] > 0)
				{
					$result[$ary['RESPONSIBLE_ID']] = $ary;
				}
			}
			else
			{
				$result[] = $ary;
			}
		}
		if (in_array(self::GROUP_BY_USER, $group))
		{
			self::parseUserInfo($result, array('RESPONSIBLE_ID' => 'USER'));
		}

		//Caching is temporary disabled
		//$this->setCacheData($result);
		return $result;
	}
	/**
	 * Get current data context
	 * @return string DataContext
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
			'entity' => \CCrmOwnerType::ActivityName,
			'title' => GetMessage('CRM_ACTIVITY_ACTIVITY_STAT_PROVIDER_PRESET'),
			'name' => self::TYPE_NAME.'::TOTAL_QTY',
			'source' => self::TYPE_NAME,
			'select' => array(
				'name' => 'TOTAL_QTY',
				'aggregate' => 'COUNT'
			),
			'context' => DataContext::ENTITY,
			'grouping' => array('extras' => array(self::GROUP_BY_PROVIDER_ID))
		);

		foreach ($categories as $categoryId => $presetPrefix)
		{
			$result[] =	array(
				'entity' => \CCrmOwnerType::ActivityName,
				'title' => GetMessage('CRM_ACTIVITY_ACTIVITY_STAT_PROVIDER_PRESET'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':TOTAL_QTY',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'TOTAL_QTY',
					'aggregate' => 'COUNT'
				),
				'context' => DataContext::ENTITY,
				'category' => $categoryId,
				'grouping' => array('extras' => array(self::GROUP_BY_PROVIDER_ID))
			);
		}

		return $result;
	}

	/**
	 * @param array $categories
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		static::includeModuleFile();
		$providers = \CCrmActivity::GetProviders();
		foreach ($providers as $provider)
		{
			$categoryId = 'ACTIVITY_'.$provider::getId();

			if(isset($categories[$categoryId]))
			{
				continue;
			}

			$types = CommunicationWidgetPanel::getProviderTypes($provider);

			if ($types && $provider::getSupportedCommunicationStatistics())
			{
				$categories[$categoryId] = array(
					'entity' => \CCrmOwnerType::ActivityName,
					'title' => Main\Localization\Loc::getMessage('CRM_ACTIVITY_ACTIVITY_STAT_PROVIDER_CATEGORY',
						array('#PROVIDER_NAME#' => $provider::getName())
					),
					'name' => $categoryId,
					'enableSemantics' => false
				);

				foreach ($types as $type)
				{
					$categoryId .= '_'.$type['PROVIDER_TYPE_ID'];
					$categories[$categoryId] = array(
						'entity' => \CCrmOwnerType::ActivityName,
						'title' => $type['NAME'],
						'name' => $categoryId,
						'enableSemantics' => false
					);
				}
			}
		}
	}

	/**
	 * @param array $data
	 * @param array $params
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
	 * @return void|array Array of arrays
	 */
	public static function prepareGroupingExtras(array &$groupings)
	{
		$sourceKey = \CCrmOwnerType::ActivityName.':'.self::GROUP_BY_PROVIDER_ID;
		if(isset($groupings[$sourceKey]))
		{
			return;
		}

		self::includeModuleFile();
		$groupings[$sourceKey] = array(
			'entity' => \CCrmOwnerType::ActivityName,
			'title' => GetMessage('CRM_ACTIVITY_ACTIVITY_STAT_GROUP_BY_PROVIDER'),
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
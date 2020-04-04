<?php
namespace Bitrix\Crm\Widget\Data\Activity;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Widget\Data\DataContext;
use Bitrix\Crm\Integration\Channel\ChannelType;
use Bitrix\Crm\Integration\Channel\ChannelTrackerManager;
use Bitrix\Crm\Statistics\Entity\ActivityChannelStatisticsTable;

class ChannelStatistics extends DataSource
{
	const TYPE_NAME = 'ACTIVITY_CHANNEL_STATS';
	const GROUP_BY_USER = 'USER';
	const GROUP_BY_DATE = 'DATE';
	const GROUP_BY_CHANNEL = 'CHANNEL';
	const GROUP_BY_DIRECTION = 'DIRECTION';
	const CATEGORY = 'CHANNEL_TRACKER';

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

		$channelTypeID = $filter->getExtraParam('channelTypeID', ChannelType::UNDEFINED);
		$channelOriginID = $filter->getExtraParam('channelOriginID', '');
		$channelComponentID = $filter->getExtraParam('channelComponentID', '');
		$direction = $filter->getExtraParam('directon', \CCrmActivityDirection::Undefined);

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
				if ($g === self::GROUP_BY_USER
					|| $g === self::GROUP_BY_DATE
					|| $g === self::GROUP_BY_CHANNEL
					|| $g === self::GROUP_BY_DIRECTION
				)
				{
					$group[] = $g;
				}
			}
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
				$aggregate = strtoupper($selectItem['aggregate']);
			}
		}

		if($name === '')
		{
			$name = 'COUNT';
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

		$query = new Query(ActivityChannelStatisticsTable::getEntity());

		$nameAlias = $name;
		if($aggregate !== '')
		{
			if($aggregate === 'COUNT')
			{
				$query->registerRuntimeField('', new ExpressionField($nameAlias, "COUNT(*)"));
			}
			else
			{
				$nameAlias = "{$nameAlias}_R";
				$query->registerRuntimeField('', new ExpressionField($nameAlias, "{$aggregate}(%s)", $name));
			}
		}

		$query->addSelect($nameAlias);
		$query->setTableAliasPostfix('_s1');

		//region Filter by period
		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		if($periodStartDate !== null && $periodEndDate !== null)
		{
			if($periodEndDate > $periodStartDate)
			{
				$query->addFilter('>=CREATED_DATE', $periodStartDate);
				$query->addFilter('<=CREATED_DATE', $periodEndDate);
			}
			else
			{
				$query->addFilter('=CREATED_DATE', $periodStartDate);
			}
		}
		else
		{
			if($periodStartDate !== null)
			{
				$query->addFilter('>=CREATED_DATE', $periodStartDate);
			}

			if($periodEndDate !== null)
			{
				$query->addFilter('<=CREATED_DATE', $periodEndDate);
			}
		}
		//endregion

		if($channelTypeID !== ChannelType::UNDEFINED)
		{
			$query->addFilter('=CHANNEL_TYPE_ID', $channelTypeID);
		}

		if($channelOriginID !== '')
		{
			$query->addFilter('=CHANNEL_ORIGIN_ID', $channelOriginID);
		}

		if($channelComponentID !== '')
		{
			$query->addFilter('=CHANNEL_COMPONENT_ID', $channelComponentID);
		}

		if ($direction !== \CCrmActivityDirection::Undefined)
		{
			$query->addFilter('=DIRECTION', $direction);
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
					if($sortName === 'COUNT')
					{
						continue;
					}

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
		if (in_array(self::GROUP_BY_DATE, $group))
		{
			$query->addSelect('CREATED_DATE');
			$query->addGroup('CREATED_DATE');
			if(!$sort)
			{
				$query->addOrder('CREATED_DATE', 'ASC');
			}
		}
		if (in_array(self::GROUP_BY_CHANNEL, $group))
		{
			$query->addSelect('CHANNEL_TYPE_ID');
			$query->addGroup('CHANNEL_TYPE_ID');

			$query->addSelect('CHANNEL_ORIGIN_ID');
			$query->addGroup('CHANNEL_ORIGIN_ID');

			$query->addSelect('CHANNEL_COMPONENT_ID');
			$query->addGroup('CHANNEL_COMPONENT_ID');
		}
		if (in_array(self::GROUP_BY_DIRECTION, $group))
		{
			$query->addSelect('DIRECTION');
			$query->addGroup('DIRECTION');
		}

		$dbResult = $query->exec();

		$result = array();
		$useAlias = $nameAlias !== $name;

		$userIDs = array();

		while($ary = $dbResult->fetch())
		{
			if ($useAlias && isset($ary[$nameAlias]))
			{
				$ary[$name] = $ary[$nameAlias];
				unset($ary[$nameAlias]);
			}

			if (in_array(self::GROUP_BY_DATE, $group))
			{
				$ary['DATE'] = $ary['CREATED_DATE']->format('Y-m-d');
				unset($ary['CREATED_DATE']);
				if ($ary['DATE'] === '9999-12-31')
				{
					//Skip empty dates
					continue;
				}
			}
			if (in_array(self::GROUP_BY_USER, $group))
			{
				$ary['RESPONSIBLE_ID'] = (int)$ary['RESPONSIBLE_ID'];
			}
			$curChannelTypeID = isset($ary['CHANNEL_TYPE_ID']) ? (int)$ary['CHANNEL_TYPE_ID'] : 0;
			$channelParams = array();
			if (in_array(self::GROUP_BY_CHANNEL, $group))
			{
				if (isset($ary['CHANNEL_ORIGIN_ID']) && $ary['CHANNEL_ORIGIN_ID'] !== '')
				{
					$channelParams['ORIGIN_ID'] = $ary['CHANNEL_ORIGIN_ID'];
				}
				if (isset($ary['CHANNEL_COMPONENT_ID']) && $ary['CHANNEL_COMPONENT_ID'] !== '')
				{
					$channelParams['COMPONENT_ID'] = $ary['CHANNEL_COMPONENT_ID'];
				}

				$ary['CHANNEL'] = ChannelTrackerManager::prepareChannelCaption($curChannelTypeID, $channelParams);
				$ary['CHANNEL_ID'] = ChannelTrackerManager::prepareChannelKey($curChannelTypeID, $channelParams);
			}

			if ($enableGroupKey === self::GROUP_BY_DATE)
			{
				$result[$ary['DATE']] = $ary;
			}
			else if ($enableGroupKey === self::GROUP_BY_USER)
			{
				$result[$ary['RESPONSIBLE_ID']] = $ary;
			}
			elseif ($enableGroupKey === self::GROUP_BY_CHANNEL)
			{
				$result[$ary['CHANNEL_ID']] = $ary;
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
		self::includeModuleFile();
		$result = array(
			array(
				'entity' => \CCrmOwnerType::ActivityName,
				'title' => GetMessage('CRM_ACTIVITY_CHANNEL_STAT_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY,
				'category' => self::CATEGORY,
				'grouping' => array('extras' => array(self::GROUP_BY_CHANNEL))
			)
		);
		return $result;
	}
	/**
	 * @param array $categories
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		if(isset($categories['CHANNEL_TRACKER']))
		{
			return;
		}

		self::includeModuleFile();
		$categories['CHANNEL_TRACKER'] = array(
			'entity' => \CCrmOwnerType::ActivityName,
			'title' => GetMessage('CRM_ACTIVITY_CHANNEL_TRACKER_CATEGORY'),
			'name' => self::CATEGORY,
			'enableSemantics' => false
		);
	}
	/**
	 * @param array $groupings
	 * @return void|array Array of arrays
	 */
	public static function prepareGroupingExtras(array &$groupings)
	{
		$sourceKey = \CCrmOwnerType::ActivityName.':'.self::GROUP_BY_CHANNEL;
		if(isset($groupings[$sourceKey]))
		{
			return;
		}

		self::includeModuleFile();
		$groupings[$sourceKey] = array(
			'entity' => \CCrmOwnerType::ActivityName,
			'title' => GetMessage('CRM_ACTIVITY_CHANNEL_STAT_GROUP_BY_CHANNEL'),
			'name' => self::GROUP_BY_CHANNEL
		);
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(ActivityChannelStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query->addFilter('>=CREATED_DATE', $periodStartDate);
		$query->addFilter('<=CREATED_DATE', $periodEndDate);

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$channelTypeID = $filter->getExtraParam('channelTypeID', ChannelType::UNDEFINED);
		if($channelTypeID !== ChannelType::UNDEFINED)
		{
			$query->addFilter('=CHANNEL_TYPE_ID', $channelTypeID);
		}

		$channelOriginID = $filter->getExtraParam('channelOriginID', '');
		if($channelOriginID !== '')
		{
			$query->addFilter('=CHANNEL_ORIGIN_ID', $channelOriginID);
		}

		$channelComponentID = $filter->getExtraParam('channelComponentID', '');
		if($channelComponentID !== '')
		{
			$query->addFilter('=CHANNEL_COMPONENT_ID', $channelComponentID);
		}

		return array(
			'__JOINS' => array(
				array(
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN('.$query->getQuery().') DS ON DS.OWNER_ID = A.ID'
				)
			)
		);
	}
	/**
	 * @return array
	 */
	public function initializeDemoData(array $data, array $params)
	{
		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group === self::GROUP_BY_CHANNEL)
		{
			$identityField = isset($data['identityField']) && $data['identityField'] !== ''
				? $data['identityField'] : 'CHANNEL_TYPE_ID';

			$titleField = isset($data['titleField']) && $data['titleField'] !== ''
				? $data['titleField'] : 'CHANNEL';

			foreach($data['items'] as &$item)
			{
				$typeID = isset($item[$identityField]) ? (int)$item[$identityField] : ChannelType::UNDEFINED ;
				if(ChannelType::isDefined($typeID))
				{
					$item[$titleField] = ChannelTrackerManager::prepareChannelCaption($typeID);
				}
			}
			unset($item);
		}
		return $data;
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
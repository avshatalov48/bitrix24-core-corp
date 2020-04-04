<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Integration\Channel\ChannelType;
use Bitrix\Crm\Integration\Channel\ChannelTrackerManager;
use Bitrix\Crm\Statistics\Entity\LeadChannelStatisticsTable;

class LeadChannelStatistics extends LeadDataSource
{
	const TYPE_NAME = 'LEAD_CHANNEL_STATS';
	const GROUP_BY_USER = 'USER';
	const GROUP_BY_DATE = 'DATE';
	const GROUP_BY_CHANNEL = 'CHANNEL';

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

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		$channelTypeID = $filter->getExtraParam('channelTypeID', ChannelType::UNDEFINED);
		$channelOriginID = $filter->getExtraParam('channelOriginID', '');
		$channelComponentID = $filter->getExtraParam('channelComponentID', '');

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== ''
			&& $group !== self::GROUP_BY_USER
			&& $group !== self::GROUP_BY_DATE
			&& $group !== self::GROUP_BY_CHANNEL)
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

		$query = new Query(LeadChannelStatisticsTable::getEntity());

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

		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$query->addFilter('=STATUS_SEMANTIC_ID', $semanticID);
		}

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
			elseif($group === self::GROUP_BY_DATE)
			{
				$query->addSelect('CREATED_DATE');
				$query->addGroup('CREATED_DATE');
				if(!$sort)
				{
					$query->addOrder('CREATED_DATE', 'ASC');
				}
			}
			elseif($group === self::GROUP_BY_CHANNEL)
			{
				$query->addSelect('CHANNEL_TYPE_ID');
				$query->addGroup('CHANNEL_TYPE_ID');

				$query->addSelect('CHANNEL_ORIGIN_ID');
				$query->addGroup('CHANNEL_ORIGIN_ID');

				$query->addSelect('CHANNEL_COMPONENT_ID');
				$query->addGroup('CHANNEL_COMPONENT_ID');
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

				$ary['DATE'] = $ary['CREATED_DATE']->format('Y-m-d');
				unset($ary['CREATED_DATE']);

				if($ary['DATE'] === '9999-12-31')
				{
					//Skip empty dates
					continue;
				}

				if($enableGroupKey)
				{
					$result[$ary['DATE']] = $ary;
				}
				else
				{
					$result[] = $ary;
				}
			}
		}
		elseif($group === self::GROUP_BY_USER)
		{
			$rawResult = array();
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
				$rawResult[] = $ary;
			}
			$userNames = self::prepareUserNames(array_keys($userIDs));
			if($enableGroupKey)
			{
				foreach($rawResult as $item)
				{
					$userID = $item['RESPONSIBLE_ID'];
					$item['USER_ID'] = $userID;
					$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
					unset($item['RESPONSIBLE_ID']);

					$result[$userID] = $item;
				}
			}
			else
			{
				foreach($rawResult as $item)
				{
					$userID = $item['RESPONSIBLE_ID'];
					$item['USER_ID'] = $userID;
					$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
					unset($item['RESPONSIBLE_ID']);

					$result[] = $item;
				}
			}
		}
		elseif($group === self::GROUP_BY_CHANNEL)
		{
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

				$curChannelTypeID = isset($ary['CHANNEL_TYPE_ID']) ? (int)$ary['CHANNEL_TYPE_ID'] : 0;
				$channelParams = array();
				if(isset($ary['CHANNEL_ORIGIN_ID']) && $ary['CHANNEL_ORIGIN_ID'] !== '')
				{
					$channelParams['ORIGIN_ID'] = $ary['CHANNEL_ORIGIN_ID'];
				}
				if(isset($ary['CHANNEL_COMPONENT_ID']) && $ary['CHANNEL_COMPONENT_ID'] !== '')
				{
					$channelParams['COMPONENT_ID'] = $ary['CHANNEL_COMPONENT_ID'];
				}

				$ary['CHANNEL'] = ChannelTrackerManager::prepareChannelCaption($curChannelTypeID, $channelParams);
				if($enableGroupKey)
				{
					$result[ChannelTrackerManager::prepareChannelKey($curChannelTypeID, $channelParams)] = $ary;
				}
				else
				{
					$result[] = $ary;
				}
			}
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
		self::includeModuleFile();
		$result = array(
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_CHANNEL_STAT_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY,
				'grouping' => array('extras' => array(self::GROUP_BY_CHANNEL))
			)
		);
		return $result;
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(LeadChannelStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		if($periodStartDate !== null)
		{
			$query->addFilter('>=CREATED_DATE', $periodStartDate);
		}

		if($periodEndDate !== null)
		{
			$query->addFilter('<=CREATED_DATE', $periodEndDate);
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$query->addFilter('=STATUS_SEMANTIC_ID', $semanticID);
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
					'SQL' => 'INNER JOIN('.$query->getQuery().') DS ON DS.OWNER_ID = L.ID'
				)
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
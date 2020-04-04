<?php
namespace Bitrix\Crm\Widget\Data\Activity;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Activity\StatisticsMark;
use Bitrix\Crm\Widget\Data\DataContext;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Statistics\Entity\ActivityStatisticsTable;

class MarkStatistics extends DataSource
{
	const TYPE_NAME = 'ACTIVITY_MARK_STATS';
	const GROUP_BY_USER = 'USER';
	const GROUP_BY_DATE = 'DATE';
	const GROUP_BY_MARK = 'MARK';
	const GROUP_BY_SOURCE = 'SOURCE';
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

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' 
			&& $group !== self::GROUP_BY_USER 
			&& $group !== self::GROUP_BY_DATE 
			&& $group !== self::GROUP_BY_MARK
			&& $group !== self::GROUP_BY_SOURCE
		)
		{
			$group = '';
		}
		$enableGroupKey = isset($params['enableGroupKey']) ? (bool)$params['enableGroupKey'] : false;

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
			$name = 'TOTAL';
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

		$query = new Query(ActivityStatisticsTable::getEntity());

		switch ($name)
		{
			case 'NONE_QTY':
				$query->addFilter('=MARK_ID', StatisticsMark::None);
				break;
			case 'POSITIVE_QTY':
				$query->addFilter('=MARK_ID', StatisticsMark::Positive);
				break;
			case 'NEGATIVE_QTY':
				$query->addFilter('=MARK_ID', StatisticsMark::Negative);
				break;
		}

		$nameAlias = 'CNT';
		$query->registerRuntimeField('', new ExpressionField($nameAlias, "COUNT(*)"));
		$query->addSelect($nameAlias);

		$query->addFilter('>=DEADLINE_DATE', $periodStartDate);
		$query->addFilter('<=DEADLINE_DATE', $periodEndDate);
		$query->addFilter('=COMPLETED', 'Y');

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

		if($group !== '')
		{
			if($group === self::GROUP_BY_USER)
			{
				$query->addSelect('RESPONSIBLE_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}
			elseif($group === self::GROUP_BY_MARK)
			{
				$query->addSelect('MARK_ID');
				$query->addGroup('MARK_ID');
			}
			elseif($group === self::GROUP_BY_SOURCE)
			{
				$query->addSelect('SOURCE_ID');
				$query->addGroup('SOURCE_ID');
			}
			else //if($groupBy === self::GROUP_BY_DATE)
			{
				$query->addSelect('DEADLINE_DATE');
				$query->addGroup('DEADLINE_DATE');
				$query->addOrder('DEADLINE_DATE', 'ASC');
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
		elseif($group === self::GROUP_BY_MARK)
		{
			$marks = StatisticsMark::getDescriptions();
			
			while($ary = $dbResult->fetch())
			{
				$resultAry = array(
					'MARK' => isset($marks[$ary['MARK_ID']]) ? $marks[$ary['MARK_ID']] : '-',
					$name => (int)$ary['CNT']
				);

				if($enableGroupKey)
				{
					$result[(int)$ary['MARK_ID']] = $resultAry;
				}
				else
				{
					$result[] = $resultAry;
				}
			}
		}
		elseif($group === self::GROUP_BY_SOURCE)
		{
			$sourceList = array();
			if ($providerId && $provider = \CCrmActivity::GetProviderById($providerId))
			{
				$sourceList = $provider::getResultSources();
			}

			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

				$sourceID = isset($ary['SOURCE_ID']) ? $ary['SOURCE_ID'] : '';
				if($sourceID === '')
				{
					$ary['SOURCE'] = '-';
				}
				else
				{
					$ary['SOURCE'] = isset($sourceList[$sourceID]) ? $sourceList[$sourceID] : "[{$sourceID}]";
				}

				if($enableGroupKey)
				{
					$result[$sourceID] = $ary;
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
	 * @return string DataContext constant
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
		$categories = static::getProviderCategories(CommunicationStatistics::STATISTICS_MARKS);

		foreach ($categories as $categoryId => $presetPrefix)
		{
			$result[] =	array(
				'entity' => \CCrmOwnerType::ActivityName,
				'title' => GetMessage('CRM_ACTIVITY_ACTIVITY_MARK_STAT_TOTAL'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':TOTAL',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'TOTAL',
					'aggregate' => 'COUNT'
				),
				'context' => DataContext::ENTITY,
				'category' => $categoryId,
				'grouping' => array('extras' => array(self::GROUP_BY_SOURCE, self::GROUP_BY_MARK))
			);
			$result[] =	array(
				'entity' => \CCrmOwnerType::ActivityName,
				'title' => GetMessage('CRM_ACTIVITY_ACTIVITY_MARK_STAT_NONE_QTY'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':NONE_QTY',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'NONE_QTY',
					'aggregate' => 'COUNT'
				),
				'context' => DataContext::ENTITY,
				'category' => $categoryId,
				'grouping' => array('extras' => array(self::GROUP_BY_SOURCE, self::GROUP_BY_MARK))
			);
			$result[] =	array(
				'entity' => \CCrmOwnerType::ActivityName,
				'title' => GetMessage('CRM_ACTIVITY_ACTIVITY_MARK_STAT_NEGATIVE_QTY'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':NEGATIVE_QTY',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'NEGATIVE_QTY',
					'aggregate' => 'COUNT'
				),
				'context' => DataContext::ENTITY,
				'category' => $categoryId,
				'grouping' => array('extras' => array(self::GROUP_BY_SOURCE, self::GROUP_BY_MARK))
			);
			$result[] =	array(
				'entity' => \CCrmOwnerType::ActivityName,
				'title' => GetMessage('CRM_ACTIVITY_ACTIVITY_MARK_STAT_POSITIVE_QTY'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':POSITIVE_QTY',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'POSITIVE_QTY',
					'aggregate' => 'COUNT'
				),
				'context' => DataContext::ENTITY,
				'category' => $categoryId,
				'grouping' => array('extras' => array(self::GROUP_BY_SOURCE, self::GROUP_BY_MARK))
			);
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @param array $params
	 * @return array
	 */
	public function initializeDemoData(array $data, array $params)
	{
		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group === self::GROUP_BY_MARK)
		{
			$marks = StatisticsMark::getDescriptions();

			$identityField = isset($data['identityField']) && $data['identityField'] !== ''
				? $data['identityField'] : 'MARK_ID';

			$titleField = isset($data['titleField']) && $data['titleField'] !== ''
				? $data['titleField'] : 'MARK';

			foreach($data['items'] as $k => $item)
			{
				$markId = isset($item[$identityField]) ? $item[$identityField] : '';
				if($markId !== '' && isset($marks[$markId]))
				{
					$data['items'][$k][$titleField] = $marks[$markId];
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
		$sourceKey = \CCrmOwnerType::ActivityName.':'.self::GROUP_BY_SOURCE;
		if(isset($groupings[$sourceKey]))
		{
			return;
		}

		self::includeModuleFile();
		$groupings[$sourceKey] = array(
			'entity' => \CCrmOwnerType::ActivityName,
			'title' => GetMessage('CRM_ACTIVITY_ACTIVITY_MARK_STAT_GROUP_BY_SOURCE'),
			'name' => self::GROUP_BY_SOURCE
		);
		$sourceKey = \CCrmOwnerType::ActivityName.':'.self::GROUP_BY_MARK;
		if(isset($groupings[$sourceKey]))
		{
			return;
		}

		self::includeModuleFile();
		$groupings[$sourceKey] = array(
			'entity' => \CCrmOwnerType::ActivityName,
			'title' => GetMessage('CRM_ACTIVITY_ACTIVITY_MARK_STAT_GROUP_BY_MARK'),
			'name' => self::GROUP_BY_MARK
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
<?php
namespace Bitrix\Crm\Widget\Data\Contact;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Activity\StatisticsStatus;
use Bitrix\Crm\Widget\Data\DataContext;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Statistics\Entity\ContactActivityStatusStatisticsTable;

class ActivityStatusStatistics extends DataSource
{
	const TYPE_NAME = 'CONTACT_ACTIVITY_STATUS_STATS';
	const GROUP_BY_USER = 'USER';
	const GROUP_BY_DATE = 'DATE';
	const GROUP_BY_STATUS = 'STATUS';
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
		if($group !== '' && $group !== self::GROUP_BY_USER && $group !== self::GROUP_BY_DATE && $group !== self::GROUP_BY_STATUS)
		{
			$group = '';
		}
		$enableGroupKey = isset($params['enableGroupKey']) ? (bool)$params['enableGroupKey'] : false;

		/** @var array $select */
		$select = isset($params['select']) && is_array($params['select']) ? $params['select'] : array();
		$name = '';
		$aggregate = 'SUM';
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

		$query = new Query(ContactActivityStatusStatisticsTable::getEntity());

		$nameAlias = $name;
		if($group !== static::GROUP_BY_STATUS)
		{
			$nameAlias = "{$nameAlias}_R";
			$query->registerRuntimeField('', new ExpressionField($nameAlias, "{$aggregate}(%s)", $name));
			$query->addSelect($nameAlias);
		}
		else
		{
			$query->registerRuntimeField('', new ExpressionField('STATUS_1', "{$aggregate}(%s)", 'UNANSWERED_QTY'));
			$query->registerRuntimeField('', new ExpressionField('STATUS_2', "{$aggregate}(%s)", 'ANSWERED_QTY'));
		}

		$query->addFilter('>=DEADLINE_DATE', $periodStartDate);
		$query->addFilter('<=DEADLINE_DATE', $periodEndDate);

		if ($providerId)
			$query->addFilter('=PROVIDER_ID', $providerId);
		if ($providerTypeId)
			$query->addFilter('=PROVIDER_TYPE_ID', $providerTypeId);

		if (
			$filter->getContextEntityTypeName() === \CCrmOwnerType::ContactName
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

		if($group !== '')
		{
			if($group === self::GROUP_BY_USER)
			{
				$query->addSelect('RESPONSIBLE_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}
			elseif($group === self::GROUP_BY_STATUS)
			{
				$query->addSelect('STATUS_1');
				$query->addSelect('STATUS_2');
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
		elseif($group === self::GROUP_BY_STATUS)
		{
			$statuses = StatisticsStatus::getDescriptions();
			
			while($ary = $dbResult->fetch())
			{
				foreach($statuses as $status => $streamDescription)
				{
					$resultAry = array(
						'STATUS' => $streamDescription,
						$name => (int)$ary['STATUS_'.$status]
					);

					if($enableGroupKey)
					{
						$result[$status] = $resultAry;
					}
					else
					{
						$result[] = $resultAry;
					}
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
		$categories = static::getProviderCategories(CommunicationStatistics::STATISTICS_STATUSES);

		foreach ($categories as $categoryId => $presetPrefix)
		{
			$result[] =	array(
				'entity' => \CCrmOwnerType::ContactName,
				'title' => GetMessage('CRM_CONTACT_ACTIVITY_STATUS_STAT_TOTAL'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':TOTAL',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'TOTAL',
					'aggregate' => 'SUM'
				),
				'context' => DataContext::ENTITY,
				'category' => $categoryId,
				'grouping' => array('extras' => array(self::GROUP_BY_STATUS))
			);
			$result[] =	array(
				'entity' => \CCrmOwnerType::ContactName,
				'title' => GetMessage('CRM_CONTACT_ACTIVITY_STATUS_STAT_UNANSWERED_QTY'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':UNANSWERED_QTY',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'UNANSWERED_QTY',
					'aggregate' => 'SUM'
				),
				'context' => DataContext::ENTITY,
				'category' => $categoryId,
				'grouping' => array('extras' => array(self::GROUP_BY_STATUS))
			);
			$result[] =	array(
				'entity' => \CCrmOwnerType::ContactName,
				'title' => GetMessage('CRM_CONTACT_ACTIVITY_STATUS_STAT_ANSWERED_QTY'),
				'name' => self::TYPE_NAME.'::'.$presetPrefix.':ANSWERED_QTY',
				'source' => self::TYPE_NAME,
				'select' => array(
					'name' => 'ANSWERED_QTY',
					'aggregate' => 'SUM'
				),
				'context' => DataContext::ENTITY,
				'category' => $categoryId,
				'grouping' => array('extras' => array(self::GROUP_BY_STATUS))
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
		if($group === self::GROUP_BY_STATUS)
		{
			$streams = StatisticsStatus::getDescriptions();

			$identityField = isset($data['identityField']) && $data['identityField'] !== ''
				? $data['identityField'] : 'STATUS_ID';

			$titleField = isset($data['titleField']) && $data['titleField'] !== ''
				? $data['titleField'] : 'STATUS';

			foreach($data['items'] as $k => $item)
			{
				$statusId = isset($item[$identityField]) ? $item[$identityField] : '';
				if($statusId !== '' && isset($streams[$statusId]))
				{
					$data['items'][$k][$titleField] = $streams[$statusId];
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
		$sourceKey = \CCrmOwnerType::ContactName.':'.self::GROUP_BY_STATUS;
		if(isset($groupings[$sourceKey]))
		{
			return;
		}

		self::includeModuleFile();
		$groupings[$sourceKey] = array(
			'entity' => \CCrmOwnerType::ContactName,
			'title' => GetMessage('CRM_CONTACT_ACTIVITY_STATUS_STAT_GROUP_BY_STATUS'),
			'name' => self::GROUP_BY_STATUS
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
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
use Bitrix\Crm\Statistics\Entity\LeadActivityStatisticsTable;

class LeadActivityStatistics extends LeadDataSource
{
	const TYPE_NAME = 'LEAD_ACTIVITY_STATS';
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

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
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
				$aggregate = strtoupper($selectItem['aggregate']);
			}
		}

		if($name === '')
		{
			$name = 'CALL_QTY';
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

		$query = new Query(LeadActivityStatisticsTable::getEntity());

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

		$newOnly = $filter->getExtraParam('newOnly', 'N');
		if($newOnly === 'Y')
		{
			$query->addFilter('>=CREATED_DATE', $periodStartDate);
			$query->addFilter('<=CREATED_DATE', $periodEndDate);
		}

		$query->addFilter('>=DEADLINE_DATE', $periodStartDate);
		$query->addFilter('<=DEADLINE_DATE', $periodEndDate);

		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$query->addFilter('=STATUS_SEMANTIC_ID', $semanticID);
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
		self::includeModuleFile();
		return array(
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_ACT_STAT_PRESET_CALL_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::CALL_OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'CALL_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY
			),
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_ACT_STAT_PRESET_MEETING_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::MEETING_OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'MEETING_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY
			),
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_ACT_STAT_PRESET_EMAIL_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::EMAIL_OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'EMAIL_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY
			),
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_ACT_STAT_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'TOTAL', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY
			),
		);
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(LeadActivityStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query->addFilter('>=DEADLINE_DATE', $periodStartDate);
		$query->addFilter('<=DEADLINE_DATE', $periodEndDate);

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

		$field = isset($filterParams['FIELD']) ? $filterParams['FIELD'] : '';
		if($field === 'CALL_QTY' || $field === 'MEETING_QTY' || $field === 'EMAIL_QTY')
		{
			$query->addFilter(">{$field}", 0);
		}
		elseif($field === 'TOTAL')
		{
			$query->registerRuntimeField(null, new ExpressionField('TOTAL', '(%s + %s + %s)', array('CALL_QTY', 'MEETING_QTY', 'EMAIL_QTY')));
			$query->addFilter('>TOTAL', 0);
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
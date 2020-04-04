<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Statistics\Entity\LeadConversionStatisticsTable;

class LeadConversionStatistics extends LeadDataSource
{
	const TYPE_NAME = 'LEAD_CONV_STATS';
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
			$name = 'TOTALS.SUM_TOTAL';
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

		$query = new Query(LeadConversionStatisticsTable::getEntity());
		$query->setTableAliasPostfix('_s2');

		$nameAlias = $name;
		if($aggregate !== '')
		{
			if($aggregate === 'COUNT')
			{
				$query->registerRuntimeField('', new ExpressionField($nameAlias, "COUNT(*)"));
			}
			else
			{
				$nameAlias = str_replace('.', '_', $name).'_R';
				$query->registerRuntimeField('', new ExpressionField($nameAlias, "{$aggregate}(%s)", $name));
			}
		}

		$query->addSelect($nameAlias);

		$subQuery = new Query(LeadConversionStatisticsTable::getEntity());
		$subQuery->setTableAliasPostfix('_s1');

		$subQuery->addSelect('OWNER_ID');

		$subQuery->addFilter('>=ENTRY_DATE', $periodStartDate);
		$subQuery->addFilter('<=ENTRY_DATE', $periodEndDate);

		$newOnly = $filter->getExtraParam('newOnly', 'N');
		if($newOnly === 'Y')
		{
			$subQuery->addFilter('>=CREATED_DATE', $periodStartDate);
			$subQuery->addFilter('<=CREATED_DATE', $periodEndDate);
		}

		if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
		{
			$subQuery->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$subQuery->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$subQuery->addGroup('OWNER_ID');

		$subQuery->addSelect('MAX_ENTRY_DATE');
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_ENTRY_DATE', 'MAX(%s)', 'ENTRY_DATE'));

		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => 'ref.OWNER_ID', '=this.ENTRY_DATE' => 'ref.MAX_ENTRY_DATE'),
				array('join_type' => 'INNER')
			)
		);

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
			else //if($group === self::GROUP_BY_DATE)
			{
				$query->addSelect('ENTRY_DATE');
				$query->addGroup('ENTRY_DATE');
				if(!$sort)
				{
					$query->addOrder('ENTRY_DATE', 'ASC');
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

				$ary['DATE'] = $ary['ENTRY_DATE']->format('Y-m-d');
				unset($ary['ENTRY_DATE']);

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
			foreach($rawResult as $item)
			{
				$userID = $item['RESPONSIBLE_ID'];
				$item['USER_ID'] = $userID;
				$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
				unset($item['RESPONSIBLE_ID']);

				$result[] = $item;
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
		return $this->getPresetName() === 'OVERALL_SUM' ? DataContext::FUND : DataContext::ENTITY;
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
				'title' => GetMessage('CRM_LEAD_CONV_STAT_PRESET_OVERALL_COUNT'),
				'listTitle' => GetMessage('CRM_LEAD_CONV_STAT_PRESET_OVERALL_COUNT_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY,
				'category' => 'CONVERTED'
			),
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_CONV_STAT_PRESET_CONTACT_COUNT'),
				'name' => self::TYPE_NAME.'::CONTACT_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'CONTACT_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY,
				'category' => 'CONVERTED'
			),
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_CONV_STAT_PRESET_COMPANY_COUNT'),
				'name' => self::TYPE_NAME.'::COMPANY_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COMPANY_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY,
				'category' => 'CONVERTED'
			),
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_CONV_STAT_PRESET_DEAL_COUNT'),
				'name' => self::TYPE_NAME.'::DEAL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'DEAL_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY,
				'category' => 'CONVERTED'
			),
			array(
				'entity' => \CCrmOwnerType::LeadName,
				'title' => GetMessage('CRM_LEAD_CONV_STAT_PRESET_OVERALL_SUM'),
				'listTitle' => GetMessage('CRM_LEAD_CONV_STAT_PRESET_OVERALL_SUM_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'TOTALS.SUM_TOTAL', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND,
				'category' => 'CONVERTED'
			)
		);

		return $result;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		if(isset($categories['CONVERTED']))
		{
			return;
		}

		self::includeModuleFile();
		$categories['CONVERTED'] = array(
			'entity' => \CCrmOwnerType::LeadName,
			'title' => GetMessage('CRM_LEAD_CONV_CATEGORY'),
			'name' => 'CONVERTED',
			'enableSemantics' => false
		);
	}

	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(LeadConversionStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query->addFilter('>=ENTRY_DATE', $periodStartDate);
		$query->addFilter('<=ENTRY_DATE', $periodEndDate);

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		return array(
			'__JOINS' => array(
				array(
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN('.$query->getQuery().') LS ON LS.OWNER_ID = L.ID'
				)
			)
		);
	}
	/**
	 * @return string
	 */
	public function getDetailsPageUrl(array $params)
	{
		static $ignoredFields = array('CONTACT_QTY' => true, 'COMPANY_QTY' => true, 'DEAL_QTY' => true);
		/** @var string $field */
		$field = isset($params['field']) ? $params['field'] : '';
		return isset($ignoredFields[$field]) ? '' : parent::getDetailsPageUrl($params);
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
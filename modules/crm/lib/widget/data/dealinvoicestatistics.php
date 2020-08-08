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
use Bitrix\Crm\Statistics\Entity\DealInvoiceStatisticsTable;

class DealInvoiceStatistics extends DealDataSource
{
	const TYPE_NAME = 'DEAL_INVOICE_STATS';
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

		$this->applyFilterContext($filter);

		$categoryID = (int)$filter->getExtraParam('dealCategoryID', -1);
		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		$isFinalSemantics = PhaseSemantics::isFinal($semanticID);

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

		if($name === '')
		{
			$name = 'INVOICE_SUM';
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

		$query = new Query(DealInvoiceStatisticsTable::getEntity());
		$query->addSelect($name);

		if($aggregate !== '')
		{
			if($aggregate === 'COUNT')
			{
				$query->registerRuntimeField('', new ExpressionField($name, "COUNT(*)"));
			}
			else
			{
				$query->registerRuntimeField('', new ExpressionField($name, "{$aggregate}({$name})"));
			}
		}

		$query->setTableAliasPostfix('_s2');

		$subQuery = new Query(DealInvoiceStatisticsTable::getEntity());
		$subQuery->setTableAliasPostfix('_s1');
		$subQuery->addSelect('OWNER_ID');

		$subQuery->addFilter('>=END_DATE', $periodStartDate);
		$subQuery->addFilter('<=START_DATE', $periodEndDate);
		//$subQuery->addFilter('<=CREATED_DATE', $periodEndDate);

		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$subQuery->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
		}

		if($categoryID >= 0)
		{
			//HACK: use SqlExpression to avoid filter sql like (CATEGORY_ID IS NULL OR CATEGORY_ID = 0), that cause the filesort.
			$subQuery->addFilter('=CATEGORY_ID', new Main\DB\SqlExpression('?i', $categoryID));
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
		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(CREATED_DATE)'));

		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => 'ref.OWNER_ID', '=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'),
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
					$query->addOrder($sortItem['name'], isset($sortItem['order']) ? $sortItem['order'] : 'asc');
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
			else if($group === self::GROUP_BY_DATE)
			{
				if($isFinalSemantics)
				{
					$query->addSelect('END_DATE', 'D');
					$query->addGroup('END_DATE');
					if(!$sort)
					{
						$query->addOrder('END_DATE', 'ASC');
					}
				}
				else
				{
					$query->addSelect('CREATED_DATE', 'D');
					$query->addGroup('CREATED_DATE');
					if(!$sort)
					{
						$query->addOrder('CREATED_DATE', 'ASC');
					}
				}
			}
		}

		$dbResult = $query->exec();
		//Trace('sql', Query::getLastQuery(), 1);
		$result = array();
		if($group === self::GROUP_BY_DATE)
		{
			while($ary = $dbResult->fetch())
			{
				$ary['DATE'] = $ary['D']->format('Y-m-d');
				unset($ary['D']);

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
				$userID = $ary['RESPONSIBLE_ID'] = (int)$ary['RESPONSIBLE_ID'];
				if($userID > 0 && !isset($userNames[$userID]))
				{
					$userIDs[$userID] = true;
				}
				$result[] = $ary;
			}
			self::parseUserInfo($result, array('RESPONSIBLE_ID' => 'USER'));
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
		return $this->getPresetName() === 'OVERALL_COUNT' ? DataContext::ENTITY : DataContext::FUND;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		self::includeModuleFile();
		return array(
			array(
				'entity' => \CCrmOwnerType::DealName,
				'title' => GetMessage('CRM_DEAL_INVOICE_STAT_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'TOTAL_INVOICE_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY
			),
			array(
				'entity' => \CCrmOwnerType::DealName,
				'title' => GetMessage('CRM_DEAL_INVOICE_STAT_PRESET_OVERALL_SUM'),
				'name' => self::TYPE_NAME.'::OVERALL_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'TOTAL_INVOICE_SUM', 'aggregate' => 'SUM'),
				'context' => DataContext::FUND
			),
			array(
				'entity' => \CCrmOwnerType::DealName,
				'title' => GetMessage('CRM_DEAL_INVOICE_STAT_PRESET_OVERALL_OWED_SUM'),
				'name' => self::TYPE_NAME.'::OVERALL_OWED_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'TOTAL_OWED', 'aggregate' => 'SUM'),
				'context' => DataContext::FUND
			)
		);
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);

		$query = new Query(DealInvoiceStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->setTableAliasPostfix('_s2');

		$subQuery = new Query(DealInvoiceStatisticsTable::getEntity());
		$subQuery->setTableAliasPostfix('_s1');
		$subQuery->addSelect('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$subQuery->addFilter('>=END_DATE', $periodStartDate);
		$subQuery->addFilter('<=START_DATE', $periodEndDate);

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$subQuery->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$subQuery->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
		}

		$categoryID = (int)$filter->getExtraParam('dealCategoryID', -1);
		if($categoryID >= 0)
		{
			//HACK: use SqlExpression to avoid filter sql like (CATEGORY_ID IS NULL OR CATEGORY_ID = 0), that cause the filesort.
			$subQuery->addFilter('=CATEGORY_ID', new Main\DB\SqlExpression('?i', $categoryID));
		}

		$subQuery->addGroup('OWNER_ID');
		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(CREATED_DATE)'));

		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => 'ref.OWNER_ID', '=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'),
				array('join_type' => 'INNER')
			)
		);

		$field = isset($filterParams['FIELD']) ? $filterParams['FIELD'] : '';
		if($field === 'TOTAL_INVOICE_SUM' || $field === 'TOTAL_OWED')
		{
			//$query->addFilter("!={$field}", 0);
			$query->addFilter("!={$field}", new Main\DB\SqlExpression('?i', 0));
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
<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Statistics\Entity\InvoiceSumStatisticsTable;

class InvoiceOverdue extends InvoiceDataSource
{
	const TYPE_NAME = 'INVOICE_OVERDUE';
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
	/**
	 * @return array
	 */
	public function getList(array $params)
	{
		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		$group = isset($params['group'])? mb_strtoupper($params['group']) : '';
		if($group !== ''
			&& $group !== self::GROUP_BY_USER
			&& $group !== self::GROUP_BY_DATE)
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
				$aggregate = mb_strtoupper($selectItem['aggregate']);
			}
		}

		if($name === '')
		{
			$name = 'SUM_TOTAL';
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

		$query = new Query(InvoiceSumStatisticsTable::getEntity());
		$query->setTableAliasPostfix('_s1');

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

		$subQuery = new Query(InvoiceSumStatisticsTable::getEntity());
		$subQuery->setTableAliasPostfix('_s2');
		$subQuery->addSelect('OWNER_ID');

		//region Overdue condition
		$subQuery->addFilter('=STATUS_SEMANTIC_ID', PhaseSemantics::PROCESS);
		$subQuery->addFilter('>=PAY_BEFORE_DATE', $periodStartDate);
		$subQuery->addFilter('<=PAY_BEFORE_DATE', $periodEndDate);
		//endregion

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
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(%s)', 'CREATED_DATE'));

		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => 'ref.OWNER_ID', '=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'),
				array('join_type' => 'INNER')
			)
		);

		//region Overdue condition
		$query->addFilter('=STATUS_SEMANTIC_ID', PhaseSemantics::PROCESS);
		//endregion

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
				$query->addSelect('RESPONSIBLE_ID', 'USER_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}
			elseif($group === self::GROUP_BY_DATE)
			{
				$query->addSelect('PAY_BEFORE_DATE', 'DATE');
				$query->addGroup('PAY_BEFORE_DATE');
				if(!$sort)
				{
					$query->addOrder('PAY_BEFORE_DATE', 'ASC');
				}
			}
		}

		$dbResult = $query->exec();

		$results = array();
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

				/** @var Date $d */
				$d = $ary['DATE'];
				$ary['DATE'] = $d->format('Y-m-d');
				if($ary['DATE'] === '9999-12-31')
				{
					//Skip empty dates
					continue;
				}

				if($enableGroupKey)
				{
					$results[$ary['DATE']] = $ary;
				}
				else
				{
					$results[] = $ary;
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

				$userID = $ary['USER_ID'] = (int)$ary['USER_ID'];
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
					$userID = $item['USER_ID'];
					$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
					$results[$userID] = $item;
				}
			}
			else
			{
				foreach($rawResult as $item)
				{
					$userID = $item['USER_ID'];
					$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
					$results[] = $item;
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
				$results[] = $ary;
			}
		}

		return $results;
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
		$result = array(
			array(
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_OVERDUE_PRESET_OVERALL_COUNT'),
				'listTitle' => GetMessage('CRM_INVOICE_OVERDUE_PRESET_OVERALL_COUNT_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY,
				'category' => 'OVERDUE'
			),
			array(
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_OVERDUE_PRESET_OVERALL_SUM'),
				'listTitle' => GetMessage('CRM_INVOICE_OVERDUE_PRESET_OVERALL_SUM_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND,
				'category' => 'OVERDUE'
			)
		);

		return $result;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		if(isset($categories['INVOICE_OVERDUE']))
		{
			return;
		}

		self::includeModuleFile();
		$categories['INVOICE_OVERDUE'] = array(
			'entity' => \CCrmOwnerType::InvoiceName,
			'title' => GetMessage('CRM_INVOICE_OVERDUE_CATEGORY'),
			'name' => 'OVERDUE',
			'enableSemantics' => false
		);
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(InvoiceSumStatisticsTable::getEntity());
		$query->setTableAliasPostfix('_s1');
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$subQuery = new Query(InvoiceSumStatisticsTable::getEntity());
		$subQuery->setTableAliasPostfix('_s2');
		$subQuery->addSelect('OWNER_ID');

		//region Overdue condition
		$subQuery->addFilter('=STATUS_SEMANTIC_ID', PhaseSemantics::PROCESS);
		$subQuery->addFilter('>=PAY_BEFORE_DATE', $periodStartDate);
		$subQuery->addFilter('<=PAY_BEFORE_DATE', $periodEndDate);
		//endregion

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$subQuery->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}
		$subQuery->addGroup('OWNER_ID');

		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(%s)', 'CREATED_DATE'));

		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => 'ref.OWNER_ID', '=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'),
				array('join_type' => 'INNER')
			)
		);

		//region Overdue condition
		$query->addFilter('=STATUS_SEMANTIC_ID', PhaseSemantics::PROCESS);
		//endregion

		return array(
			'__CONDITIONS' => array(
				array(
					'SQL' => 'crm_invoice_internals_invoice.ID IN ('.$query->getQuery().')'
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
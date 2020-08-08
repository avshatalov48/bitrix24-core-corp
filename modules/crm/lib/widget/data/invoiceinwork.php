<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\History\Entity\InvoiceStatusHistoryTable;
use Bitrix\Crm\Statistics\Entity\InvoiceSumStatisticsTable;


class InvoiceInWork extends InvoiceDataSource
{
	const TYPE_NAME = 'INVOICE_IN_WORK';
	const GROUP_BY_DATE = 'DATE';
	const GROUP_BY_USER = 'USER';
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

		$semanticID = PhaseSemantics::UNDEFINED;
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

		if($aggregate !== '' && !in_array($aggregate, array('SUM', 'COUNT')))
		{
			throw new Main\NotSupportedException("Aggregate function '{$aggregate}' is not supported in current context");
		}

		if($name === '')
		{
			throw new Main\ArgumentException('Could not find column name in select params', 'params');
		}
		$group = isset($params['group'])? mb_strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_DATE && $group !== self::GROUP_BY_USER)
		{
			$group = '';
		}

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];
		$responsibleIDs = $filter->getResponsibleIDs();

		$nameAlias = $name;
		if($name === 'COUNT' || $name === 'COUNT_OWED')
		{
			if($name === 'COUNT_OWED')
			{
				$semanticID = PhaseSemantics::PROCESS;
				//Start period is not used in OWED category - we look backward to start
				$periodStartDate = null;
			}

			$query = self::prepareHistoryQuery($periodStartDate, $periodEndDate, $responsibleIDs, $semanticID);

			if($group === self::GROUP_BY_DATE)
			{
				$query->addSelect('ACTIVITY_DATE', 'DATE');
				$query->addGroup('ACTIVITY_DATE');
				$query->addOrder('ACTIVITY_DATE', 'ASC');
			}
			elseif($group === self::GROUP_BY_USER)
			{
				$query->addSelect('RESPONSIBLE_ID', 'USER_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}

			if($aggregate === 'COUNT')
			{
				$query->registerRuntimeField('', new ExpressionField($name, 'COUNT(1)'));
			}
			else
			{
				throw new Main\NotSupportedException();
			}

			$query->addSelect($name);

		}
		else if($name === 'SUM_TOTAL' || $name === 'SUM_OWED_TOTAL')
		{
			if($name === 'SUM_OWED_TOTAL')
			{
				$semanticID = PhaseSemantics::PROCESS;
				//Start period is not used in OWED category - we look backward to start
				$periodStartDate = null;
			}

			$query = new Query(InvoiceSumStatisticsTable::getEntity());
			$query->setTableAliasPostfix('_s1');

			$subQuery1 = new Query(InvoiceSumStatisticsTable::getEntity());
			$subQuery1->setTableAliasPostfix('_s2');
			$subQuery1->addSelect('OWNER_ID');
			$subQuery1->addGroup('OWNER_ID');
			$subQuery1->addFilter('<=CREATED_DATE', $periodEndDate);
			$subQuery1->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(%s)', 'CREATED_DATE'));
			$subQuery1->addSelect('MAX_CREATED_DATE');

			$query->registerRuntimeField(
				'',
				new ReferenceField(
					'S',
					Base::getInstanceByQuery($subQuery1),
					array(
						'=this.OWNER_ID' => 'ref.OWNER_ID',
						'=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'
					),
					array('join_type' => 'INNER')
				)
			);

			$subQuery2 = self::prepareHistoryQuery($periodStartDate, $periodEndDate, $responsibleIDs, $semanticID);
			$subQuery2->addSelect('OWNER_ID');
			if($group === self::GROUP_BY_DATE)
			{
				$subQuery2->addSelect('ACTIVITY_DATE');
			}

			if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
			{
				$subQuery2->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
			}

			$query->registerRuntimeField(
				'',
				new ReferenceField(
					'H',
					Base::getInstanceByQuery($subQuery2),
					array('=this.OWNER_ID' => 'ref.OWNER_ID'),
					array('join_type' => 'INNER')
				)
			);

			if($group === self::GROUP_BY_DATE)
			{
				$query->addSelect('H.ACTIVITY_DATE', 'DATE');
				$query->addGroup('H.ACTIVITY_DATE');
				$query->addOrder('H.ACTIVITY_DATE', 'ASC');
			}
			elseif($group === self::GROUP_BY_USER)
			{
				$query->addSelect('RESPONSIBLE_ID', 'USER_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}

			if($aggregate === 'SUM')
			{
				$nameAlias = "{$name}_R";
				$query->registerRuntimeField('', new ExpressionField($nameAlias, 'SUM(%s)', 'SUM_TOTAL'));
				$query->addSelect($nameAlias);
			}
			else
			{
				throw new Main\NotSupportedException();
			}
		}
		else
		{
			throw new Main\NotSupportedException();
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
				$s = $d->format('Y-m-d');
				if($s === '9999-12-31')
				{
					//Skip empty dates
					continue;
				}

				$ary['DATE'] = $s;
				$results[] = $ary;
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
			foreach($rawResult as $item)
			{
				$userID = $item['USER_ID'];
				$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
				$results[] = $item;
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

		if(isset($params['sort']) && is_array($params['sort']))
		{
			foreach($params['sort'] as $sortItem)
			{
				if(isset($sortItem['name']) && $sortItem['name'] === $name)
				{
					$order = isset($sortItem['order']) && mb_strtolower($sortItem['order']) === 'desc'
						? SORT_DESC : SORT_ASC;
					Collection::sortByColumn($results, array($name => $order));
					break;
				}
			}
		}

		return $results;
	}
	/**
	 * @return Query
	 */
	protected static function prepareHistoryQuery($startDate, $endDate, $responsibleIDs = null, $semanticID = '')
	{
		$query = new Query(InvoiceStatusHistoryTable::getEntity());
		$query->setTableAliasPostfix('_h1');

		$subQuery = new Query(InvoiceStatusHistoryTable::getEntity());
		$subQuery->setTableAliasPostfix('_h2');

		if($startDate !== null)
		{
			$subQuery->addFilter('>=ACTIVITY_DATE', $startDate);
		}

		if($endDate !== null)
		{
			$subQuery->addFilter('<=ACTIVITY_DATE', $endDate);
		}

		$subQuery->registerRuntimeField('', new ExpressionField('MAX_ID', 'MAX(%s)', 'ID'));
		$subQuery->addSelect('MAX_ID');
		$subQuery->addGroup('OWNER_ID');

		$query->registerRuntimeField(
			'',
			new ReferenceField(
				'HL',
				Base::getInstanceByQuery($subQuery),
				array('=this.ID' => 'ref.MAX_ID'),
				array('join_type' => 'INNER')
			)
		);

		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$query->addFilter('=STATUS_SEMANTIC_ID', $semanticID);
		}

		if(is_array($responsibleIDs) && !empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		return $query;
	}
	/**
	 * @return Query
	 */
	protected static function prepareSumStatisticsQuery($endDate)
	{
		$query = new Query(InvoiceSumStatisticsTable::getEntity());
		$query->setTableAliasPostfix('_s1');

		$query->addSelect('OWNER_ID');
		$query->addSelect('SUM_TOTAL');

		$subQuery = new Query(InvoiceSumStatisticsTable::getEntity());
		$subQuery->setTableAliasPostfix('_s2');
		$subQuery->addSelect('OWNER_ID');
		$subQuery->addGroup('OWNER_ID');
		$subQuery->addFilter('<=CREATED_DATE', $endDate);
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(%s)', 'CREATED_DATE'));
		$subQuery->addSelect('MAX_CREATED_DATE');

		$query->registerRuntimeField(
			'',
			new ReferenceField(
				'SL',
				Base::getInstanceByQuery($subQuery),
				array(
					'=this.OWNER_ID' => 'ref.OWNER_ID',
					'=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'
				),
				array('join_type' => 'INNER')
			)
		);

		return $query;
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];
		$responsibleIDs = $filter->getResponsibleIDs();

		$semanticID = PhaseSemantics::UNDEFINED;
		$fieldName = isset($filterParams['FIELD']) ? $filterParams['FIELD'] : '';
		if($fieldName === 'COUNT_OWED' || $fieldName === 'SUM_OWED_TOTAL')
		{
			$semanticID = PhaseSemantics::PROCESS;
			//Start period is not used in OWED category - we look backward to start
			$periodStartDate = null;
		}

		$query = self::prepareHistoryQuery($periodStartDate, $periodEndDate, $responsibleIDs, $semanticID);
		$query->addSelect('OWNER_ID');

		$sqlQuery = $query->getQuery();
		return array('__CONDITIONS' => array(array('SQL' => 'crm_invoice_internals_invoice.ID IN('.$sqlQuery.')')));
	}
	/**
	 * Get current data context
	 * @return DataContext
	 */
	public function getDataContext()
	{
		$name = $this->getPresetName();
		return ($name === 'OVERALL_COUNT' || $name === 'OVERALL_COUNT_OWED')
			? DataContext::ENTITY : DataContext::FUND;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		self::includeModuleFile();
		return array(
			array(
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_IN_WORK_PRESET_OVERALL_COUNT'),
				'listTitle' => GetMessage('CRM_INVOICE_IN_WORK_PRESET_OVERALL_COUNT_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY,
				'category' => 'IN_WORK'
			),
			array(
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_IN_WORK_PRESET_OVERALL_SUM'),
				'listTitle' => GetMessage('CRM_INVOICE_IN_WORK_PRESET_OVERALL_SUM_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND,
				'category' => 'IN_WORK'
			),
			array(
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_IN_WORK_PRESET_OVERALL_COUNT_OWED'),
				'listTitle' => GetMessage('CRM_INVOICE_IN_WORK_PRESET_OVERALL_COUNT_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT_OWED',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT_OWED', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY,
				'category' => 'OWED'
			),
			array(
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_IN_WORK_PRESET_OVERALL_SUM_OWED'),
				'listTitle' => GetMessage('CRM_INVOICE_IN_WORK_PRESET_OVERALL_SUM_SHORT'),
				'name' => self::TYPE_NAME.'::OVERALL_SUM_OWED',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'SUM_OWED_TOTAL', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND,
				'category' => 'OWED'
			)
		);
	}
	/**
	 * @return array Array of arrays
	 */
	public static function prepareCategories(array &$categories)
	{
		if(isset($categories['INVOICE_IN_WORK']) && isset($categories['INVOICE_OWED']))
		{
			return;
		}

		self::includeModuleFile();

		if(!isset($categories['INVOICE_IN_WORK']))
		{
			$categories['INVOICE_IN_WORK'] = array(
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_IN_WORK_CATEGORY'),
				'name' => 'IN_WORK',
				'enableSemantics' => false
			);
		}

		if(!isset($categories['INVOICE_OWED']))
		{
			$categories['INVOICE_OWED'] = array(
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_OWED_CATEGORY'),
				'name' => 'OWED',
				'enableSemantics' => false
			);
		}
	}
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
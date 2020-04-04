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
use Bitrix\Crm\Statistics\DealSumStatisticEntry;
use Bitrix\Crm\Statistics\Entity\DealSumStatisticsTable;
use Bitrix\Crm\Statistics\Entity\DealInvoiceStatisticsTable;

class DealSumStatistics extends DealDataSource
{
	const TYPE_NAME = 'DEAL_SUM_STATS';
	const GROUP_BY_USER = 'USER';
	const GROUP_BY_DATE = 'DATE';

	/** @var bool $messagesLoaded */
	private static $messagesLoaded = false;
	/**
	 * Get type name.
	 * @return string
	 */
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}
	/**
	 * Prepare item list according to income parameters.
	 * @param array $params Parameters.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\InvalidOperationException
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

		$this->applyFilterContext($filter);

		$categoryID = (int)$filter->getExtraParam('dealCategoryID', -1);
		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		$isFinalSemantics = PhaseSemantics::isFinal($semanticID);
		$hasInvoices = $filter->getExtraParam('hasInvoices', null);
		$isLost = $filter->getExtraParam('isLost', null);

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_USER && $group !== self::GROUP_BY_DATE)
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

		/*
		//Caching is temporary disabled
		$cacheID = serialize($params);
		$data = $this->getCacheData($cacheID, $filter);
		if ($data !== false)
		{
			return $data;
		}
		*/

		$query = new Query(DealSumStatisticsTable::getEntity());
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
		$query->setTableAliasPostfix('_s2');

		$subQuery = new Query(DealSumStatisticsTable::getEntity());
		$subQuery->setTableAliasPostfix('_s1');
		$subQuery->addSelect('OWNER_ID');

		if($isFinalSemantics)
		{
			$subQuery->addFilter('>=END_DATE', $periodStartDate);
			$subQuery->addFilter('<=END_DATE', $periodEndDate);
		}
		else
		{
			$subQuery->addFilter('>=END_DATE', $periodStartDate);
			$subQuery->addFilter('<=START_DATE', $periodEndDate);
		}

		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$subQuery->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
		}

		if($categoryID >= 0)
		{
			//HACK: use SqlExpression to avoid filter sql like (CATEGORY_ID IS NULL OR CATEGORY_ID = 0), that cause the filesort.
			$subQuery->addFilter('=CATEGORY_ID', new Main\DB\SqlExpression('?i', $categoryID));
		}

		if(is_bool($isLost))
		{
			$subQuery->addFilter('=IS_LOST', $isLost);
		}

		if(is_bool($hasInvoices))
		{
			$invoiceSubQuery = new Query(DealInvoiceStatisticsTable::getEntity());
			$invoiceSubQuery->addSelect('OWNER_ID');
			$invoiceSubQuery->addGroup('OWNER_ID');

			$invoiceSubQuery->addFilter('>=END_DATE', $periodStartDate);
			$invoiceSubQuery->addFilter('<=START_DATE', $periodEndDate);

			if($semanticID !== PhaseSemantics::UNDEFINED)
			{
				$invoiceSubQuery->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
			}

			if($isFinalSemantics)
			{
				$invoiceSubQuery->addFilter('<=END_DATE', $periodEndDate);
			}
			else
			{
				$invoiceSubQuery->addFilter('<=CREATED_DATE', $periodEndDate);
			}

			$subQuery->addFilter(
				$hasInvoices ? '@OWNER_ID' : '!@OWNER_ID',
				new SqlExpression($invoiceSubQuery->getQuery())
			);
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
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(%s)', 'CREATED_DATE'));

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
				$dateFieldName = $isFinalSemantics ? 'END_DATE' : 'CREATED_DATE';

				$query->addSelect($dateFieldName, 'DATE');
				$query->addGroup($dateFieldName);
				if(!$sort)
				{
					$query->addOrder($dateFieldName, 'ASC');
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

				$ary['DATE'] = $ary['DATE']->format('Y-m-d');
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
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);

				}
				$ary['RESPONSIBLE_ID'] = (int)$ary['RESPONSIBLE_ID'];
				if($enableGroupKey)
					$result[$ary['RESPONSIBLE_ID']] = $ary;
				else
					$result[] = $ary;
			}
			self::parseUserInfo($result, array('RESPONSIBLE_ID' => "USER"));
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

		//Caching is temporary disabled
		//$this->setCacheData($result);
		return $result;
	}
	/**
	 * Get current data context ID.
	 * @return string
	 */
	public function getDataContext()
	{
		return $this->getPresetName() === 'OVERALL_COUNT' ? DataContext::ENTITY : DataContext::FUND;
	}
	/**
	 * Prepare presets collection.
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		self::includeModuleFile();
		$result = array(
			array(
				'entity' => \CCrmOwnerType::DealName,
				'title' => GetMessage('CRM_DEAL_SUM_STAT_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY
			),
			array(
				'entity' => \CCrmOwnerType::DealName,
				'title' => GetMessage('CRM_DEAL_SUM_STAT_PRESET_OVERALL_SUM'),
				'name' => self::TYPE_NAME.'::OVERALL_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND
			)
		);

		$bindingInfos = DealSumStatisticEntry::getBindingInfos();
		foreach($bindingInfos as $bindingInfo)
		{
			$result[] = array(
				'entity' => \CCrmOwnerType::DealName,
				'title' => $bindingInfo['TITLE'],
				'name' => self::TYPE_NAME.'::'.$bindingInfo['SLOT_NAME'],
				'source' => self::TYPE_NAME,
				'select' => array('name' => $bindingInfo['SLOT_NAME'], 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND
			);
		}

		return $result;
	}
	/**
	 * Prepare filter for entity list.
	 * @param array $filterParams Income filter params.
	 * @return array
	 * @throws Main\InvalidOperationException
	 */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(DealSumStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		$isFinalSemantics = PhaseSemantics::isFinal($semanticID);

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		if($isFinalSemantics)
		{
			$query->addFilter('>=END_DATE', $periodStartDate);
			$query->addFilter('<=END_DATE', $periodEndDate);
		}
		else
		{
			$query->addFilter('>=END_DATE', $periodStartDate);
			$query->addFilter('<=START_DATE', $periodEndDate);
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$query->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
		}

		$categoryID = (int)$filter->getExtraParam('dealCategoryID', -1);
		if($categoryID >= 0)
		{
			//HACK: use SqlExpression to avoid filter sql like (CATEGORY_ID IS NULL OR CATEGORY_ID = 0), that cause the filesort.
			$query->addFilter('=CATEGORY_ID', new Main\DB\SqlExpression('?i', $categoryID));
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
	 * Include language file.
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
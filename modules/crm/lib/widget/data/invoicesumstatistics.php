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
use Bitrix\Crm\Statistics\InvoiceSumStatisticEntry;
use Bitrix\Crm\Statistics\Entity\InvoiceSumStatisticsTable;

class InvoiceSumStatistics extends InvoiceDataSource
{
	const TYPE_NAME = 'INVOICE_SUM_STATS';
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

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		$isJunk = $filter->getExtraParam('isJunk', null);

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

		$dateFieldName = 'CREATED_DATE';
		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$subQuery->addFilter('=STATUS_SEMANTIC_ID', $semanticID);
			if(PhaseSemantics::isFinal($semanticID))
			{
				$dateFieldName = 'CLOSED_DATE';
			}
		}

		$subQuery->addFilter(">={$dateFieldName}", $periodStartDate);
		$subQuery->addFilter("<={$dateFieldName}", $periodEndDate);

		if(is_bool($isJunk))
		{
			$subQuery->addFilter('=IS_JUNK', $isJunk);
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
				$query->addSelect('RESPONSIBLE_ID', 'USER_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}
			elseif($group === self::GROUP_BY_DATE)
			{
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
					$result[$userID] = $item;
				}
			}
			else
			{
				foreach($rawResult as $item)
				{
					$userID = $item['USER_ID'];
					$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
					$result[] = $item;
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
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_SUM_STAT_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY
			),
			array(
				'entity' => \CCrmOwnerType::InvoiceName,
				'title' => GetMessage('CRM_INVOICE_SUM_STAT_PRESET_OVERALL_SUM'),
				'name' => self::TYPE_NAME.'::OVERALL_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND
			)
		);

		$bindingInfos = InvoiceSumStatisticEntry::getBindingInfos();
		foreach($bindingInfos as $bindingInfo)
		{
			$result[] = array(
				'entity' => \CCrmOwnerType::InvoiceName,
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

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$responsibleIDs = $filter->getResponsibleIDs();
		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		$isJunk = $filter->getExtraParam('isJunk', null);

		$query = new Query(InvoiceSumStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$dateFieldName = 'CREATED_DATE';
		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$query->addFilter('=STATUS_SEMANTIC_ID', $semanticID);
			if(PhaseSemantics::isFinal($semanticID))
			{
				$dateFieldName = 'CLOSED_DATE';
			}
		}

		$query->addFilter(">={$dateFieldName}", $periodStartDate);
		$query->addFilter("<={$dateFieldName}", $periodEndDate);

		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		if(is_bool($isJunk))
		{
			$query->addFilter('=IS_JUNK', $isJunk);
		}

		return array(
			'__CONDITIONS' => array(
				array(
					'SQL' => 'crm_invoice_internals_invoice.ID IN('.$query->getQuery().')'
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
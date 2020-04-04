<?php
namespace Bitrix\Crm\Widget\Data\Contact;

use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Widget\Data\DataContext;
use Bitrix\Crm\Widget\Data\DealDataSource;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Statistics\Entity\DealSumStatisticsTable;

class DealSumStatistics extends DealDataSource
{
	const TYPE_NAME = 'CONTACT_DEAL_SUM_STATS';
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

		$semanticID = PhaseSemantics::UNDEFINED;
		switch ($name)
		{
			case 'SUCCESS_SUM':
				$semanticID = PhaseSemantics::SUCCESS;
				break;
			case 'PROCESS_SUM':
				$semanticID = PhaseSemantics::PROCESS;
				break;
			case 'FAILURE_SUM':
				$semanticID = PhaseSemantics::FAILURE;
				break;
		}

		$isFinalSemantics = PhaseSemantics::isFinal($semanticID);

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
				$query->registerRuntimeField('', new ExpressionField($nameAlias, "{$aggregate}(%s)", 'SUM_TOTAL'));
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

		if (
			$filter->getContextEntityTypeName() === \CCrmOwnerType::ContactName
			&& $filter->getContextEntityID() > 0
		)
		{
			$query->registerRuntimeField('',
				new ReferenceField('CD',
					DealContactTable::getEntity(),
					array('=this.OWNER_ID' => 'ref.DEAL_ID'),
					array('join_type' => 'INNER')
				)
			);
			$query->addFilter('=CD.CONTACT_ID', $filter->getContextEntityID());
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
		return DataContext::FUND;
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
				'entity' => \CCrmOwnerType::ContactName,
				'title' => GetMessage('CRM_CONTACT_DEAL_SUM_STAT_PRESET_SUM_TOTAL'),
				'name' => self::TYPE_NAME.'::SUM_TOTAL',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND
			),
			array(
				'entity' => \CCrmOwnerType::ContactName,
				'title' => GetMessage('CRM_CONTACT_DEAL_SUM_STAT_PRESET_SUCCESS_SUM'),
				'name' => self::TYPE_NAME.'::SUCCESS_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'SUCCESS_SUM', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND
			),
			array(
				'entity' => \CCrmOwnerType::ContactName,
				'title' => GetMessage('CRM_CONTACT_DEAL_SUM_STAT_PRESET_PROCESS_SUM'),
				'name' => self::TYPE_NAME.'::PROCESS_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'PROCESS_SUM', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND
			),
			array(
				'entity' => \CCrmOwnerType::ContactName,
				'title' => GetMessage('CRM_CONTACT_DEAL_SUM_STAT_PRESET_LOST_SUM'),
				'name' => self::TYPE_NAME.'::FAILURE_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'FAILURE_SUM', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND
			)
		);

		return $result;
	}
	/**
	 * Get details page URL.
	 * @param array $params Parameters.
	 * @return string
	 */
	public function getDetailsPageUrl(array $params)
	{
		$urlParams = array('WG' => 'Y', 'DS' => $this->getTypeName(), 'page' => '1', 'PN' => $this->getPresetName());

		/** @var string $field */
		$field = isset($params['field']) ? $params['field'] : '';
		if($field !== '')
		{
			$urlParams['FIELD'] = $field;
		}

		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		$filterParams = self::externalizeFilter($filter);
		foreach($filterParams as $k => $v)
		{
			if(!is_array($v))
			{
				$urlParams[$k] = $v;
			}
			else
			{
				$qty = count($v);
				for($i = 0; $i < $qty; $i++)
				{
					$urlParams["{$k}[{$i}]"] = $v[$i];
				}
			}
		}

		if (
			$filter->getContextEntityTypeName() === \CCrmOwnerType::ContactName
			&& $filter->getContextEntityID() > 0
		)
		{
			$urlParams['ENTITY_ID'] = $filter->getContextEntityID();
		}

		return \CHTTP::urlAddParams(self::getEntityListPath(), $urlParams);
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

		$field = isset($filterParams['FIELD']) ? $filterParams['FIELD'] : '';
		$semanticID = PhaseSemantics::UNDEFINED;
		switch ($field)
		{
			case 'SUCCESS_SUM':
				$semanticID = PhaseSemantics::SUCCESS;
				break;
			case 'PROCESS_SUM':
				$semanticID = PhaseSemantics::PROCESS;
				break;
			case 'FAILURE_SUM':
				$semanticID = PhaseSemantics::FAILURE;
				break;
		}

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

		if (isset($filterParams['ENTITY_ID']))
		{
			$query->registerRuntimeField('',
				new ReferenceField('CD',
					DealContactTable::getEntity(),
					array('=this.OWNER_ID' => 'ref.DEAL_ID'),
					array('join_type' => 'INNER')
				)
			);
			$query->addFilter('=CD.CONTACT_ID', (int)$filterParams['ENTITY_ID']);
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
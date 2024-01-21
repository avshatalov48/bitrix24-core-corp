<?php

namespace Bitrix\Crm\Integration\Report\Handler;

use Bitrix\Crm\History\Entity\LeadStatusHistoryWithSupposedTable;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesFunnelBoard;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesFunnelByStageHistory;
use Bitrix\Crm\Integration\Report\View\ColumnFunnel;
use Bitrix\Crm\Integration\Report\View\FunnelGrid;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Report\VisualConstructor\Fields\Valuable\DropDown;
use Bitrix\Report\VisualConstructor\Fields\Valuable\Hidden;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Report\VisualConstructor\IReportMultipleGroupedData;
use Bitrix\Report\VisualConstructor\IReportSingleData;

/**
 * Class Lead
 * @package Bitrix\Crm\Integration\Report\Handler
 */
class Lead extends Base implements IReportSingleData, IReportMultipleData, IReportMultipleGroupedData
{
	const WHAT_WILL_CALCULATE_LEAD_COUNT = 'LEAD_COUNT';
	const WHAT_WILL_CALCULATE_GOOD_LEAD_COUNT = 'GOOD_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_LEAD_CONVERSION = 'LEAD_CONVERSION';
	const WHAT_WILL_CALCULATE_LEAD_LOSES = 'LEAD_LOSES';
	const WHAT_WILL_CALCULATE_ACTIVE_LEAD_COUNT = 'ACTIVE_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_CONVERTED_LEAD_COUNT = 'CONVERTED_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_LOST_LEAD_COUNT = 'LOST_LEAD_COUNT';

	const WHAT_WILL_CALCULATE_LEAD_DATA_FOR_FUNNEL = 'LEAD_DATA_FOR_FUNNEL';
	const WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL = 'SUCCESS_LEAD_DATA_FOR_FUNNEL';

	const GROUPING_BY_STATE = 'STATE';
	const GROUPING_BY_DATE = 'DATE';
	const GROUPING_BY_SOURCE = 'SOURCE';
	const GROUPING_BY_RESPONSIBLE = 'RESPONSIBLE';

	const FILTER_FIELDS_PREFIX = 'FROM_LEAD_';

	const STATUS_DEFAULT_COLORS = [
		'DEFAULT_COLOR' => '#ACE9FB',
		'DEFAULT_FINAL_SUCCESS__COLOR' => '#DBF199',
		'DEFAULT_FINAL_UN_SUCCESS_COLOR' => '#FFBEBD',
		'DEFAULT_LINE_COLOR' => '#ACE9FB',
	];

	private array $statusesList = [];
	private string $leadUnSuccessStatusName = '';
	private ?int $leadAmountCount = null;

	public function __construct()
	{
		parent::__construct();
		$this->setTitle('Lead');
		$this->setCategoryKey('crm');
	}

	protected function collectFormElements()
	{
		parent::collectFormElements();
		$disableSuccessStatusField = new Hidden('disableSuccessStates');
		$disableSuccessStatusField->setDefaultValue(false);
		$this->addFormElement($disableSuccessStatusField);

		$shortModeField = new Hidden('shortMode');
		$shortModeField->setDefaultValue(false);
		$this->addFormElement($shortModeField);
	}


	/**
	 * @return array
	 */
	protected function getGroupByOptions()
	{
		return [
			self::GROUPING_BY_DATE => 'Date',
			self::GROUPING_BY_STATE => 'State',
			self::GROUPING_BY_SOURCE => 'Source',
			self::GROUPING_BY_RESPONSIBLE => 'Responsible'
		];
	}

	/**
	 *
	 * @param null $groupingValue Grouping field value.
	 * @return array
	 */
	public function getWhatWillCalculateOptions($groupingValue = null)
	{
		return [
			self::WHAT_WILL_CALCULATE_LEAD_COUNT => 'Lead count',
		];
	}

	public function mutateFilterParameter($filterParameters, array $fieldList)
	{
		$filterParameters =  parent::mutateFilterParameter($filterParameters, $fieldList);

		$fieldsToOrmMap =  $this->getLeadFieldsToOrmMap();

		foreach ($filterParameters as $key => $value)
		{
			if (in_array($key, ['TIME_PERIOD', 'FIND']) || (mb_strpos($key, 'UF_') === 0))
			{
				continue;
			}

			if ($key == 'COMMUNICATION_TYPE')
			{
				if (in_array(\CCrmFieldMulti::PHONE, $value['value']))
				{
					$filterParameters['HAS_PHONE']['type'] = 'checkbox';
					$filterParameters['HAS_PHONE']['value'] = 'Y';
				}

				if (in_array(\CCrmFieldMulti::EMAIL, $value['value']))
				{
					$filterParameters['HAS_EMAIL']['type'] = 'checkbox';
					$filterParameters['HAS_EMAIL']['value'] = 'Y';
				}

				unset($filterParameters[$key]);
				continue;
			}

			if (isset($fieldsToOrmMap[$key]) && $fieldsToOrmMap[$key] !== $key)
			{
				$filterParameters[$fieldsToOrmMap[$key]] = $value;
				unset($filterParameters[$key]);
			}
			elseif (!isset($fieldsToOrmMap[$key]))
			{
				unset($filterParameters[$key]);
			}

		}

		return $filterParameters;
	}

	/**
	 * Called every time when calculate some report result before passing some concrete handler, such us getMultipleData or getSingleData.
	 * Here you can get result of configuration fields of report, if report in widget you can get configurations of widget.
	 *
	 * @return mixed
	 */
	public function prepare()
	{
		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (!\CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Lead, 0, $userPermission))
		{
			return false;
		}

		/** @var DropDown $grouping */
		$groupingField = $this->getFormElement('groupingBy');
		$groupingValue = $groupingField ? $groupingField->getValue() : null;

		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;

		$disableSuccessStatesField = $this->getFormElement('disableSuccessStates');
		$disableSuccessStatesValue = $disableSuccessStatesField ? $disableSuccessStatesField->getValue() : false;

		$query = new Query(LeadTable::getEntity());

		switch ($groupingValue)
		{
			case self::GROUPING_BY_DATE:
				$helper = Application::getConnection()->getSqlHelper();
				$query->registerRuntimeField(new ExpressionField('DATE_CREATE_DAY', $helper->formatDate('%%Y-%%m-%%d 00:00', '%s'), 'DATE_CREATE'));
				$query->addSelect('DATE_CREATE_DAY');
				$query->addGroup('DATE_CREATE_DAY');
				break;
			case self::GROUPING_BY_STATE:
				$query->addSelect('FULL_HISTORY.STATUS_ID', 'STATUS_KEY');
				$query->addGroup('FULL_HISTORY.STATUS_ID');

				$statusNameListByStatusId = [];
				foreach ($this->getStatusList() as $status)
				{
					$statusNameListByStatusId[$status['STATUS_ID']] = $status['NAME'];
				}

				break;
			case self::GROUPING_BY_SOURCE:
				$query->addGroup('SOURCE_ID');
				$query->addSelect('SOURCE_ID');

				$sourceNameListByStatusId = [];
				foreach ($this->getSourceNameList() as $source)
				{
					$sourceNameListByStatusId[$source['STATUS_ID']] = $source['NAME'];
				}
				break;
			case self::GROUPING_BY_RESPONSIBLE:
				$query->addGroup('ASSIGNED_BY_ID');
				$query->addSelect('ASSIGNED_BY_ID');
				break;
		}

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_LEAD_DATA_FOR_FUNNEL:
			case self::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL:
				$query->addSelect(Query::expr()->max('OPPORTUNITY_ACCOUNT'), 'MAX_OPPORTUNITY_ACCOUNT');
				$query->addSelect('FULL_HISTORY.OWNER_ID', 'FULL_HISTORY_OWNER_ID');
				$query->addSelect(Query::expr()->min('FULL_HISTORY.IS_SUPPOSED'), 'FULL_HISTORY_IS_SUPPOSED');
				$query->addSelect(Query::expr()->max('FULL_HISTORY.SPENT_TIME'), 'FULL_HISTORY_SPENT_TIME');
				$query->addSelect(Query::expr()->max('ACCOUNT_CURRENCY_ID'), 'MAX_ACCOUNT_CURRENCY_ID');
				break;
			default:
				$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('VALUE', 'COUNT(DISTINCT %s)', 'FULL_HISTORY.OWNER_ID'));
		}

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_GOOD_LEAD_COUNT:
				$query->whereIn('FULL_HISTORY.STATUS_SEMANTIC_ID', ['P', 'S']);
				break;
			case self::WHAT_WILL_CALCULATE_ACTIVE_LEAD_COUNT:
				$query->where('FULL_HISTORY.STATUS_SEMANTIC_ID', 'P');
				break;
			case self::WHAT_WILL_CALCULATE_CONVERTED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL:
				$query->where('FULL_HISTORY.STATUS_SEMANTIC_ID', 'S');
				break;
			case self::WHAT_WILL_CALCULATE_LOST_LEAD_COUNT:
				$query->where('FULL_HISTORY.STATUS_SEMANTIC_ID', 'F');
				break;
			case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
				$query->addGroup('FULL_HISTORY.STATUS_SEMANTIC_ID');
				$query->addSelect('FULL_HISTORY.STATUS_SEMANTIC_ID', 'STATUS_SEMANTIC_ID_VALUE');
				break;
		}

		$this->addToQueryFilterCase($query);
		$this->addPermissionsCheck($query);

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL:
			case self::WHAT_WILL_CALCULATE_LEAD_DATA_FOR_FUNNEL:
				$querySql = "SELECT 
								res.STATUS_KEY,
								(SUM(res.FULL_HISTORY_SPENT_TIME) / SUM(CASE WHEN res.FULL_HISTORY_IS_SUPPOSED = 'N' AND res.FULL_HISTORY_SPENT_TIME IS NOT NULL THEN 1 ELSE 0 END)) as SPENT_TIME,  
								COUNT(DISTINCT res.FULL_HISTORY_OWNER_ID) as VALUE, 
								SUM(res.MAX_OPPORTUNITY_ACCOUNT) as SUM, 
								MAX_ACCOUNT_CURRENCY_ID as ACCOUNT_CURRENCY_ID FROM(";
				$querySql .= $query->getQuery();
				$querySql .= ") as res GROUP BY res.STATUS_KEY";
				$results = Application::getConnection()->query($querySql);
				break;
			default:
				$results = $query->exec()->fetchAll();
		}

		$amountLeadCount = 0;
		$amountLeadSum = 0;
		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
				$allLeadCount = [];
				$successLeadCount = [];
				$successAmountLeadCount = 0;
				$groupingFieldName = 'withoutGrouping';

				switch ($groupingValue)
				{
					case self::GROUPING_BY_RESPONSIBLE:
						$allLeadCount = $this->getLeadAmountCountByResponsible();
						$allAmountLeadCount = array_sum($allLeadCount);
						break;
					default:
						$allLeadCount['withoutGrouping'] = $this->getLeadAmountCount();
						$allAmountLeadCount = $allLeadCount['withoutGrouping'];
				}

				foreach ($results as $result)
				{
					switch ($groupingValue)
					{
						case self::GROUPING_BY_RESPONSIBLE:
							$groupingFieldName = 'ASSIGNED_BY_ID';
							$groupingFieldValue = $result[$groupingFieldName];
							break;
						default:
							$groupingFieldValue = 'withoutGrouping';
					}

					if ($result['STATUS_SEMANTIC_ID_VALUE'] == 'S')
					{
						$successLeadCount[$groupingFieldValue] += $result['VALUE'];
						$successAmountLeadCount += $result['VALUE'];
					}
				}
				$results = [];

				foreach ($allLeadCount as $groupingKey => $count)
				{
					if (!empty($successLeadCount[$groupingKey]))
					{
						$results[] = [
							$groupingFieldName => $groupingKey,
							'VALUE' => $count > 0 ? ($successLeadCount[$groupingKey] / $count) * 100 : 0
						];
					}
					else
					{
						$results[] = [
							$groupingFieldName => $groupingKey,
							'VALUE' => 0
						];
					}
				}

				$amountLeadCount = $allAmountLeadCount ? (($successAmountLeadCount / $allAmountLeadCount) * 100) : 0;

				break;
			case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
				$allLeadCount = [];
				$loseLeadCount = [];
				$losesAmountLeadCount = 0;
				$groupingFieldName = 'withoutGrouping';

				switch ($groupingValue)
				{
					case self::GROUPING_BY_RESPONSIBLE:
						$allLeadCount = $this->getLeadAmountCountByResponsible();
						$allAmountLeadCount = array_sum($allLeadCount);
						break;
					default:
						$allLeadCount['withoutGrouping'] = $this->getLeadAmountCount();
						$allAmountLeadCount = $allLeadCount['withoutGrouping'];
				}

				foreach ($results as $result)
				{
					switch ($groupingValue)
					{
						case self::GROUPING_BY_RESPONSIBLE:
							$groupingFieldName = 'ASSIGNED_BY_ID';
							$groupingFieldValue = $result[$groupingFieldName];
							break;
						default:
							$groupingFieldValue = 'withoutGrouping';
					}

					if ($result['STATUS_SEMANTIC_ID_VALUE'] == 'F')
					{
						$loseLeadCount[$groupingFieldValue] += $result['VALUE'];
						$losesAmountLeadCount += $result['VALUE'];
					}
				}
				$results = [];

				foreach ($allLeadCount as $groupingKey => $count)
				{
					if (!empty($loseLeadCount[$groupingKey]))
					{
						$results[] = [
							$groupingFieldName => $groupingKey,
							'VALUE' => $count > 0 ? ($loseLeadCount[$groupingKey] / $count) * 100 : 0
						];
					}
					else
					{
						$results[] = [
							$groupingFieldName => $groupingKey,
							'VALUE' => 0
						];
					}
				}

				$amountLeadCount = $allAmountLeadCount ? (($losesAmountLeadCount / $allAmountLeadCount) * 100) : 0;
				break;
		}

		$leadCalculatedValue = [];
		$percentageMetricsList = [
			self::WHAT_WILL_CALCULATE_LEAD_CONVERSION,
			self::WHAT_WILL_CALCULATE_LEAD_LOSES,
		];

		$statusNum = 0;
		foreach ($results as $result)
		{
			if (!in_array($calculateValue, $percentageMetricsList))
			{
				$statusNum++;
				if ($this->getView()->getKey() !== ColumnFunnel::VIEW_KEY)
				{
					$amountLeadCount += $result['VALUE'];
				}
			}

			switch ($groupingValue)
			{
				case self::GROUPING_BY_DATE:
					$leadCalculatedValue[$result['DATE_CREATE_DAY']]['value'] = $result['VALUE'];
					$leadCalculatedValue[$result['DATE_CREATE_DAY']]['title'] = $result['DATE_CREATE_DAY'];
					break;
				case self::GROUPING_BY_STATE:
					if ($statusNum === 1)
					{
						$leadCountAndSum = $this->getLeadAmountCountAndSum();
						$amountLeadCount = $leadCountAndSum['COUNT'];
						$amountLeadSum = $leadCountAndSum['SUM'];
					}

					$leadCalculatedValue[$result['STATUS_KEY']]['value'] = $result['VALUE'];
					$leadCalculatedValue[$result['STATUS_KEY']]['additionalValues']['sum']['VALUE'] = 0;
					$leadCalculatedValue[$result['STATUS_KEY']]['additionalValues']['sum']['currencyId'] = \CCrmCurrency::GetAccountCurrencyID();

					if ($result['SUM'])
					{
						$leadCalculatedValue[$result['STATUS_KEY']]['additionalValues']['sum'] = [
							'VALUE' => $result['SUM'],
							'currencyId' => !empty($result['ACCOUNT_CURRENCY_ID']) ? $result['ACCOUNT_CURRENCY_ID'] : null
						];
					}

					$statusSemanticId = \CCrmLead::GetSemanticID($result['STATUS_KEY']);
					if (!PhaseSemantics::isFinal($statusSemanticId))
					{
						$leadCalculatedValue[$result['STATUS_KEY']]['additionalValues']['avgSpentTime']['VALUE'] = (int)$result['SPENT_TIME'];
					}
					$leadCalculatedValue[$result['STATUS_KEY']]['title'] = !empty($statusNameListByStatusId[$result['STATUS_KEY']]) ? $statusNameListByStatusId[$result['STATUS_KEY']] : '';
					$leadCalculatedValue[$result['STATUS_KEY']]['color'] = $this->getStatusColor($result['STATUS_KEY']);
					break;
				case self::GROUPING_BY_SOURCE:
					$leadCalculatedValue[$result['SOURCE_ID']]['value'] = $result['VALUE'];
					$leadCalculatedValue[$result['SOURCE_ID']]['title'] = !empty($sourceNameListByStatusId[$result['SOURCE_ID']]) ? $sourceNameListByStatusId[$result['SOURCE_ID']] : '';
					break;
				case self::GROUPING_BY_RESPONSIBLE:
					if ($result['ASSIGNED_BY_ID']  == 0)
					{
						continue 2;
					}
					//TODO optimise here
					$userInfo = $this->getUserInfo($result['ASSIGNED_BY_ID']);
					$leadCalculatedValue[$result['ASSIGNED_BY_ID']]['value'] = $result['VALUE'];
					$leadCalculatedValue[$result['ASSIGNED_BY_ID']]['title'] = $userInfo['name'];
					$leadCalculatedValue[$result['ASSIGNED_BY_ID']]['logo'] = $userInfo['icon'];
					$leadCalculatedValue[$result['ASSIGNED_BY_ID']]['targetUrl'] = $userInfo['link'];
					break;
				default:
					$leadCalculatedValue['withoutGrouping'] = $result['VALUE'];
					break;
			}
		}

		if ($groupingValue === self::GROUPING_BY_STATE && isset($statusNameListByStatusId) && $calculateValue !==self::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL)
		{
			$sortedLeadCountListByStatus = [];
			foreach ($statusNameListByStatusId as $statusId => $statusName)
			{
				if (!empty($leadCalculatedValue[$statusId]))
				{
					$sortedLeadCountListByStatus[$statusId] = $leadCalculatedValue[$statusId];
				}
				else
				{
					$sortedLeadCountListByStatus[$statusId] = [
						'value' => 0,
						'title' => $statusName,
						'color' => $this->getStatusColor($statusId)
					];
				}
			}
			$leadCalculatedValue = $sortedLeadCountListByStatus;
		}

		$leadCalculatedValue['amount']['count'] = $amountLeadCount;
		$leadCalculatedValue['amount']['sum'] = $amountLeadSum;

		if ($calculateValue !== self::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL)
		{
			$leadCalculatedValue['amount']['successPassTime'] = $this->getLeadPassingTime();
		}

		if ($disableSuccessStatesValue)
		{
			unset($leadCalculatedValue['CONVERTED']);
		}

		//replace converted value to the end in column funnel
		if ($calculateValue === self::WHAT_WILL_CALCULATE_LEAD_DATA_FOR_FUNNEL)
		{
			if (!empty($leadCalculatedValue['CONVERTED']))
			{
				$convertedValue = $leadCalculatedValue['CONVERTED'];
				unset($leadCalculatedValue['CONVERTED']);

				$leadCalculatedValue['CONVERTED'] = $convertedValue;
			}
		}

		return $leadCalculatedValue;
	}


	private function getLeadPassingTime()
	{
		$query = new Query(LeadTable::getEntity());
		$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('AVG_SPENT_TIME', 'AVG(%s)', 'FULL_HISTORY.SPENT_TIME'));
		$this->addToQueryFilterCase($query);
		$this->addPermissionsCheck($query);
		$query->whereNot('FULL_HISTORY.STATUS_SEMANTIC_ID', 'S');
		$query->where('FULL_HISTORY.IS_SUPPOSED', 'N');
		//$query->addGroup('FULL_HISTORY.STATUS_ID');

		$results = $query->exec()->fetchAll();

		$successSpentTime = 0;
		if (!$results)
		{
			return $successSpentTime;
		}
		foreach ($results as $result)
		{
			$successSpentTime += $result['AVG_SPENT_TIME'];
		}

		return $successSpentTime;
	}

	private function getLeadAmountCount(): int
	{
		if (isset($this->leadAmountCount))
		{
			return $this->leadAmountCount;
		}

		$query = new Query(LeadTable::getEntity());
		$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('COUNT', 'COUNT(DISTINCT %s)', 'FULL_HISTORY.OWNER_ID'));
		$this->addToQueryFilterCase($query);
		$this->addPermissionsCheck($query);
		$result = $query->exec()->fetchAll();

		$this->leadAmountCount = empty($result[0]['COUNT']) ? 0 : (int)$result[0]['COUNT'];

		return $this->leadAmountCount;
	}

	private function getLeadAmountCountByResponsible()
	{
		$query = new Query(LeadTable::getEntity());
		$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('COUNT', 'COUNT(DISTINCT %s)', 'FULL_HISTORY.OWNER_ID'));
		$query->addSelect('ASSIGNED_BY_ID');
		$query->addGroup('ASSIGNED_BY_ID');
		$this->addToQueryFilterCase($query);
		$this->addPermissionsCheck($query);
		$results = $query->exec()->fetchAll();

		$amountByResponsible = [];
		foreach ($results as $result)
		{
			$amountByResponsible[$result['ASSIGNED_BY_ID']] = $result['COUNT'];
		}
		return $amountByResponsible;
	}

	private function getLeadAmountCountAndSum()
	{

		$query = new Query(LeadTable::getEntity());
		$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('DISTINCT_OWNER_ID', 'DISTINCT %s', 'FULL_HISTORY.OWNER_ID'));
		$query->addSelect('OPPORTUNITY_ACCOUNT');
		$query->addSelect('ACCOUNT_CURRENCY_ID', 'CURRENCY');
		$this->addToQueryFilterCase($query);
		$this->addPermissionsCheck($query);

		$connection = Application::getConnection();

		$querySql = 'SELECT SUM(res.OPPORTUNITY_ACCOUNT) as SUM, COUNT(res.DISTINCT_OWNER_ID) COUNT, res.CURRENCY as CURRENCY FROM(';
		$querySql .= $query->getQuery();
		$querySql .= ') as res';
		$queryWithResult = $connection->query($querySql);
		$result = $queryWithResult->fetchAll();

		return !empty($result[0])
			? $result[0]
			: [
				'COUNT' => 0,
				'SUM' => 0,
				'CURRENCY' => \CCrmCurrency::GetAccountCurrencyID()
			];
	}

	private function addToQueryFilterCase(Query $query, $filterParameters = null)
	{
		if ($filterParameters === null)
		{
			$filterParameters = $this->getFilterParameters();
		}

		if (!$this->isConversionCalculateMode())
		{
			$query->where('FULL_HISTORY.IS_SUPPOSED', 'N');
		}

		foreach ($filterParameters as $key => $value)
		{
			if ($key === 'TIME_PERIOD')
			{
				if ($value['from'] !== "" && $value['to'] !== "")
				{
					$query->where('FULL_HISTORY.LAST_UPDATE_DATE', '<=', $this->getConvertedToServerTime($value['to']))
						->where('FULL_HISTORY.CLOSE_DATE', '>=', $this->getConvertedToServerTime($value['from']));
					continue;
				}
			}

			if ($key === 'FIND')
			{
				if ($value !== '')
				{
					$query->whereMatch('SEARCH_CONTENT', $value);
				}
				continue;
			}

			switch 	($value['type'])
			{
				case 'date':
					if ($value['from'] !== "")
					{
						$query->where($key, '>=', $this->getConvertedToServerTime($value['from']));
					}

					if ($value['to'] !== "")
					{
						$query->where($key, '<=', $this->getConvertedToServerTime($value['to']));
					}
					break;
				case 'diapason':
					if ($value['from'] !== "")
					{
						$query->where($key, '>=', $value['from']);
					}

					if ($value['to'] !== "")
					{
						$query->where($key, '<=', $value['to']);
					}
					break;
				case 'text':
					$query->whereLike($key, '%'.$value['value'].'%');
					break;
				case 'none':
				case 'list':
				case 'checkbox':
				case 'custom_entity':
				case 'dest_selector':
				case 'entity_selector':
					$query->addFilter($key, $value['value']);
					break;
			}

			if ($key === 'STAGE_SEMANTIC_ID')
			{
				$subQuery = LeadStatusHistoryWithSupposedTable::query();
				$subQuery->addSelect('*');
				if (!empty($filterParameters['TIME_PERIOD']))
				{
					$subQuery->where('LAST_UPDATE_DATE', '<=', $this->getConvertedToServerTime($filterParameters['TIME_PERIOD']['to']))
						->where('CLOSE_DATE', '>=', $this->getConvertedToServerTime($filterParameters['TIME_PERIOD']['from']));
				}

				$subQuery->addFilter('STATUS_SEMANTIC_ID', $value['value']);

				$query->whereExists($subQuery);
			}
		}
	}

	private function isConversionCalculateMode()
	{
		$result = false;
		$viewKey = $this->getView()->getKey();
		if ($viewKey === ColumnFunnel::VIEW_KEY)
		{
			$funnelCalculateModeField = $this->getWidgetHandler()->getFormElement('calculateMode');
			$funnelCalculateModeValue = $funnelCalculateModeField->getValue();
			if ($funnelCalculateModeValue === ColumnFunnel::CONVERSION_CALCULATE_MODE)
			{
				$result = true;
			}
		}
		elseif ($viewKey === FunnelGrid::VIEW_KEY)
		{
			$gridCalculationModeField = $this->getWidgetHandler()->getFormElement('calculateMode');
			$gridCalculationModeValue = $gridCalculationModeField->getValue();
			if ($gridCalculationModeValue === FunnelGrid::CONVERSION_CALCULATE_MODE)
			{
				$result = true;
			}
		}

		return $result;
	}

	private function getLeadFieldsToOrmMap()
	{
		$map = array(
			'ID' => 'ID',
			'TITLE' => 'TITLE',
			'SOURCE_ID' => 'SOURCE_ID',
			'NAME' => 'NAME',
			'SECOND_NAME' => 'SECOND_NAME',
			'LAST_NAME' => 'LAST_NAME',
			'BIRTHDATE' => 'BIRTHDATE',
			'DATE_CREATE' => 'DATE_CREATE',
			'DATE_MODIFY' => 'DATE_MODIFY',
			'STATUS_ID' => 'STATUS_ID',
			'STATUS_ID_FROM_HISTORY' => 'HISTORY.STATUS_ID',
			'STATUS_SEMANTIC_ID_FROM_HISTORY' => 'HISTORY.STATUS_SEMANTIC_ID',
			'STATUS_SEMANTIC_ID' => 'STATUS_SEMANTIC_ID',
			//'STATUS_CONVERTED' => 'STATUS_CONVERTED',
			'OPPORTUNITY' => 'OPPORTUNITY',
			'CURRENCY_ID' => 'CURRENCY_ID',
			'ASSIGNED_BY_ID' => 'ASSIGNED_BY_ID',
			'CREATED_BY_ID' => 'CREATED_BY_ID',
			'MODIFY_BY_ID' => 'MODIFY_BY_ID',
			'IS_RETURN_CUSTOMER' => 'IS_RETURN_CUSTOMER',
			//'ACTIVITY_COUNTER' => 'ACTIVITY_COUNTER',
			//'COMMUNICATION_TYPE' => 'COMMUNICATION_TYPE',
			'HAS_PHONE' => 'HAS_PHONE',
			'PHONE' => 'PHONE',
			'HAS_EMAIL' => 'HAS_EMAIL',
			'EMAIL' => 'EMAIL',
			//'WEB' => 'WEB',
			//'IM' => 'IM',
			'CONTACT_ID' => 'CONTACT_ID',
			'COMPANY_ID' => 'COMPANY_ID',
			'COMPANY_TITLE' => 'COMPANY_TITLE',
			'POST' => 'POST',
			'ADDRESS' => 'ADDRESS',
			'ADDRESS_2' => 'ADDRESS_ENTITY.ADDRESS_2',
			'ADDRESS_CITY' => 'ADDRESS_ENTITY.CITY',
			'ADDRESS_REGION' => 'ADDRESS_ENTITY.REGION',
			'ADDRESS_PROVINCE' => 'ADDRESS_ENTITY.PROVINCE',
			'ADDRESS_POSTAL_CODE' => 'ADDRESS_ENTITY.POSTAL_CODE',
			'ADDRESS_COUNTRY' => 'ADDRESS_ENTITY.COUNTRY',
			'COMMENTS' => 'COMMENTS',
			'PRODUCT_ROW_PRODUCT_ID' => 'PRODUCT_ROW.PRODUCT_ID',
			'WEBFORM_ID' => 'WEBFORM_ID',
		);

		//region UTM
		foreach (UtmTable::getCodeNames() as $code => $name)
		{
			$map[$code] = $code . '.VALUE';
		}

		return $map;
	}

	private function addPermissionsCheck(Query $query, $userId = 0)
	{
		if($userId <= 0)
		{
			$userId = EntityAuthorization::getCurrentUserID();
		}
		$userPermissions = EntityAuthorization::getUserPermissions($userId);

		$permissionSql = $this->buildPermissionSql(
			array(
				'alias' => 'L',
				'permissionType' => 'READ',
				'options' => array(
					'PERMS' => $userPermissions,
					'RAW_QUERY' => true
				)
			)
		);

		if ($permissionSql)
		{
			$query->whereIn('ID', new SqlExpression($permissionSql));
		}
	}

	private function buildPermissionSql(array $params)
	{
		return \CCrmLead::BuildPermSql(
			isset($params['alias']) ? $params['alias'] : 'L',
			isset($params['permissionType']) ? $params['permissionType'] : 'READ',
			isset($params['options']) && is_array($params['options']) ? $params['options'] : array()
		);
	}

	/**
	 * @param $statusId
	 * @return mixed
	 */
	private function getStatusColor($statusId)
	{
		$statusList = $this->getStatusList();
		return $statusList[$statusId]['COLOR'];
	}

	/**
	 * @return array
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getStatusList(): array
	{
		if (empty($this->statusList))
		{
			$result = \CCrmStatus::GetStatus('STATUS');

			$this->statusesList = \Bitrix\Crm\Color\PhaseColorScheme::fillDefaultColors($result);
		}

		return $this->statusesList;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getSourceNameList()
	{
		$sourceListQuery = new Query(StatusTable::getEntity());
		$sourceListQuery->where('ENTITY_ID', 'SOURCE');
		$sourceListQuery->addSelect('STATUS_ID');
		$sourceListQuery->addSelect('NAME');
		return $sourceListQuery->exec()->fetchAll();
	}

	/**
	 * @return string
	 */
	private function getLeadUnSuccessStatusName(): string
	{
		if (empty($this->leadUnSuccessStatusName))
		{
			$statusSemanticInfo = \CCrmStatus::GetLeadStatusSemanticInfo();

			$this->leadUnSuccessStatusName = $statusSemanticInfo['FINAL_UNSUCCESS_FIELD'];
		}

		return $this->leadUnSuccessStatusName;
	}

	/**
	 * array with format
	 * array(
	 *     'title' => 'Some Title',
	 *     'value' => 0,
	 *     'targetUrl' => 'http://url.domain?params=param'
	 * )
	 * @return array
	 */
	public function getSingleData()
	{
		$calculatedData = $this->getCalculatedData();
		$result = [
			'value' => $calculatedData['withoutGrouping'],
		];

		$calculateValue = $this->getFormElement('calculate')->getValue();
		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
				$result['config']['unitOfMeasurement'] = '%';
				$result['value'] = round($result['value'], 2);
				break;
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function getSingleDemoData()
	{
		return [
			'value' => 5
		];
	}

	/**
	 * array with format
	 * array(
	 *     'items' => array(
	 *            array(
	 *                'label' => 'Some Title',
	 *                'value' => 5,
	 *                'targetUrl' => 'http://url.domain?params=param'
	 *          )
	 *     )
	 * )
	 * @return array
	 */
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		$items = [];
		$config = [];
		if (!empty($calculatedData))
		{
			$calculateField = $this->getFormElement('calculate');
			$calculateValue = $calculateField ? $calculateField->getValue() : null;

			$shortModeField = $this->getFormElement('shortMode');
			$shortModeValue = $shortModeField ? $shortModeField->getValue() : false;

			switch ($calculateValue)
			{
				case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
				case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
					$config['mode'] = 'singleData';
					$config['unitOfMeasurement'] = '%';
					$item['value'] = round($calculatedData['withoutGrouping'], 2);
					$item['color'] = '#9DCF00';
					$items[] = $item;
					break;
				default:
					$itemCount = 0;
					foreach ($calculatedData as $key => $data)
					{
						if ($key === 'amount')
						{
							continue;
						}


						$item = [
							'label' => $data['title'],
							'value' => $data['value'],
							'color' => $data['color'],
						];

						if (
							$calculateValue === self::WHAT_WILL_CALCULATE_LEAD_DATA_FOR_FUNNEL
							|| $calculateValue === self::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL
						)
						{
							if ($this->isConversionCalculateMode())
							{
								$item['link'] = $this->getTargetUrl('/crm/lead/analytics/list/', [
									'STATUS_ID_FROM_SUPPOSED_HISTORY' => $key
								]);
							}
							else
							{
								$item['link'] = $this->getTargetUrl('/crm/lead/analytics/list/', [
									'STATUS_ID_FROM_HISTORY' => $key
								]);
							}
						}

						$config['additionalValues']['firstAdditionalValue']['titleShort'] =  Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_COUNT_SHORT_TITLE');
						$item['additionalValues']['firstAdditionalValue'] = [
							'value' => $data['value']
						];

						if (isset($data['additionalValues']['sum']))
						{
							$amountLeadCurrencyId = $data['additionalValues']['sum']['currencyId'];
							$config['additionalValues']['secondAdditionalValue']['titleShort'] = Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_SUM_SHORT_TITLE');

							$item['additionalValues']['secondAdditionalValue'] = [
								'value' => \CCrmCurrency::MoneyToString($data['additionalValues']['sum']['VALUE'], $data['additionalValues']['sum']['currencyId']),
								'currencyId' => $data['additionalValues']['sum']['currencyId']
							];
						}

						if (isset($data['additionalValues']['avgSpentTime']))
						{
							$config['additionalValues']['thirdAdditionalValue']['titleShort'] = Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_SPENT_TIME_SHORT_TITLE');
							$item['additionalValues']['thirdAdditionalValue'] = [
								'value' => $this->getFormattedPassTime($data['additionalValues']['avgSpentTime']['VALUE'])
							];
						}

						$statusSemanticId = \CCrmLead::GetSemanticID($key);
						$config['additionalValues']['forthAdditionalValue']['titleShort'] = Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_CONVERSION_SHORT_TITLE');
						$item['additionalValues']['forthAdditionalValue'] = [
							'title' => PhaseSemantics::isLost($statusSemanticId) ?
											Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_LOSSES_SHORT_TITLE')
											: Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_CONVERSION_SHORT_TITLE'),
							'value' => $calculatedData['amount']['count'] ? round(($data['value'] / $calculatedData['amount']['count']) * 100, 2) : 0,
							'unitOfMeasurement' => '%',
							'helpLink' => 'someLink',
							'helpInSlider' => true
						];

						//hidden conversion on first column
						if ($calculateValue !== self::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL && $itemCount < 1)
						{
							unset($item['additionalValues']['forthAdditionalValue']);
						}

						$itemCount++;


						$items[] = $item;
					}
					$config['titleShort'] = Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_COUNT_SHORT_TITLE');
					$config['titleMedium'] = 'meduim';

					$config['valuesAmount'] = [
						'firstAdditionalAmount' => [
							'title' => Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_SUM_SHORT_TITLE'),
							'value' => \CCrmCurrency::MoneyToString($calculatedData['amount']['sum'], $amountLeadCurrencyId ?? ''),
							'targetUrl' => $this->getTargetUrl('/crm/lead/analytics/list/'),
						],
//						'secondAdditionalAmount' => [
//							'title' => Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_COUNT_SHORT_TITLE'),
//							'value' => $calculatedData['amount']['count']
//						],
//						'secondAdditionalAmount' => [
//							'title' => Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_PASS_AVG_TIME_SHORT_TITLE'),
//							'value' => $calculatedData['amount']['successPassTime'] . ' ' . Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_SPENT_TIME_DAYS')
//						],
//						'thirdAdditionalAmount' => [
//							'title' => Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_PASS_AVG_TIME_SHORT_TITLE'),
//							'value' => $calculatedData['amount']['successPassTime'] . Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_SPENT_TIME_DAYS')
//						]
					];

					if ($calculatedData['amount']['successPassTime'] ?? false)
					{
						$config['valuesAmount']['secondAdditionalAmount'] = [
							'title' => Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_PASS_AVG_TIME_SHORT_TITLE'),
							'value' => $this->getFormattedPassTime($calculatedData['amount']['successPassTime'])
						];
					}

					switch ($calculateValue)
					{
						case self::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL:
							$config['topAdditionalTitle'] = Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_CONVERSION_SHORT_TITLE');
							$config['topAdditionalValue'] = !empty($items[0]['additionalValues']['forthAdditionalValue']['value']) ? $items[0]['additionalValues']['forthAdditionalValue']['value'] : 0;
							$config['topAdditionalValueUnit'] = '%';
							$config['valuesAmount']['firstAdditionalAmount']['value'] =
								($items[0]['additionalValues']['secondAdditionalValue']['value'] ?? null)
							;
							//$config['valuesAmount']['secondAdditionalAmount']['value'] = $items[0]['additionalValues']['thirdAdditionalValue']['value'];

							if ($shortModeValue)
							{
								$config['mode'] = 'singleData';
							}
							unset($config['valuesAmount']['thirdAdditionalAmount']);
							break;
					}
			}

		}

		$result = [
			'items' => $items,
			'config' => $config
		];

		return $result;
	}



	/**
	 * @return array
	 */
	public function getMultipleDemoData()
	{
		return [
			'items' => [
				[
					'label' => 'First group',
					'value' => 1
				],
				[
					'label' => 'Second group',
					'value' => 5
				],
				[
					'label' => 'Third group',
					'value' => 1
				],
				[
					'label' => 'Fourth group',
					'value' => 8
				]
			]
		];
	}

	/**
	 * Array format for return this method:<br>
	 * array(
	 *      'items' => array(
	 *           array(
	 *              'groupBy' => 01.01.1970 or 15 etc.
	 *              'title' => 'Some Title',
	 *              'value' => 1,
	 *              'targetUrl' => 'http://url.domain?params=param'
	 *          ),
	 *          array(
	 *              'groupBy' => 01.01.1970 or 15 etc.
	 *              'title' => 'Some Title',
	 *              'value' => 2,
	 *              'targetUrl' => 'http://url.domain?params=param'
	 *          )
	 *      ),
	 *      'config' => array(
	 *          'groupsLabelMap' => array(
	 *              '01.01.1970' => 'Start of our internet evolution'
	 *              '15' =>  'Just a simple integer'
	 *          ),
	 *          'reportTitle' => 'Some title for this report'
	 *      )
	 * )
	 * @return array
	 */
	public function getMultipleGroupedData()
	{
		$calculatedData = $this->getCalculatedData();

		$grouping = $this->getFormElement('groupingBy');
		$groupingValue = $grouping ? $grouping->getValue() : null;
		$items = [];
		$config = [];
		if ($groupingValue == self::GROUPING_BY_DATE)
		{
			$config['mode'] = 'date';
		}
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;

		$amount = [];
		$amount['value'] = 0;
		$amount['prefix'] = '';
		$amount['postfix'] = '';

		$amountCalculateItem = $calculatedData['amount']['count'];
		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
				$amount['value'] += round($amountCalculateItem, 2);
				$amount['postfix'] = '%';
				break;
			default:
				$amount['value'] += $amountCalculateItem;
		}

		unset($calculatedData['amount']);

		foreach ($calculatedData as $groupingKey => $item)
		{

			switch ($calculateValue)
			{
				case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
				case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
					$config['unitOfMeasurement'] = '%';
					$items[] = [
						'groupBy' => $groupingKey,
						'label' => $item['title'],
						'value' => round($item['value'], 2),
						'postfix' => '%',
					];
					break;
				default:
					$items[] = array(
						'groupBy' => $groupingKey,
						'label' => $item['title'],
						'value' => $item['value'],
						'slider' => true,
						'targetUrl' => $this->getTargetUrl('/crm/lead/analytics/list/', [
							'ASSIGNED_BY_ID' => $groupingKey,
						]),
					);
			}


			$config['groupsLabelMap'][$groupingKey] = $item['title'];
			$config['groupsLogoMap'][$groupingKey] = $item['logo'];
			$config['groupsTargetUrlMap'][$groupingKey] = $item['targetUrl'];
		}

		$config['reportTitle'] = $this->getFormElement('label')->getValue();

		$sliderDisableCalculateTypes = [
			self::WHAT_WILL_CALCULATE_LEAD_CONVERSION,
			self::WHAT_WILL_CALCULATE_LEAD_LOSES
		];

		if (!in_array($calculateValue, $sliderDisableCalculateTypes))
		{
			$amount['slider'] = true;
			$amount['targetUrl'] = $this->getTargetUrl('/crm/lead/analytics/list/');
		}

		$config['amount'] = $amount;
		$result =  [
			'items' => $items,
			'config' => $config,
		];
		return $result;
	}

	/**
	 * @param $baseUri
	 * @param array $params
	 * @return string
	 */
	public function getTargetUrl($baseUri, $params = [])
	{
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_LOST_LEAD_COUNT:
				$params['STATUS_SEMANTIC_ID_FROM_HISTORY'] = 'F';
				break;
			case self::WHAT_WILL_CALCULATE_GOOD_LEAD_COUNT:
				$params['STATUS_SEMANTIC_ID_FROM_HISTORY'] = ['P', 'S'];
				break;
			case self::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL:
			case self::WHAT_WILL_CALCULATE_CONVERTED_LEAD_COUNT:
				$params['STATUS_SEMANTIC_ID_FROM_HISTORY'] = 'S';
				break;
			case self::WHAT_WILL_CALCULATE_ACTIVE_LEAD_COUNT:
				$params['STATUS_SEMANTIC_ID_FROM_HISTORY'] = 'P';
				break;
		}
		return parent::getTargetUrl($baseUri, $params);
	}

	public function prepareEntityListFilter($requestParameters)
	{
		$filterParameters = $this->getFilterParameters();
		$query = LeadTable::query();
		$query->addSelect('ID');
		$this->addToQueryFilterCase($query, $filterParameters);

		foreach ($requestParameters as $parameter => $value)
		{
			switch ($parameter)
			{
				case 'STAGE_SEMANTIC_ID':
				case 'ASSIGNED_BY_ID':
					$query->where($parameter, $value);
					break;
				case 'STATUS_ID_FROM_SUPPOSED_HISTORY':
					$query->where('FULL_HISTORY.STATUS_ID', $value);
					break;
				case 'STATUS_ID_FROM_HISTORY':
					$query->where('FULL_HISTORY.STATUS_ID', $value);
					$query->where('FULL_HISTORY.IS_SUPPOSED', 'N');
					break;
				case 'STATUS_SEMANTIC_ID_FROM_HISTORY':
					$query->where('FULL_HISTORY.STATUS_SEMANTIC_ID', $value);
					$query->where('FULL_HISTORY.IS_SUPPOSED', 'N');
					break;
			}
		}
		$query->setDistinct(true);

		return [
			'__JOINS' => [
				[
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN('.$query->getQuery().') REP ON REP.ID = L.ID'
				]
			]
		];
	}

	/**
	 * @return array
	 */
	public function getMultipleGroupedDemoData()
	{
		return [];
	}

	/**
	 * In some case, need to dynamically disable some report handler
	 * @return bool
	 */
	public function isEnabled()
	{
		if (!LeadSettings::isEnabled())
		{
			return false;
		}
		$boardKey = $this->getWidgetHandler()->getWidget()->getBoardId();
		if ($boardKey === SalesFunnelBoard::BOARD_KEY || $boardKey === SalesFunnelByStageHistory::BOARD_KEY)
		{
			return \CUserOptions::GetOption('crm',SalesFunnelBoard::SHOW_LEADS_OPTION, 'Y') === 'Y';
		}

		return true;
	}
}
